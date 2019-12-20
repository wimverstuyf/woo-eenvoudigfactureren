# EenvoudigFactureren plugin for WooCommerce

Generate invoices or order forms in EenvoudigFactureren for WooCommerce orders.

## Description

The WooCommerce EenvoudigFactureren plugin is an extension for your WooCommerce online shop that automatically generates invoices/order forms in EenvoudigFactureren for new WooCommerce orders.

### Key features

* Every new order automatically trigger the creation of an invoice or order form at EenvoudigFactureren.
* Customer information is synchronised to EenvoudigFactureren.
* Supports EU VAT numbers and VAT exemptions.
* Configure automatic email sending to your customers with attached PDF invoices from EenvoudigFactureren.
* Administrator and Store Manager can directly open the generated invoice or order form from WooCommerce.

### About EenvoudigFactureren

EenvoudigFactureren is an accounting solution for Belgian entrepreneurs (Dutch-speaking only). You can create a new account for free at [EenvoudigFactureren](https://www.eenvoudigfactureren.be/).

### Technical specifications

* Requires at least WooCommerce 3.6 version.
* Developed for single-site installations.

## Installation

### Steps of installation

1. Upload and unzip `woo-eenvoudigfactureren.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In the Wordpress Admin section of your online shop configure the plugin by adding your EenvoudigFactureren credentials in the EenvoudigFactureren API Settings page.
4. That's it! By default a new invoice will be automatically created in EenvoudigFactureren on order creation.

### Required

EenvoudigFactureren account is required. You have create an account at [EenvoudigFactureren](https://www.eenvoudigfactureren.be/).

### Limitations

* Coupons usage is only limited supported. When a single document has mixed tax rates creation will fail.
* Only the EURO currency is supported.
