<?php

class WcEenvoudigFactureren_Column {

    private $generation;
    private $options;

    public function __construct($options, $generation) {
        $this->options = $options;
        $this->generation = $generation;
    }

    public function register_actions() {
        add_filter( 'manage_edit-shop_order_columns', array($this, 'new_order_column') );
        add_action( 'manage_shop_order_posts_custom_column', array($this, 'new_order_column_content'), 2 );
        add_action( 'wp_ajax_new_order_create_document', array($this, 'new_order_create_document'));
    }

    public function new_order_column( $columns ) {
        $columns['wcef_document'] = __( 'Document', 'eenvoudigfactureren-for-woocommerce' );
        return $columns;
    }

    public function new_order_create_document() {
        $order_id = sanitize_key( $_POST['order_id'] );

        if ($order_id) {
            $this->generation->generate($order_id);
        }
    }

    public function new_order_column_content($column_name ) {
        global $post;
        if ( $column_name === 'wcef_document' ) {
            $document_url = get_post_meta( $post->ID, WC_EENVFACT_OPTION_PREFIX.'document_url', true );
            $document_name = get_post_meta( $post->ID, WC_EENVFACT_OPTION_PREFIX.'document_name', true );
            if($document_url && $document_name) {
                echo '<a href="'.esc_url($document_url).'" class="button button-primary" target="_blank" >'.esc_html($document_name).'</a>';
            } else {
?>
<script  type='text/javascript'>
<!--
jQuery(document).on('click', '#create-document-<?php echo esc_attr($post->ID); ?>', function(){
    jQuery.ajax({
        type: "post",url: "admin-ajax.php",data: { action: 'new_order_create_document', order_id: <?php echo esc_attr($post->ID); ?>, _ajax_nonce: '<?php echo wp_create_nonce(); ?>' },
        success: function(){
            window.location.reload(true);
        }
    });
})
-->
</script>

<a id="create-document-<?php echo esc_attr($post->ID); ?>" href="#create-document" class="button button-primary"><?php echo($this->options->get('document_type') == 'order' ? __( 'Create order', 'eenvoudigfactureren-for-woocommerce' ) : ($this->options->get('document_type') == 'receipt' ? __( 'Create receipt', 'eenvoudigfactureren-for-woocommerce' ) : __( 'Create invoice', 'eenvoudigfactureren-for-woocommerce' )) ); ?></a>
<?php
            }
        }
    }
}
