<?php

class WooEenvoudigFactureren_Generation {

    private $options;
    private $client;
    private $logger;

    public function __construct($options, $client, $logger) {
        $this->options = $options;
        $this->client = $client;
        $this->logger = $logger;
    }

    public function register_actions() {
        if ($this->options->get('verified')) {
            add_action('woocommerce_thankyou', array($this, 'triggered_new_order'), 10, 1);
            add_action('woocommerce_order_status_completed', array($this, 'triggered_order_completed'), 10, 1);
        }
    }

    public function triggered_new_order( $order_id ) {

        // if is vat exempt find exempt reason in 'Zero rate' tarifs
        if ($order_id && get_post_meta( $order_id, 'is_vat_exempt', true ) == 'yes') {
            $exemptRates = WC_Tax::get_rates('Zero rate', WC()->customer);
            $exemptRate = array_shift($exemptRates);
            if ($exemptRate && $exemptRate['label']) {
                $order = wc_get_order( $order_id );
                $order->update_meta_data( 'vat_exempt_reason', $exemptRate['label'] );
                $order->save();
            }
        }

        $status = $this->options->get('document_status');
        if ($status == 'processing' || !$status) {
            $this->generate($order_id);
        }
    }

    public function triggered_order_completed( $order_id ) {
        if ($this->options->get('document_status') == 'completed') {
            $this->generate($order_id);
        }
    }

    private function generate( $order_id ) {
        if (!$order_id) {
            return;
        }

        if( ! get_post_meta( $order_id, WC_EENVFACT_OPTION_PREFIX . 'document_generated', true ) ) {
            $order = wc_get_order( $order_id );

            $error = '';
            $clientId = $this->create_or_update_client($order, $error);

            if (!$clientId || $error) {
                if (!$error) {
                    $error = __("Could not create or update client", 'woo-eenvoudigfactureren');
                }
                $this->logger->error($error, $order_id);
                return;
            }

            $postData = $this->build_document($order, $clientId, $error);

            if (!$postData || $error) {
                if (!$error) {
                    $error = __("Could not generate document", 'woo-eenvoudigfactureren');
                }
                $this->logger->error($error, $order_id);
                return;
            }

            $domain = 'invoices';
            if ($this->options->get('document_type') == 'order') {
                $domain = 'orders';
            }

            $documentData = $this->client->post($domain, $postData, $error);

            if ($documentData && property_exists($documentData, 'error')) {
                $error = $documentData->error;
                $documentData = null;
            }

            if (!$documentData) {
                if (!$error) {
                    $error = __("Could not create document", 'woo-eenvoudigfactureren');
                }
                $this->logger->error($error, $order_id);
            } else {
                if ($domain == 'orders') {
                    $document_id = $documentData->order_id;
                } else {
                    $document_id = $documentData->invoice_id;
                }

                $document = $this->client->get($domain . '/' . $document_id);

                $document_url = $this->options->get('website_url') . '/' . $domain . '#pg=view&doc_id=' . $document_id;
                $document_name = ($domain == 'invoices'?__('Invoice','woo-eenvoudigfactureren'):__('Order Form','woo-eenvoudigfactureren')) . ' ' . $document->number;

                $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_generated', true );
                $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_url', $document_url );
                $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_name', $document_name );
                $order->save();

                if ($this->options->get('mail')) {
                    $this->client->post($domain.'/'.$document_id.'/sendemail', ['recipient'=>'main_contact'], $error);

                    if ($error) {
                        $this->logger->error($error, $order_id);
                    }
                }
            }
        }
    }

    private function build_client($order) {
        $firstName = $order->get_billing_first_name();
        $lastName = $order->get_billing_last_name();
        $name = $order->get_billing_company();
        $attention = '';
        if (!$name) {
            $name = trim($firstName . ' ' . $lastName);
        } else {
            $attention = trim($firstName . ' ' . $lastName);
        }
        if ($attention == $name) {
            $attention = '';
        }

        // Try looking for a vat number
        foreach(['_vat_number', '_billing_vat_number', 'vat_number'] as $vatNumberMeta) {
            $vat_number = $order->get_meta( $vatNumberMeta, true );
            if ($vat_number) {
                break;
            }
        }
        if (!$vat_number) {
            $vat_number = '';
        }

        $client = (object)[
            'external_client_id' => '',
            'name' => $name,
            'tax_code' => $vat_number,
            'attention' => $attention,
            'street' => $order->get_billing_address_1(),
            'street2' => $order->get_billing_address_2(),
            'postal_code' => $order->get_billing_postcode(),
            'city' => $order->get_billing_city(),
            'country_code' => $order->get_shipping_country(),
            'email_address' => $order->get_billing_email(),
            'phone_number' => $order->get_billing_phone(),
            'delivery_address' => null,
        ];

        if ($order->get_customer_id() > 0) {
            $client->external_client_id = 'WC:'.$order->get_customer_id();
        }

        if ($order->has_shipping_address() && ($order->get_billing_address_1() != $order->get_shipping_address_1() || $order->get_billing_address_2() != $order->get_shipping_address_2() || $order->get_billing_postcode() != $order->get_shipping_postcode() || $order->get_billing_city() != $order->get_shipping_city() || $order->get_billing_country() != $order->get_shipping_country())) {
            $client->delivery_address = (object)[
                'street' => $order->get_shipping_address_1(),
                'street2' => $order->get_shipping_address_2(),
                'postal_code' => $order->get_shipping_postcode(),
                'city' => $order->get_shipping_city(),
                'country_code' => $order->get_shipping_country(),
            ];
        }

        return $client;
    }

    private function build_document($order, $clientId, &$error) {

        $postData = array();
        $postData['client_id'] = $clientId;

        $layoutId = $this->options->get('layout_id');
        if ($layoutId) {
            $postData['layout_id'] = (int)$layoutId;
        }

        $exempt = $order->get_meta('is_vat_exempt', true) == 'yes';
        $exemptReason = $exempt?(string)$order->get_meta('vat_exempt_reason', true):'';

        $items = array();
        $usedTaxRates = [];
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();

            $amount = round($item->get_total()/$item->get_quantity(), 6);

            $taxRate = 0;
            if ($item->get_total_tax() != 0 && $item->get_total() != 0) {
                $taxRate = round($item->get_total_tax() / $item->get_total() * 100, wc_get_price_decimals());
            }
            $usedTaxRates[] = $taxRate;

            $items[] = (object)[
                'description' => $product->get_name(),
                'amount' => $amount,
                'quantity' => $item->get_quantity(),
                'tax_rate' => $taxRate,
                'tax_rate_special_status' => $exemptReason,
            ];
        }
        if ($order->get_shipping_total() != 0) {
            $shipping_items = $order->get_items( 'shipping' );
            if (count($shipping_items) > 1) {
                $error = __('Multiple shipping methods not supported','woo-eenvoudigfactureren');
                return null;
            }

            $shipping = array_shift( $shipping_items );

            $taxRate = 0;
            if ($order->get_shipping_tax() != 0 && $order->get_shipping_total() != 0) {
                $taxRate = round($order->get_shipping_tax() / $order->get_shipping_total() * 100, wc_get_price_decimals());
            }
            $items[] = (object)[
                'description' => __('Shipping Costs:', 'woo-eenvoudigfactureren') . ' ' . $order->get_shipping_method(),
                'amount' => $order->get_shipping_total(),
                'quantity' => 1,
                'tax_rate' => $taxRate,
                'tax_rate_special_status' => $exemptReason,
            ];
        }
        if ($order->get_discount_total() != 0) {
            $taxRate = 0;
            if ($order->get_discount_tax() != 0 && $order->get_discount_total() != 0) {
                $taxRate = round($order->get_discount_tax() / $order->get_discount_total() * 100, wc_get_price_decimals());
            }
            if (!in_array($taxRate, $usedTaxRates)) {
                $error = __('Discount with different tax rates not supported', 'woo-eenvoudigfactureren');
                return null;
            }
            $items[] = (object)[
                'description' => __('Discount', 'woo-eenvoudigfactureren'),
                'amount' => $order->get_discount_total()*-1,
                'quantity' => 1,
                'tax_rate' => $taxRate,
                'tax_rate_special_status' => $exemptReason,
            ];
        }
        $postData['items'] = $items;

        return (object)$postData;
    }

    private function get_existing_client($customer_id) {
        $existingClient = null;

        if ($customer_id > 0) { // is not guest account
            $clientIdFromMeta = (int)get_user_meta($customer_id, WC_EENVFACT_OPTION_PREFIX . 'client_id', true);

            if ($clientIdFromMeta > 0) {
                $existingClient = $this->client->get('clients/'.$clientIdFromMeta);
                if ($existingClient && !property_exists($existingClient, 'client_id')) {
                    $existingClient = null;
                }
            }

            if (!$existingClient && $this->options->get('search_client_number')) {
                $existingClients = $this->client->get('clients?filter=number__eq__'.$customer_id);
                if ($existingClients) {
                    $existingClient = array_shift($existingClients);
                }
            }
        }

        return $existingClient;
    }

    private function create_or_update_client($order, &$error) {

        $existingClient = $this->get_existing_client($order->get_customer_id());
        $client = $this->build_client($order);

        $clientId = null;
        if ($existingClient) {
            $clientId = $existingClient->client_id;

            $props = array_keys(get_object_vars($client));
            $existingClientCopy = (object)array_filter(get_object_vars($existingClient), function($key) use($props) { return in_array($key, $props); }, ARRAY_FILTER_USE_KEY);

            if (json_encode($existingClientCopy) != json_encode($client)) {
                // update client
                $result = $this->client->post('clients/'.$clientId, $client, $error);
                if (!$result) {
                    if (!$error) {
                        $error = __('Could not update client', 'woo-eenvoudigfactureren');
                    }
                } elseif (property_exists($result, 'error')) {
                    $error = $result->error;
                }
            }
        } else {
            // create client
            $result = $this->client->post('clients', $client, $error);

            if (!$result) {
                if (!$error) {
                    $error = __('Could not create client', 'woo-eenvoudigfactureren');
                }
            } elseif (property_exists($result, 'error')) {
                $error = $result->error;
            } else {
                $clientId = $result->client_id;
            }
        }

        if ($clientId > 0 && $order->get_customer_id() > 0) {
            update_user_meta($order->get_customer_id(), WC_EENVFACT_OPTION_PREFIX . 'client_id', $clientId);
        }

        return $clientId;
    }
}
