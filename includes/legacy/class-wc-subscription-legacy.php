<?php
/**
 * Subscription Legacy Object
 *
 * Extends WC_Subscription to provide WC 2.7 methods when running WooCommerce < 2.7.
 *
 * @class    WC_Subscription_Legacy
 * @version  2.1
 * @package  WooCommerce Subscriptions/Classes
 * @category Class
 * @author   Brent Shepherd
 */

class WC_Subscription_Legacy extends WC_Subscription {

	/**
	 * Initialize the subscription object.
	 *
	 * @param int|WC_Subscription $order
	 */
	public function __construct( $subscription ) {

		parent::__construct( $subscription );

		$this->order_type = 'shop_subscription';

		$this->schedule = new stdClass();
	}

	/**
	 * Populates a subscription from the loaded post data.
	 *
	 * @param mixed $result
	 */
	public function populate( $result ) {
		parent::populate( $result );

		if ( $this->post->post_parent > 0 ) {
			$this->order = wc_get_order( $this->post->post_parent );
		}
	}

	/**
	 * Returns the unique ID for this object.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get parent order ID.
	 *
	 * @since 2.1.4
	 * @return int
	 */
	public function get_parent_id() {
		return $this->order->id;
	}

	/**
	 * Gets order currency.
	 *
	 * @return string
	 */
	public function get_currency() {
		return $this->get_order_currency();
	}

	/**
	 * Get customer_note.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_customer_note( $context = 'view' ) {
		return $this->customer_note;
	}

	/**
	 * Get prices_include_tax.
	 *
	 * @param  string $context
	 * @return bool
	 */
	public function get_prices_include_tax( $context = 'view' ) {
		return $this->prices_include_tax;
	}

	/**
	 * Get the payment method.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_payment_method( $context = 'view' ) {
		return $this->payment_method;
	}

	/**
	 * Get the payment method's title.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_payment_method_title( $context = 'view' ) {
		return $this->payment_method_title;
	}

	/** Address Getters **/

	/**
	 * Get billing_first_name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_first_name( $context = 'view' ) {
		return $this->billing_first_name;
	}

	/**
	 * Get billing_last_name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_last_name( $context = 'view' ) {
		return $this->billing_last_name;
	}

	/**
	 * Get billing_company.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_company( $context = 'view' ) {
		return $this->billing_company;
	}

	/**
	 * Get billing_address_1.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_address_1( $context = 'view' ) {
		return $this->billing_address_1;
	}

	/**
	 * Get billing_address_2.
	 *
	 * @param  string $context
	 * @return string $value
	 */
	public function get_billing_address_2( $context = 'view' ) {
		return $this->billing_address_2;
	}

	/**
	 * Get billing_city.
	 *
	 * @param  string $context
	 * @return string $value
	 */
	public function get_billing_city( $context = 'view' ) {
		return $this->billing_city;
	}

	/**
	 * Get billing_state.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_state( $context = 'view' ) {
		return $this->billing_state;
	}

	/**
	 * Get billing_postcode.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_postcode( $context = 'view' ) {
		return $this->billing_postcode;
	}

	/**
	 * Get billing_country.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_country( $context = 'view' ) {
		return $this->billing_country;
	}

	/**
	 * Get billing_email.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_email( $context = 'view' ) {
		return $this->billing_email;
	}

	/**
	 * Get billing_phone.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_phone( $context = 'view' ) {
		return $this->billing_phone;
	}

	/**
	 * Get shipping_first_name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_first_name( $context = 'view' ) {
		return $this->shipping_first_name;
	}

	/**
	 * Get shipping_last_name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_last_name( $context = 'view' ) {
		return $this->shipping_last_name;
	}

	/**
	 * Get shipping_company.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_company( $context = 'view' ) {
		return $this->shipping_company;
	}

	/**
	 * Get shipping_address_1.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_address_1( $context = 'view' ) {
		return $this->shipping_address_1;
	}

	/**
	 * Get shipping_address_2.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_address_2( $context = 'view' ) {
		return $this->shipping_address_2;
	}

	/**
	 * Get shipping_city.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_city( $context = 'view' ) {
		return $this->shipping_city;
	}

	/**
	 * Get shipping_state.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_state( $context = 'view' ) {
		return $this->shipping_state;
	}

	/**
	 * Get shipping_postcode.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_postcode( $context = 'view' ) {
		return $this->shipping_postcode;
	}

	/**
	 * Get shipping_country.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_country( $context = 'view' ) {
		return $this->shipping_country;
	}

	/**
	 * Get order key.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return string
	 */
	public function get_order_key( $context = 'view' ) {
		return $this->order_key;
	}

	/**
	 * Helper function to make sure when WC_Subscription calls get_prop() from
	 * it's new getters that the property is both retreived from the legacy class
	 * property and done so from post meta.
	 *
	 * @return string
	 */
	protected function get_prop( $prop ) {

		if ( 'switch_data' == $prop ) {
			$prop = 'subscription_switch_data';
		}

		if ( ! isset( $this->$prop ) || empty( $this->$prop ) ) {
			$this->$prop = get_post_meta( $this->get_id(), '_' . $prop, true );
		}

		return $this->$prop;
	}

	/*** Setters *****************************************************/

	/**
	 * Helper function to make sure when WC_Subscription calls set_prop() that property is
	 * both set in the legacy class property and saved in post meta immediately.
	 *
	 * @return string
	 */
	protected function set_prop( $prop, $value ) {

		if ( 'switch_data' == $prop ) {
			$prop = 'subscription_switch_data';
		}

		$this->$prop = $value;
		update_post_meta( $this->get_id(), '_' . $prop, $value );
	}

	/**
	 * Set discount_total.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_discount_total( $value ) {
		$this->set_total( $value, 'cart_discount' );
	}

	/**
	 * Set discount_tax.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_discount_tax( $value ) {
		$this->set_total( $value, 'cart_discount_tax' );
	}

	/**
	 * Set shipping_total.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_total( $value ) {
		$this->set_total( $value, 'shipping' );
	}

	/**
	 * Set shipping_tax.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_tax( $value ) {
		$this->set_total( $value, 'shipping_tax' );
	}

	/**
	 * Set cart tax.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_cart_tax( $value ) {
		$this->set_total( $value, 'tax' );
	}

	/**
	 * Save data to the database. Nothing to do here as it's all done separately when calling @see this->set_prop().
	 *
	 * @return int order ID
	 */
	public function save() {
		return $this->get_id();
	}

	/**
	 * Update meta data by key or ID, if provided.
	 *
	 * @since  2.1.4
	 * @param  string $key
	 * @param  string $value
	 * @param  int $meta_id
	 */
	public function update_meta_data( $key, $value, $meta_id = '' ) {
		if ( ! empty( $meta_id ) ) {
			update_metadata_by_mid( 'post', $meta_id, $value, $key );
		} else {
			update_post_meta( $this->get_id(), $key, $value );
		}
	}
}
