=== EenvoudigFactureren for WooCommerce ===
Contributors: wimverstuyf
Tags: WooCommerce, Invoice, Accounting, EenvoudigFactureren
Requires at least: 5.2.0
Tested up to: 6.8
Requires PHP: 7.1
Stable tag: 1.1.3
WC requires at least: 3.6
WC tested up to: 10.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate invoices in EenvoudigFactureren for WooCommerce orders.

== Description ==

The WooCommerce EenvoudigFactureren plugin is an extension for your WooCommerce online shop that automatically generates invoices/order forms in EenvoudigFactureren for WooCommerce orders.

= Key features =

* Every new order manually or automatically triggers the creation of an invoice or order form at EenvoudigFactureren.
* Customer information is synchronised to EenvoudigFactureren.
* Supports EU VAT numbers and VAT exemptions.
* Configure automatic email sending to your customers with attached PDF invoices from EenvoudigFactureren.
* Administrator and Store Manager can directly open the generated invoice or order form from WooCommerce.

= About EenvoudigFactureren =

EenvoudigFactureren is an invoicing solution for Belgian entrepreneurs (Dutch-speaking only). You can create a new account for free at [EenvoudigFactureren](https://eenvoudigfactureren.be/).

= Technical specifications =

* Requires at least WooCommerce 3.6 version.
* Developed for single-site installations.

== Installation ==

= Steps of installation =

1. Unzip `eenvoudigfactureren-for-woocommerce.zip` to the `/wp-content/plugins/eenvoudigfactureren-for-woocommerce/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the Wordpress Admin section of your online shop configure the plugin by adding your EenvoudigFactureren credentials in the EenvoudigFactureren API Settings page.
4. That's it! By default a new invoice will be automatically created in EenvoudigFactureren on order creation.

= Required =

EenvoudigFactureren account is required. You have create an account at [EenvoudigFactureren](https://eenvoudigfactureren.be/).

= Options =

By default new customers in WooCommerce are added as new client in EenvoudigFacturen. To link an existing customer in WooCommerce to an existing client in EenvoudigFactureren add the WooCommerce customer ID as client number in EenvoudigFactureren. In WooCommerce you need to activate the option 'Use client number to search for existing clients' in the Settings page to enable this behaviour.
Only WooCommerce customers which have created an account can be linked to existing clients in EenvoudigFactureren.

When a VAT exemption is applied for foreign business customers the VAT exemption reason code (e.g. 'ICL') can be added as 'Zero rate' tax rate for the required countries. The VAT exemption reason code should be added as tax name.

= Limitations =

* Only EURO currency is supported.

== Changelog ==

= 1.0 =
* Initial release of the plugin.

== Upgrade Notice ==

= 1.0 =
Just released to the WordPress repository.
