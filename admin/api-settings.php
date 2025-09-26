<?php

class WcEenvoudigFactureren_ApiSettings {

    private $options;
    private $client;

    public function __construct($options, $client) {
        $this->options = $options;
        $this->client = $client;
    }

    public function register_actions() {
        add_action( 'admin_post_wcef_save_api_setting', array( $this, 'save' ) );
    }

    private function scramble_apikey($apikey) {
        if (!$apikey) {
            return $apikey;
        }

        return substr($apikey, 0, 5) . '*****';
    }

    public function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient privileges', 'eenvoudigfactureren-for-woocommerce' ) );
        }
        check_admin_referer( 'wcef_data', 'wcef_post_security' );

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

        $page = isset($_POST['_wcef_page']) ? sanitize_key($_POST['_wcef_page']) : 'wc-eenvoudigfactureren-settings-page';

        $result = $this->options->get('verified') ? 'ok' : 'fail';
        $url = add_query_arg(
            array( 'page' => $page, 'wcef_result' => $result ),
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $url );
        exit;
    }
    
    public function show() {
?>
    <div class="wrap">
        <h1><?php _e('EenvoudigFactureren API Settings', 'eenvoudigfactureren-for-woocommerce' ); ?></h1>

        <?php if ( isset($_GET['wcef_result']) ) : ?>
            <?php if ( $_GET['wcef_result'] === 'ok' ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo __( 'Connection verified:', 'eenvoudigfactureren-for-woocommerce' ) . ' ' . esc_html( $this->options->get('account') ); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php
                        echo esc_html__( 'ERROR: Not connected', 'eenvoudigfactureren-for-woocommerce' );
                        $last_error = $this->options->get('last_error');
                        if ( $last_error ) {
                            echo ' — ' . esc_html( mb_substr( $last_error, 0, 10000 ) );
                        }
                    ?></p>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <?php if ( $this->options->get('verified') ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo __( 'Connection verified:', 'eenvoudigfactureren-for-woocommerce' ) . ' ' . esc_html( $this->options->get('account') ); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e( 'ERROR: Not connected', 'eenvoudigfactureren-for-woocommerce' ); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($this->options->get('last_error')) { ?>
            <div class="error notice">
                <p><?php echo __( 'LAST ERROR:', 'eenvoudigfactureren-for-woocommerce' ) . ' ' . esc_html(mb_substr($this->options->get('last_error'), 0, 10000)); ?></p>
            </div>
        <?php } ?>


        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="wcef_save_api_setting">
            <input type="hidden" name="wcef_post_security" value="<?php echo wp_create_nonce( "wcef_data" ); ?>">
            <input type="hidden" name="_wcef_page" value="<?php echo esc_attr( isset($_GET['page']) ? $_GET['page'] : 'wc-eenvoudigfactureren-settings-page' ); ?>">


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
                            <input name="wcef_apikey" style="width: 30em;" type="text" autocomplete="off" placeholder="<?php _e('Enter your API key at EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?>" value="<?php echo esc_attr($this->scramble_apikey($this->options->get('apikey'))); ?>">
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

        if ( is_wp_error( $account ) ) {
            $this->options->update('last_error', $account->get_error_message() );
            return;
        }
        if ( ! $account || empty( $account->number ) ) {
            $this->options->update('last_error', __( 'Invalid response from server', 'eenvoudigfactureren-for-woocommerce' ) );
            return;
        }

        $this->options->update('verified',true);
        $this->options->update('account', sanitize_text_field( $account->name ? $account->name : $account->number ));
    }
}
