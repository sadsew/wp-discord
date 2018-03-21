<style>
	.wpda_table {
		max-width: 600px;
		margin-bottom: 40px;
	}

	.wpda_table h3 {
		padding-bottom: 5px;
		border-bottom: 2px solid #ccc;
	}

	.wpda_table div {
		height: 35px;
		line-height: 35px;
	}

	.wpda_table_left {
		width: 35%;
		float: left;
	}
	.wpda_table_right {
		width: 65%;
		float: right;
	}
	.wpda_table_right input[type="text"] {
		width: 100%;
	}


	.wpda_clear {
		clear: both;
	}
</style>

<form method='post' action='options.php'>
<?php settings_fields('wpda_settings'); ?>
<?php do_settings_sections('wpda_settings'); ?>
<div class="wpda_table">
	<h3>DISCORD: НАСТРОЙКИ СОЕДИНЕНИЯ С API</h3>
	<div class="wpda_table_left">
		Активирован:
	</div>
	<div class="wpda_table_right">
		<input type='checkbox' name='wpda_discord_api_enabled' value='1' <?php checked(get_option('wpda_discord_api_enabled') == 1); ?> />
	</div>
	<div class="wpda_table_left">
		Client ID:
	</div>
	<div class="wpda_table_right">
		<input type='text' name='wpda_discord_api_id' value='<?php echo get_option('wpda_discord_api_id'); ?>' />
	</div>
	<div class="wpda_table_left">
		Client Secret:
	</div>
	<div class="wpda_table_right">
		<input type='text' name='wpda_discord_api_secret' value='<?php echo get_option('wpda_discord_api_secret'); ?>' />
	</div>
	<div class="clear">
		<?php submit_button('Save all settings'); ?>
	</div>
</div>

<div class="wpda_table">
	<h3>DISCORD: РОЛЬ ПРИ РЕГИСТРАЦИИ</h3>
	<div class="wpda_table_left">
		Назначить роль:
	</div>
	<div class="wpda_table_right">
		<select name="wpda_new_user_role"><?php wp_dropdown_roles(get_option('wpda_new_user_role')); ?></select>
	</div>
	<div class="clear">
		<?php submit_button('Save all settings'); ?>
	</div>
</div>

<div class="wpda_table">
	<h3>DISCORD: НАСТРОЙКИ РЕДИРЕКТА</h3>
	<div class="wpda_table_left">
		При входе:
	</div>
	<div class="wpda_table_right">
		<select name='wpda_login_redirect'>
			<option value='home_page' <?php selected(get_option('wpda_login_redirect'), 'home_page'); ?>>Home Page</option>
			<option value='last_page' <?php selected(get_option('wpda_login_redirect'), 'last_page'); ?>>Last Page</option>
			<option value='specific_page' <?php selected(get_option('wpda_login_redirect'), 'specific_page'); ?>>Specific Page</option>
			<option value='admin_dashboard' <?php selected(get_option('wpda_login_redirect'), 'admin_dashboard'); ?>>Admin Dashboard</option>
			<option value='user_profile' <?php selected(get_option('wpda_login_redirect'), 'user_profile'); ?>>User's Profile Page</option>
			<option value='custom_url' <?php selected(get_option('wpda_login_redirect'), 'custom_url'); ?>>Custom URL</option>
		</select>
		<?php wp_dropdown_pages(array("id" => "wpda_login_redirect_page", "name" => "wpda_login_redirect_page", "selected" => get_option('wpda_login_redirect_page'))); ?><br>
	</div>
	<div class="wpda_table_left">
	</div>
	<div class="wpda_table_right">
		<input type="text" name="wpda_login_redirect_url" value="<?php echo get_option('wpda_login_redirect_url'); ?>" />
	</div>
	<div class="wpda_table_left">
		При выходе:
	</div>
	<div class="wpda_table_right">
		<select name='wpda_logout_redirect'>
			<option value='default_handling' <?php selected(get_option('wpda_logout_redirect'), 'default_handling'); ?>>Let WordPress handle it</option>
			<option value='home_page' <?php selected(get_option('wpda_logout_redirect'), 'home_page'); ?>>Home Page</option>
			<option value='last_page' <?php selected(get_option('wpda_logout_redirect'), 'last_page'); ?>>Last Page</option>
			<option value='specific_page' <?php selected(get_option('wpda_logout_redirect'), 'specific_page'); ?>>Specific Page</option>
			<option value='admin_dashboard' <?php selected(get_option('wpda_logout_redirect'), 'admin_dashboard'); ?>>Admin Dashboard</option>
			<option value='user_profile' <?php selected(get_option('wpda_logout_redirect'), 'user_profile'); ?>>User's Profile Page</option>
			<option value='custom_url' <?php selected(get_option('wpda_logout_redirect'), 'custom_url'); ?>>Custom URL</option>
		</select>
		<?php wp_dropdown_pages(array("id" => "wpda_logout_redirect_page", "name" => "wpda_logout_redirect_page", "selected" => get_option('wpda_logout_redirect_page'))); ?>
	</div>
	<div class="wpda_table_left">
	</div>
	<div class="wpda_table_right">
		<input type="text" name="wpda_logout_redirect_url" value="<?php echo get_option('wpda_logout_redirect_url'); ?>"/>
	</div>
	<div class="clear">
		<?php submit_button('Save all settings'); ?>
	</div>
</div>

<div class="wpda_table">
	<h3>DISCORD: СБРОС НАСТРОЕК</h3>
	<div class="wpda_table_left">
		Сбросить настройки:
	</div>
	<div class="wpda_table_right">
		<input type='checkbox' name='wpda_restore_default_settings' value='1' <?php checked(get_option('wpda_restore_default_settings') == 1); ?> />
	</div>
	<div class="clear">
		<?php submit_button('Save all settings'); ?>
	</div>
</div>
</form>
