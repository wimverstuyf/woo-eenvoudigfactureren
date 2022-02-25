<?php

class WcEenvoudigFactureren_Menu {

    private $api_settings;
    private $general_settings;

    public function __construct($api_settings, $general_settings) {
        $this->api_settings = $api_settings;
        $this->general_settings = $general_settings;
    }

    public function register_actions() {
        add_action( 'admin_menu', array($this, 'menu_page') );
    }

    public function menu_page() {
        add_menu_page( __( 'EenvoudigFactureren Settings', 'wc-eenvoudigfactureren' ), 'EenvoudigFactureren', 'manage_options', 'wc-eenvoudigfactureren-settings-page', false, 'dashicons-admin-generic', 80);

        add_submenu_page( 'wc-eenvoudigfactureren-settings-page', __( 'EenvoudigFactureren Settings', 'wc-eenvoudigfactureren' ), __( 'API Settings', 'wc-eenvoudigfactureren' ), 'manage_options', 'wc-eenvoudigfactureren-settings-page', array($this->api_settings, 'show'));
        add_submenu_page( 'wc-eenvoudigfactureren-settings-page', __( 'EenvoudigFactureren Settings', 'wc-eenvoudigfactureren' ), __( 'General Settings', 'wc-eenvoudigfactureren' ), 'manage_options', 'wc-eenvoudigfactureren-general-settings-page', array($this->general_settings, 'show'));
    }
}
