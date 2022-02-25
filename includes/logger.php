<?php

class WcEenvoudigFactureren_Logger {

    private $options;

    public function __construct($options) {
        $this->options = $options;
    }

    public function error($message, $order_id) {
        $this->options->update('last_error', __('Order', 'wc-eenvoudigfactureren' ) . ' #' . $order_id . ': '. $message . ' ( '.date('c').' )');

        wc_get_logger()->error($message . ' (Order #'.$order_id.')', array('source' => 'EenvoudigFactureren'));
    }
}
