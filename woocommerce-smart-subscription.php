<?php
/**
 * Plugin Name: WooCommerce Smart Subscription
 * Plugin URI: https://github.com/BigTonni/woocommerce-smart-subscription
 * Description: To create a subscription during checkout.
 * Author: Anton Shulga
 * Author URI: https://github.com/BigTonni
 * Version: 1.0
 * Text Domain: woocommerce-smart-subscription
 * Domain Path: /i18n/languages
 */
if (!defined('ABSPATH')){
    exit; // Exit if accessed directly
}

if (!function_exists('woothemes_queue_update')) {
    require_once( 'woo-includes/woo-functions.php' );
}

// notify user if Localmetrix plugin is inactive
add_action('admin_notices', 'wss_inactive_notice');
function wss_inactive_notice(){
    // WC active check
    if (!is_woocommerce_active() || get_option('woocommerce_subscriptions_is_active', false) == false) {
        if (current_user_can('activate_plugins')) {
            ?>
            <div id="message" class="error">
                <p><?php
                    printf(esc_html__('%1$sWooCommerce Smart Subscription is inactive.%2$s The %3$sWooCommerce%4$s and %5$sWooCommerce Subscriptions%6$s plugins must be active for WooCommerce Smart Subscription to work. Please install & activate WooCommerce &raquo;', 'wss'), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="http://www.woothemes.com/products/woocommerce-subscriptions/">', '</a>');
                    ?>
                </p>
            </div>
            <style>#message.updated.notice.is-dismissible{display: none;}</style>
            <?php
        }
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

register_uninstall_hook(__FILE__, array('WC_Smart_Subscription', 'uninstall'));

class WC_Smart_Subscription {

    /** plugin version number */
    public $version = '1.0';

    /**
     * The single instance of the class.
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     *
     * @since 1.0
     * @static
     * @return Main instance.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /* Set the constants needed by the plugin. */

    private function define_constants() {
        $this->define('WSS_PLUGIN_FILE', __FILE__);
        $this->define('WSS_PLUGIN_BASENAME', plugin_basename(__FILE__));
        $this->define('WSS_VERSION', $this->version);
        $this->define('WSS_TEXT_DOMAIN', 'woocommerce-smart-subscription');
    }

    /**
     * Define constant if not already set.
     *
     * @param  string $name
     * @param  string|bool $value
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes() {
        include_once( 'includes/wss-helpers.php' );
        include_once( 'includes/class-wss-assets.php' );
        include_once( 'includes/class-wss-checkout.php' );
//        include_once( 'includes/wss-ajax.php' );
    }

    /**
     * Hook into actions and filters.
     * @since  2.3
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        add_action('init', array($this, 'init'), 0);
        add_filter( 'plugin_row_meta', array($this, 'custom_plugin_row_meta'), 10, 2 );

        add_filter( 'manage_edit-shop_subscription_columns', array( $this, 'wss_shop_subscription_columns' ), 11 );
        add_action( 'manage_shop_subscription_posts_custom_column', array( $this, 'wss_render_shop_subscription_columns' ), 11, 2 );

        add_filter( 'manage_edit-shop_order_columns', array( $this, 'wss_add_custom_columns_to_order_list_in_admin' ), 11 );
        add_action( 'manage_shop_order_posts_custom_column' , array( $this, 'wss_custom_column_in_orders_list_admin_content' ), 10, 2 );

        add_action ( 'woocommerce_checkout_update_order_meta', array( $this, 'wss_add_order_type_meta' ), 10, 2 );
        
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Init WSS when WordPress Initialises.
     */
    public function init() {
        // Set up localisation.
        $this->load_plugin_textdomain();        
    }
    
    function custom_plugin_row_meta( $links, $file ) {
        if ( $file == WSS_PLUGIN_BASENAME ) {
                $last_el= array_pop($links);
		$new_links['develop'] = 'By <a href="" target="_blank">Serhii Moroka</a>';
                $new_links[] = $last_el;
		
		$links = array_merge( $links, $new_links );
        }
	
	return $links;
    }

    /**
     * Load the translation of the plugin.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('woocommerce-smart-subscription', false, plugin_basename(dirname(__FILE__)) . '/i18n/languages');
    }
  
    /**
     * Called when the plugin is deactivated.
     *
     * @since 1.0
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    public function plugin_url() {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Get the plugin path.
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Do things on plugin activation.
     */
    public function activate() {
        return true;        
    }

    /**
     * Do things on plugin uninstall.
     */
    public function uninstall() {
        if (!current_user_can('activate_plugins')){
            return;
        }
        check_admin_referer('bulk-plugins');

        if (__FILE__ != WP_UNINSTALL_PLUGIN){
            return;
        }
    }
    /**
     * Define custom columns for subscription
     *
     * Column names that have a corresponding `WC_Order` column use the `order_` prefix here
     * to take advantage of core WooCommerce assets, like JS/CSS.
     *
     * @param  array $existing_columns
     * @return array
     */
    public function wss_shop_subscription_columns( $existing_columns ) {

        $existing_columns['subscription_items_delivery_date'] = __( 'Delivery Date', 'wss' );

        return $existing_columns;
    }

    /**
     * Output custom columns for subscriptions
     * @param  string $column
     */
    public function wss_render_shop_subscription_columns( $column ) {
        if( $column == 'subscription_items_delivery_date' ) {
            global $post, $woocommerce;
            
            $subscription_id = $post->ID;
            $subscription_items_delivery_date = get_post_meta( $subscription_id, '_schedule_next_payment', true );

            $subscription_items_delivery_date_formatted = ( !empty( $subscription_items_delivery_date ) ) ? date( 'l jS \of F Y', strtotime( $subscription_items_delivery_date ) ) : '';
            echo $subscription_items_delivery_date_formatted;
        }
    }
    /**
     * Define custom columns for orders
     *
     * Column names that have a corresponding `WC_Order` column use the `order_` prefix here
     * to take advantage of core WooCommerce assets, like JS/CSS.
     *
     * @param  array $columns
     * @return array
     */
    public function wss_add_custom_columns_to_order_list_in_admin( $columns ) {
        $columns['wss_order_type_column'] = __( 'Order Type', 'wss' );
        $columns['wss_order_delivery_date_column'] = __( 'Order Delivery Date', 'wss' );
       return $columns;
    }
    /**
     * Output custom columns for woocommerce orders
     * @param string $column
     */
    public function wss_custom_column_in_orders_list_admin_content( $column ) {
        global $post, $woocommerce, $the_order;

        $order_id = $the_order->id;

        switch( $column ) {
            case 'wss_order_type_column':
                $order_type_column_content = get_post_meta( $order_id, '_order_type', true );
                echo $order_type_column_content;
            break;
            case 'wss_order_delivery_date_column':
                $order_delivery_date_column_content = get_post_meta( $order_id, '_order_delivery_date', true );
                echo ( !empty( $order_delivery_date_column_content ) ) ? date( 'l jS \of F Y', strtotime( $order_delivery_date_column_content ) ) : '';
            break;
        }
    }
    public function wss_add_order_type_meta( $order_id ) {

        $subscription_period = sanitize_text_field( $_POST['wss_billing_period'] );
        $is_user_wants_to_subscribe = ( $subscription_period == 'one_off' ) ? false : true;

        $order_delivery_start_date = '';
        $order_delivery_start_date = sanitize_text_field( date( 'Y-m-d', strtotime( $_POST['wss_start_date'] ) ) );
        $order_type = ( $is_user_wants_to_subscribe == true ) ? 'Subscription' : 'One time delivery';

        $order_type_updated = update_post_meta( $order_id, '_order_type', $order_type );
        $order_delivery_start_date_updated = update_post_meta( $order_id, '_order_delivery_date', $order_delivery_start_date );
    }
} // end WC_Smart_Subscription class

/**
 * Main instance of WSS.
 *
 * @since  1.0
 * @return WSS
 */
function WSS() {
	return WC_Smart_Subscription::instance();
}
$wss_plugin = WSS();
