<?php
/**
 * Plugin Name: EenvoudigFactureren for WooCommerce
 * Plugin URI: https://eenvoudigfactureren.be
 * Description: Generate invoices in EenvoudigFactureren for WooCommerce orders.
 * Author: EenvoudigFactureren
 * Author URI: https://eenvoudigfactureren.be
 * Text Domain: eenvoudigfactureren-for-woocommerce
 * Version: 1.1.2
 * Requires at least: 5.2.0
 * Requires PHP: 7.1
 * Domain Path: /languages
 * License: GPLv2 or later
 * WC requires at least: 3.6.0
 */

require_once plugin_dir_path( __FILE__ ) . 'includes/loader.php';

WcEenvoudigFactureren_Loader::init();
