<?php

class WooEenvoudigFactureren_Menu {

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
        add_menu_page( __( 'EenvoudigFactureren Settings', 'woo-eenvoudigfactureren' ), 'EenvoudigFactureren', 'manage_options', 'woo-eenvoudigfactureren-settings-page', false, 'dashicons-admin-generic', 80);

        add_submenu_page( 'woo-eenvoudigfactureren-settings-page', __( 'EenvoudigFactureren Settings', 'woo-eenvoudigfactureren' ), __( 'API Settings', 'woo-eenvoudigfactureren' ), 'manage_options', 'woo-eenvoudigfactureren-settings-page', array($this->api_settings, 'show'));
        add_submenu_page( 'woo-eenvoudigfactureren-settings-page', __( 'EenvoudigFactureren Settings', 'woo-eenvoudigfactureren' ), __( 'General Settings', 'woo-eenvoudigfactureren' ), 'manage_options', 'woo-eenvoudigfactureren-general-settings-page', array($this->general_settings, 'show'));
    }
}
