<?php

class WcEenvoudigFactureren_Generation {

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
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ( $order && $order->get_meta('is_vat_exempt', true) === 'yes' ) {
                $exempt_rates = WC_Tax::get_rates('Zero rate', WC()->customer);

                if (!$exempt_rates) {
                    $exempt_rates = WC_Tax::get_rates('Nultarief', WC()->customer);
                }

                $exempt_rate = array_shift($exempt_rates);
                if ($exempt_rate && $exempt_rate['label']) {
                    $order = wc_get_order( $order_id );
                    $order->update_meta_data( 'vat_exempt_reason', $exempt_rate['label'] );
                    $order->save();
                }
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

    public function generate( $order_id ) {
        if (!$order_id) {
            return;
        }

        $gen_error = '';

        // Allow skipping of invoice generation
        // Skip when document is already generating or generated
        $order = wc_get_order( $order_id );
        $generated  = wc_string_to_bool( (string) $order->get_meta( WC_EENVFACT_OPTION_PREFIX . 'document_generated' ) );
        $generating = wc_string_to_bool( (string) $order->get_meta( WC_EENVFACT_OPTION_PREFIX . 'document_generating' ) );
        $should_skip = (bool) apply_filters(
            'wc_eenvfact_should_skip_generation',
            ($generated || $generating),
            $order_id,
            $order
        );

        if ( $should_skip ) {
            return ['ok' => false, 'message' => __('Already generating or generated', 'eenvoudigfactureren-for-woocommerce')];
        }

        $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_generating', true );
        $order->save();

        try {
            $client_id = $this->create_or_update_client($order, $gen_error);

            if ( ! $client_id || $gen_error ) {
                if ( ! $gen_error ) {
                    $gen_error = __("Could not create or update client", 'eenvoudigfactureren-for-woocommerce');
                }
                throw new \Exception( $gen_error );
            }

            $document = $this->build_document($order, $client_id, $gen_error);

            if ( ! $document || $gen_error ) {
                if ( ! $gen_error ) {
                    $gen_error = __( 'Could not generate document', 'eenvoudigfactureren-for-woocommerce' );
                }
                throw new \Exception( $gen_error );
            }

            $domain = 'invoices';
            if ($this->options->get('document_type') == 'order') {
                $domain = 'orders';
            }
            if ($this->options->get('document_type') == 'receipt') {
                $domain = 'receipts';
            }

            $create_result = $this->client->post($domain, $document, $gen_error);

            if ( $create_result && property_exists( $create_result, 'error' ) ) {
                $gen_error = $create_result->error . ' (' . json_encode( $document ) . ')';
                throw new \Exception( $gen_error );
            }

            $document_id = 0;
            if ($create_result && property_exists($create_result, 'invoice_id')) {
                $document_id = (int)$create_result->invoice_id;
            }
            if ($create_result && property_exists($create_result, 'order_id')) {
                $document_id = (int)$create_result->order_id;
            }
            if ($create_result && property_exists($create_result, 'receipt_id')) {
                $document_id = (int)$create_result->receipt_id;
            }

            if ( ! $document_id ) {
                if ( ! $gen_error ) {
                    $gen_error = __( 'Could not create document', 'eenvoudigfactureren-for-woocommerce' );
                }
                throw new \Exception( $gen_error );
            }

            $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_generated', true );

            $document = $this->client->get($domain . '/' . $document_id);
            if ($document) { // should always be true
                $document_url = $this->options->get('website_url') . '/' . $domain . '#pg=view&doc_id=' . $document_id;
                $document_name = ($domain == 'invoices'?__('Invoice','eenvoudigfactureren-for-woocommerce'):($domain == 'receipts'?__('Receipt','eenvoudigfactureren-for-woocommerce'):__('Order Form','eenvoudigfactureren-for-woocommerce'))) . ' ' . $document->number;

                $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_url', $document_url );
                $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_name', $document_name );
            }

            if ($this->options->get('mail')) {
                $should_send = !$this->options->get('mail_document_only_business_orders') || (!empty($this->get_vat_number($order)));
                
                if ($should_send) {
                    if ($domain == 'invoices' && !empty($this->get_vat_number($order))) {
                        $this->client->post($domain.'/'.$document_id.'/send', ['methods'=>['peppol'=>[], 'email'=>['recipient'=>'main_contact']]], $gen_error);
                    } else {
                        $this->client->post($domain.'/'.$document_id.'/sendemail', ['recipient'=>'main_contact'], $gen_error);
                    }
                    
                    if ($gen_error) {
                        $this->logger->error($gen_error, $order_id);
                    }
                }
            }

            return ['ok' => true, 'message' => 'created', 'id' => $document_id];
        } catch ( \Exception $e ) {
            $this->logger->error( $e->getMessage(), $order_id );
            $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_error', $e->getMessage() );
            return ['ok' => false, 'message' => $e->getMessage()];
        } finally {
            // ALWAYS reset generating flag and persist
            $order->update_meta_data( WC_EENVFACT_OPTION_PREFIX . 'document_generating', false );
            $order->save();
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
                        $error = __('Could not update client', 'eenvoudigfactureren-for-woocommerce');
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
                    $error = __('Could not create client', 'eenvoudigfactureren-for-woocommerce');
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

        $vat_number = $this->get_vat_number($order);
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

    private function tax_rates() {
        $tax_rates = [];
        $taxes = $this->client->get('api/v1/settings?fields=taxes');
        if (property_exists($taxes->taxes, 'tax1') && $taxes->taxes->tax1->rate > 0) {
            $tax_rates[] = floatval($taxes->taxes->tax1->rate);
        }
        if (property_exists($taxes->taxes, 'tax2') && $taxes->taxes->tax2->rate > 0) {
            $tax_rates[] = floatval($taxes->taxes->tax2->rate);
        }
        if (property_exists($taxes->taxes, 'tax3') && $taxes->taxes->tax3->rate > 0) {
            $tax_rates[] = floatval($taxes->taxes->tax3->rate);
        }
        return $tax_rates;
    }

    private function determine_tax_rate($available_tax_rates, $total, $tax) {
        $tax_rate = 0;
        if ($tax != 0 && $total != 0) {
            $tax_rate = null;

            $tax = abs($tax);
            $total = abs($total);
            foreach($available_tax_rates as $available_tax_rate) {
                $tax_calculated = round($total * $available_tax_rate / 100, 2);
                if (abs($tax_calculated - $tax) <= 0.01) {
                    $tax_rate = $available_tax_rate;
                    break;
                }
            }
            if (is_null($tax_rate)) {
                $tax_rate = round($tax / $total * 100, 0);
            }
        }

        return $tax_rate;
    }

    private function build_document($order, $client_id, &$error) {
        $document = array();
        $document['client_id'] = $client_id;

        $language = $this->get_order_language($order);
        if ($language) {
            $document['language'] = $language;
        }

        $layout_id = $this->options->get('layout_id');
        if ($layout_id) {
            $document['layout_id'] = (int)$layout_id;
        }

        if ($this->options->get('use_order_reference')) {
            $order_reference = $order->get_order_number();
            $document['reference'] = __('Order', 'eenvoudigfactureren-for-woocommerce') . ' ' . $order_reference;
        }

        $exempt = $order->get_meta('is_vat_exempt', true) == 'yes';
        $exempt_reason = $exempt?(string)$order->get_meta('vat_exempt_reason', true):'';

        $tax_rates = $this->tax_rates();
        $document['tax_included'] = $order->get_prices_include_tax();

        $items = array();
        $tax_rates_in_use = [];
        $gl_account_products = $this->options->get('gl_account_products');
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();

            $amount = round($item->get_total()/$item->get_quantity(), 2);
            $amount_with_tax = round(($item->get_total()+$item->get_total_tax())/$item->get_quantity(), 2);

            $tax_rate = $this->determine_tax_rate($tax_rates, $item->get_total(), $item->get_total_tax());
            $tax_rates_in_use[] = $tax_rate;

            $item = (object)[
                'description' => $item->get_name(),
                'amount' => $amount,
                'amount_with_tax' => $amount_with_tax,
                'quantity' => $item->get_quantity(),
                'tax_rate' => $tax_rate,
                'tax_rate_special_status' => $exempt_reason == 'IC' ? ($product->is_virtual() ? 'ICD' : 'ICL') : $exempt_reason,
            ];

            if ($gl_account_products) {
                $item->general_ledger_account = $gl_account_products;
            }

            if ($product && $this->options->get('add_sku') && $product->get_sku()) {
                $item->{'stockitem_code'} = mb_substr((string)$product->get_sku(), 0, 20);
            }

            $items[] = $item;
        }
        $gl_account_fees = $this->options->get('gl_account_fees');
        foreach ( $order->get_fees() as $item_id => $item_fee) {
            $amount = $item_fee->get_total();
            $amount_with_tax = $amount + $item_fee->get_total_tax();

            $tax_rate = $this->determine_tax_rate($tax_rates, $item_fee->get_total(), $item_fee->get_total_tax());
            $tax_rates_in_use[] = $tax_rate;

            $item = (object)[
                'description' => $item_fee->get_name(),
                'amount' => $amount,
                'amount_with_tax' => $amount_with_tax,
                'quantity' => 1,
                'tax_rate' => $tax_rate,
                'tax_rate_special_status' => $exempt_reason == 'IC' ? 'ICD' : $exempt_reason,
            ];

            if ($gl_account_fees) {
                $item->general_ledger_account = $gl_account_fees;
            }

            $items[] = $item;
        }
        if ($order->get_shipping_total() != 0) {
            $gl_account_shipping = $this->options->get('gl_account_shipping');

            $tax_rate = $this->determine_tax_rate($tax_rates, $order->get_shipping_total(), $order->get_shipping_tax());
            $item = (object)[
                'description' => __('Shipping Costs:', 'eenvoudigfactureren-for-woocommerce') . ' ' . $order->get_shipping_method(),
                'amount' => $order->get_shipping_total(),
                'amount_with_tax' => $order->get_shipping_total()+$order->get_shipping_tax(),
                'quantity' => 1,
                'tax_rate' => $tax_rate,
                'tax_rate_special_status' => $exempt_reason == 'IC' ? 'ICD' : $exempt_reason,
            ];

            if ($gl_account_shipping) {
                $item->general_ledger_account = $gl_account_shipping;
            }

            $items[] = $item;
        }
        $document['items'] = $items;

        if ($this->options->get('set_paid') && $this->options->get('document_type') == 'invoice' && $order->is_paid()) {
            $document['payments'] = [(object)[
                'remaining_amount' => 'yes',
                'description' => 'WooCommerce',
            ]];
        }
        
        return (object)$document;
    }

    private function get_vat_number($order) {
        // try looking for a vat number
        $meta_keys = apply_filters('wc_eenvfact_vat_keys', [
            '_vat_number',
            '_billing_vat_number',
            '_billing_vat',
            '_billing_eu_vat_number',
            '_billing_btw_nummer',
            '_billing_yweu_vat',
            'vat_number',
            'yweu_billing_vat'
        ]);

        foreach($meta_keys as $meta) {
            $vat_number = $order->get_meta( $meta, true );
            if ($vat_number) {
                return $vat_number;
            }
        }
        
        return '';
    }

    private function get_order_language($order) {
        $meta_keys = ['wpml_language'];
        $locales = [
            'nl' => 'dutch',
            'fr' => 'french',
            'de' => 'german',
            'en' => 'english',
        ];
    
        foreach ($meta_keys as $meta) {
            $code = $order->get_meta($meta, true);
            if (!empty($code ) && isset( $locales[$code])) {
                return $locales[$code];
            }
        }

        return false;
    }
}
