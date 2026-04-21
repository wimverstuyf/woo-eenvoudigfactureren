<?php

class WcEenvoudigFactureren_Menu {

    private $api_settings;
    private $general_settings;
    private $api_logs;

    public function __construct($api_settings, $general_settings, $api_logs) {
        $this->api_settings = $api_settings;
        $this->general_settings = $general_settings;
        $this->api_logs = $api_logs;
    }

    public function register_actions() {
        add_action( 'admin_menu', array($this, 'menu_page') );
    }

    public function menu_page() {
        add_menu_page( __( 'EenvoudigFactureren Settings', 'eenvoudigfactureren-for-woocommerce' ), 'EenvoudigFactureren', 'manage_options', 'wc-eenvoudigfactureren-settings-page', false, 'dashicons-admin-generic', 80);

        add_submenu_page( 'wc-eenvoudigfactureren-settings-page', __( 'EenvoudigFactureren Settings', 'eenvoudigfactureren-for-woocommerce' ), __( 'Connection', 'eenvoudigfactureren-for-woocommerce' ), 'manage_options', 'wc-eenvoudigfactureren-settings-page', array($this->api_settings, 'show'));
        add_submenu_page( 'wc-eenvoudigfactureren-settings-page', __( 'EenvoudigFactureren Settings', 'eenvoudigfactureren-for-woocommerce' ), __( 'General Settings', 'eenvoudigfactureren-for-woocommerce' ), 'manage_options', 'wc-eenvoudigfactureren-general-settings-page', array($this->general_settings, 'show'));
        add_submenu_page( 'wc-eenvoudigfactureren-settings-page', __( 'EenvoudigFactureren Settings', 'eenvoudigfactureren-for-woocommerce' ), __( 'Logbook', 'eenvoudigfactureren-for-woocommerce' ), 'manage_options', 'wc-eenvoudigfactureren-api-logs-page', array($this->api_logs, 'show'));
    }
}
