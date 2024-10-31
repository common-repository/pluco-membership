<?php
namespace PLCOMembership\front\templates;

use PLCODashboard\admin\classes\PLCO_Const;
use PLCODashboard\classes\PLCO_Connection;
use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\front\classes\PLCOM_Card;
use PLCOMembership\front\classes\PLCOM_Membership_Level;
use PLCOMembership\front\classes\PLCOM_Recurrences;
use PLCOMembership\front\classes\repositories\PLCOM_Card_Repository;

/** @var $data */

?>

<form class="plcom-regiter-form plcom-regiter-form-styles">
	<span class="plcom-cards-title">
			<?php echo apply_filters( 'plcom_select_membership_recurrence', __( 'Membership Data', PLCOM_Const::T ) ); ?></span>
	<div class="plcom-memberships-recurrences">
		<div class="plcom-memberships-wrapper">
			<?php /** @var PLCOM_Membership_Level $level */
			$j = 0;
			if ( ! isset( $data['atts']['membership'] ) && ! isset( $data['atts']['recurrence'] ) ) { ?>
				<span class="plcom-membership-title">
			<?php echo apply_filters( 'plcom_select_membership', __( 'Membership', PLCOM_Const::T ) ); ?>
		</span>
			<?php } ?>

			<div
				class="plcom-levels <?php echo count( $data['levels'] ) === 1 || isset( $data['atts']['membership'] ) || isset( $data['atts']['recurrence'] ) ? 'plcom-single-level' : '' ?>">
				<?php foreach ( $data['levels'] as $key => $level ) {
					if ( isset( $data['atts']['membership'] ) || isset( $data['atts']['recurrence'] ) ) { ?>
						<div class="plcom-level">
							<input type="hidden" class="plcom-membership-level-radio"
							       id="level-<?php echo $level->getID() ?>"
							       name="level" value="<?php echo $level->getID() ?>">
							<label
								for="level-<?php echo $level->getID() ?>"><?php echo $level->getMembershipName() ?></label>
						</div>

					<?php } else { ?>
						<div class="plcom-level <?php echo $j === 0 ? "plco-selected" : ""; ?>">
							<input type="radio" <?php echo $j === 0 ? "checked='checked'" : ""; ?>
							       class="plcom-membership-level-radio"
							       id="level-<?php echo $level->getID() ?>" name="level"
							       value="<?php echo $level->getID() ?>">
							<label
								for="level-<?php echo $level->getID() ?>"><?php echo $level->getMembershipName() ?></label>
						</div>
					<?php }

					$j ++;
				} ?>
			</div>
		</div>

		<div class="plcom-recurrences-wrapper">
			<?php if ( ! isset( $data['atts']['recurrence'] ) ) { ?>
				<span class="plcom-recurrences-title">
				<?php echo apply_filters( 'plcom_select_recurrence', __( 'Billing Cycle', PLCOM_Const::T ) ); ?>
			</span>
			<?php }

			$i             = 0;
			$x             = 0;
			$first_is_free = false; ?>

			<?php foreach ( $data['levels'] as $level ) { ?>
				<div
					class="plcom-recurrences <?php echo count( $level->getRecurrences() ) === 1 || isset( $data['atts']['recurrence'] ) ? 'plcom-single-recurrence' : '' ?>"
					<?php if ( $x > 0 ) { ?>
						style="display: none"
					<?php } ?>
				>
					<?php /** @var PLCOM_Recurrences $recurrence */
					foreach ( $level->getRecurrences() as $recurrence ) {

						if ( isset( $data['atts']['recurrence'] ) && (int) $recurrence->getID() !== (int) $data['atts']['recurrence'] ) {
							continue;
						}

						foreach ( $this->const::CURRENCIES as $currency ) {
							if ( (int) $currency["ID"] === (int) $recurrence->getCurrency() ) {
								$currency_handle = strtoupper( $currency["sign"] );
							}
						}

						foreach ( $this->const::RECURRENCE_TYPES as $recurrence_type ) {
							if ( (int) $recurrence_type["ID"] === (int) $recurrence->getRecurrenceType() ) {
								$recurrence_type_name = $recurrence->getRecurrence() > 1 ? $recurrence_type["name"] : rtrim( $recurrence_type["name"], "s" );
							}
						}

						if ( $i === 0 && (int) ceil( $recurrence->getAmount() ) === 0 ) {
							$first_is_free = true;
						} ?>

						<div
							class="plcom-recurrence-membership recurrence-membership-<?php echo $recurrence->getMembershipId() ?> <?php echo $i === 0 ? "plco-selected" : ""; ?>">

							<?php if ( isset( $data['atts']['recurrence'] ) ) { ?>
								<input type="hidden"
							<?php } else { ?>
							<input type="radio" <?php echo $i === 0 ? "checked='checked'" : ""; ?>
								<?php } ?>
								<?php echo (int) ceil( $recurrence->getAmount() ) === 0 ? "data-free='true'" : ""; ?>
								   class="plcom-recurrence" id="recurrence-<?php echo $recurrence->getID() ?>"
								   name="recurrence"
								   value="<?php echo $recurrence->getID() ?>">
							<?php $recurrence_number = $recurrence->getRecurrence() > 1 ? $recurrence->getRecurrence() . ' ' : '' ?>
							<label
								for="recurrence-<?php echo $recurrence->getID() ?>"><?php echo( (int) ceil( $recurrence->getAmount() ) > 0 ? ( $currency_handle . number_format( $recurrence->getAmount(), 2, '.', '' ) . "" . ' / ' . $recurrence_number . $recurrence_type_name ) : 'Free' . ( $level->getAutorenew() ? '' : ' (Expires after ' . $recurrence->getRecurrence() . ' ' . $recurrence_type_name . ")" ) ) ?></label><br>

						</div>

						<?php $i ++;
					}
					$x ++; ?>
				</div>
			<?php } ?>
		</div>
	</div>

	<div class="plcom-errors-wrapper"></div>
	<?php if ( ! $data['logged_in'] ) { ?>
		<span class="plcom-cards-title">
			<?php echo apply_filters( 'plcom_select_client_data', __( 'Your Information', PLCOM_Const::T ) ); ?></span>

		<div class="plcom-client-data">
			<?php if ( isset( $data['html'] ) && ! empty( $data['html'] ) ) { ?>
				<?php echo $data['html']; ?>
			<?php } else { ?>
				<label for="plco-first-name">First Name:
					<input required type="text" id="plco-first-name" name="first_name" value=""
					       placeholder="First Name">
				</label>

				<label for="plco-last-name">Last Name:
					<input required type="text" id="plco-last-name" name="last_name" value="" placeholder="Last Name">
				</label>

				<label for="plco-email">Email:
					<input required type="email" id="plco-email" name="email" value=""
					       placeholder="john.smith@example.com">
				</label>
			<?php } ?>
		</div>
	<?php } ?>

	<?php $style = $first_is_free ? "display: none" : ""; ?>

	<span style="<?php echo $style; ?>" class="plcom-cards-title">
			<?php echo apply_filters( 'plcom_select_card', __( 'Payment Data', PLCOM_Const::T ) ); ?></span>

	<div class="plco-payments-wrapper" style="<?php echo $style ?>">
		<span class="plcom-payment-method-title"><?php _e( 'Payment Method', PLCOM_Const::T ) ?>:</span>
		<div class="plco-payments">

			<?php /** @var PLCO_Connection $connection */
			$i                 = 0;
			$contains_card     = false;
			$display_card_data = false;
			foreach ( $data['connections'] as $connection ) {
				$card = false;
				foreach ( PLCO_Const::AVAILABLE_CONNECTION as $connection_data ) {
					if ( $connection->getConnectionName() === $connection_data["handle"] ) {
						$connection_name = $connection_data["name"];
						if ( $connection_data['type'] === 'card' ) {
							$contains_card = true;
							$card          = true;
						}
					}
				}

				$src = $this->const::url( 'images/' . $connection->getConnectionName() . '-connection.png' );

				?>

				<input type="radio" <?php echo $i === 0 ? "checked='checked'" : "" ?> class="plco_payment_method"
				       id="<?php echo $connection->getConnectionName() ?>" name="payment_method"
				       value="<?php echo $connection->getConnectionName() ?>" data-card="<?php echo (int) $card ?>">
				<label for="<?php echo $connection->getConnectionName() ?>"
					<?php echo $i === 0 ? " class='plco-selected'" : ""; ?>
				><?php echo $connection->getConnectionName() === 'stripe' ? __( 'Credit Card', PLCOM_Const::T ) : $connection->getConnectionName() ?>
					<img src="<?php echo $src ?>" alt=""></label><br>

				<?php
				if ( $i === 0 && $card ) {
					$display_card_data = true;
				}

				$i ++;
			} ?>

		</div>
	</div>

	<?php if ( $contains_card ) { ?>
		<span class="plcom-cards-title plcom-card-data" <?php if ( ! $display_card_data || $first_is_free ) { ?>
			style="display: none"
		<?php } ?>>
			<?php echo apply_filters( 'plcom_select_card', __( 'Card Data', PLCOM_Const::T ) ); ?>
		</span>

		<div class="plco-cc-holder"
			<?php if ( ! $display_card_data || $first_is_free ) { ?>
				style="display: none"
			<?php } ?>
		>

			<?php $cards = array();
			$cid         = '';
			if ( $data['logged_in'] ) {

				$cards = PLCOM_Card_Repository::find_by( array( 'user_id' => get_current_user_id() ) );

				$i = 0;
				if ( count( $cards ) > 0 ) { ?>
					<div class="plco-existing-cards"><p>Pay with existing card:</p>

						<?php
						/** @var PLCOM_Card $card */
						foreach ( $cards as $card ) { ?>
							<input type="radio"
								<?php echo $i === 0 ? "checked='checked'" : ""; ?> class="plco_existing_card"
								   id="card-<?php echo $card->getID() ?>" name="existing_card"
								   value="<?php echo $card->getID() ?>"><label
								class="plco-existing-card-label <?php echo $i === 0 ? "plco-selected" : ""; ?>"
								for="card-<?php echo $card->getID() ?>">****-****-****<?php echo $card->getCardNumber() ?></label>

							<?php if ( $i === 0 ) {
								$cid = $card->getID();
							}
							$i ++;
						} ?>
					</div>
					<input type="button" class="plco-new-card" value="Add New Credit Card">
				<?php }
			} ?>

			<div class='plco-new-card-wrapper' style='
			<?php if ( $data['logged_in'] && count( $cards ) > 0 ) { ?>
				display: none
			<?php } ?>
				'>

				<div class="container preload">
					<div class="creditcard">
						<div class="front">
							<div id="ccsingle"></div>
							<svg version="1.1" id="cardfront" xmlns="http://www.w3.org/2000/svg"
							     xmlns:xlink="http://www.w3.org/1999/xlink"
							     x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;"
							     xml:space="preserve">
		                    <g id="Front">
			                    <g id="CardBackground">
				                    <g id="Page-1_1_">
					                    <g id="amex_1_">
						                    <path id="Rectangle-1_1_" class="lightcolor grey" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40
		                            C0,17.9,17.9,0,40,0z"/>
					                    </g>
				                    </g>
				                    <path class="darkcolor greydark"
				                          d="M750,431V193.2c-217.6-57.5-556.4-13.5-750,24.9V431c0,22.1,17.9,40,40,40h670C732.1,471,750,453.1,750,431z"/>
			                    </g>
			                    <text transform="matrix(1 0 0 1 60.106 295.0121)" id="svgnumber" class="st2 st3 st4">
				                    0123 4567
				                    8910 1112
			                    </text>
			                    <text transform="matrix(1 0 0 1 54.1064 428.1723)" id="svgname" class="st2 st5 st6">JOHN
				                    DOE
			                    </text>
			                    <text transform="matrix(1 0 0 1 54.1074 389.8793)" class="st7 st5 st8">cardholder name
			                    </text>
			                    <text transform="matrix(1 0 0 1 479.7754 388.8793)" class="st7 st5 st8">expiration
			                    </text>
			                    <text transform="matrix(1 0 0 1 65.1054 241.5)" class="st7 st5 st8">card number</text>
			                    <g>
				                    <text transform="matrix(1 0 0 1 574.4219 433.8095)" id="svgexpire"
				                          class="st2 st5 st9">
					                    01/23
				                    </text>
				                    <text transform="matrix(1 0 0 1 479.3848 417.0097)" class="st2 st10 st11">VALID
				                    </text>
				                    <text transform="matrix(1 0 0 1 479.3848 435.6762)" class="st2 st10 st11">THRU
				                    </text>
				                    <polygon class="st2" points="554.5,421 540.4,414.2 540.4,427.9 		"/>
			                    </g>
			                    <g id="cchip">
				                    <g>
					                    <path class="st2" d="M168.1,143.6H82.9c-10.2,0-18.5-8.3-18.5-18.5V74.9c0-10.2,8.3-18.5,18.5-18.5h85.3
		                        c10.2,0,18.5,8.3,18.5,18.5v50.2C186.6,135.3,178.3,143.6,168.1,143.6z"/>
				                    </g>
				                    <g>
					                    <g>
						                    <rect x="82" y="70" class="st12" width="1.5" height="60"/>
					                    </g>
					                    <g>
						                    <rect x="167.4" y="70" class="st12" width="1.5" height="60"/>
					                    </g>
					                    <g>
						                    <path class="st12" d="M125.5,130.8c-10.2,0-18.5-8.3-18.5-18.5c0-4.6,1.7-8.9,4.7-12.3c-3-3.4-4.7-7.7-4.7-12.3
		                            c0-10.2,8.3-18.5,18.5-18.5s18.5,8.3,18.5,18.5c0,4.6-1.7,8.9-4.7,12.3c3,3.4,4.7,7.7,4.7,12.3
		                            C143.9,122.5,135.7,130.8,125.5,130.8z M125.5,70.8c-9.3,0-16.9,7.6-16.9,16.9c0,4.4,1.7,8.6,4.8,11.8l0.5,0.5l-0.5,0.5
		                            c-3.1,3.2-4.8,7.4-4.8,11.8c0,9.3,7.6,16.9,16.9,16.9s16.9-7.6,16.9-16.9c0-4.4-1.7-8.6-4.8-11.8l-0.5-0.5l0.5-0.5
		                            c3.1-3.2,4.8-7.4,4.8-11.8C142.4,78.4,134.8,70.8,125.5,70.8z"/>
					                    </g>
					                    <g>
						                    <rect x="82.8" y="82.1" class="st12" width="25.8" height="1.5"/>
					                    </g>
					                    <g>
						                    <rect x="82.8" y="117.9" class="st12" width="26.1" height="1.5"/>
					                    </g>
					                    <g>
						                    <rect x="142.4" y="82.1" class="st12" width="25.8" height="1.5"/>
					                    </g>
					                    <g>
						                    <rect x="142" y="117.9" class="st12" width="26.2" height="1.5"/>
					                    </g>
				                    </g>
			                    </g>
		                    </g>
								<g id="Back">
								</g>
                </svg>
						</div>
						<div class="back">
							<svg version="1.1" id="cardback" xmlns="http://www.w3.org/2000/svg"
							     xmlns:xlink="http://www.w3.org/1999/xlink"
							     x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;"
							     xml:space="preserve">
                    <g id="Front">
	                    <line class="st0" x1="35.3" y1="10.4" x2="36.7" y2="11"/>
                    </g>
								<g id="Back">
									<g id="Page-1_2_">
										<g id="amex_2_">
											<path id="Rectangle-1_2_" class="darkcolor greydark" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40
                        C0,17.9,17.9,0,40,0z"/>
										</g>
									</g>
									<rect y="61.6" class="st2" width="750" height="78"/>
									<g>
										<path class="st3" d="M701.1,249.1H48.9c-3.3,0-6-2.7-6-6v-52.5c0-3.3,2.7-6,6-6h652.1c3.3,0,6,2.7,6,6v52.5
                    C707.1,246.4,704.4,249.1,701.1,249.1z"/>
										<rect x="42.9" y="198.6" class="st4" width="664.1" height="10.5"/>
										<rect x="42.9" y="224.5" class="st4" width="664.1" height="10.5"/>
										<path class="st5"
										      d="M701.1,184.6H618h-8h-10v64.5h10h8h83.1c3.3,0,6-2.7,6-6v-52.5C707.1,187.3,704.4,184.6,701.1,184.6z"/>
									</g>
									<text transform="matrix(1 0 0 1 621.999 227.2734)" id="svgsecurity" class="st6 st7">
										985
									</text>
									<g class="st8">
										<text transform="matrix(1 0 0 1 518.083 280.0879)" class="st9 st6 st10">security
											code
										</text>
									</g>
									<rect x="58.1" y="378.6" class="st11" width="375.5" height="13.5"/>
									<rect x="58.1" y="405.6" class="st11" width="421.7" height="13.5"/>
									<text transform="matrix(1 0 0 1 59.5073 228.6099)" id="svgnameback"
									      class="st12 st13">John Doe
									</text>
								</g>
                </svg>
						</div>
					</div>
				</div>
				<div class="form-container">
					<div class="field-container">
						<label for="name">Name</label>
						<input name="card_name" id="name" maxlength="20" type="text">
					</div>
					<div class="field-container">
						<label for="cardnumber">Card Number</label>
						<input name="card_number" id="cardnumber" type="text" inputmode="numeric">
						<svg id="ccicon" class="ccicon" width="750" height="471" viewBox="0 0 750 471" version="1.1"
						     xmlns="http://www.w3.org/2000/svg"
						     xmlns:xlink="http://www.w3.org/1999/xlink">
						</svg>
					</div>
					<div class="field-container">
						<label for="expirationdate">Expiration (mm/yy)</label>
						<input name="card_exp" id="expirationdate" type="text" inputmode="numeric">
					</div>
					<div class="field-container">
						<label for="securitycode">Security Code</label>
						<input name="card_ccv" id="securitycode" type="text" inputmode="numeric">
					</div>
				</div>

			</div>
		</div>

	<?php } ?>

	<?php $register_value = ! is_user_logged_in() ? 'Register Now' : 'Purchase Membership';

	$register_value = apply_filters( "plcom_registratio_button", $register_value ); ?>

	<?php do_action( 'plcom_before_registration_form_submit' ); ?>

	<br/><input type="submit" class="plco-submit-registration" value="<?php echo $register_value ?>">
</form>
