<?php

class WooEenvoudigFactureren_Column {

    public function register_actions() {
        add_filter( 'manage_edit-shop_order_columns', array($this, 'new_order_column') );
        add_action( 'manage_shop_order_posts_custom_column', array($this, 'new_order_column_content'), 2 );
    }

    public function new_order_column( $columns ) {
        $columns['wcef_document'] = __( 'Document', 'woo-eenvoudigfactureren' );
        return $columns;
    }

    public function new_order_column_content($column_name ) {
        global $post;
        if ( $column_name === 'wcef_document' ) {
            $document_url = get_post_meta( $post->ID, WC_EENVFACT_OPTION_PREFIX.'document_url', true );
            $document_name = get_post_meta( $post->ID, WC_EENVFACT_OPTION_PREFIX.'document_name', true );
            if($document_url && $document_name) {
                echo '<a href="'.$document_url.'" class="button button-primary" target="_blank" >'.$document_name.'</a>';
            }
        }
    }
}
