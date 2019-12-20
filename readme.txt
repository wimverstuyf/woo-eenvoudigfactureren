=== EenvoudigFactureren for WooCommerce integration ===
Contributors: wimverstuyf
Tags: WooCommerce, Invoice, Accounting, EenvoudigFactureren
Requires at least: 5.2.0
Tested up to: 5.3.0
Requires PHP: 5.6.20
Stable tag: 0.1.0
WC requires at least: 3.6
WC tested up to: 3.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate invoices or order forms in EenvoudigFactureren for WooCommerce orders.

== Description ==

The WooCommerce EenvoudigFactureren plugin is an extension for your WooCommerce online shop that automatically generates invoices/order forms in EenvoudigFactureren for new WooCommerce orders.

= Key features =

* Every new order automatically trigger the creation of an invoice or order form at EenvoudigFactureren.
* Customer information is synchronised to EenvoudigFactureren.
* Supports EU VAT numbers and VAT exemptions.
* Configure automatic email sending to your customers with attached PDF invoices from EenvoudigFactureren.
* Administrator and Store Manager can directly open the generated invoice or order form from WooCommerce.

= About EenvoudigFactureren =

EenvoudigFactureren is an accounting solution for Belgian entrepreneurs (Dutch-speaking only). You can create a new account for free at [EenvoudigFactureren](https://eenvoudigfactureren.be/).

= Technical specifications =

* Requires at least WooCommerce 3.6 version.
* Developed for single-site installations.

== Installation ==

= Steps of installation =

1. Upload and unzip `woo-eenvoudigfactureren.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the Wordpress Admin section of your online shop configure the plugin by adding your EenvoudigFactureren credentials in the EenvoudigFactureren API Settings page.
4. That's it! By default a new invoice will be automatically created in EenvoudigFactureren on order creation.

= Required =

EenvoudigFactureren account is required. You have create an account at [EenvoudigFactureren](https://eenvoudigfactureren.be/).

= Limitations =

* Coupons usage is only limited supported. When a single document has mixed tax rates creation will fail.
* Only the EURO currency is supported.

== Changelog ==

= 1.0 =
* Initial release of the plugin.

== Upgrade Notice ==

= 1.0 =
Just released to the WordPress repository.
