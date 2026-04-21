<?php

class WcEenvoudigFactureren_ApiLogs {

    private $logger;

    public function __construct($logger) {
        $this->logger = $logger;
    }

    public function register_actions() {
        add_action( 'admin_post_wcef_export_api_logs', array($this, 'export_csv') );
    }

    public function show() {
        $logs = $this->logger->get_api_logs();
        $filter = isset($_GET['wcef_status']) ? sanitize_key($_GET['wcef_status']) : 'all';
        $logs = $this->filter_logs($logs, $filter);
?>
    <div class="wrap">
        <h1><?php _e('Logbook of EenvoudigFactureren', 'eenvoudigfactureren-for-woocommerce' ); ?></h1>
        <p><?php _e('Showing stored API calls, newest first. At least the latest 1000 calls are kept, and all calls from the last 30 days are preserved.', 'eenvoudigfactureren-for-woocommerce' ); ?></p>

        <form method="get" style="margin: 16px 0;">
            <input type="hidden" name="page" value="wc-eenvoudigfactureren-api-logs-page">
            <label for="wcef_status"><strong><?php _e('Filter', 'eenvoudigfactureren-for-woocommerce' ); ?>:</strong></label>
            <select name="wcef_status" id="wcef_status">
                <option value="all" <?php selected($filter, 'all'); ?>><?php _e('All', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
                <option value="success" <?php selected($filter, 'success'); ?>><?php _e('Success', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
                <option value="error" <?php selected($filter, 'error'); ?>><?php _e('Error', 'eenvoudigfactureren-for-woocommerce' ); ?></option>
            </select>
            <input type="submit" class="button" value="<?php _e('Apply', 'eenvoudigfactureren-for-woocommerce' ); ?>">
            <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wcef_export_api_logs&wcef_status=' . $filter ), 'wcef_export_api_logs' ) ); ?>"><?php _e('Export CSV', 'eenvoudigfactureren-for-woocommerce' ); ?></a>
        </form>

        <?php if (empty($logs)) : ?>
            <p><?php _e('No API calls logged yet.', 'eenvoudigfactureren-for-woocommerce' ); ?></p>
        <?php else : ?>
            <table class="widefat striped" style="table-layout: fixed;">
                <colgroup>
                    <col style="width: 150px;">
                    <col style="width: 90px;">
                    <col style="width: 28%;">
                    <col style="width: 120px;">
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th><?php _e('Date', 'eenvoudigfactureren-for-woocommerce' ); ?></th>
                        <th><?php _e('Method', 'eenvoudigfactureren-for-woocommerce' ); ?></th>
                        <th><?php _e('URL', 'eenvoudigfactureren-for-woocommerce' ); ?></th>
                        <th><?php _e('Status', 'eenvoudigfactureren-for-woocommerce' ); ?></th>
                        <th><?php _e('Details', 'eenvoudigfactureren-for-woocommerce' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html(isset($log['created_at']) ? $log['created_at'] : ''); ?></td>
                            <td><?php echo esc_html(isset($log['method']) ? $log['method'] : ''); ?></td>
                            <td style="word-break: break-all;"><?php echo esc_html(isset($log['url']) ? $log['url'] : ''); ?></td>
                            <td>
                                <?php
                                $status = !empty($log['success']) ? __('Success', 'eenvoudigfactureren-for-woocommerce') : __('Error', 'eenvoudigfactureren-for-woocommerce');
                                $response_code = isset($log['response_code']) && $log['response_code'] !== '' ? ' (' . $log['response_code'] . ')' : '';
                                echo esc_html($status . $response_code);
                                ?>
                            </td>
                            <td>
                                <details>
                                    <summary><?php _e('Show details', 'eenvoudigfactureren-for-woocommerce' ); ?></summary>
                                    <?php if (!empty($log['error_message'])) : ?>
                                        <p><strong><?php _e('Error message', 'eenvoudigfactureren-for-woocommerce' ); ?>:</strong><br><?php echo nl2br(esc_html($log['error_message'])); ?></p>
                                    <?php endif; ?>
                                    <p><strong><?php _e('Request body', 'eenvoudigfactureren-for-woocommerce' ); ?>:</strong></p>
                                    <pre style="white-space: pre-wrap; max-width: 100%; overflow: auto;"><?php echo esc_html(isset($log['request_body']) ? $log['request_body'] : ''); ?></pre>
                                    <p><strong><?php _e('Response body', 'eenvoudigfactureren-for-woocommerce' ); ?>:</strong></p>
                                    <pre style="white-space: pre-wrap; max-width: 100%; overflow: auto;"><?php echo esc_html(isset($log['response_body']) ? $log['response_body'] : ''); ?></pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
    }

    public function export_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient privileges', 'eenvoudigfactureren-for-woocommerce' ) );
        }

        check_admin_referer( 'wcef_export_api_logs' );

        $filter = isset($_GET['wcef_status']) ? sanitize_key($_GET['wcef_status']) : 'all';
        $logs = $this->filter_logs($this->logger->get_api_logs(), $filter);

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="wcef-api-logs-' . gmdate('Y-m-d-H-i-s') . '.csv"' );

        $output = fopen('php://output', 'w');
        if (!$output) {
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            __( 'Date', 'eenvoudigfactureren-for-woocommerce' ),
            __( 'Method', 'eenvoudigfactureren-for-woocommerce' ),
            __( 'URL', 'eenvoudigfactureren-for-woocommerce' ),
            __( 'Status', 'eenvoudigfactureren-for-woocommerce' ),
            __( 'Response code', 'eenvoudigfactureren-for-woocommerce' ),
            __( 'Error message', 'eenvoudigfactureren-for-woocommerce' ),
            __( 'Request body', 'eenvoudigfactureren-for-woocommerce' ),
            __( 'Response body', 'eenvoudigfactureren-for-woocommerce' ),
        ]);

        foreach ($logs as $log) {
            fputcsv($output, [
                isset($log['created_at']) ? $log['created_at'] : '',
                isset($log['method']) ? $log['method'] : '',
                isset($log['url']) ? $log['url'] : '',
                !empty($log['success']) ? __( 'Success', 'eenvoudigfactureren-for-woocommerce' ) : __( 'Error', 'eenvoudigfactureren-for-woocommerce' ),
                isset($log['response_code']) ? $log['response_code'] : '',
                isset($log['error_message']) ? $log['error_message'] : '',
                isset($log['request_body']) ? $log['request_body'] : '',
                isset($log['response_body']) ? $log['response_body'] : '',
            ]);
        }

        fclose($output);
        exit;
    }

    private function filter_logs($logs, &$filter) {
        if ($filter === 'success') {
            return array_values(array_filter($logs, function($log) {
                return !empty($log['success']);
            }));
        }

        if ($filter === 'error') {
            return array_values(array_filter($logs, function($log) {
                return empty($log['success']);
            }));
        }

        $filter = 'all';
        return $logs;
    }
}
