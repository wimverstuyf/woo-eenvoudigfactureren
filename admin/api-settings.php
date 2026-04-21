<?php

class WcEenvoudigFactureren_ApiSettings {

    const WEBSITE_OPTION_EENVOUDIGFACTUREREN = 'eenvoudigfactureren';
    const WEBSITE_OPTION_SIMPLYBOOKS = 'simplybooks';
    const WEBSITE_OPTION_OTHER = 'other';

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

    private function normalize_website_url($url) {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        return untrailingslashit($url);
    }

    private function get_website_choices() {
        return [
            self::WEBSITE_OPTION_EENVOUDIGFACTUREREN => 'https://eenvoudigfactureren.be',
            self::WEBSITE_OPTION_SIMPLYBOOKS => 'https://app.simplybooks.be',
        ];
    }

    private function determine_website_choice($website_url) {
        $website_url = $this->normalize_website_url($website_url);

        foreach ($this->get_website_choices() as $choice => $url) {
            if ($website_url === $this->normalize_website_url($url)) {
                return $choice;
            }
        }

        return self::WEBSITE_OPTION_OTHER;
    }

    public function save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient privileges', 'eenvoudigfactureren-for-woocommerce' ) );
        }
        check_admin_referer( 'wcef_data', 'wcef_post_security' );

        $website_choice = isset($_POST['wcef_website_choice']) ? sanitize_key($_POST['wcef_website_choice']) : self::WEBSITE_OPTION_EENVOUDIGFACTUREREN;
        $website_url = '';
        $website_choices = $this->get_website_choices();
        if (isset($website_choices[$website_choice])) {
            $website_url = $website_choices[$website_choice];
        } else {
            $website_choice = self::WEBSITE_OPTION_OTHER;
            $website_url = sanitize_text_field( $_POST['wcef_websiteurl_other'] );
        }

        $website_url = $this->normalize_website_url($website_url);
        if ($website_url === '') {
            $website_url = WC_EENVFACT_URL;
        }

        $this->options->update('website_url', $website_url);
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
        $website_url = $this->normalize_website_url($this->options->get('website_url') ? $this->options->get('website_url') : WC_EENVFACT_URL);
        $website_choice = $this->determine_website_choice($website_url);
?>
    <div class="wrap">
        <h1><?php _e('Connection with EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?></h1>

        <?php if ( isset($_GET['wcef_result']) ) : ?>
            <?php if ( $_GET['wcef_result'] === 'ok' ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html( sprintf( __( 'The connection was set up successfully. Connected account: %s', 'eenvoudigfactureren-for-woocommerce' ), $this->options->get('account') ) ); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html__( 'ERROR: Not connected', 'eenvoudigfactureren-for-woocommerce' ); ?></p>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <?php if ( $this->options->get('verified') ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html( sprintf( __( 'The connection was set up successfully. Connected account: %s', 'eenvoudigfactureren-for-woocommerce' ), $this->options->get('account') ) ); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e( 'ERROR: Not connected', 'eenvoudigfactureren-for-woocommerce' ); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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
                            <fieldset>
                                <label>
                                    <input type="radio" name="wcef_website_choice" value="<?php echo esc_attr(self::WEBSITE_OPTION_EENVOUDIGFACTUREREN); ?>" <?php checked($website_choice, self::WEBSITE_OPTION_EENVOUDIGFACTUREREN); ?>>
                                    <?php _e('EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?> (https://eenvoudigfactureren.be)
                                </label><br>
                                <label>
                                    <input type="radio" name="wcef_website_choice" value="<?php echo esc_attr(self::WEBSITE_OPTION_SIMPLYBOOKS); ?>" <?php checked($website_choice, self::WEBSITE_OPTION_SIMPLYBOOKS); ?>>
                                    <?php _e('SimplyBooks', 'eenvoudigfactureren-for-woocommerce' ); ?> (https://app.simplybooks.be)
                                </label><br>
                                <label>
                                    <input type="radio" name="wcef_website_choice" value="<?php echo esc_attr(self::WEBSITE_OPTION_OTHER); ?>" <?php checked($website_choice, self::WEBSITE_OPTION_OTHER); ?>>
                                    <?php _e('Other', 'eenvoudigfactureren-for-woocommerce' ); ?>
                                </label>
                            </fieldset>
                            <p style="margin-top: 8px;">
                                <input name="wcef_websiteurl_other" style="width: 30em;" type="text" placeholder="<?php _e('Enter a custom website URL', 'eenvoudigfactureren-for-woocommerce' ); ?>" value="<?php echo $website_choice === self::WEBSITE_OPTION_OTHER ? esc_url($website_url) : ''; ?>">
                            </p>
                            <p class="description"><?php _e('Choose Other only when you need to connect to a different environment.', 'eenvoudigfactureren-for-woocommerce' ); ?></p>
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
                            <label><?php _e('Advanced settings', 'eenvoudigfactureren-for-woocommerce' ); ?></label>
                        </th>
                        <td>
                            <details>
                                <summary><?php _e('Show legacy username/password login', 'eenvoudigfactureren-for-woocommerce' ); ?></summary>
                                <p class="description"><?php _e('This login method is deprecated and should only be used when an API key cannot be used.', 'eenvoudigfactureren-for-woocommerce' ); ?></p>
                                <div style="margin-top: 12px;">
                                    <p>
                                        <label><?php _e('Username', 'eenvoudigfactureren-for-woocommerce' ); ?><br>
                                            <input name="wcef_username" style="width: 30em;" type="text" placeholder="<?php _e('Enter your e-mail address at EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?>" value="<?php echo esc_attr($this->options->get('username'));?>">
                                        </label>
                                    </p>
                                    <p>
                                        <label><?php _e('Password', 'eenvoudigfactureren-for-woocommerce' ); ?><br>
                                            <input name="wcef_password" style="width: 30em;" type="password" placeholder="<?php _e('Enter your password at EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?>" value="<?php echo esc_attr(str_repeat('*', strlen($this->options->get('password')))); ?>">
                                        </label>
                                    </p>
                                </div>
                            </details>
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
