<?php

class WcEenvoudigFactureren_Options {

    public function get($name) {
        return get_option(WC_EENVFACT_OPTION_PREFIX . $name);
    }

    public function update($name, $value) {
        update_option(WC_EENVFACT_OPTION_PREFIX . $name, $value);
    }
}
