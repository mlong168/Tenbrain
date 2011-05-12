<?php echo doctype('xhtml1-trans') ?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>TenBrain - Save your Brain, While saving Money! - Profile</title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url() ?>css/main.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo base_url() ?>css/account.css" />

	<?php if ($this->session->flashdata('linked_info') || $this->session->flashdata('linked_error')) : ?>
		<?php $this->session->set_userdata('active_menu_item', 'linked_accounts') ?>
		<script type="text/javascript">
			if(top === self) // if not in an iframe
			{
				document.location.assign('<?php echo base_url() ?>control_panel');
			}
		</script>
	<?php endif; ?>

</head>
<body>

<div class="profile_content">
	<h2><?php echo lang('linked_page_name'); ?></h2><br />
	<h3><?php echo lang('linked_currently_linked_accounts'); ?></h3>
	
	<?php if ($this->session->flashdata('linked_info')) : ?>
		<p class="center"><?php echo $this->session->flashdata('linked_info'); ?></p>
	<?php endif; ?>
	
	<?php if ($num_of_linked_accounts == 0) : ?>
		<p class="center"><?php echo lang('linked_no_linked_accounts'); ?></p>
	<?php else :?>
		<?php if ($facebook_links) : ?>
			<?php foreach ($facebook_links as $facebook_link) : ?>
				<div class="linked_account">
					<div class="control third_party big picture centered facebook"></div>
					<span class="caption"><?php echo lang('connect_facebook'); ?></span>
						<?php if ($num_of_linked_accounts != 1) : ?>
							<?php echo form_open(uri_string(), array('class' => 'centered')); ?>
								<?php echo form_hidden('facebook_id', $facebook_link->facebook_id); ?>
								<?php echo form_button(array(
										'type'		=> 'submit',
										'name'		=> 'submit',
										'class'		=> 'login_submit',
										'content'	=> lang('linked_remove')
									)); ?>
							<?php echo form_close(); ?>
						<?php endif; ?>
					<?php echo anchor('http://facebook.com/profile.php?id='.$facebook_link->facebook_id, substr('http://facebook.com/profile.php?id='.$facebook_link->facebook_id, 0, 30).(strlen('http://facebook.com/profile.php?id='.$facebook_link->facebook_id) > 30 ? '...' : ''), array('target'	=> '_blank', 'class' => 'linked_link', 'title'	=> 'http://facebook.com/profile.php?id='.$facebook_link->facebook_id)); ?>
				</div>	
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if ($twitter_links) : ?>
			<?php foreach ($twitter_links as $twitter_link) : ?>
				<div class="linked_account">
					<div class="control third_party big picture centered twitter"></div>
					<span class="caption"><?php echo lang('connect_twitter'); ?></span>
						<?php if ($num_of_linked_accounts != 1) : ?>
							<?php echo form_open(uri_string(), array('class' => 'centered')); ?>
								<?php echo form_hidden('twitter_id', $twitter_link->twitter_id); ?>
								<?php echo form_button(array(
										'type'		=> 'submit',
										'name'		=> 'submit',
										'class'		=> 'login_submit',
										'content'	=> lang('linked_remove')
									)); ?>
							<?php echo form_close(); ?>
						<?php endif; ?>
					<?php echo anchor('http://twitter.com/'.$twitter_link->twitter->screen_name, substr('http://twitter.com/'.$twitter_link->twitter->screen_name, 0, 30).(strlen('http://twitter.com/'.$twitter_link->twitter->screen_name) > 30 ? '...' : ''), array('target'	=> '_blank', 'class' => 'linked_link', 'title'	=> 'http://twitter.com/'.$twitter_link->twitter->screen_name)); ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if ($openid_links) : ?>
			<?php foreach ($openid_links as $openid_link) : ?>
				<div class="linked_account">
					<div class="control third_party big picture centered openid"></div>
					<span class="caption"><?php echo lang('connect_'.$openid_link->provider); ?></span>
						<?php if ($num_of_linked_accounts != 1) : ?>
							<?php echo form_open(uri_string(), array('class' => 'centered')); ?>
								<?php echo form_hidden('openid', $openid_link->openid); ?>
								<?php echo form_button(array(
										'type'		=> 'submit',
										'name'		=> 'submit',
										'class'		=> 'login_submit',
										'content'	=> lang('linked_remove')
									)); ?>
							<?php echo form_close(); ?>
						<?php endif; ?>
					<?php echo anchor($openid_link->openid, substr($openid_link->openid, 0, 30).(strlen($openid_link->openid) > 30 ? '...' : ''), array('target' => '_blank', 'title' => $openid_link->openid, 'class' => 'linked_link')); ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
<h3><?php echo lang('linked_link_with_your_account_from'); ?></h3>
<ul class="third_parties">
	<?php foreach($this->config->item('third_party_auth_providers') as $provider) : ?>
	<li class="control third_party big <?php echo $provider; ?>">
		<?php echo anchor('account/connect_'.$provider,
			lang('connect_'.$provider), 
			array(
				'title'	=> sprintf(lang('connect_with_x'), lang('connect_'.$provider)),
				'class'	=> 'social'
			)
		); ?>
	</li>
	<?php endforeach; ?>
</ul>
<div class="clearfix"></div>
<?php if ($this->session->flashdata('linked_error')) : ?>
<p class="input_wrapper center account_error"><?php echo $this->session->flashdata('linked_error'); ?></p>
<?php endif; ?>

</body></html>