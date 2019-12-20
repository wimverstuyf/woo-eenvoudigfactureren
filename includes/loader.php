<?php

define( 'WC_EENVFACT_OPTION_PREFIX', 'wc_eenvoudigfactureren_' );
define( 'WC_EENVFACT_URL', 'https://eenvoudigfactureren.be' );

class WooEenvoudigFactureren_Loader {

    public static function init() {
        self::load_dependencies();
        self::register_actions();
    }

    private static function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/options.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/client.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/logger.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/generation.php';

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/api-settings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/general-settings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/column.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/menu.php';
    }

    public static function load_languages() {
        load_plugin_textdomain('woo-eenvoudigfactureren', false,  dirname( dirname( plugin_basename( __FILE__ ) ) )  . '/languages/');
    }

    private static function register_actions() {
        add_action( 'plugins_loaded', array(self::class, 'load_languages') );

        $options = new WooEenvoudigFactureren_Options();
        $client = new WooEenvoudigFactureren_Client($options);
        $logger = new WooEenvoudigFactureren_Logger($options);

        $generation = new WooEenvoudigFactureren_Generation($options, $client, $logger);
        $generation->register_actions();

        $general_settings = new WooEenvoudigFactureren_GeneralSettings($options, $client);
        $api_settings = new WooEenvoudigFactureren_ApiSettings($options, $client);
        $menu = new WooEenvoudigFactureren_Menu($api_settings, $general_settings);
        $column = new WooEenvoudigFactureren_Column();

        $general_settings->register_actions();
        $api_settings->register_actions();
        $menu->register_actions();
        $column->register_actions();
    }
}
