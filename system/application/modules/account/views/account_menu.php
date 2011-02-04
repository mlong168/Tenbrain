<ul class="account_menu">
	<li><?php echo anchor('account/account_settings', lang('website_account')); ?></li>
	<?php if ($account->password) : ?>
	<li><?php echo anchor('account/account_password', lang('website_password')); ?></li>
	<?php endif; ?>
	<li><?php echo anchor('account/account_profile', lang('website_profile')); ?></li>
	<li><?php echo anchor('account/account_linked', lang('website_linked')); ?></li>
</ul>