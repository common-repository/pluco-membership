<?php

namespace PLCOMembership\front\templates;

use PLCOMembership\admin\classes\PLCOM_Const;
use PLCOMembership\front\classes\PLCOM_Payment;
use PLCOMembership\front\classes\PLCOM_User_Membership;

/** @var $data */
?>

<div class="plcom-account-data">
	<h2>Account Data</h2>

	<form class="plcom-account-form">
		<div class="plcom-account-inputs">
			<label for="plco-first-name">First Name: </label>
			<input required type="text" id="plco-first-name" name="first_name"
			       value="<?php echo $data['user_meta']['first_name'][0] ?>" placeholder="First Name">
			<label for="plco-last-name">Last Name: </label>
			<input required type="text" id="plco-first-name" name="last_name"
			       value="<?php echo $data['user_meta']['last_name'][0] ?>" placeholder="Last Name">
			<label for="plco-email">Email: </label>
			<input required type="email" id="plco-email" name="email"
			       value="<?php echo $data['wp_user']->data->user_email ?>" placeholder="Email">

			<?php if ( isset( $data['html'] ) && ! empty( $data['html'] ) ) { ?>
				<?php echo $data['html']; ?>
			<?php } ?>

		</div>
		<input type="hidden" name="user_id" value="<?php echo $data['wp_user']->data->ID ?>">
		<input type="submit" class="plco-submit-account" value="Save Account Data">
	</form>

	<div class='plcom-memberships'>
		<h3>My Memberships</h3>
		<table class="plcom-user-memberships">
			<tr>
				<th>Membership Name</th>
				<th>Status</th>
				<th>Started At</th>
				<th>Next Billing Date</th>
				<th>Actions</th>
			</tr>
			<?php /** @var PLCOM_User_Membership $membership */ ?>
			<?php foreach ( $data['memberships'] as $membership ) { ?>
				<tr>
					<td><?php echo $membership->getMembership()->getMembershipName() ?></td>
					<td><?php echo $membership->getState() ?></td>
					<td><?php echo substr($membership->getCreatedAt(), 0, 10) ?></td>

					<?php if ( $membership->getMembership()->getID() > 1 ) { ?>
						<?php if ( $membership->getState() === "ACTIVE" ) { ?>
							<td> <?php echo $membership->getNextBillingDate() ?></td>
						<?php } else { ?>
							<td>-</td>
						<?php } ?>
					<?php } else { ?>
						<td>-</td>
					<?php } ?>


					<?php if ( $membership->getState() === "ACTIVE" && $membership->getMembership()->getID() > 1 ) { ?>
						<td>
							<a class="plcom-cancel-membership" data-id="<?php echo $membership->getID() ?>"
							   href="javascript:void(0)">Cancel</a>
						</td>
					<?php } else { ?>
						<td>-</td>
					<?php } ?>
				</tr>
			<?php } ?>
		</table>
	</div>

	<?php if ( count( $data['due_payments'] ) ) { ?>
		<div class='plcom-requires-action'>
			<table class="plcom-due-payments">
				<h3>Due Payments</h3>
				<tr>
					<th>Membership Name</th>
					<th>Status</th>
					<th>Next Billing Date</th>
					<th>Actions</th>
				</tr>
				<?php /** @var PLCOM_Payment $due_payment */  ?>
				<?php foreach ( $data['due_payments'] as $due_payment ) {  ?>
					<tr>
						<td><?php echo $due_payment->getMembership()->getMembershipName() ?></td>
						<td><?php echo $due_payment->getStatus() ?></td>
						<td><?php echo $due_payment->getUserMembership()->getNextBillingDate() ?></td>
						<td>
							<a class="plcom-confirm-membership" data-id="<?php echo $due_payment->getID() ?>"
							   href="javascript:void(0)">Confirm Payment</a>
						</td>
					</tr>
				<?php } ?>
			</table>
		</div>
	<?php } ?>

	<div class="plcom-account-actions">
		<a class="plcom-logout" href="<?php echo $data['logout_url'] ?>"><?php _e( 'Logout', PLCOM_Const::T ) ?></a>
		<?php if ( $data['allow_account_delete'] ) { ?>
			<a href="javascript:void(0)" class="plco-delete-account plco-open-modal"
			   data-id="delete-modal"><?php _e( 'Delete Account', PLCOM_Const::T ) ?></a>
		<?php } ?>
	</div>

	<div id="delete-modal" class="modal">
		<div class="modal-content">
			<div class="modal-header">
				<h3><?php _e( 'Are you sure you want to delete your account ?', PLCOM_Const::T ) ?></h3>
				<span class="close">&times;</span>
			</div>
			<h4><?php _e( 'CAUTION:', PLCOM_Const::T ) ?></h4>
			<p><?php _e( 'By deleting your account you will be automatically and immediately logged out and you will lose all your data and it cannot be recovered.', PLCOM_Const::T ) ?></p>
			<div class="modal-footer">
				<a href="javascript:void(0)" data-id="<?php echo $data['wp_user']->data->ID ?>"
				   class="plcom-yes-delete"><?php _e( 'Delete Account', PLCOM_Const::T ) ?></a>
				<a href="javascript:void(0)" class="plcom-no-delete close"><?php _e( 'Cancel', PLCOM_Const::T ) ?></a>
			</div>
		</div>
	</div>
</div>
