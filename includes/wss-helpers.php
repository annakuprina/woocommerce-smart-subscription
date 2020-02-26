<?php
if (!defined('ABSPATH')) {
    exit;
}
/* 
 * Helpers
 */

function vardump( $str ) {
    echo "<pre>";
    var_dump($str);
    echo "</pre>";
}

/**
* Getting all subscriptions for specific user
*
* @param int $user_id - user ID to take all subscription for, string $status - subscription status
*
* @return array - all subscriptions, bool false - if something went wrong or user id is not specified
*/
function wss_get_all_user_subscriptions( $user_id, $status = 'any' ) {
    if( !isset( $user_id ) || empty( $user_id ) ) 
        return false;

    $subscriptions_parameters = array(
        'author'    	 => $user_id,
        'orderby'  	     => 'post_date',
        'order'     	 => 'DESC',
        'post_type' 	 => 'shop_subscription',
        'post_status'	 => $status,
        'posts_per_page' => -1
    );

    $all_subscriptions = get_posts( $subscriptions_parameters );

    return $all_subscriptions;
}

/**
* Getting all products in subscription
*
* @param int $subscription_id - ID of the subscription
*
* @return array - all products in subscription, bool false - if something went wrong or subscription id is not specified
*/
function wss_get_all_subscription_products( $subscription_id ) {
	if( empty( $subscription_id ) )
		return false;

	$subscription = new WC_Order( $subscription_id );
	$all_products = $subscription->get_items();

	return $all_products;
}
/**
* Checking if arrays are equal
*
* @param array $array1, array $array2
*
* @return bool true - if arrays are equal, false - if not
*/
function wss_are_arrays_equal( $array1, $array2 ) {
	array_multisort( $array1 );
	array_multisort( $array2 );
	return ( serialize( $array1 ) === serialize( $array2 ) );
}

/**
* Compares 2 dates and checks if first one is greated than another one
*
* @param string $date_start ('Y-m-d') - start date
*
* @param string $date_end ('Y-m-d') - end date
*
* @return bool true - if dates are equal, false - if not or something went wrong
*/
function wss_are_dates_equal( $start_date, $end_date ) {
    if( empty( $start_date ) || empty( $end_date ) ) {
        return false;
    }

    $formatted_start_date = date( 'Y-m-d', strtotime( $start_date ) );
    $formatted_end_date = date( 'Y-m-d', strtotime( $end_date ) );

    return ( strtotime( $formatted_start_date ) == strtotime( $formatted_end_date ) );
}

/**
* Getting trial period duration in days
*
* @param string $order_creation_date ('Y-m-d')
*
* @param string $subscription_start_date ('Y-m-d')
*
* @return integer $trial_days - number of days, or bool false if something went wrong
*/
function wss_get_subscription_trial_period_days( $order_creation_date, $subscription_start_date ) {
    if( empty( $order_creation_date ) || empty( $subscription_start_date ) ) {
        return false;
    }
    if( strtotime( $subscription_start_date ) < strtotime( $order_creation_date ) )
        return false;

    $formatted_order_creation_date = date( 'Y-m-d', strtotime( $order_creation_date ) );
    $formatted_subscription_start_date = date( 'Y-m-d', strtotime( $subscription_start_date ) );

    $order_creation_date_object = new DateTime( $formatted_order_creation_date );
    $subscription_start_date_object = new DateTime( $formatted_subscription_start_date );
    $difference = $subscription_start_date_object->diff( $order_creation_date_object );

    $trial_days = $difference->d;

    return $trial_days;
}
/**
* Creates subscription based on order id
*
* @param int $order_id - order id the subscription is based on
*
* @param string $subscription_trial_period_end_date - date when the trial period ends
*
* @param string $subscription_next_scheduled_payment_date - date when new payment is scheduled
*
* @return string subscription_id if everything is ok, false -  if something went wrong
*/
function wss_create_subscription_based_on_order( $order_id, $subscription_trial_period_end_date = '', $subscription_next_scheduled_payment_date = '' ) {
    if( empty( $order_id ) ) {
        return false;
    }

    $order_details = new WC_Order( $order_id );
    $order_meta = get_post_meta( $order_id, '', true );

    $order_owner_id = get_post_meta( $order_id, '_customer_user', true );
    $all_ordered_items = $order_details->get_items();

    $subscription_status = ( $order_details->post->post_status == 'wc-processing' ) ? 'wc-active' : 'wc-on-hold';
    $subscription_id = wss_create_subscription_with_specified_status( $subscription_status, $order_owner_id );    

    $subscription_details = array(
        '_billing_interval'                 => $order_meta['_wss_billing_interval'][0],
        '_billing_period'                   => $order_meta['_wss_billing_period'][0],
        '_schedule_end'                     => '',
        '_schedule_trial_end'               => $subscription_trial_period_end_date,
        '_schedule_next_payment'            => $subscription_next_scheduled_payment_date,
        '_customer_user'                    => $order_owner_id,
        '_billing_first_name'               => $order_meta['_billing_first_name'][0],
        '_billing_last_name'                => $order_meta['_billing_last_name'][0],
        '_billing_company'                  => $order_meta['_billing_company'][0],
        '_billing_address_1'                => $order_meta['_billing_address_1'][0],
        '_billing_address_2'                => $order_meta['_billing_address_2'][0],
        '_billing_city'                     => $order_meta['_billing_city'][0],
        '_billing_postcode'                 => $order_meta['_billing_postcode'][0],
        '_billing_country'                  => $order_meta['_billing_country'][0],
        '_billing_state'                    => $order_meta['_billing_state'][0],
        '_billing_email'                    => $order_meta['_billing_email'][0],
        '_billing_phone'                    => $order_meta['_billing_phone'][0],
        '_shipping_first_name'              => $order_meta['_billing_first_name'][0],
        '_shipping_last_name'               => $order_meta['_billing_last_name'][0],
        '_shipping_company'                 => $order_meta['_billing_company'][0],
        '_shipping_country'                 => $order_meta['_billing_country'][0],
        '_shipping_address_1'               => $order_meta['_billing_address_1'][0],
        '_shipping_address_2'               => $order_meta['_billing_address_2'][0],
        '_shipping_city'                    => $order_meta['_billing_city'][0],
        '_shipping_state'                   => $order_meta['_billing_state'][0],
        '_shipping_postcode'                => $order_meta['_billing_postcode'][0],
        '_requires_manual_renewal'          => false,
        '_order_total'                      => $order_details->get_total()
    );

    foreach( $subscription_details as $meta_name => $meta_value ) {
        update_post_meta( $subscription_id, $meta_name, $meta_value );
    }

    $is_subscription_items_successfully_added = wss_create_and_update_subscription_items( $subscription_id, $all_ordered_items );

    return $subscription_id;
}
/**
* Creates subscription
*
* @param string $subscription_status (default: wc-active), $subscription_owner_id
*
* @return string $subscription_id, bool false - if something went wrong
*/
function wss_create_subscription_with_specified_status( $subscription_status = 'wc-active', $subscription_owner_id ) {
    if( empty( $subscription_owner_id ) ) {
        return false;
    }
    $subscription_id = wp_insert_post( array(
        'post_type'     => 'shop_subscription',
        'post_title'    => 'Subscription',
        'post_content'  => '',
        'post_author'   => $subscription_owner_id,
        'post_status'   => $subscription_status
    ));
    return $subscription_id;
}
/**
* Creates subscription items and update all items details
*
* @param string $subscription_id - subscription ID to add and update items for
*
* @param array $order_take_as_base_items - all items from the order the subscription is built on
*
* @return false is something went wrong, true - if everything is ok
*/
function wss_create_and_update_subscription_items( $subscription_id, $order_take_as_base_items = array() ) {
    if( empty( $subscription_id ) ) {
        return false;
    }
    if( !empty( $order_take_as_base_items ) ) {
        foreach( $order_take_as_base_items as $order_item_id => $order_item_details ) {
            $subscription_item_id = woocommerce_add_order_item( $subscription_id, array(
                'order_item_name'       => $order_item_details['name'],
                'order_item_type'       => 'line_item',
            ));
            if( $subscription_item_id ) {
                woocommerce_add_order_item_meta( $subscription_item_id, '_qty', $order_item_details['item_meta']['_qty'][0] );
                woocommerce_add_order_item_meta( $subscription_item_id, '_tax_class', $order_item_details['item_meta']['_tax_class'][0] );
                woocommerce_add_order_item_meta( $subscription_item_id, '_product_id', $order_item_details['item_meta']['_product_id'][0] );
                woocommerce_add_order_item_meta( $subscription_item_id, '_variation_id', $order_item_details['item_meta']['_variation_id'][0] );
                woocommerce_add_order_item_meta( $subscription_item_id, '_line_subtotal', $order_item_details['item_meta']['_line_subtotal'][0] );
                woocommerce_add_order_item_meta( $subscription_item_id, '_line_subtotal_tax', $order_item_details['item_meta']['_line_subtotal_tax'][0] );
                woocommerce_add_order_item_meta( $subscription_item_id, '_line_total', $order_item_details['item_meta']['_line_total'][0] );
                woocommerce_add_order_item_meta( $subscription_item_id, '_line_tax', $order_item_details['item_meta']['_line_tax'][0] );
                woocommerce_add_order_item_meta( $subscription_item_id, '_line_tax_data', $order_item_details['item_meta']['_line_tax_data'][0] );
            }
        }
    }

    return true;
}