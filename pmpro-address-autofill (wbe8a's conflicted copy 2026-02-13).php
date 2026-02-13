<?php
/**
 * Plugin Name: Address Autofill for Paid Memberships Pro
 * Description: Allows users to autofill their billing address from their last successful order.
 * Version: 1.3
 * Author: Raphael Suzuki
 * Text Domain: pmpro-address-autofill
 */

namespace pmpro_address_autofill;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save per-user preferences after a successful checkout.
 *
 * @param int    $user_id The user ID.
 * @param object $order   The member order object.
 */
function save_preferences_on_checkout( $user_id, $order ) {
	if ( ! empty( $_POST['pmpro_address_autofill_always'] ) ) {
		update_user_meta( $user_id, 'pmpro_address_autofill_always', '1' );
	} else {
		update_user_meta( $user_id, 'pmpro_address_autofill_always', '0' );
	}
}
add_action( 'pmpro_after_checkout', __NAMESPACE__ . '\\save_preferences_on_checkout', 10, 2 );

/**
 * Autofill billing address pre-header if "always" is enabled.
 */
function maybe_autofill_on_load() {
	if ( ! is_user_logged_in() || \pmpro_was_checkout_form_submitted() ) {
		return;
	}

	$user_id = get_current_user_id();
	$always  = get_user_meta( $user_id, 'pmpro_address_autofill_always', true );

	if ( '1' === $always ) {
		$address_data = get_address_data( $user_id );
		if ( ! empty( $address_data ) ) {
			global $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail;

			if ( empty( $bfirstname ) ) { $bfirstname = $address_data['bfirstname']; }
			if ( empty( $blastname ) ) { $blastname = $address_data['blastname']; }
			if ( empty( $baddress1 ) ) { $baddress1 = $address_data['baddress1']; }
			if ( empty( $baddress2 ) ) { $baddress2 = $address_data['baddress2']; }
			if ( empty( $bcity ) ) { $bcity = $address_data['bcity']; }
			if ( empty( $bstate ) ) { $bstate = $address_data['bstate']; }
			if ( empty( $bzipcode ) ) { $bzipcode = $address_data['bzipcode']; }
			if ( empty( $bcountry ) ) { $bcountry = $address_data['bcountry']; }
			if ( empty( $bphone ) ) { $bphone = $address_data['bphone']; }
			if ( empty( $bemail ) ) {
				$bemail         = $address_data['bemail'];
				$bconfirmemail = $address_data['bemail'];
			}
		}
	}
}
add_action( 'pmpro_checkout_preheader', __NAMESPACE__ . '\\maybe_autofill_on_load' );

/**
 * Inject the UI.
 */
function inject_checkout_ui() {
	$user_id      = get_current_user_id();
	$address_data = is_user_logged_in() ? get_address_data( $user_id ) : false;
	$always       = is_user_logged_in() ? get_user_meta( $user_id, 'pmpro_address_autofill_always', true ) : '0';
	$is_prefilled = ( '1' === $always );

	?>
	<div id="pmpro_address_autofill_container" style="display: none;">
		<div id="pmpro_address_autofill_actions" class="pmpro_card_actions">
			<?php if ( is_user_logged_in() && ! empty( $address_data ) ) : ?>
				<!-- Logged-in UI -->
				<div id="pmpro_address_autofill_logged_in">
					<p class="pmpro_address_autofill_links">
						<a href="#" id="pmpro_address_autofill_toggle">
							<?php echo $is_prefilled ? esc_html__( 'Clear address', 'pmpro-address-autofill' ) : esc_html__( 'Fill from last saved address', 'pmpro-address-autofill' ); ?>
						</a>
					</p>
					<div id="pmpro_address_autofill_always_container" style="<?php echo $is_prefilled ? '' : 'display: none;'; ?>">
						<label for="pmpro_address_autofill_always" class="pmpro_label-checkbox">
							<input type="checkbox" id="pmpro_address_autofill_always" name="pmpro_address_autofill_always" value="1" <?php checked( $always, '1' ); ?>>
							<?php esc_html_e( 'Always autofill address on checkout', 'pmpro-address-autofill' ); ?>
						</label>
					</div>
				</div>
			<?php elseif ( ! is_user_logged_in() ) : ?>
				<!-- Logged-out UI -->
				<div id="pmpro_address_autofill_logged_out">
					<label for="pmpro_address_autofill_always" class="pmpro_label-checkbox">
						<input type="checkbox" id="pmpro_address_autofill_always" name="pmpro_address_autofill_always" value="1">
						<?php esc_html_e( 'Save this address for future autofill.', 'pmpro-address-autofill' ); ?>
					</label>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			const $targetCard = $('#pmpro_billing_address_fields .pmpro_card');
			if (!$targetCard.length) return;

			const addressData = <?php echo wp_json_encode( $address_data ); ?>;
			const $autofillActions = $('#pmpro_address_autofill_actions');

			// Move our actions into the billing card.
			if (<?php echo is_user_logged_in() ? 'true' : 'false'; ?>) {
				$autofillActions.prependTo($targetCard);
			} else {
				$autofillActions.appendTo($targetCard);
			}

			// Logic for logged-in toggle.
			const $toggle = $('#pmpro_address_autofill_toggle');
			if ($toggle.length) {
				const $alwaysContainer = $('#pmpro_address_autofill_always_container');
				const $alwaysCheckbox = $('#pmpro_address_autofill_always');
				const $fields = $('#bfirstname, #blastname, #baddress1, #baddress2, #bcity, #bstate, #bzipcode, #bcountry, #bphone, #bemail, #bconfirmemail');
				
				let isFilled = <?php echo $is_prefilled ? 'true' : 'false'; ?>;

				$toggle.on('click', function(e) {
					e.preventDefault();
					if (!isFilled) {
						// Fill
						$('#bfirstname').val(addressData.bfirstname);
						$('#blastname').val(addressData.blastname);
						$('#baddress1').val(addressData.baddress1);
						$('#baddress2').val(addressData.baddress2);
						$('#bcity').val(addressData.bcity);
						$('#bstate').val(addressData.bstate);
						$('#bzipcode').val(addressData.bzipcode);
						$('#bcountry').val(addressData.bcountry).change();
						$('#bphone').val(addressData.bphone);
						if (!$('#bemail').val()) {
							$('#bemail').val(addressData.bemail);
							$('#bconfirmemail').val(addressData.bemail);
						}
						
						$toggle.text('<?php esc_html_e( 'Clear address', 'pmpro-address-autofill' ); ?>');
						$alwaysContainer.slideDown();
						isFilled = true;
					} else {
						// Clear
						$fields.val('').change();
						$toggle.text('<?php esc_html_e( 'Fill from last saved address', 'pmpro-address-autofill' ); ?>');
						$alwaysContainer.slideUp();
						$alwaysCheckbox.prop('checked', false);
						isFilled = false;
					}
				});
			}
		});
	</script>
	<?php
}
add_action( 'pmpro_checkout_after_billing_fields', __NAMESPACE__ . '\\inject_checkout_ui' );

/**
 * Centralized function to get user address data with fallbacks.
 *
 * @param int $user_id The user ID.
 * @return array|false The address data or false.
 */
function get_address_data( $user_id ) {
	$baddress1 = get_user_meta( $user_id, 'pmpro_baddress1', true );
	if ( ! empty( $baddress1 ) ) {
		return array(
			'bfirstname' => get_user_meta( $user_id, 'pmpro_bfirstname', true ),
			'blastname'  => get_user_meta( $user_id, 'pmpro_blastname', true ),
			'baddress1'  => $baddress1,
			'baddress2'  => get_user_meta( $user_id, 'pmpro_baddress2', true ),
			'bcity'      => get_user_meta( $user_id, 'pmpro_bcity', true ),
			'bstate'     => get_user_meta( $user_id, 'pmpro_bstate', true ),
			'bzipcode'   => get_user_meta( $user_id, 'pmpro_bzipcode', true ),
			'bcountry'   => get_user_meta( $user_id, 'pmpro_bcountry', true ),
			'bphone'     => get_user_meta( $user_id, 'pmpro_bphone', true ),
			'bemail'     => get_userdata( $user_id )->user_email,
		);
	}

	if ( class_exists( 'MemberOrder' ) ) {
		$last_order = new \MemberOrder();
		$last_order->getLastMemberOrder( $user_id, 'success' );

		if ( ! empty( $last_order->id ) && ! empty( $last_order->billing ) ) {
			$address_data = array(
				'bfirstname' => '',
				'blastname'  => '',
				'baddress1'  => ! empty( $last_order->billing->street ) ? $last_order->billing->street : '',
				'baddress2'  => ! empty( $last_order->billing->street2 ) ? $last_order->billing->street2 : '',
				'bcity'      => ! empty( $last_order->billing->city ) ? $last_order->billing->city : '',
				'bstate'     => ! empty( $last_order->billing->state ) ? $last_order->billing->state : '',
				'bzipcode'   => ! empty( $last_order->billing->zip ) ? $last_order->billing->zip : '',
				'bcountry'   => ! empty( $last_order->billing->country ) ? $last_order->billing->country : '',
				'bphone'     => ! empty( $last_order->billing->phone ) ? $last_order->billing->phone : '',
				'bemail'     => ! empty( $last_order->Email ) ? $last_order->Email : '',
			);

			$name       = ! empty( $last_order->billing->name ) ? trim( $last_order->billing->name ) : '';
			$name_parts = explode( ' ', $name, 2 );
			$address_data['bfirstname'] = isset( $name_parts[0] ) ? $name_parts[0] : '';
			$address_data['blastname']  = isset( $name_parts[1] ) ? $name_parts[1] : '';

			return $address_data;
		}
	}

	return false;
}
