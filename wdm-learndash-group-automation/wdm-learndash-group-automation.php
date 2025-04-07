<?php

/*
 * Plugin Name:       WDM Lionheart Customization
 * Description:       Automates the group course restriction on custom course pages.
 * Version:           2.2.0
 * Author:            WisdmLabs
 * Author URI:        https://wisdmlabs.com/
 * Text Domain:       wdm-learndash-group-automation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WDM_LEARNDASH_GROUP_AUTOMATION_DIR' ) ) {
	define( 'WDM_LEARNDASH_GROUP_AUTOMATION_DIR', plugin_dir_path( __FILE__ ) );
}

define( 'WDM_LEARNDASH_GROUP_AUTOMATION_PLUGIN_URL', plugins_url( '', __FILE__ ) );


require_once WDM_LEARNDASH_GROUP_AUTOMATION_DIR . 'admin/main.php';
require_once WDM_LEARNDASH_GROUP_AUTOMATION_DIR . 'admin/class-wdm-lionheart-admin-settings.php';
require_once WDM_LEARNDASH_GROUP_AUTOMATION_DIR . 'public/class-wdm-wc-checkout.php';
require_once WDM_LEARNDASH_GROUP_AUTOMATION_DIR . 'public/class-wdm-learndash-design-upgrade.php';


add_action( 'wp_enqueue_scripts', 'wdm_lionheart_enqueue_scripts' );
function wdm_lionheart_enqueue_scripts() {
	wp_enqueue_script( 'lionheart-script', WDM_LEARNDASH_GROUP_AUTOMATION_PLUGIN_URL . '/assets/js/wdm-lionheart-script.js', array( 'jquery' ), '1.0', true );
	wp_enqueue_script( 'lionheart-sweetaleart', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array( 'jquery' ), '1.0', true );
	wp_localize_script(
		'lionheart-script',
		'wdm_ajax',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'complete_course_nonce' ),
		)
	);
	wp_enqueue_style( 'lionheart-styles', WDM_LEARNDASH_GROUP_AUTOMATION_PLUGIN_URL . '/assets/css/wdm-lionheart-styles.css', array(), '1.0.0', 'all' );
	wp_enqueue_style( 'lionheart-course-styles', WDM_LEARNDASH_GROUP_AUTOMATION_PLUGIN_URL . '/assets/css/wdm-lionheart-course-styles.css', array(), time(), 'all' );
}

add_shortcode( 'wdm_ld_course_complete_btn', 'wdm_ld_course_complete_btn' );
function wdm_ld_course_complete_btn( $atts ) {
	 // Extract shortcode attributes.
	 $atts = shortcode_atts(
        array(
            'course_id' => 0, // Default value
        ), 
        $atts
    );

    // Get the current user ID.

    $user_id = get_current_user_id();
	$course_id = intval( $atts['course_id'] );
    // If the user is not logged in, show a message.
    if ( ! $user_id || ! $course_id || ! sfwd_lms_has_access( $course_id, $user_id ) ) {
        return '';
    } else {
		return '<button class="complete-course-button" data-course-id="' . $course_id . '" data-user-id="' . $user_id . '">Get Certificate</button>';
	}
}

add_action( 'add_meta_boxes', 'wdm_custom_order_meta_box' );
function wdm_custom_order_meta_box() {
	add_meta_box(
		'custom-order-meta-box',
		'Order fields',
		'wdm_display_custom_order_meta_box',
		'shop_order',
		'normal',
		'low'
	);
}

// Display custom meta box content
function wdm_display_custom_order_meta_box( $post ) {
	$order = wc_get_order( $post->ID );

		// Get specific meta keys
	$meta_keys  = array( '_shipping_title', '_shipping_department' );
	$meta_label = array(
		'_shipping_title'      => 'Title',
		'_shipping_department' => 'Department',
	);

	// Output meta data in a form
	echo '<table class="form-table">';
	foreach ( $meta_keys as $meta_key ) {
		$meta_value = $order->get_meta( $meta_key );
		echo '<tr>';
		echo '<th><label for="custom-meta-' . esc_attr( $meta_key ) . '">' . esc_html( $meta_label[ $meta_key ] ) . '</label></th>';
		echo '<td><input type="text" id="custom-meta-' . esc_attr( $meta_key ) . '" name="custom_meta[' . esc_attr( $meta_key ) . ']" value="' . esc_attr( $meta_value ) . '" /></td>';
		echo '</tr>';
	}
	echo '</table>';
}

// Save custom meta data when order is updated
add_action( 'save_post_shop_order', 'wdm_save_custom_order_meta_data', 10, 3 );
function wdm_save_custom_order_meta_data( $post_id, $post, $update ) {
	if ( 'shop_order' !== $post->post_type ) {
		return;
	}

	// Check if custom meta data is present in the request
	if ( isset( $_POST['custom_meta'] ) && is_array( $_POST['custom_meta'] ) ) {
		$order = wc_get_order( $post_id );

		foreach ( $_POST['custom_meta'] as $meta_key => $meta_value ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( $meta_value ) );
		}
	}
}

// Add donor user role with capabilities of subscriber and customer
function wdm_add_donor_role() {
	$subscriber_caps = get_role( 'subscriber' )->capabilities;
	$customer_caps   = get_role( 'customer' )->capabilities;

	$donor_caps = array_merge( $subscriber_caps, $customer_caps );

	add_role( 'donor', __( 'Donor' ), $donor_caps );
}
add_action( 'init', 'wdm_add_donor_role' );

// Assign donor role to users who purchase products in the donations category
function wdm_assign_donor_role_on_purchase( $order_id ) {
	$order = wc_get_order( $order_id );

	// Get the term ID of the "Donations" category
	$donations_category_id = get_term_by( 'name', 'Donations', 'product_cat' )->term_id;

	// Check if the order contains any product in the "Donations" category
	$product_found = false;
	foreach ( $order->get_items() as $item ) {
		$product_id         = $item->get_product_id();
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		if ( in_array( $donations_category_id, $product_categories ) ) {
			$product_found = true;
			break;
		}
	}

	if ( $product_found ) {
		$user_id = $order->get_user_id();
		$user    = new WP_User( $user_id );

		// Check if the user doesn't already have the 'donor' role
		if ( ! $user->has_cap( 'donor' ) ) {
			$user->add_role( 'donor' );
		}
	}
}
add_action( 'woocommerce_order_status_completed', 'wdm_assign_donor_role_on_purchase' );

add_filter( 'fl_builder_render_module_html_content', 'wdm_show_hide_nav_menu', 10, 4 );

/**
 * Hides the "EQ2 Community Board" button in the navigation menu
 * for users who are not administrators and are not part of group ID 6.
 *
 * @param string $content The HTML content of the module.
 * @param string $type The type of module.
 * @param object $settings The settings of the module.
 * @param object $module The module object.
 *
 * @return string The modified HTML content of the module.
 */
function wdm_show_hide_nav_menu( $content, $type, $settings, $module ) {
	if ( 'button' === $type && 'EQ2 Community Board' === $settings->text ) {
		$user_id  = get_current_user_id();
		$group_id = '';
		// Check if new method exists.
		if ( method_exists( WPF()->member, 'get_groupid' ) ) {
			$group_id = absint( WPF()->member->get_groupid( $user_id ) );
		}

		if ( current_user_can( 'manage_options' ) || ( ! empty( $group_id ) && 7 === $group_id ) ) {
			return $content;
		}
		return '';
	}
	return $content;
}

add_action( 'wp_ajax_complete_learndash_course', 'wdm_complete_learndash_course' );
function wdm_complete_learndash_course() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ): '';
	if ( ! wp_verify_nonce( $nonce, 'complete_course_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce.' );
	}
	$course_id = isset( $_POST['course_id'] ) ? sanitize_text_field( wp_unslash( $_POST['course_id'] ) ) : '';
	$user_id   = isset( $_POST['user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['user_id'] ) ) : '';
	$course_id = absint( $course_id );
	$user_id   = absint( $user_id );
	if ( ! learndash_course_completed( $user_id, $course_id ) ) {
		learndash_user_course_complete_all_steps( $user_id, $course_id );
		wp_send_json_success( array( 'heading' => 'Success', 'message' => 'Certificate is added in your account.' ), 200 );
	} else {
		wp_send_json_success( array( 'heading' => 'Success', 'message' => 'Certificate is already generated.' ), 200 );
	}
}
