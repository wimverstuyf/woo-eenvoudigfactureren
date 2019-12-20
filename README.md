# EenvoudigFactureren plugin for WooCommerce

Generate invoices or order forms in EenvoudigFactureren for WooCommerce orders.

## Description

The WooCommerce EenvoudigFactureren plugin is an extension for your WooCommerce online shop that automatically generates invoices or order forms in EenvoudigFactureren for new WooCommerce orders.

### Key features

* Every new order automatically triggers the creation of an invoice or order form at EenvoudigFactureren.
* Customer information is synchronised to EenvoudigFactureren.
* Supports EU VAT numbers and VAT exemptions.
* Configure automatic email sending to your customers with attached PDF from EenvoudigFactureren.
* Administrator and Store Manager can directly open the generated invoice or order form from WooCommerce.

### About EenvoudigFactureren

EenvoudigFactureren is an invoicing solution for Belgian entrepreneurs (Dutch only). You can create a new account for free at [EenvoudigFactureren](https://eenvoudigfactureren.be/).

### Technical specifications

* Requires at least WooCommerce 3.6 version.
* Developed for single-site installations.

## Installation

### Steps of installation

1. Unzip `woo-eenvoudigfactureren.zip` to the `/wp-content/plugins/woo-eenvoudigfactureren/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the WordPress admin section of your online shop configure the plugin by adding your EenvoudigFactureren credentials in the EenvoudigFactureren API Settings page.
4. That's it! By default a new invoice will be automatically created in EenvoudigFactureren on order creation.

### Required

EenvoudigFactureren account is required. You can create an account at [EenvoudigFactureren](https://eenvoudigfactureren.be/).

### Options

By default new customers in WooCommerce are added as new client in EenvoudigFacturen. To link an existing customer in WooCommerce to an existing client in EenvoudigFactureren add the WooCommerce customer ID as client number in EenvoudigFactureren. In WooCommerce you need to activate the option 'Use client number to search for existing clients' in the Settings page to enable this behaviour.
Only WooCommerce customers which have created an account can be linked to existing clients in EenvoudigFactureren.

When a VAT exemption is applied for foreign business customers the VAT exemption reason code (e.g. 'ICL') can be added as 'Zero rate' tax rate for the required countries. The VAT exemption reason code should be added as tax name.

### Limitations

* Limited support for coupon usage! When an order has mixed tax rates creation will fail when a coupon is applied.
* Only EURO as currency is supported.
