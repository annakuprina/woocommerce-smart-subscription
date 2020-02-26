<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WSS_Assets')) {

    /**
     * WSS_Assets Class.
     */
    class WSS_Assets {

        /**
         * Hook in tabs.
         */
        public function __construct() {
            add_action('wp_enqueue_scripts', array($this, 'load_styles_scripts'));
        }

        /**
         * Enqueue scripts.
         */
        public function load_styles_scripts() {
            wp_enqueue_style( 'wss_front_styles', WSS()->plugin_url() . '/assets/css/front.css' );
            wp_enqueue_script('wss_front_js', WSS()->plugin_url() . '/assets/js/front.js', array('jquery'));
            wp_localize_script('wss_front_js', 'WSSFrontParams', array(
                'ajax_url' => admin_url('admin-ajax.php'),
            ));
            wp_enqueue_style( 'jquery-ui-styles', WSS()->plugin_url() . '/assets/css/jquery-ui.min.css' );
            wp_enqueue_script( 'jquery-ui', WSS()->plugin_url() . '/assets/js/jquery-ui.min.js' );
        }

    }

}

new WSS_Assets();
