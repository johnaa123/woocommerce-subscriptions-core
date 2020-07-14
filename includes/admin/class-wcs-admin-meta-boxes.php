<?php
/**
 * WooCommerce Subscriptions Admin Meta Boxes
 *
 * Sets up the write panels used by the subscription custom order/post type
 *
 * @author   Prospress
 * @category Admin
 * @package  WooCommerce Subscriptions/Admin
 * @version  2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin_Meta_Boxes
 */
class WCS_Admin_Meta_Boxes {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 25 );

		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 35 );

		// We need to remove core WC save methods for meta boxes we don't use
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'remove_meta_box_save' ), -1, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 20 );

		// We need to hook to the 'shop_order' rather than 'shop_subscription' because we declared that the 'shop_susbcription' order type supports 'order-meta-boxes'
		add_action( 'woocommerce_process_shop_order_meta', 'WCS_Meta_Box_Schedule::save', 10, 2 );
		add_action( 'woocommerce_process_shop_order_meta', 'WCS_Meta_Box_Subscription_Data::save', 10, 2 );

		add_filter( 'woocommerce_order_actions', array( __CLASS__, 'add_subscription_actions' ), 10, 1 );

		add_action( 'woocommerce_order_action_wcs_process_renewal', array( __CLASS__, 'process_renewal_action_request' ), 10, 1 );
		add_action( 'woocommerce_order_action_wcs_create_pending_renewal', array( __CLASS__, 'create_pending_renewal_action_request' ), 10, 1 );
		add_action( 'woocommerce_order_action_wcs_create_pending_parent', array( __CLASS__, 'create_pending_parent_action_request' ), 10, 1 );

		if ( WC_Subscriptions::is_woocommerce_pre( '3.2' ) ) {
			add_filter( 'woocommerce_resend_order_emails_available', array( __CLASS__, 'remove_order_email_actions' ), 0, 1 );
		}

		add_action( 'woocommerce_order_action_wcs_retry_renewal_payment', array( __CLASS__, 'process_retry_renewal_payment_action_request' ), 10, 1 );

		// Disable stock managment while adding line items to a subscription via AJAX.
		add_action( 'option_woocommerce_manage_stock', array( __CLASS__, 'override_stock_management' ) );

		// Parent order line item price lock option.
		add_action( 'woocommerce_order_item_add_action_buttons', array( __CLASS__, 'output_price_lock_html' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save_increased_price_lock' ) );
		add_action( 'wp_ajax_wcs_order_price_lock' , array( __CLASS__, 'save_increased_price_lock' ) );

		// After calculating subscription/renewal order line item taxes, update base location tax item meta.
		add_action( 'woocommerce_ajax_add_order_item_meta', array( __CLASS__, 'store_item_base_location_tax' ), 10, 3 );
	}

	/**
	 * Add WC Meta boxes
	 */
	public function add_meta_boxes() {
		global $post_ID;

		add_meta_box( 'woocommerce-subscription-data', _x( 'Subscription Data', 'meta box title', 'woocommerce-subscriptions' ), 'WCS_Meta_Box_Subscription_Data::output', 'shop_subscription', 'normal', 'high' );

		add_meta_box( 'woocommerce-subscription-schedule', _x( 'Schedule', 'meta box title', 'woocommerce-subscriptions' ), 'WCS_Meta_Box_Schedule::output', 'shop_subscription', 'side', 'default' );

		remove_meta_box( 'woocommerce-order-data', 'shop_subscription', 'normal' );

		add_meta_box( 'subscription_renewal_orders', __( 'Related Orders', 'woocommerce-subscriptions' ), 'WCS_Meta_Box_Related_Orders::output', 'shop_subscription', 'normal', 'low' );

		// Only display the meta box if an order relates to a subscription
		if ( 'shop_order' === get_post_type( $post_ID ) && wcs_order_contains_subscription( $post_ID, 'any' ) ) {
			add_meta_box( 'subscription_renewal_orders', __( 'Related Orders', 'woocommerce-subscriptions' ), 'WCS_Meta_Box_Related_Orders::output', 'shop_order', 'normal', 'low' );
		}
	}

	/**
	 * Removes the core Order Data meta box as we add our own Subscription Data meta box
	 */
	public function remove_meta_boxes() {
		remove_meta_box( 'woocommerce-order-data', 'shop_subscription', 'normal' );
	}

	/**
	 * Don't save save some order related meta boxes
	 */
	public function remove_meta_box_save( $post_id, $post ) {

		if ( 'shop_subscription' == $post->post_type ) {
			remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40 );
		}
	}

	/**
	 * Print admin styles/scripts
	 */
	public function enqueue_styles_scripts() {
		global $post;

		// Get admin screen id
		$screen    = get_current_screen();
		$screen_id = isset( $screen->id ) ? $screen->id : '';

		if ( 'shop_subscription' == $screen_id ) {

			wp_register_script( 'jstz', plugin_dir_url( WC_Subscriptions::$plugin_file ) . 'assets/js/admin/jstz.min.js' );

			wp_register_script( 'momentjs', plugin_dir_url( WC_Subscriptions::$plugin_file ) . 'assets/js/admin/moment.min.js' );

			wp_enqueue_script( 'wcs-admin-meta-boxes-subscription', plugin_dir_url( WC_Subscriptions::$plugin_file ) . 'assets/js/admin/meta-boxes-subscription.js', array( 'wc-admin-meta-boxes', 'jstz', 'momentjs' ), WC_VERSION );

			wp_localize_script( 'wcs-admin-meta-boxes-subscription', 'wcs_admin_meta_boxes', apply_filters( 'woocommerce_subscriptions_admin_meta_boxes_script_parameters', array(
				'i18n_start_date_notice'         => __( 'Please enter a start date in the past.', 'woocommerce-subscriptions' ),
				'i18n_past_date_notice'          => WC_Subscriptions::is_duplicate_site() ? __( 'Please enter a date at least 2 minutes into the future.', 'woocommerce-subscriptions' ) : __( 'Please enter a date at least one hour into the future.', 'woocommerce-subscriptions' ),
				'i18n_next_payment_start_notice' => __( 'Please enter a date after the trial end.', 'woocommerce-subscriptions' ),
				'i18n_next_payment_trial_notice' => __( 'Please enter a date after the start date.', 'woocommerce-subscriptions' ),
				'i18n_trial_end_start_notice'    => __( 'Please enter a date after the start date.', 'woocommerce-subscriptions' ),
				'i18n_trial_end_next_notice'     => __( 'Please enter a date before the next payment.', 'woocommerce-subscriptions' ),
				'i18n_end_date_notice'           => __( 'Please enter a date after the next payment.', 'woocommerce-subscriptions' ),
				'process_renewal_action_warning' => __( "Are you sure you want to process a renewal?\n\nThis will charge the customer and email them the renewal order (if emails are enabled).", 'woocommerce-subscriptions' ),
				'payment_method'                 => wcs_get_subscription( $post )->get_payment_method(),
				'search_customers_nonce'         => wp_create_nonce( 'search-customers' ),
				'get_customer_orders_nonce'      => wp_create_nonce( 'get-customer-orders' ),
				'is_duplicate_site'              => WC_Subscriptions::is_duplicate_site(),
			) ) );
		} else if ( 'shop_order' == $screen_id ) {

			wp_enqueue_script( 'wcs-admin-meta-boxes-order', plugin_dir_url( WC_Subscriptions::$plugin_file ) . 'assets/js/admin/wcs-meta-boxes-order.js' );

			wp_localize_script(
				'wcs-admin-meta-boxes-order',
				'wcs_admin_order_meta_boxes',
				array(
					'retry_renewal_payment_action_warning' => __( "Are you sure you want to retry payment for this renewal order?\n\nThis will attempt to charge the customer and send renewal order emails (if emails are enabled).", 'woocommerce-subscriptions' ),
				)
			);
		}

		// Enqueue the metabox script for coupons.
		if ( ! WC_Subscriptions::is_woocommerce_pre( '3.2' ) && in_array( $screen_id, array( 'shop_coupon', 'edit-shop_coupon' ) ) ) {
			wp_enqueue_script(
				'wcs-admin-coupon-meta-boxes',
				plugin_dir_url( WC_Subscriptions::$plugin_file ) . 'assets/js/admin/meta-boxes-coupon.js',
				array( 'jquery', 'wc-admin-meta-boxes' ),
				WC_Subscriptions::$version
			);
		}
	}

	/**
	 * Adds actions to the admin edit subscriptions page, if the subscription hasn't ended and the payment method supports them.
	 *
	 * @param array $actions An array of available actions
	 * @return array An array of updated actions
	 * @since 2.0
	 */
	public static function add_subscription_actions( $actions ) {
		global $theorder;

		if ( wcs_is_subscription( $theorder ) ) {
			if ( ! WC_Subscriptions::is_woocommerce_pre( '3.2' ) ) {
				unset( $actions['send_order_details'], $actions['send_order_details_admin'] );
			}

			if ( ! $theorder->has_status( wcs_get_subscription_ended_statuses() ) ) {
				if ( $theorder->payment_method_supports( 'subscription_date_changes' ) && $theorder->has_status( 'active' ) ) {
					$actions['wcs_process_renewal'] = esc_html__( 'Process renewal', 'woocommerce-subscriptions' );
				}

				if ( count( $theorder->get_related_orders() ) > 0 ) {
					$actions['wcs_create_pending_renewal'] = esc_html__( 'Create pending renewal order', 'woocommerce-subscriptions' );
				} else {
					$actions['wcs_create_pending_parent'] = esc_html__( 'Create pending parent order', 'woocommerce-subscriptions' );
				}
			}
		} else if ( self::can_renewal_order_be_retried( $theorder ) ) {
			$actions['wcs_retry_renewal_payment'] = esc_html__( 'Retry Renewal Payment', 'woocommerce-subscriptions' );
		}

		return $actions;
	}

	/**
	 * Handles the action request to process a renewal order.
	 *
	 * @param array $subscription
	 * @since 2.0
	 */
	public static function process_renewal_action_request( $subscription ) {
		$subscription->add_order_note( __( 'Process renewal order action requested by admin.', 'woocommerce-subscriptions' ), false, true );
		do_action( 'woocommerce_scheduled_subscription_payment', $subscription->get_id() );
	}

	/**
	 * Handles the action request to create a pending renewal order.
	 *
	 * @param array $subscription
	 * @since 2.0
	 */
	public static function create_pending_renewal_action_request( $subscription ) {
		$subscription->add_order_note( __( 'Create pending renewal order requested by admin action.', 'woocommerce-subscriptions' ), false, true );
		$subscription->update_status( 'on-hold' );

		$renewal_order = wcs_create_renewal_order( $subscription );

		if ( ! $subscription->is_manual() ) {

			$renewal_order->set_payment_method( wc_get_payment_gateway_by_order( $subscription ) ); // We need to pass the payment gateway instance to be compatible with WC < 3.0, only WC 3.0+ supports passing the string name

			if ( is_callable( array( $renewal_order, 'save' ) ) ) { // WC 3.0+
				$renewal_order->save();
			}
		}
	}

	/**
	 * Handles the action request to create a pending parent order.
	 *
	 * @param array $subscription
	 * @since 2.3
	 */
	public static function create_pending_parent_action_request( $subscription ) {

		if ( ! $subscription->has_status( array( 'pending', 'on-hold' ) ) ) {
			$subscription->update_status( 'on-hold' );
		}

		$parent_order = wcs_create_order_from_subscription( $subscription, 'parent' );

		$subscription->set_parent_id( wcs_get_objects_property( $parent_order, 'id' ) );
		$subscription->save();

		if ( ! $subscription->is_manual() ) {

			$parent_order->set_payment_method( wc_get_payment_gateway_by_order( $subscription ) ); // We need to pass the payment gateway instance to be compatible with WC < 3.0, only WC 3.0+ supports passing the string name

			if ( is_callable( array( $parent_order, 'save' ) ) ) { // WC 3.0+
				$parent_order->save();
			}
		}

		wc_maybe_reduce_stock_levels( $parent_order );
		$subscription->add_order_note( __( 'Create pending parent order requested by admin action.', 'woocommerce-subscriptions' ), false, true );
	}

	/**
	 * Removes order related emails from the available actions.
	 *
	 * @param array $available_emails
	 * @since 2.0
	 */
	public static function remove_order_email_actions( $email_actions ) {
		global $theorder;

		if ( wcs_is_subscription( $theorder ) ) {
			$email_actions = array();
		}

		return $email_actions;
	}

	/**
	 * Process the action request to retry renewal payment for failed renewal orders.
	 *
	 * @param WC_Order $order
	 * @since 2.1
	 */
	public static function process_retry_renewal_payment_action_request( $order ) {

		if ( self::can_renewal_order_be_retried( $order ) ) {
			// init payment gateways
			WC()->payment_gateways();

			$order->add_order_note( __( 'Retry renewal payment action requested by admin.', 'woocommerce-subscriptions' ), false, true );
			do_action( 'woocommerce_scheduled_subscription_payment_' . wcs_get_objects_property( $order, 'payment_method' ), $order->get_total(), $order );
		}
	}

	/**
	 * Determines if a renewal order payment can be retried. A renewal order payment can only be retried when:
	 *  - Order is a renewal order
	 *  - Order status is failed
	 *  - Order payment method isn't empty
	 *  - Order total > 0
	 *  - Subscription/s aren't manual
	 *  - Subscription payment method supports date changes
	 *  - Order payment method has_action('woocommerce_scheduled_subscription_payment_..')
	 *
	 * @param WC_Order $order
	 * @return bool
	 * @since 2.1
	 */
	private static function can_renewal_order_be_retried( $order ) {

		$can_be_retried = false;

		if ( wcs_order_contains_renewal( $order ) && $order->needs_payment() && '' != wcs_get_objects_property( $order, 'payment_method' ) ) {
			$supports_date_changes          = false;
			$order_payment_gateway          = wc_get_payment_gateway_by_order( $order );
			$order_payment_gateway_supports = ( isset( $order_payment_gateway->id ) ) ? has_action( 'woocommerce_scheduled_subscription_payment_' . $order_payment_gateway->id ) : false;

			foreach ( wcs_get_subscriptions_for_renewal_order( $order ) as $subscription ) {
				$supports_date_changes = $subscription->payment_method_supports( 'subscription_date_changes' );
				$is_automatic = ! $subscription->is_manual();
				break;
			}

			$can_be_retried = $order_payment_gateway_supports && $supports_date_changes && $is_automatic;
		}

		return $can_be_retried;
	}

	/**
	 * Disables stock managment while adding items to a subscription via the edit subscription screen.
	 *
	 * @since 3.0.6
	 *
	 * @param string $manage_stock The default manage stock setting.
	 * @return string Whether the stock should be managed.
	 */
	public static function override_stock_management( $manage_stock ) {

		// Override stock management while adding line items to a subscription via AJAX.
		if ( isset( $_POST['order_id'] ) && wp_verify_nonce( $_REQUEST['security'], 'order-item' ) && doing_action( 'wp_ajax_woocommerce_add_order_item' ) && wcs_is_subscription( absint( wp_unslash( $_POST['order_id'] ) ) ) ) {
			$manage_stock = 'no';
		}

		return $manage_stock;
	}


	/**
	 * Displays a checkbox allowing admin to lock in prices increases in the edit order line items meta box.
	 *
	 * This checkbox is only displayed if the following criteria is met:
	 * - The order is unpaid.
	 * - The order is a subscription parent order. Renewal orders already lock in the subscription recurring price.
	 * - The order's currency matches the base store currency.
	 * - The order contains a line item with a subtotal greater than the product's current live price.
	 *
	 * @since 3.1.0
	 *
	 * @param WC_Order $order The order being edited.
	 */
	public static function output_price_lock_html( $order ) {

		if ( ! $order->needs_payment() || ! wcs_order_contains_subscription( $order, 'parent' ) ) {
			return;
		}

		// If the order currency doesn't match the base currency we can't know if the order contains manually increased prices.
		if ( $order->get_currency() !== get_woocommerce_currency() ) {
			return;
		}

		$needs_price_lock = false;

		foreach ( $order->get_items() as $line_item ) {
			$product = $line_item->get_product();

			// If the line item price is above the current live price.
			if ( $product && ( $line_item->get_subtotal() / $line_item->get_quantity() ) > $product->get_price() ) {
				$needs_price_lock = true;
				break;
			}
		}

		if ( $needs_price_lock ) {
			// So the help tip is initialized when the line items are reloaded, we need to add the 'tips' class to the element.
			$help_tip = wcs_help_tip( __( "This order contains line items with prices above the current product price. To override the product's live price when the customer pays for this order, lock in the manual price increases.", 'woocommerce-subscriptions' ) );
			$help_tip = str_replace( 'woocommerce-help-tip', 'woocommerce-help-tip tips', $help_tip );

			printf(
				'<div id="wcs_order_price_lock"><label for="wcs-order-price-lock">%s</label>%s<input id="wcs-order-price-lock" type="checkbox" name="wcs_order_price_lock" value="yes" %s></div>',
				esc_html__( 'Lock manual price increases', 'woocommerce-subscriptions' ),
				$help_tip, // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				checked( $order->get_meta( '_manual_price_increases_locked' ), 'true', false )
			);
		}
	}

	/**
	 * Saves the manual price increase lock via Edit order save and ajax request.
	 *
	 * @since 3.1.0
	 *
	 * @param string $order_id Optional. The order ID. For non-ajax requests, this parameter is required.
	 */
	public static function save_increased_price_lock( $order_id = '' ) {

		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
			return;
		}

		$order = wc_get_order( is_ajax() ? absint( $_POST['order_id'] ) : $order_id );

		if ( ! $order ) {
			return;
		}

		if ( isset( $_POST['wcs_order_price_lock'] ) && 'yes' === wc_clean( $_POST['wcs_order_price_lock'] ) ) {
			$order->update_meta_data( '_manual_price_increases_locked', 'true' );
			$order->save();
		} elseif ( $order->meta_exists( '_manual_price_increases_locked' ) ) {
			$order->delete_meta_data( '_manual_price_increases_locked' );
			$order->save();
		}
	}

	/**
	 * Stores the subtracted base location tax totals for subscription and renewal line items.
	 *
	 * @since 3.1.0
	 *
	 * @param int                   $item_id   The ID of the order item added.
	 * @param WC_Order_Item_Product $line_item The line item added.
	 * @param WC_Abstract_Order     $order     The order or subscription the product was added to.
	 */
	public static function store_item_base_location_tax( $item_id, $line_item, $order ) {

		if ( ! apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
			return;
		}

		if ( ! wc_prices_include_tax() ) {
			return;
		}

		if ( ! wcs_is_subscription( $order ) && ! wcs_order_contains_renewal( $order ) ) {
			return;
		}

		if ( '0' !== $line_item->get_tax_class() && 'taxable' === $line_item->get_tax_status() ) {
			$base_tax_rates = WC_Tax::get_base_tax_rates( $line_item->get_tax_class() );

			$line_item->update_meta_data( '_subtracted_base_location_tax', WC_Tax::calc_tax( $line_item->get_product()->get_price() * $line_item->get_quantity(), $base_tax_rates, true ) );
			$line_item->save();
		}
	}
}
