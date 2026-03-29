<?php
/**
 * Plugin Name: Address Autofill for Paid Memberships Pro
 * Description: Allows users to autofill their billing address from their last successful order.
 * Version: 1.7.0
 * Author: Raphael Suzuki
 * Text Domain: pmpro-address-autofill
 * GitHub Plugin URI: https://github.com/raphaelsuzuki/pmpro-address-autofill
 * Primary Branch: main
 */

namespace pmpro_address_autofill;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Save per-user preferences after a successful checkout.
 *
 * @param int    $user_id The user ID.
 * @param object $order   The member order object.
 */
function save_preferences_on_checkout($user_id, $order)
{
    // CSRF Protection: Verify nonce if present.
    if (isset($_POST['pmpro_address_autofill_nonce']) && ! wp_verify_nonce($_POST['pmpro_address_autofill_nonce'], 'pmpro_address_autofill_save_prefs')) {
        return;
    }

    // Strict Input Validation.
    $always = (isset($_POST['pmpro_address_autofill_always']) && '1' === $_POST['pmpro_address_autofill_always']) ? '1' : '0';

    // Only update if the preference field was actually present in the form submission.
    if (isset($_POST['pmpro_address_autofill_always_present'])) {
        update_user_meta($user_id, 'pmpro_address_autofill_always', $always);
    }
}
add_action('pmpro_after_checkout', __NAMESPACE__ . '\\save_preferences_on_checkout', 10, 2);

/**
 * Autofill billing address pre-header if "always" is enabled.
 */
function maybe_autofill_on_load()
{
    if (! function_exists('pmpro_was_checkout_form_submitted')) {
        return;
    }

    if (! is_user_logged_in() || \pmpro_was_checkout_form_submitted()) {
        return;
    }

    $user_id = get_current_user_id();
    $always  = get_user_meta($user_id, 'pmpro_address_autofill_always', true);

    if ('1' === (string) $always) {
        $address_data = get_address_data($user_id);
        if (! empty($address_data) && is_array($address_data)) {
            global $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail;

            // Populate globals with sanitized data.
            if (empty($bfirstname)) {
                $bfirstname = $address_data['bfirstname'];
            }
            if (empty($blastname)) {
                $blastname = $address_data['blastname'];
            }
            if (empty($baddress1)) {
                $baddress1 = $address_data['baddress1'];
            }
            if (empty($baddress2)) {
                $baddress2 = $address_data['baddress2'];
            }
            if (empty($bcity)) {
                $bcity = $address_data['bcity'];
            }
            if (empty($bstate)) {
                $bstate = $address_data['bstate'];
            }
            if (empty($bzipcode)) {
                $bzipcode = $address_data['bzipcode'];
            }
            if (empty($bcountry)) {
                $bcountry = $address_data['bcountry'];
            }
            if (empty($bphone)) {
                $bphone = $address_data['bphone'];
            }
            if (empty($bemail) && ! empty($address_data['bemail'])) {
                $bemail         = $address_data['bemail'];
                $bconfirmemail = $address_data['bemail'];
            }
        }
    }
}
add_action('pmpro_checkout_preheader', __NAMESPACE__ . '\\maybe_autofill_on_load');

/**
 * Inject the UI safely.
 */
function inject_checkout_ui()
{
    $user_id      = get_current_user_id();
    $address_data = is_user_logged_in() ? get_address_data($user_id) : false;
    $always       = is_user_logged_in() ? get_user_meta($user_id, 'pmpro_address_autofill_always', true) : '0';
    $is_prefilled = ('1' === (string) $always);

    ?>
	<div id="pmpro_address_autofill_container" style="display: none;" aria-hidden="true">
		<!-- Hidden fields for security and state signaling -->
		<input type="hidden" name="pmpro_address_autofill_always_present" value="1" />
		<?php wp_nonce_field('pmpro_address_autofill_save_prefs', 'pmpro_address_autofill_nonce', false); ?>

		<?php if (is_user_logged_in() && ! empty($address_data)) : ?>
			<div id="pmpro_address_autofill_logged_in" class="pmpro_address_autofill_logged_in" style="padding-bottom: 20px;">
				<p class="pmpro_address_autofill_links">
					<a href="#" id="pmpro_address_autofill_toggle" role="button">
						<?php echo $is_prefilled ? esc_html__('Clear address', 'pmpro-address-autofill') : esc_html__('Fill from last saved address', 'pmpro-address-autofill'); ?>
					</a>
				</p>
				<div id="pmpro_address_autofill_always_container" style="<?php echo $is_prefilled ? '' : 'display: none;'; ?>">
					<label for="pmpro_address_autofill_always" class="pmpro_label-checkbox">
						<input type="checkbox" id="pmpro_address_autofill_always" name="pmpro_address_autofill_always" value="1" <?php checked($always, '1'); ?>>
						<?php esc_html_e('Always autofill address on checkout', 'pmpro-address-autofill'); ?>
					</label>
				</div>
			</div>
		<?php elseif (! is_user_logged_in()) : ?>
			<div id="pmpro_address_autofill_actions" class="pmpro_card_actions">
				<div id="pmpro_address_autofill_logged_out">
					<label for="pmpro_address_autofill_always" class="pmpro_label-checkbox">
						<input type="checkbox" id="pmpro_address_autofill_always" name="pmpro_address_autofill_always" value="1">
						<?php esc_html_e('Save this address for future autofill.', 'pmpro-address-autofill'); ?>
					</label>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<script type="text/javascript">
		(function($) {
			$(function() {
				try {
					const $targetFieldset = $('#pmpro_billing_address_fields');
					if (!$targetFieldset.length) return;
					
					const $targetCard = $targetFieldset.find('.pmpro_card');

					// Hardened JSON output
					const addressData = <?php echo wp_json_encode($address_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS); ?>;

					// Logged-in: Move after the legend.
					const $loggedInUI = $('#pmpro_address_autofill_logged_in');
					if ($loggedInUI.length) {
						const $legend = $targetFieldset.find('.pmpro_form_legend');
						if ($legend.length) {
							$loggedInUI.insertAfter($legend);
						} else {
							$loggedInUI.prependTo($targetFieldset);
						}
					}

					// Logged-out: Move to card footer.
					const $loggedOutActions = $('#pmpro_address_autofill_actions');
					if ($loggedOutActions.length && $targetCard.length) {
						$loggedOutActions.appendTo($targetCard);
					}

					// Logic for logged-in toggle.
					const $toggle = $('#pmpro_address_autofill_toggle');
					if ($toggle.length && addressData) {
						const $alwaysContainer = $('#pmpro_address_autofill_always_container');
						const $alwaysCheckbox = $('#pmpro_address_autofill_always');
						const fieldSelectors = [
							'#bfirstname', '#blastname', '#baddress1', '#baddress2', 
							'#bcity', '#bstate', '#bzipcode', '#bcountry', '#bphone'
						];
						
						let isFilled = <?php echo $is_prefilled ? 'true' : 'false'; ?>;

						$toggle.on('click', function(e) {
							e.preventDefault();
							try {
								if (!isFilled) {
									// Safe Auto-fill using sanitized data
									if (addressData.bfirstname) $('#bfirstname').val(addressData.bfirstname);
									if (addressData.blastname)  $('#blastname').val(addressData.blastname);
									if (addressData.baddress1)  $('#baddress1').val(addressData.baddress1);
									if (addressData.baddress2)  $('#baddress2').val(addressData.baddress2);
									if (addressData.bcity)      $('#bcity').val(addressData.bcity);
									if (addressData.bstate)     $('#bstate').val(addressData.bstate);
									if (addressData.bzipcode)   $('#bzipcode').val(addressData.bzipcode);
									if (addressData.bcountry)   $('#bcountry').val(addressData.bcountry).trigger('change');
									if (addressData.bphone)     $('#bphone').val(addressData.bphone);
									
									const $email = $('#bemail');
									if ($email.length && !$email.val()) {
										$email.val(addressData.bemail);
										$('#bconfirmemail').val(addressData.bemail);
									}
									
									$toggle.text('<?php echo esc_js(__('Clear address', 'pmpro-address-autofill')); ?>');
									if ($alwaysContainer.length) $alwaysContainer.slideDown();
									isFilled = true;
								} else {
									// Safe Clear
									$.each(fieldSelectors, function(i, selector) {
										$(selector).val('').trigger('change');
									});
									$toggle.text('<?php echo esc_js(__('Fill from last saved address', 'pmpro-address-autofill')); ?>');
									if ($alwaysContainer.length) $alwaysContainer.slideUp();
									if ($alwaysCheckbox.length) $alwaysCheckbox.prop('checked', false);
									isFilled = false;
								}
							} catch (innerError) {
								console.error('PMPro Address Autofill Error:', innerError);
							}
						});
					}
				} catch (outerError) {
					// Fallback: If anything fails during init, the checkout page remains usable.
				}
			});
		})(jQuery);
	</script>
	<?php
}
add_action('pmpro_checkout_after_billing_fields', __NAMESPACE__ . '\\inject_checkout_ui');

/**
 * Access-controlled function to get user address data.
 *
 * @param int $user_id The user ID.
 * @return array|false The address data or false.
 */
function get_address_data($user_id)
{
    // Security: Strict ownership check.
    // Users can ONLY see their own address data.
    if (empty($user_id) || (int) $user_id !== (int) get_current_user_id()) {
        return false;
    }

    $address_data = array();

    // 1. Try standard PMPro meta keys.
    $baddress1 = get_user_meta($user_id, 'pmpro_baddress1', true);
    if (! empty($baddress1)) {
        $user = get_userdata($user_id);
        $address_data = array(
            'bfirstname' => get_user_meta($user_id, 'pmpro_bfirstname', true),
            'blastname'  => get_user_meta($user_id, 'pmpro_blastname', true),
            'baddress1'  => $baddress1,
            'baddress2'  => get_user_meta($user_id, 'pmpro_baddress2', true),
            'bcity'      => get_user_meta($user_id, 'pmpro_bcity', true),
            'bstate'     => get_user_meta($user_id, 'pmpro_bstate', true),
            'bzipcode'   => get_user_meta($user_id, 'pmpro_bzipcode', true),
            'bcountry'   => get_user_meta($user_id, 'pmpro_bcountry', true),
            'bphone'     => get_user_meta($user_id, 'pmpro_bphone', true),
            'bemail'     => is_object($user) ? $user->user_email : '',
        );
    }

    // 2. Try last successful PMPro order.
    if (empty($address_data) && class_exists('MemberOrder')) {
        try {
            $last_order = new \MemberOrder();
            $last_order->getLastMemberOrder($user_id, 'success');

            if (! empty($last_order->id) && ! empty($last_order->billing)) {
                $address_data = array(
                    'bfirstname' => '',
                    'blastname'  => '',
                    'baddress1'  => ! empty($last_order->billing->street) ? $last_order->billing->street : '',
                    'baddress2'  => ! empty($last_order->billing->street2) ? $last_order->billing->street2 : '',
                    'bcity'      => ! empty($last_order->billing->city) ? $last_order->billing->city : '',
                    'bstate'     => ! empty($last_order->billing->state) ? $last_order->billing->state : '',
                    'bzipcode'   => ! empty($last_order->billing->zip) ? $last_order->billing->zip : '',
                    'bcountry'   => ! empty($last_order->billing->country) ? $last_order->billing->country : '',
                    'bphone'     => ! empty($last_order->billing->phone) ? $last_order->billing->phone : '',
                    'bemail'     => ! empty($last_order->Email) ? $last_order->Email : '',
                );

                $name       = ! empty($last_order->billing->name) ? trim($last_order->billing->name) : '';
                if (! empty($name)) {
                    $name_parts = explode(' ', $name, 2);
                    $address_data['bfirstname'] = isset($name_parts[0]) ? $name_parts[0] : '';
                    $address_data['blastname']  = isset($name_parts[1]) ? $name_parts[1] : '';
                }
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    if (empty($address_data)) {
        return false;
    }

    // SECURITY: Final Whitelist & Sanitization Pass
    // We strictly ensure only text data is returned and everything is sanitized.
    $sanitized_data = array();
    $whitelist = array( 'bfirstname', 'blastname', 'baddress1', 'baddress2', 'bcity', 'bstate', 'bzipcode', 'bcountry', 'bphone', 'bemail' );

    foreach ($whitelist as $key) {
        $value = isset($address_data[ $key ]) ? (string) $address_data[ $key ] : '';

        if ('bemail' === $key) {
            $sanitized_data[ $key ] = sanitize_email($value);
        } else {
            $sanitized_data[ $key ] = sanitize_text_field($value);
        }
    }

    return $sanitized_data;
}
