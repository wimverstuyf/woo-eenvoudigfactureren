<?php

class WcEenvoudigFactureren_Logger {

    const API_LOG_OPTION = WC_EENVFACT_OPTION_PREFIX . 'api_logs';
    const API_LOG_LIMIT = 1000;
    const API_LOG_RETENTION_DAYS = 30;
    const API_LOG_VALUE_LIMIT = 4000;

    private $options;

    public function __construct($options) {
        $this->options = $options;
    }

    public function error($message, $order_id) {
        wc_get_logger()->error($message . ' (Order #'.$order_id.')', array('source' => 'EenvoudigFactureren'));
    }

    public function log_api_call($method, $url, $request_body, $response_body, $response_code = null, $error_message = '') {
        $logs = get_option(self::API_LOG_OPTION, []);
        if (!is_array($logs)) {
            $logs = [];
        }

        $success = empty($error_message);
        if ($success && $response_code !== null && $response_code !== '') {
            $success = ((int) $response_code) < 400;
        }

        array_unshift($logs, [
            'created_at' => current_time('mysql'),
            'method' => strtoupper((string) $method),
            'url' => (string) $url,
            'request_body' => $this->limit_value($request_body),
            'response_body' => $this->limit_value($response_body),
            'response_code' => is_null($response_code) ? '' : (string) $response_code,
            'error_message' => $this->limit_value($error_message),
            'success' => $success,
        ]);

        $logs = $this->prune_api_logs($logs);

        update_option(self::API_LOG_OPTION, $logs, false);
    }

    public function get_api_logs() {
        $logs = get_option(self::API_LOG_OPTION, []);
        return is_array($logs) ? $logs : [];
    }

    private function limit_value($value) {
        if (is_null($value) || $value === '') {
            return '';
        }

        if (!is_string($value)) {
            $encoded = wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $value = $encoded !== false ? $encoded : print_r($value, true);
        }

        if (strlen($value) <= self::API_LOG_VALUE_LIMIT) {
            return $value;
        }

        return substr($value, 0, self::API_LOG_VALUE_LIMIT) . '...';
    }

    private function prune_api_logs($logs) {
        $cutoff = current_time('timestamp') - (DAY_IN_SECONDS * self::API_LOG_RETENTION_DAYS);
        $recent_logs = [];
        $older_logs = [];

        foreach ($logs as $log) {
            $created_at = isset($log['created_at']) ? strtotime((string) $log['created_at']) : false;
            if ($created_at !== false && $created_at >= $cutoff) {
                $recent_logs[] = $log;
                continue;
            }

            $older_logs[] = $log;
        }

        $older_limit = max(self::API_LOG_LIMIT - count($recent_logs), 0);
        if ($older_limit > 0 && count($older_logs) > $older_limit) {
            $older_logs = array_slice($older_logs, 0, $older_limit);
        } elseif ($older_limit === 0) {
            $older_logs = [];
        }

        return array_merge($recent_logs, $older_logs);
    }
}
