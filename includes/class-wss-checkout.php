<?php

/**
 * WSS_Checkout
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WSS_Checkout.
 */
class WSS_Checkout {

    /**
     * Initialize the admin actions.
     */
    public function __construct() {
        add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'wss_subscription_fields' ) );
        add_action( 'woocommerce_checkout_process', array( $this, 'wss_checkout_subscription_process' ) );
        add_action( 'woocommerce_payment_complete', array( $this, 'wss_create_subscription' ), 10 );
        add_action( 'woocommerce_thankyou', array( $this, 'wss_create_subscription' ), 10 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'wss_save_subscription_fields' ) );
    }

    /**
    * Process the checkout
    */
    public function wss_checkout_subscription_process() {  
        // Check if our fields are set and if they are not add an error
        if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            if( !isset( $_POST['wss_billing_period'] ) || empty( $_POST['wss_billing_period'] ) ) {
                wc_add_notice( __( 'Please select subscription period' ), 'error' );
            }
            if( $_POST['wss_billing_period'] == 'week' ) {
                if( !isset( $_POST['wss_available_days_for_delivery'] ) || empty( $_POST['wss_available_days_for_delivery'] ) ) {
                    wc_add_notice( __( 'Please select at least one day for delivery' ), 'error' );
                }
            }
            if( !isset( $_POST['wss_start_date'] ) || empty( $_POST['wss_start_date'] ) ) {
                wc_add_notice( __( 'Please select subscription start date' ), 'error' );
            }
        }
    }

    public function wss_subscription_fields( $checkout ) {
     
        echo '<div id="wss_subscription_field"><h1>' . __( 'Subscribe?', WSS_TEXT_DOMAIN ) . '</h1>';
        echo '<p class="subscription-description">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text</p>';
        // Period field
        echo '<label class="checkout-label choose-period">Choose your period</label>';
        echo '<div id="delivery_periods">';
            // Daily
            echo '<div id="available_delivery_period_daily" class="available_delivery_period">';
                echo '<p class="form-row form-row input-radio make-inline">';
                    echo '<input id="wss_billing_period_daily" class="input-radio " name="wss_billing_period" value="day" type="radio">';
                    echo '<label for="wss_billing_period_daily" class="radio">';
                        echo 'Daily';
                    echo '</label>';
                echo '</p>';
            echo '</div>';
            // Weekly
            echo '<div id="available_delivery_period_weekly" class="available_delivery_period">';
                echo '<p class="form-row form-row input-radio make-inline">';
                    echo '<input id="wss_billing_period_weekly" class="input-radio " name="wss_billing_period" value="week" type="radio">';
                    echo '<label for="wss_billing_period_weekly" class="radio">';
                        echo 'Weekly';
                    echo '</label>';
                echo '</p>';
            echo '</div>';
            // One off
            echo '<div id="available_delivery_period_one_off" class="available_delivery_period">';
                echo '<p class="form-row form-row input-radio make-inline">';
                    echo '<input id="wss_billing_period_one_off" class="input-radio " name="wss_billing_period" value="one_off" type="radio">';
                    echo '<label for="wss_billing_period_one_off" class="radio">';
                        echo 'One Off(just fill in your details and pay!)';
                    echo '</label>';
                echo '</p>';
            echo '</div>';
        echo '</div>';
        // Days of week available for delivery
        echo '<div class="available_days_delivery_wrap">';
            echo '<div id="available_days_delivery">';
            	echo '<p class="form-row form-row input-checkbox make-inline">';
                    echo '<input id="available_days_delivery_monday" class="input-checkbox " name="wss_available_days_for_delivery[]" value="1" type="checkbox">';
    				echo '<label for="available_days_delivery_monday" class="checkbox ">';
    					echo 'Monday';
    				echo '</label>';
    			echo '</p>';
    			echo '<p class="form-row form-row input-checkbox make-inline">';
                    echo '<input id="available_days_delivery_tuesday" class="input-checkbox " name="wss_available_days_for_delivery[]" value="2" type="checkbox">';
    				echo '<label for="available_days_delivery_tuesday" class="checkbox ">';
    					echo 'Tuesday';
    				echo '</label>';
    			echo '</p>';
    			echo '<p class="form-row form-row input-checkbox make-inline">';
    				echo '<input id="available_days_delivery_wednesday" class="input-checkbox " name="wss_available_days_for_delivery[]" value="3" type="checkbox">';
                    echo '<label for="available_days_delivery_wednesday" class="checkbox ">';
    					echo 'Wednesday';
    				echo '</label>';
    			echo '</p>';
    			echo '<p class="form-row form-row input-checkbox make-inline">';
                    echo '<input id="available_days_delivery_thursday" class="input-checkbox " name="wss_available_days_for_delivery[]" value="4" type="checkbox">';
    				echo '<label for="available_days_delivery_thursday" class="checkbox ">';
    					echo 'Thursday';
    				echo '</label>';
    			echo '</p>';
    			echo '<p class="form-row form-row input-checkbox make-inline">';
                    echo '<input id="available_days_delivery_friday" class="input-checkbox " name="wss_available_days_for_delivery[]" value="5" type="checkbox">';
    				echo '<label for="available_days_delivery_friday" class="checkbox ">';
    					echo 'Friday';
    				echo '</label>';
    			echo '</p>';
            echo '</div>';
        echo '</div>';
        // Start date
        echo '<p id="wss_start_date_field" class="form-row form-row wss-start-date wss-form-field validate-required">';
            echo '<label class="checkout-label pick-date-label" for="wss_start_date">';
                echo 'Choose Start Date';
            echo '</label>';
            echo '<input id="wss_start_date" class="input-text" name="wss_start_date" placeholder="YYYY-MM-DD" value="' . date( "Y-m-d" ) . '" type="text" required="required">';
        echo '</p>';
        echo '</div>';
    }

    /**
    * Update order meta with subscription details data
    */
    public function wss_save_subscription_fields( $order_id ) {
        $subscription_billing_interval = 'every';
        $subscription_billing_period = sanitize_text_field( $_POST['wss_billing_period'] );
        $subscription_start_date = sanitize_text_field( $_POST['wss_start_date'] );

        $subscription_selected_delivery_days = ( $subscription_billing_period == 'week' ) ? $_POST['wss_available_days_for_delivery'] : array();

        $all_subscription_fields = array(
            '_wss_billing_interval' => $subscription_billing_interval,
            '_wss_billing_period'   => $subscription_billing_period,
            '_wss_start_date'       => $subscription_start_date,
            '_wss_delivery_days'	=> $subscription_selected_delivery_days
        );

        foreach( $all_subscription_fields as $post_meta_name => $post_meta_value ) {
            update_post_meta( $order_id, $post_meta_name, $post_meta_value );
        }
    }

    /**
    * Creates subscription based on order
    * 
    * @param int $order_id - order id that is taken as base
    *
    * @param bool $subscribing - is subscription option enabled
    */
    public function wss_create_subscription( $order_id ) {
        $order_details = new WC_Order( $order_id );
        $order_meta = get_post_meta( $order_id, '', true );

        $order_creation_date = date( 'Y-m-d H:i', strtotime( $order_details->post->post_date ) );
        $subscription_start_date = date( 'Y-m-d H:i', strtotime( $order_meta['_wss_start_date'][0] ) );

        $order_owner_id = get_post_meta( $order_id, '_customer_user', true );

        $subscription_period = $order_meta['_wss_billing_period'][0];

        $is_user_wants_to_subscribe = ( $subscription_period == 'one_off' ) ? false : true;

        $order_type = 'One time delivery';

        if( $is_user_wants_to_subscribe == true ) {

            $order_type = 'Subscription';

            $order_owner_details = get_user_by( 'id', $order_owner_id );
            $all_ordered_goods = $order_details->get_items();

            $is_subscription_start_date_and_order_creation_date_equal = wss_are_dates_equal( $order_creation_date, $subscription_start_date );

            $is_subscription_set_as_futured = ( $is_subscription_start_date_and_order_creation_date_equal ) ? false : true;

            $subscription_trial_period_end_date = '';
            if( $is_subscription_set_as_futured == true ) {
                $gmt_offset = (int)get_option( 'gmt_offset' );
                $gmt_hours = ( !empty( $gmt_offset ) && $gmt_offset > 0 ) ? current_time( 'H' ) - $gmt_offset : current_time( 'H' ) + $gmt_offset;
                $trial_period_duration = wss_get_subscription_trial_period_days( $order_creation_date, $subscription_start_date );
                $subscription_trial_period_end_date = date( 'Y-m-d', strtotime( $order_creation_date . ' +' . $trial_period_duration . ' days' ) );
                $subscription_trial_period_end_date = date( 'Y-m-d H:i', strtotime( $subscription_trial_period_end_date . ' +' . $gmt_hours . ' hours' ) );
                $subscription_trial_period_end_date = date( 'Y-m-d H:i', strtotime( $subscription_trial_period_end_date . ' +' . current_time( 'i' ) . ' minutes' ) );
                $subscription_next_scheduled_payment_date = $subscription_trial_period_end_date;
            } else {
                $subscription_next_scheduled_payment_date = date( 'Y-m-d H:i', strtotime( $order_creation_date . '+1 day' ) );
            }

            if( $subscription_period == 'week' ) {
                $subscription_weekly_delivery_days_serialized = $order_meta['_wss_delivery_days'][0];
                $subscription_selected_delivery_days = unserialize( $subscription_weekly_delivery_days_serialized );
                $subscription_created_day_of_week = date( 'w', strtotime( $order_creation_date ) );

                foreach( $subscription_selected_delivery_days as $key => $subscription_weekly_delivery_at_day ) {

                    switch( $subscription_weekly_delivery_at_day ) {
                        case '1':
                            $subscription_weekly_delivery_at_day_name = 'Monday';
                        break;
                        case '2':
                            $subscription_weekly_delivery_at_day_name = 'Tuesday';
                        break;
                        case '3':
                            $subscription_weekly_delivery_at_day_name = 'Wednesday';
                        break;
                        case '4':
                            $subscription_weekly_delivery_at_day_name = 'Thursday';
                        break;
                        case '5':
                            $subscription_weekly_delivery_at_day_name = 'Friday';
                        break;
                    }

                    $subscription_weekly_delivery_at_day_date = date( 'Y-m-d H:i', strtotime( 'this ' . $subscription_weekly_delivery_at_day_name . ' ' . date( 'Y-m-d H:i', strtotime( $order_creation_date ) ) ) );

                    if( strtotime( $subscription_weekly_delivery_at_day_date ) < strtotime( $subscription_trial_period_end_date ) ) {
                        $subscription_next_scheduled_payment_date = date( 'Y-m-d H:i', strtotime( 'this ' . $subscription_weekly_delivery_at_day_name . ' ' . date( 'Y-m-d H:i', strtotime( $subscription_trial_period_end_date ) ) ) );
                    } elseif( strtotime( date( 'Y-m-d', strtotime( $subscription_weekly_delivery_at_day_date ) ) ) == strtotime( date( 'Y-m-d', strtotime( $order_creation_date ) ) ) ) {
                        $subscription_next_scheduled_payment_date = date( 'Y-m-d H:i', strtotime( $subscription_weekly_delivery_at_day_date . '+7 day' ) );
                    } else {
                        $subscription_next_scheduled_payment_date = $subscription_weekly_delivery_at_day_date;
                    }

                    $new_subscription_id = wss_create_subscription_based_on_order( $order_id, $subscription_trial_period_end_date, $subscription_next_scheduled_payment_date );
                }
            } else {
                $new_subscription_id = wss_create_subscription_based_on_order( $order_id, $subscription_trial_period_end_date, $subscription_next_scheduled_payment_date);
            }
        }
    }
}

new WSS_Checkout();