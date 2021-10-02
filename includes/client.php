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

        // $p = curl_init($this->get_url($resource));
        // $headers = $this->get_headers();
        // curl_setopt($p, CURLOPT_HTTPHEADER, array_map(function($key, $value) { return "$key: $value"; }, array_keys($headers), array_values($headers)));
        // curl_setopt($p, CURLOPT_RETURNTRANSFER, TRUE);
        // $response = curl_exec($p);
        // curl_close($p);
        // $data = json_decode($response);

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

        // $p = curl_init($this->get_url($resource));
        // $headers = $this->get_headers();
        // curl_setopt($p, CURLOPT_HTTPHEADER, array_map(function($key, $value) { return "$key: $value"; }, array_keys($headers), array_values($headers)));
        // curl_setopt($p, CURLOPT_POST, TRUE);
        // curl_setopt($p, CURLOPT_POSTFIELDS, json_encode($data));
        // curl_setopt($p, CURLOPT_RETURNTRANSFER, TRUE);
        // $response = curl_exec($p);

        // $has_error = false;
        // if (curl_errno($p)) {
        //     $has_error = true;
        //     $error = curl_error($p);
        // }        
        // curl_close($p);

        // $to_return = null;
        // if (!$has_error) {
        //     $to_return = json_decode($response);
        // }

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

}
