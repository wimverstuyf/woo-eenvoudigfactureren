<?php

class WcEenvoudigFactureren_ApiSettings {

    private $options;
    private $client;

    public function __construct($options, $client) {
        $this->options = $options;
        $this->client = $client;
    }

    public function register_actions() {
        add_action( 'init', array($this, 'save') );
    }

    private function scramble_apikey($apikey) {
        if (!$apikey) {
            return $apikey;
        }

        return substr($apikey, 0, 5) . '*****';
    }

    public function save() {
        if(isset($_POST['wcef_save_api_setting'])){
            $nonce = sanitize_text_field( $_POST['wcef_post_security'] );
            if ( ! wp_verify_nonce( $nonce, 'wcef_data' ) ) {
                die( __( 'Security check', 'eenvoudigfactureren-for-woocommerce' ) );
            } else {
                $this->options->update('website_url', sanitize_text_field( $_POST['wcef_websiteurl'] ));
                $this->options->update('username', sanitize_text_field( $_POST['wcef_username'] ));
                $this->options->update('last_error', '');

                $password = sanitize_text_field( $_POST['wcef_password'] );
                if (!$password || $password != str_repeat('*', strlen($password))) {
                    $this->options->update('password',$password);
                }

                $apikey = sanitize_text_field( $_POST['wcef_apikey'] );
                if (!$apikey || $apikey != $this->scramble_apikey($apikey)) {
                    $this->options->update('apikey',$apikey);
                }

                $this->verify();
            }
        }
    }

    public function show() {
?>
    <div class="wrap">
        <h1><?php _e('EenvoudigFactureren API Settings', 'eenvoudigfactureren-for-woocommerce' ); ?></h1>

        <?php if(isset($_GET['error_description'])){ ?>
            <div class="error">
                <p><strong><?php echo esc_html(sanitize_text_field( $_GET['error_description'] ));?></strong></p></div>
            </div>
        <?php } ?>
        <?php if ($this->options->get('verified')) { ?>
            <div class="updated notice">
                <p><?php echo __( 'Connection verified:', 'eenvoudigfactureren-for-woocommerce' ) . ' ' . esc_html($this->options->get('account')); ?></p>
            </div>
        <?php } else { ?>
            <div class="error notice">
                <p><?php _e( 'ERROR: Not connected', 'eenvoudigfactureren-for-woocommerce' ); ?></p>
            </div>
        <?php } ?>
        <?php if ($this->options->get('last_error')) { ?>
            <div class="error notice">
                <p><?php echo __( 'LAST ERROR:', 'eenvoudigfactureren-for-woocommerce' ) . ' ' . esc_html(mb_substr($this->options->get('last_error'), 0, 10000)); ?></p>
            </div>
        <?php } ?>

        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Website URL', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <input name="wcef_websiteurl" style="width: 30em;" type="text" placeholder="<?php _e('Enter the EenvoudigFactureren Website URL', 'eenvoudigfactureren-for-woocommerce' ); ?>" value="<?php echo $this->options->get('website_url')?esc_url($this->options->get('website_url')):WC_EENVFACT_URL;?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Connect', 'eenvoudigfactureren-for-woocommerce' ); ?> (<?php _e('Recommended', 'eenvoudigfactureren-for-woocommerce' ); ?>):</label>
                        </th>
                        <td>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Api Key', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <input name="wcef_apikey" style="width: 30em;" type="text" placeholder="<?php _e('Enter your API key at EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?>" value="<?php echo esc_attr($this->scramble_apikey($this->options->get('apikey'))); ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Connect', 'eenvoudigfactureren-for-woocommerce' ); ?> (<?php _e('Alternative', 'eenvoudigfactureren-for-woocommerce' ); ?>):</label>
                        </th>
                        <td>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Username', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <input name="wcef_username" style="width: 30em;" type="text" placeholder="<?php _e('Enter your e-mail address at EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?>" value="<?php echo esc_attr($this->options->get('username'));?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Password', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <input name="wcef_password" style="width: 30em;" type="password" placeholder="<?php _e('Enter your password at EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?>" value="<?php echo esc_attr(str_repeat('*', strlen($this->options->get('password')))); ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <p class="submit">
                                <input type="hidden" name="wcef_post_security" value="<?php echo wp_create_nonce( "wcef_data" ); ?>">
                                <input type="submit" name="wcef_save_api_setting" class="button button-primary" value="<?php _e('Verify and save settings', 'eenvoudigfactureren-for-woocommerce' ); ?>">
                            </p>
                        </td>
                    </tr>

                </tbody>
            </table>
        </form>
    </div>
<?php
    }

    private function verify() {
        $this->options->update('verified',false);
        $this->options->update('account','');

        $account = $this->client->get('accounts/current');

        if ($account && $account->number !== '') {
            $this->options->update('verified',true);
            $this->options->update('account', sanitize_text_field( $account->name ? $account->name : $account->number ));
        }
    }
}
