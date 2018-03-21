<?php
/*
Plugin Name: WP-Discord-Auth
Description: Authenticating with Discord oAuth2.
Author: Zak
Version: 1.0.0
*/

session_start();

Class WPDA {

	const PLUGIN_VERSION = "1.0.0";

	protected static $instance = NULL;
	public static function get_instance() {
		NULL === self::$instance and self::$instance = new self;
		return self::$instance;
	}

	private $settings = array(
		'wpda_login_redirect' => 'home_page',
		'wpda_login_redirect_page' => 0,
		'wpda_login_redirect_url' => '',
		'wpda_logout_redirect' => 'home_page',
		'wpda_logout_redirect_page' => 0,
		'wpda_logout_redirect_url' => '',
		'wpda_suppress_welcome_email' => 0,
		'wpda_new_user_role' => 'contributor',
		'wpda_discord_api_enabled' => 0,
		'wpda_discord_api_id' => '',
		'wpda_discord_api_secret' => '',
		'wpda_restore_default_settings' => 0,
		'wpda_delete_settings_on_uninstall' => '',
	);
	
	function __construct() {
		register_activation_hook(__FILE__, array($this, 'wpda_activate'));
		register_deactivation_hook(__FILE__, array($this, 'wpda_deactivate'));
		add_action('init', array($this, 'init'));
	}
	
	function wpda_activate() {}
	function wpda_deactivate() {}

	function wpda_add_missing_settings() {
		foreach($this->settings as $setting_name => $default_value) {
			if (is_array($this->settings[$setting_name])) {
				$default_value = json_encode($default_value);
			}
			$added = add_option($setting_name, $default_value);
		}
	}
	function wpda_restore_default_settings() {
		foreach($this->settings as $setting_name => $default_value) {
			if (is_array($this->settings[$setting_name])) {
				$default_value = json_encode($default_value);
			}
			update_option($setting_name, $default_value);
		}
		add_action('admin_notices', array($this, 'wpda_restore_default_settings_notice'));
	}
	function wpda_restore_default_settings_notice() {
		echo 'Default settings was restored';
	}

	// initialize the plugin's functionality by hooking into wordpress:
	function init() {
		if (get_option("wpda_restore_default_settings")) {$this->wpda_restore_default_settings();}
		add_filter('query_vars', array($this, 'wpda_qvar_triggers'));
		add_action('template_redirect', array($this, 'wpda_qvar_handlers'));
		add_action('admin_menu', array($this, 'wpda_settings_page'));
		add_action('admin_init', array($this, 'wpda_register_settings'));
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array($this, 'wpda_settings_link'));
		add_action('wp_logout', array($this, 'wpda_end_logout'));
	}

	// add a settings link to the plugins page:
	function wpda_settings_link($links) {
		$settings_link = "<a href='options-general.php?page=discord-auth.php'>Settings</a>";
		array_unshift($links, $settings_link); 
		return $links; 
	}
	
	// ===============
	// GENERIC HELPERS
	// ===============
	
	function wpda_add_basic_auth($url, $username, $password) {
		$url = str_replace("https://", "", $url);
		$url = "https://" . $username . ":" . $password . "@" . $url;
		return $url;
	}
	
	// ===================
	// LOGIN FLOW HANDLING
	// ===================

	function wpda_qvar_triggers($vars) {
		$vars[] = 'connect';
		$vars[] = 'code';
		$vars[] = 'error_description';
		$vars[] = 'error_message';
		return $vars;
	}
	function wpda_qvar_handlers() {
		if (get_query_var('connect')) {
			$provider = get_query_var('connect');
			$this->wpda_include_connector($provider);
		}
		elseif (get_query_var('code')) {
			$provider = $_SESSION['WPDA']['PROVIDER'];
			$this->wpda_include_connector($provider);
		}
		elseif (get_query_var('error_description') || get_query_var('error_message')) {
			$provider = $_SESSION['WPDA']['PROVIDER'];
			$this->wpda_include_connector($provider);
		}
	}
	function wpda_include_connector($provider) {
		$provider = strtolower($provider);
		$provider = str_replace(" ", "", $provider);
		$provider = str_replace(".", "", $provider);
		include 'login-' . $provider . '.php';
	}
	
	// =======================
	// LOGIN / LOGOUT HANDLING
	// =======================

	function wpda_match_wordpress_user($oauth_identity) {
		global $wpdb;
		$usermeta_table = $wpdb->usermeta;
		$query_string = "SELECT $usermeta_table.user_id FROM $usermeta_table WHERE $usermeta_table.meta_key = 'wpda_identity' AND $usermeta_table.meta_value LIKE '%" . $oauth_identity['provider'] . "|" . $oauth_identity['id'] . "%'";
		$query_result = $wpdb->get_var($query_string);
		$user = get_user_by('id', $query_result);
		return $user;
	}
	
	function wpda_login_user($oauth_identity) {
		$_SESSION["WPDA"]["USER_ID"] = $oauth_identity["id"];
		$matched_user = $this->wpda_match_wordpress_user($oauth_identity);
		if ( $matched_user ) {
			$user_id = $matched_user->ID;
			$user_login = $matched_user->user_login;
			wp_set_current_user( $user_id, $user_login );
			wp_set_auth_cookie( $user_id );
			do_action( 'wp_login', $user_login, $matched_user );
			$this->wpda_end_login("Logged in successfully!");
		}
		if ( is_user_logged_in() ) {
			global $current_user;
			get_currentuserinfo();
			$user_id = $current_user->ID;
			$this->wpda_link_account($user_id);
			$this->wpda_end_login("Account was linked.");
		}
		if ( !is_user_logged_in() && !$matched_user ) {
			include 'register.php';
		}
		$this->wpda_end_login("The login flow terminated.");
	}
	
	function wpda_end_login($msg) {
		$last_url = $_SESSION["WPDA"]["LAST_URL"];
		unset($_SESSION["WPDA"]["LAST_URL"]);
		$_SESSION["WPDA"]["RESULT"] = $msg;
		$this->wpda_clear_login_state();
		$redirect_method = get_option("wpda_login_redirect");
		$redirect_url = "";
		switch ($redirect_method) {
			case "home_page":
				$redirect_url = site_url();
				break;
			case "last_page":
				$redirect_url = $last_url;
				break;
			case "specific_page":
				$redirect_url = get_permalink(get_option('wpda_login_redirect_page'));
				break;
			case "admin_dashboard":
				$redirect_url = admin_url();
				break;
			case "user_profile":
				$redirect_url = get_edit_user_link();
				break;
			case "custom_url":
				$redirect_url = get_option('wpda_login_redirect_url');
				break;
		}
		wp_safe_redirect($redirect_url);
		die();
	}
	
	function wpda_logout_user() {
		$user = null;
		session_destroy();
		wp_logout();
	}

	function wpda_end_logout() {
		$_SESSION["WPDA"]["RESULT"] = 'Logged out successfully.';
		if (is_user_logged_in()) {
			$last_url = $_SERVER['HTTP_REFERER'];
		}
		else {
			$last_url = strtok($_SERVER['HTTP_REFERER'], "?");
		}
		unset($_SESSION["WPDA"]["LAST_URL"]);
		$this->wpda_clear_login_state();
		$redirect_method = get_option("wpda_logout_redirect");
		$redirect_url = "";
		switch ($redirect_method) {
			case "default_handling":
				return false;
			case "home_page":
				$redirect_url = site_url();
				break;
			case "last_page":
				$redirect_url = $last_url;
				break;
			case "specific_page":
				$redirect_url = get_permalink(get_option('wpda_logout_redirect_page'));
				break;
			case "admin_dashboard":
				$redirect_url = admin_url();
				break;
			case "user_profile":
				$redirect_url = get_edit_user_link();
				break;
			case "custom_url":
				$redirect_url = get_option('wpda_logout_redirect_url');
				break;
		}
		wp_safe_redirect($redirect_url);
		die();
	}

	function wpda_link_account($user_id) {
		if ($_SESSION['WPDA']['USER_ID'] != '') {
			add_user_meta( $user_id, 'wpda_identity', $_SESSION['WPDA']['PROVIDER'] . '|' . $_SESSION['WPDA']['USER_ID'] . '|' . time());
		}
	}

	// clears the login state:
	function wpda_clear_login_state() {
		unset($_SESSION["WPDA"]["USER_ID"]);
		unset($_SESSION["WPDA"]["USER_EMAIL"]);
		unset($_SESSION["WPDA"]["ACCESS_TOKEN"]);
		unset($_SESSION["WPDA"]["EXPIRES_IN"]);
		unset($_SESSION["WPDA"]["EXPIRES_AT"]);
		//unset($_SESSION["WPDA"]["LAST_URL"]);
	}
	
	// ===================================
	// DEFAULT LOGIN SCREEN CUSTOMIZATIONS
	// ===================================

	// show a custom login form on the default login screen:
	function wpda_customize_login_screen() {
		$html = "<div style='text-align:center;'>";
		$html .= '<a href="' . get_site_url() . '/?connect='. $_SESSION["WPDA"]["PROVIDER"] .'">Войти с помощью Discord</a>';
		$html .= "</div>";
		echo $html;
	}

	// ====================
	// PLUGIN SETTINGS PAGE
	// ====================
	
	function wpda_register_settings() {
		foreach ($this->settings as $setting_name => $default_value) {
			register_setting('wpda_settings', $setting_name);
		}
	}
	function wpda_settings_page() {
		add_options_page( 'Discord Options', 'Discord', 'manage_options', 'discord-auth', array($this, 'wpda_settings_page_content') );
	}
	function wpda_settings_page_content() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$blog_url = rtrim(site_url(), "/") . "/";
		include 'wp-discord-settings.php';
	}
}

// instantiate the plugin class ONCE and maintain a single instance (singleton):
WPDA::get_instance();
?>
