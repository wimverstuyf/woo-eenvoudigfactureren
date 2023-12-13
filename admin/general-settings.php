<?php

class WcEenvoudigFactureren_GeneralSettings {

    private $options;
    private $client;

    public function __construct($options, $client) {
        $this->options = $options;
        $this->client = $client;
    }

    public function register_actions() {
        add_action('init',array($this, 'save'));
    }

    public function save() {
        if(isset($_POST['wcef_save_general_setting'])){
            $nonce = sanitize_text_field( $_POST['wcef_post_security'] );
            if ( ! wp_verify_nonce( $nonce, 'wcef_data' ) ) {
                die( __( 'Security check', 'eenvoudigfactureren-for-woocommerce' ) );
            } else {
                $layout_id = sanitize_text_field($_POST['wcef_document_layout']);
                $document_type = sanitize_text_field($_POST['wcef_document_type']);
                $document_status = sanitize_text_field($_POST['wcef_document_status']);
                $mail_document = isset($_POST['wcef_mail_document']) && $_POST['wcef_mail_document']=='1';
                $set_paid = isset($_POST['wcef_set_paid']) && $_POST['wcef_set_paid']=='1';
                $add_sku = isset($_POST['wcef_add_sku']) && $_POST['wcef_add_sku']=='1';
                $search_client_number = isset($_POST['wcef_search_client_number']) && $_POST['wcef_search_client_number']=='1';
                $use_order_reference = isset($_POST['wcef_use_order_reference']) && $_POST['wcef_use_order_reference']=='1';
                $gl_account_products = sanitize_text_field($_POST['wcef_gl_account_products']);
                $gl_account_shipping = sanitize_text_field($_POST['wcef_gl_account_shipping']);
                $gl_account_fees = sanitize_text_field($_POST['wcef_gl_account_fees']);

                $this->options->update('layout_id',$layout_id);
                $this->options->update('document_type',$document_type);
                $this->options->update('document_status',$document_status);
                $this->options->update('mail',$mail_document);
                $this->options->update('set_paid',$set_paid);
                $this->options->update('add_sku',$add_sku);
                $this->options->update('search_client_number', $search_client_number);
                $this->options->update('use_order_reference', $use_order_reference);
                $this->options->update('gl_account_products', $gl_account_products);
                $this->options->update('gl_account_shipping', $gl_account_shipping);
                $this->options->update('gl_account_fees', $gl_account_fees);
            }
        }
    }

    public function show() {
        if($this->options->get('verified') == false) {
            $this->show_not_connected();
            return;
        }

        $document_status = $this->options->get('document_status');
        if (!$document_status) {
            $document_status = 'processing';
        }

        $document_type = $this->options->get('document_type');
        if (!$document_type) {
            $document_type = 'invoice';
        }

        $mail_document = !!$this->options->get('mail');

        $set_paid = !!$this->options->get('set_paid');

        $add_sku = !!$this->options->get('add_sku');

        $search_client_number = !!$this->options->get('search_client_number');

        $use_order_reference = !!$this->options->get('use_order_reference');

        $gl_account_products = $this->options->get('gl_account_products');

        $gl_account_shipping = $this->options->get('gl_account_shipping');

        $gl_account_fees = $this->options->get('gl_account_fees');

        $layout_id = $this->options->get('layout_id');
        $layouts = $this->client->get('layouts');
        if (!$layouts) {
            $layouts = [];
        }

?>
    <div class="wrap">
        <h1><?php _e('EenvoudigFactureren General Settings', 'eenvoudigfactureren-for-woocommerce' ); ?></h1>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Trigger Document Creation', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <select name="wcef_document_status" style="width: 30em;" id="wcef_document_status">
                                <option value="processing" <?php if ($document_status=='processing') echo 'selected'; ?>><?php _e('On Order Created', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
                                <option value="completed" <?php if ($document_status=='completed') echo 'selected'; ?>><?php _e('On Order Completed', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
                                <option value="manual" <?php if ($document_status=='manual') echo 'selected'; ?>><?php _e('Manually', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Document Type To Create', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <select name="wcef_document_type" style="width: 30em;" id="wcef_document_type">
                                <option value="order" <?php if ($document_type=='order') echo 'selected'; ?>><?php _e('Order Form', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
                                <option value="invoice" <?php if ($document_type=='invoice') echo 'selected'; ?>><?php _e('Invoice', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
                                <option value="receipt" <?php if ($document_type=='receipt') echo 'selected'; ?>><?php _e('Receipt', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <label><input type="checkbox" value="1" name="wcef_mail_document" <?php if ($mail_document) echo 'checked'; ?>/> <?php _e('On creation automatically send to customer', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <label><input type="checkbox" value="1" name="wcef_set_paid" <?php if ($set_paid) echo 'checked'; ?>/> <?php _e('Automatically set as paid', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <label><input type="checkbox" value="1" name="wcef_add_sku" <?php if ($add_sku) echo 'checked'; ?>/> <?php _e('Add SKU', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <label><input type="checkbox" value="1" name="wcef_search_client_number" <?php if ($search_client_number) echo 'checked'; ?>/> <?php _e('Use client number to search for existing clients', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <label><input type="checkbox" value="1" name="wcef_use_order_reference" <?php if ($use_order_reference) echo 'checked'; ?>/> <?php _e('Use WooCommerce order number as reference on the document', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Layout To Use', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <select name="wcef_document_layout" style="width: 30em;">
                                <option value=""></option>
                                <?php foreach ($layouts as $value): ?>
                                    <option  value="<?php echo esc_attr($value->layout_id); ?>" <?php if ($layout_id == $value->layout_id) echo "selected"; ?>><?php echo esc_html($value->name); ?></option>';
                                <?php endforeach ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('General Ledger Account for Products', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <input type="text" value="<?php echo $gl_account_products; ?>" name="wcef_gl_account_products"/><br>
                            <small><?php _e('(Optional) Between 6 and 8 numeric characters, e.g. 700000', 'eenvoudigfactureren-for-woocommerce' ); ?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('General Ledger Account for Fees', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <input type="text" value="<?php echo $gl_account_fees; ?>" name="wcef_gl_account_fees"/><br>
                            <small><?php _e('(Optional) Between 6 and 8 numeric characters, e.g. 700000', 'eenvoudigfactureren-for-woocommerce' ); ?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('General Ledger Account for Shipping', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <input type="text" value="<?php echo $gl_account_shipping; ?>" name="wcef_gl_account_shipping"/><br>
                            <small><?php _e('(Optional) Between 6 and 8 numeric characters, e.g. 700000', 'eenvoudigfactureren-for-woocommerce' ); ?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"></th>
                        <td>
                            <input type="hidden" name="wcef_post_security" value="<?php echo wp_create_nonce( 'wcef_data' );?>">
                            <p class="submit"><input type="submit" name="wcef_save_general_setting" class="button button-primary" value="<?php _e('Save Changes', 'eenvoudigfactureren-for-woocommerce'); ?>"></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
<?php
    }

    private function show_not_connected() {
?>
    <div class="wrap">
        <h1><?php _e('EenvoudigFactureren General Settings', 'eenvoudigfactureren-for-woocommerce' ); ?></h1>
        <div class="error">
            <p><strong><?php _e('ERROR :', 'eenvoudigfactureren-for-woocommerce' );?></strong> <?php _e('Add credentials and verify connection to EenvoudigFactureren to continue.', 'eenvoudigfactureren-for-woocommerce' );?></p>
        </div>
    </div>
<?php
    }
}
