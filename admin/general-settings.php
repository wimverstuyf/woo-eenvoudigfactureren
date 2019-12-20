<?php

class WooEenvoudigFactureren_GeneralSettings {

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
            $nonce = $_POST['wcef_post_security'];
            if ( ! wp_verify_nonce( $nonce, 'wcef_data' ) ) {
                die( __( 'Security check', 'woo-eenvoudigfactureren' ) );
            } else {
                $layoutId = sanitize_text_field($_POST['wcef_document_layout']);
                $documentType = sanitize_text_field($_POST['wcef_document_type']);
                $documentStatus = sanitize_text_field($_POST['wcef_document_status']);
                $mailDocument = isset($_POST['wcef_mail_document']) && $_POST['wcef_mail_document']=='1';
                $searchClientNumber = isset($_POST['wcef_search_client_number']) && $_POST['wcef_search_client_number']=='1';

                $this->options->update('layout_id',$layoutId);
                $this->options->update('document_type',$documentType);
                $this->options->update('document_status',$documentStatus);
                $this->options->update('mail',$mailDocument);
                $this->options->update('search_client_number', $searchClientNumber);
            }
        }
    }

    private function show_not_connected() {
?>
    <div class="wrap">
        <h1><?php _e('EenvoudigFactureren General Settings', 'woo-eenvoudigfactureren' ); ?></h1>
        <div class="error">
            <p><strong><?php _e('ERROR :', 'woo-eenvoudigfactureren' );?></strong> <?php _e('Add credentials and verify connection to EenvoudigFactureren to continue.', 'woo-eenvoudigfactureren' );?></p>
        </div>
    </div>
<?php
    }

    public function show() {
        if($this->options->get('verified') == false) {
            $this->show_not_connected();
            return;
        }

        $documentStatus = $this->options->get('document_status');
        if (!$documentStatus) {
            $documentStatus = 'processing';
        }

        $documentType = $this->options->get('document_type');
        if (!$documentType) {
            $documentType = 'invoice';
        }

        $mailDocument = !!$this->options->get('mail');

        $searchClientNumber = !!$this->options->get('search_client_number');

        $layoutId = $this->options->get('layout_id');
        $layouts = $this->client->get('layouts');
        if (!$layouts) {
            $layouts = [];
        }

?>
    <div class="wrap">
        <h1><?php _e('EenvoudigFactureren General Settings', 'woo-eenvoudigfactureren' ); ?></h1>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Trigger Document Creation', 'woo-eenvoudigfactureren' ); ?></label>
                        </th>
                        <td>
                            <select name="wcef_document_status" style="width: 30em;" id="wcef_document_status">
                                <option value="processing" <?php if ($documentStatus=='processing') echo 'selected'; ?>><?php _e('On Order Created', 'woo-eenvoudigfactureren' ); ?></option>
                                <option value="completed" <?php if ($documentStatus=='completed') echo 'selected'; ?>><?php _e('On Order Completed', 'woo-eenvoudigfactureren' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Document Type To Create', 'woo-eenvoudigfactureren' ); ?></label>
                        </th>
                        <td>
                            <select name="wcef_document_type" style="width: 30em;" id="wcef_document_type">
                                <option value="order" <?php if ($documentType=='order') echo 'selected'; ?>><?php _e('Order Form', 'woo-eenvoudigfactureren' ); ?></option>
                                <option value="invoice" <?php if ($documentType=='invoice') echo 'selected'; ?>><?php _e('Invoice', 'woo-eenvoudigfactureren' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <label><input type="checkbox" value="1" name="wcef_mail_document" <?php if ($mailDocument) echo 'checked'; ?>/> <?php _e('On creation automatically send to customer', 'woo-eenvoudigfactureren' ); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <label><input type="checkbox" value="1" name="wcef_search_client_number" <?php if ($searchClientNumber) echo 'checked'; ?>/> <?php _e('Use client number to search for existing clients', 'woo-eenvoudigfactureren' ); ?></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Layout To Use', 'woo-eenvoudigfactureren' ); ?></label>
                        </th>
                        <td>
                            <select name="wcef_document_layout" style="width: 30em;">
                                <option value=""></option>
                                <?php foreach ($layouts as $value): ?>
                                    <option  value="<?php echo $value->layout_id ?>" <?php if ($layoutId == $value->layout_id) echo "selected"; ?>><?php echo $value->name; ?></option>';
                                <?php endforeach ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"></th>
                        <td>
                            <input type="hidden" name="wcef_post_security" value="<?php echo wp_create_nonce( 'wcef_data' );?>">
                            <p class="submit"><input type="submit" name="wcef_save_general_setting" class="button button-primary" value="<?php _e('Save Changes', 'woo-eenvoudigfactureren'); ?>"></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
<?php
    }
}
