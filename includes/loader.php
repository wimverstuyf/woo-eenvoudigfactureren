<?php

define( 'WC_EENVFACT_OPTION_PREFIX', 'wc_eenvoudigfactureren_' );
define( 'WC_EENVFACT_URL', 'https://eenvoudigfactureren.be' );

class WcEenvoudigFactureren_Loader {

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
        load_plugin_textdomain('wc-eenvoudigfactureren', false,  dirname( dirname( plugin_basename( __FILE__ ) ) )  . '/languages/');
    }

    private static function register_actions() {
        add_action( 'plugins_loaded', array(self::class, 'load_languages') );

        $options = new WcEenvoudigFactureren_Options();
        $client = new WcEenvoudigFactureren_Client($options);
        $logger = new WcEenvoudigFactureren_Logger($options);

        $generation = new WcEenvoudigFactureren_Generation($options, $client, $logger);
        $generation->register_actions();

        $general_settings = new WcEenvoudigFactureren_GeneralSettings($options, $client);
        $api_settings = new WcEenvoudigFactureren_ApiSettings($options, $client);
        $menu = new WcEenvoudigFactureren_Menu($api_settings, $general_settings);
        $column = new WcEenvoudigFactureren_Column($options, $generation);

        $general_settings->register_actions();
        $api_settings->register_actions();
        $menu->register_actions();
        $column->register_actions();
    }
}
