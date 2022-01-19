<?php
/**
 * Plugin Name: EenvoudigFactureren for WooCommerce
 * Description: Generate invoices of order forms in EenvoudigFactureren for WooCommerce orders.
 * Author: wimverstuyf
 * Text Domain: woo-eenvoudigfactureren
 * Version: 0.5.3
 * Requires at least: 5.2.0
 * Requires PHP: 5.6.20
 * Domain Path: /languages
 * WC requires at least: 3.6.0
 * WC tested up to: 3.8.0
 */

require_once plugin_dir_path( __FILE__ ) . 'includes/loader.php';

WooEenvoudigFactureren_Loader::init();
