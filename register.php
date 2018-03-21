<?php

global $wpdb;

session_start();

if (!get_option("users_can_register")) {
	$_SESSION["WPDA"]["RESULT"] = "User registration is disabled.";
	header("Location: " . $_SESSION["WPDA"]["LAST_URL"]);
	exit;
}
if ($_SESSION["WPDA"]["USER_ID"] != "") {
	$username = uniqid('', true);
	$password = wp_generate_password();
}
if ( $_SESSION["WPDA"]["USER_ID"] == "" ) {
	$username = $_POST['identity'];
	$password = $_POST['password'];
}

$email = $_SESSION["WPDA"]["USERINFO"]["email"];
$user_id = wp_create_user( $username, $password, $email );

if (is_wp_error($user_id)) {
	$_SESSION["WPDA"]["RESULT"] = $user_id->get_error_message();
	header("Location: " . $_SESSION["WPDA"]["LAST_URL"]);
	exit;
}

$username = $_SESSION["WPDA"]["USERINFO"]['username'];
$avatar = $_SESSION["WPDA"]["USERINFO"]['avatar'];
$role = get_option('wpda_new_user_role');

$update_username_result = $wpdb->update($wpdb->users, array('user_login' => $username, 'user_nicename' => $username, 'display_name' => $username), array('ID' => $user_id));

$update_nickname_result = update_user_meta($user_id, 'nickname', $username);
$update_avatar_result = update_user_meta($user_id, 'avatar', $avatar);
$update_role_result = wp_update_user(array('ID' => $user_id, 'role' => $role));


if ($update_username_result == false || $update_nickname_result == false) {
	$_SESSION["WPDA"]["RESULT"] = "Update username error.";
	header("Location: " . $_SESSION["WPDA"]["LAST_URL"]); exit;
}
elseif ($update_role_result == false) {
	$_SESSION["WPDA"]["RESULT"] = "Update role error.";
	header("Location: " . $_SESSION["WPDA"]["LAST_URL"]); exit;
}
else {
	$this->wpda_link_account($user_id);
	$creds = array();
	$creds['user_login'] = $username;
	$creds['user_password'] = $password;
	$creds['remember'] = true;
	$user = wp_signon( $creds, false );
	// if (!get_option('wpda_suppress_welcome_email')) {
	// 	wp_new_user_notification( $user_id, $password );
	// }
	$site_url = get_site_url();
	header("Location: " . $site_url); exit;
}
?>
