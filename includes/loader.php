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
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/api-logs.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/general-settings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/column.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/menu.php';
    }

    public static function load_languages() {
        $domain = 'eenvoudigfactureren-for-woocommerce';
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        $mofile = plugin_dir_path( dirname( __FILE__ ) ) . 'languages/' . $domain . '-' . $locale . '.mo';

        if (is_readable($mofile)) {
            unload_textdomain($domain);
            load_textdomain($domain, $mofile);
            return;
        }

        load_plugin_textdomain($domain, false,  dirname( dirname( plugin_basename( __FILE__ ) ) )  . '/languages/');
    }

    private static function register_actions() {
        add_action( 'init', array(self::class, 'load_languages') );

        $options = new WcEenvoudigFactureren_Options();
        $logger = new WcEenvoudigFactureren_Logger($options);
        $client = new WcEenvoudigFactureren_Client($options, $logger);

        $generation = new WcEenvoudigFactureren_Generation($options, $client, $logger);
        $generation->register_actions();

        $general_settings = new WcEenvoudigFactureren_GeneralSettings($options, $client);
        $api_settings = new WcEenvoudigFactureren_ApiSettings($options, $client);
        $api_logs = new WcEenvoudigFactureren_ApiLogs($logger);
        $menu = new WcEenvoudigFactureren_Menu($api_settings, $general_settings, $api_logs);
        $column = new WcEenvoudigFactureren_Column($options, $generation);

        $general_settings->register_actions();
        $api_settings->register_actions();
        $menu->register_actions();
        $column->register_actions();
    }
}
