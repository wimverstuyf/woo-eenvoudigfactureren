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
        // needs to be executed here because customer is only available at checkout
        if ($order_id && get_post_meta( $order_id, 'is_vat_exempt', true ) == 'yes') {
            $exempt_rates = WC_Tax::get_rates('Zero rate', WC()->customer);
            $exempt_rate = array_shift($exempt_rates);
            if ($exempt_rate && $exempt_rate['label']) {
                $order = wc_get_order( $order_id );
                $order->update_meta_data( 'vat_exempt_reason', $exempt_rate['label'] );
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
            $client_id = $this->create_or_update_client($order, $error);

            if (!$client_id || $error) {
                if (!$error) {
                    $error = __("Could not create or update client", 'woo-eenvoudigfactureren');
                }
                $this->logger->error($error, $order_id);
                return;
            }

            $document = $this->build_document($order, $client_id, $error);

            if (!$document || $error) {
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

            $create_result = $this->client->post($domain, $document, $error);

            if ($create_result && property_exists($create_result, 'error')) {
                $error = $create_result->error . ' ('.json_encode($document).')';
            }
            $document_id = 0;
            if ($create_result && property_exists($create_result, 'invoice_id')) {
                $document_id = (int)$create_result->invoice_id;
            }
            if ($create_result && property_exists($create_result, 'order_id')) {
                $document_id = (int)$create_result->order_id;
            }

            if ($error || !$document_id) {
                if (!$error) {
                    $error = __("Could not create document", 'woo-eenvoudigfactureren');
                }
                $this->logger->error($error, $order_id);
            } else {
                $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_generated', true );

                $document = $this->client->get($domain . '/' . $document_id);
                if ($document) { // should always be true
                    $document_url = $this->options->get('website_url') . '/' . $domain . '#pg=view&doc_id=' . $document_id;
                    $document_name = ($domain == 'invoices'?__('Invoice','woo-eenvoudigfactureren'):__('Order Form','woo-eenvoudigfactureren')) . ' ' . $document->number;

                    $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_url', $document_url );
                    $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_name', $document_name );
                }

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

    private function create_or_update_client($order, &$error) {

        $existing_client = $this->get_existing_client($order->get_customer_id());
        $client = $this->build_client($order);

        $client_id = null;
        if ($existing_client) {
            $client_id = $existing_client->client_id;

            $props = array_keys(get_object_vars($client));
            $existing_client_copy = (object)array_filter(get_object_vars($existing_client), function($key) use($props) { return in_array($key, $props); }, ARRAY_FILTER_USE_KEY);

            if (json_encode($existing_client_copy) != json_encode($client)) {
                // update client
                $result = $this->client->post('clients/'.$client_id, $client, $error);
                if (!$result) {
                    if (!$error) {
                        $error = __('Could not update client', 'woo-eenvoudigfactureren');
                    }
                } elseif (property_exists($result, 'error')) {
                    $error = $result->error . ' ('.json_encode($client).')';
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
                $error = $result->error . ' ('.json_encode($client).')';
            } else {
                $client_id = $result->client_id;
            }
        }

        if ($client_id > 0 && $order->get_customer_id() > 0) {
            update_user_meta($order->get_customer_id(), WC_EENVFACT_OPTION_PREFIX . 'client_id', $client_id);
        }

        return $client_id;
    }

    private function get_existing_client($customer_id) {
        $existing_client = null;

        if ($customer_id > 0) { // if is not guest account
            $client_id_meta = (int)get_user_meta($customer_id, WC_EENVFACT_OPTION_PREFIX . 'client_id', true);

            if ($client_id_meta > 0) {
                $existing_client = $this->client->get('clients/'.$client_id_meta);
                if ($existing_client && !property_exists($existing_client, 'client_id')) {
                    $existing_client = null;
                }
            }

            if (!$existing_client && $this->options->get('search_client_number')) {
                $existing_clients = $this->client->get('clients?filter=number__eq__'.$customer_id);
                if ($existing_clients) {
                    $existing_client = array_shift($existing_clients);
                }
            }
        }

        return $existing_client;
    }

    private function build_client($order) {
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $name = $order->get_billing_company();
        $attention = '';
        if (!$name) {
            $name = trim($first_name . ' ' . $last_name);
        } else {
            $attention = trim($first_name . ' ' . $last_name);
        }
        if ($attention == $name) {
            $attention = '';
        }

        // try looking for a vat number
        foreach(['_vat_number', '_billing_vat_number', 'vat_number'] as $meta) {
            $vat_number = $order->get_meta( $meta, true );
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
            'country_code' => $order->get_billing_country(),
            'email_address' => $order->get_billing_email(),
            'phone_number' => $order->get_billing_phone(),
            'delivery_address' => null,
        ];

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

    private function build_document($order, $client_id, &$error) {

        $document = array();
        $document['client_id'] = $client_id;

        $layout_id = $this->options->get('layout_id');
        if ($layout_id) {
            $document['layout_id'] = (int)$layout_id;
        }

        $exempt = $order->get_meta('is_vat_exempt', true) == 'yes';
        $exempt_reason = $exempt?(string)$order->get_meta('vat_exempt_reason', true):'';

        $items = array();
        $tax_rates_in_use = [];
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();

            $amount = round($item->get_total()/$item->get_quantity(), 6);

            $tax_rate = 0;
            if ($item->get_total_tax() != 0 && $item->get_total() != 0) {
                $tax_rate = round($item->get_total_tax() / $item->get_total() * 100, 1);
            }
            $tax_rates_in_use[] = $tax_rate;

            $items[] = (object)[
                'description' => $product->get_name(),
                'amount' => $amount,
                'quantity' => $item->get_quantity(),
                'tax_rate' => $tax_rate,
                'tax_rate_special_status' => $exempt_reason,
            ];
        }
        if ($order->get_shipping_total() != 0) {
            $shipping_items = $order->get_items( 'shipping' );
            if (count($shipping_items) > 1) {
                $error = __('Multiple shipping methods not supported', 'woo-eenvoudigfactureren');
                return null;
            }

            $shipping = array_shift( $shipping_items );

            $tax_rate = 0;
            if ($order->get_shipping_tax() != 0 && $order->get_shipping_total() != 0) {
                $tax_rate = round($order->get_shipping_tax() / $order->get_shipping_total() * 100, wc_get_price_decimals());
            }
            $items[] = (object)[
                'description' => __('Shipping Costs:', 'woo-eenvoudigfactureren') . ' ' . $order->get_shipping_method(),
                'amount' => $order->get_shipping_total(),
                'quantity' => 1,
                'tax_rate' => $tax_rate,
                'tax_rate_special_status' => $exempt_reason,
            ];
        }
        if ($order->get_discount_total() != 0) {
            $tax_rate = 0;
            if ($order->get_discount_tax() != 0 && $order->get_discount_total() != 0) {
                $tax_rate = round($order->get_discount_tax() / $order->get_discount_total() * 100, wc_get_price_decimals());
            }
            if (!in_array($tax_rate, $tax_rates_in_use)) {
                $error = __('Discount with different tax rates not supported', 'woo-eenvoudigfactureren');
                return null;
            }
            $items[] = (object)[
                'description' => __('Discount', 'woo-eenvoudigfactureren'),
                'amount' => $order->get_discount_total()*-1,
                'quantity' => 1,
                'tax_rate' => $tax_rate,
                'tax_rate_special_status' => $exempt_reason,
            ];
        }
        $document['items'] = $items;

        return (object)$document;
    }
}
