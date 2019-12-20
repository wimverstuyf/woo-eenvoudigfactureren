<?php

class WooEenvoudigFactureren_Client {

    private $options;

    public function __construct($options) {
        $this->options = $options;
    }

    public function get($resource) {
        if (!$this->verify()) {
            return null;
        }

        $get_data = wp_remote_get( $this->get_url($resource), array('headers' => $this->get_headers()) );
        $data = json_decode( wp_remote_retrieve_body($get_data) );

        return $data;
    }

    public function post($resource, $data, &$error) {
        if (!$this->verify()) {
            $error = 'not connected';
            return null;
        }

        $result = wp_remote_post( $this->get_url($resource), array(
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
        } else {
            $to_return = json_decode( wp_remote_retrieve_body($result) );
        }

        return $to_return;
    }

    private function verify() {
        $baseUrl = $this->options->get('website_url');
        $username = $this->options->get('username');
        $password = $this->options->get('password');

        return ($baseUrl && $username && $password && substr($baseUrl, 0, 4) === 'http');
    }

    private function get_url($resource) {
        return $this->options->get('website_url').'/api/v1/'.$resource;
    }

    private function get_headers() {
        $username = $this->options->get('username');
        $password = $this->options->get('password');

        return array(
            'Content-Type' => 'application/json', 'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username.':'.$password),
        );
    }

}
