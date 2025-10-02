<?php

class WcEenvoudigFactureren_Column {

    private $generation;
    private $options;

    public function __construct($options, $generation) {
        $this->options = $options;
        $this->generation = $generation;
    }

    public function register_actions() {
        // Klassieke orders-lijst (post type scherm)
        add_filter( 'manage_edit-shop_order_columns',               [$this, 'add_order_column'] );
        add_action( 'manage_shop_order_posts_custom_column',        [$this, 'render_order_column_classic'], 10, 2 );

        // Nieuwe WooCommerce Orders-lijst (HPOS / nieuwe table)
        add_filter( 'woocommerce_shop_order_list_table_columns',    [$this, 'add_order_column'] );
        add_action( 'woocommerce_shop_order_list_table_custom_column', [$this, 'render_order_column_hpos'], 10, 2 );

        // AJAX
        add_action( 'wp_ajax_wcef_new_order_create_document',       [$this, 'new_order_create_document'] );
    }    

    public function add_order_column( $columns ) {
        $columns['wcef_document'] = __( 'Document', 'eenvoudigfactureren-for-woocommerce' );
        return $columns;
    }

    public function render_order_column_classic( $column, $post_id ) {
        if ( $column !== 'wcef_document' ) return;
        $this->render_cell( (int) $post_id );
    }

    public function render_order_column_hpos( $column, $order ) {
        if ( $column !== 'wcef_document' ) return;
        // $order is een WC_Order
        $order_id = is_object( $order ) ? (int) $order->get_id() : (int) $order;
        $this->render_cell( $order_id );
    }

    private function render_cell( int $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            echo '<em>' . esc_html__( 'Order not found', 'eenvoudigfactureren-for-woocommerce' ) . '</em>';
            return;
        }
        
        $document_url   = (string) $order->get_meta( WC_EENVFACT_OPTION_PREFIX . 'document_url', true );
        $document_name  = (string) $order->get_meta( WC_EENVFACT_OPTION_PREFIX . 'document_name', true );

        $generating     = wc_string_to_bool( (string) $order->get_meta( WC_EENVFACT_OPTION_PREFIX . 'document_generating', true ) );
        $generated      = wc_string_to_bool( (string) $order->get_meta( WC_EENVFACT_OPTION_PREFIX . 'document_generated', true ) );

        if ( $document_url && $document_name ) {
            echo '<a href="' . esc_url( $document_url ) . '" class="button button-primary" target="_blank">'
               . esc_html( $document_name ) . '</a>';
            return;
        }

        if ( $generating && ! $generated ) {
            echo '<em>' . esc_html__( 'Generating…', 'eenvoudigfactureren-for-woocommerce' ) . '</em>';
            return;
        }        

        // Knop + AJAX
        $label = ($this->options->get('document_type') === 'order')
            ? __( 'Create order', 'eenvoudigfactureren-for-woocommerce' )
            : (($this->options->get('document_type') === 'receipt')
                ? __( 'Create receipt', 'eenvoudigfactureren-for-woocommerce' )
                : __( 'Create invoice', 'eenvoudigfactureren-for-woocommerce' ));

        $btn_id = 'create-document-' . $order_id;
        $nonce  = wp_create_nonce( 'wcef_create_doc' );

        echo '<a id="' . esc_attr( $btn_id ) . '" href="#" class="button button-primary">'
           . esc_html( $label ) . '</a>';

        // Gebruik ajaxurl (WP zet dit global in admin), geen hardcoded "admin-ajax.php"
        ?>
        <script>
        jQuery(function($){
          $(document).on('click', '#<?php echo esc_js( $btn_id ); ?>', function(e){
            e.preventDefault();
            $.post(ajaxurl, {
              action: 'wcef_new_order_create_document',
              order_id: <?php echo (int) $order_id; ?>,
              nonce: '<?php echo esc_js( $nonce ); ?>'
            }).done(function(resp){
              if (resp && resp.success) {
                location.reload(true);
              } else {
                alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Error');
                location.reload(true); // flag is sowieso teruggezet in finally
              }
            }).fail(function(xhr){
              var msg = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ? xhr.responseJSON.data.message : 'Error';
              alert(msg);
              location.reload(true);
            });
          });
        });

        </script>
        <?php
    }

    public function new_order_create_document() {
        check_ajax_referer( 'wcef_create_doc', 'nonce' );
        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( 'forbidden', 403 );
        }
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( 'missing id', 400 );
        }

        $result = $this->generation->generate( $order_id );

        if ( is_array($result) && ! empty($result['ok']) ) {
            wp_send_json_success( $result );
        } else {
            $msg = is_array($result) && isset($result['message']) ? $result['message'] : __('Unknown error','eenvoudigfactureren-for-woocommerce');
            wp_send_json_error( ['message' => $msg], 500 );
        }        
    }
}
