<?php

class WcEenvoudigFactureren_Client {

    private $options;
    private $logger;

    public function __construct($options, $logger = null) {
        $this->options = $options;
        $this->logger = $logger;
    }

    public function get($resource) {
        if (!$this->verify()) {
            $this->log_api_call('GET', $this->get_url($resource), null, null, null, 'not connected');
            return null;
        }

        $url = $this->get_url($resource);
        $get_data = wp_remote_get( $url, array('headers' => $this->get_headers()) );
        $response_body = is_wp_error($get_data) ? '' : wp_remote_retrieve_body($get_data);
        $response_code = is_wp_error($get_data) ? null : wp_remote_retrieve_response_code($get_data);

        if (is_wp_error($get_data)) {
            $this->log_api_call('GET', $url, null, null, $response_code, $get_data->get_error_message());
            return null;
        }

        $data = json_decode($response_body);
        $this->log_api_call('GET', $url, null, $response_body, $response_code);

        return $data;
    }

    public function post($resource, $data, &$error) {
        if (!$this->verify()) {
            $error = 'not connected';
            $this->log_api_call('POST', $this->get_url($resource), $data, null, null, $error);
            return null;
        }

        $url = $this->get_url($resource);
        $result = wp_remote_post( $url, array(
            'method'      => 'POST',
            'timeout'     => 20,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers'     => $this->get_headers(),
            'body'        => json_encode($data),
            'cookies'     => array()
            )
        );

        $to_return = null;
        if ( is_wp_error( $result ) ) {
            $error = $result->get_error_message();
            $this->log_api_call('POST', $url, $data, null, null, $error);
        } else {
            $response_body = wp_remote_retrieve_body($result);
            $response_code = wp_remote_retrieve_response_code($result);
            $to_return = json_decode($response_body);
            $this->log_api_call('POST', $url, $data, $response_body, $response_code);
        }

        return $to_return;
    }

    private function verify() {
        $baseUrl = $this->options->get('website_url');
        $username = $this->options->get('username');
        $password = $this->options->get('password');
        $apikey = $this->options->get('apikey');

        return ($baseUrl && (($username && $password) || $apikey) && substr($baseUrl, 0, 4) === 'http');
    }

    private function get_url($resource) {
        return $this->options->get('website_url').'/api/v1/'.$resource;
    }

    private function get_headers() {
        $headers = array(
            'Content-Type' => 'application/json', 'Accept' => 'application/json',
        );

        $apikey = $this->options->get('apikey');
        if ($apikey) {
            $headers['X-API-Key'] = $apikey;
        } else {
            $headers['Authorization'] = 'Basic ' . base64_encode($this->options->get('username').':'.$this->options->get('password'));
        }

        return $headers;
    }

    private function log_api_call($method, $url, $request_body, $response_body, $response_code = null, $error_message = '') {
        if ($this->should_skip_api_log($url)) {
            return;
        }

        if ($this->logger && method_exists($this->logger, 'log_api_call')) {
            $this->logger->log_api_call($method, $url, $request_body, $response_body, $response_code, $error_message);
        }
    }

    private function should_skip_api_log($url) {
        $path = wp_parse_url((string) $url, PHP_URL_PATH);
        $query = wp_parse_url((string) $url, PHP_URL_QUERY);
        if (!$path) {
            return false;
        }

        $path = untrailingslashit($path);
        $skip_paths = [
            '/api/v1/accounts/current',
            '/api/v1/layouts',
        ];

        if (in_array($path, $skip_paths, true)) {
            return true;
        }

        if ($path === '/api/v1/api/v1/settings' && $query === 'fields=taxes') {
            return true;
        }

        return false;
    }

}
