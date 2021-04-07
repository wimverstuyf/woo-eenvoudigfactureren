<?php

class WooEenvoudigFactureren_ApiSettings {

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
            $nonce = $_POST['wcef_post_security'];
            if ( ! wp_verify_nonce( $nonce, 'wcef_data' ) ) {
                die( __( 'Security check', 'woo-eenvoudigfactureren' ) );
            } else {
                $this->options->update('website_url',sanitize_text_field($_POST['wcef_websiteurl']));
                $this->options->update('username',sanitize_text_field($_POST['wcef_username']));

                $password = sanitize_text_field($_POST['wcef_password']);
                if (!$password || $password != str_repeat('*', strlen($password))) {
                    $this->options->update('password',$password);
                }

                $apikey = sanitize_text_field($_POST['wcef_apikey']);
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
        <h1><?php _e('EenvoudigFactureren API Settings', 'woo-eenvoudigfactureren' ); ?></h1>

        <?php if(isset($_GET['error_description'])){ ?>
            <div class="error">
                <p><strong><?php echo $_GET['error_description'];?></strong></p></div>
            </div>
        <?php } ?>
        <?php if ($this->options->get('verified')) { ?>
            <div class="updated notice">
                <p><?php echo __( 'Connection verified:', 'woo-eenvoudigfactureren' ) . ' ' . $this->options->get('account'); ?></p>
            </div>
        <?php } else { ?>
            <div class="error notice">
                <p><?php _e( 'ERROR: Not connected', 'woo-eenvoudigfactureren' ); ?></p>
            </div>
        <?php } ?>
        <?php if ($this->options->get('last_error')) { ?>
            <div class="error notice">
                <p><?php echo __( 'LAST ERROR:', 'woo-eenvoudigfactureren' ) . ' ' . $this->options->get('last_error'); ?></p>
            </div>
        <?php } ?>

        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Website URL', 'woo-eenvoudigfactureren' ); ?></label>
                        </th>
                        <td>
                            <input name="wcef_websiteurl" style="width: 30em;" type="text" placeholder="<?php _e('Enter the EenvoudigFactureren Website URL', 'woo-eenvoudigfactureren' ); ?>" value="<?php echo $this->options->get('website_url')?$this->options->get('website_url'):WC_EENVFACT_URL;?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Connect', 'woo-eenvoudigfactureren' ); ?> (<?php _e('Recommended', 'woo-eenvoudigfactureren' ); ?>):</label>
                        </th>
                        <td>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Api Key', 'woo-eenvoudigfactureren' ); ?></label>
                        </th>
                        <td>
                            <input name="wcef_apikey" style="width: 30em;" type="text" placeholder="<?php _e('Enter your API key at EenvoudigFactureren', 'woo-eenvoudigfactureren' ); ?>" value="<?php echo $this->scramble_apikey($this->options->get('apikey')); ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Connect', 'woo-eenvoudigfactureren' ); ?> (<?php _e('Alternative', 'woo-eenvoudigfactureren' ); ?>):</label>
                        </th>
                        <td>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Username', 'woo-eenvoudigfactureren' ); ?></label>
                        </th>
                        <td>
                            <input name="wcef_username" style="width: 30em;" type="text" placeholder="<?php _e('Enter your e-mail address at EenvoudigFactureren', 'woo-eenvoudigfactureren' ); ?>" value="<?php echo $this->options->get('username');?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e('Password', 'woo-eenvoudigfactureren' ); ?></label>
                        </th>
                        <td>
                            <input name="wcef_password" style="width: 30em;" type="password" placeholder="<?php _e('Enter your password at EenvoudigFactureren', 'woo-eenvoudigfactureren' ); ?>" value="<?php echo str_repeat('*', strlen($this->options->get('password'))); ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                        </th>
                        <td>
                            <p class="submit">
                                <input type="hidden" name="wcef_post_security" value="<?php echo wp_create_nonce( "wcef_data" ); ?>">
                                <input type="submit" name="wcef_save_api_setting" class="button button-primary" value="<?php _e('Verify and save settings', 'woo-eenvoudigfactureren' ); ?>">
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
            $this->options->update('account', $account->name ? $account->name : $account->number);
        }
    }
}
