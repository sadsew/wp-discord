<?php

session_start();

# DEFINE THE OAUTH PROVIDER AND SETTINGS TO USE #
$_SESSION['WPDA']['PROVIDER'] = 'Discord';
define('CLIENT_ENABLED', get_option('wpda_discord_api_enabled'));
define('CLIENT_ID', get_option('wpda_discord_api_id'));
define('CLIENT_SECRET', get_option('wpda_discord_api_secret'));
define('REDIRECT_URI', rtrim(site_url(), '/'));
define('SCOPE', 'identify email');
define('URL_AUTH', "https://discordapp.com/api/oauth2/authorize?");
define('URL_TOKEN', "https://discordapp.com/api/oauth2/token?");
define('URL_USER', "https://discordapp.com/api/users/@me?");
# END OF DEFINE THE OAUTH PROVIDER AND SETTINGS TO USE #


// remember the user's last url so we can redirect them back to there after the login ends:
if (!$_SESSION['WPDA']['LAST_URL']) {
	$redirect_url = esc_url($_GET['redirect_to']);
	if (!$redirect_url) {
		$redirect_url = strtok($_SERVER['HTTP_REFERER'], "?");
	}
	$_SESSION['WPDA']['LAST_URL'] = $redirect_url;
}

# AUTHENTICATION FLOW #
if (!CLIENT_ENABLED) {
	$this->wpda_end_login("Provider error.");
}
elseif (!CLIENT_ID || !CLIENT_SECRET) {
	$this->wpda_end_login("Provider error.");
}
elseif (isset($_GET['error_description'])) {
	$this->wpda_end_login($_GET['error_description']);
}
elseif (isset($_GET['error_message'])) {
	$this->wpda_end_login($_GET['error_message']);
}
elseif (isset($_GET['code'])) {
	if ($_SESSION['WPDA']['STATE'] == $_GET['state']) {
		get_oauth_token($this);
		$oauth_identity = get_oauth_identity($this);
		$this->wpda_login_user($oauth_identity);
	}
	else {
		$this->wpda_end_login("Sorry, we couldn't log you in.");
	}
}
else {
	if ((empty($_SESSION['WPDA']['EXPIRES_AT'])) || (time() > $_SESSION['WPDA']['EXPIRES_AT'])) {
		$this->wpda_clear_login_state();
	}
	get_oauth_code($this);
}
$this->wpda_end_login("Auth error.");
# END OF AUTHENTICATION FLOW #

# AUTHENTICATION FLOW HELPER FUNCTIONS #
function get_oauth_code($wpda) {
	$params = array(
		'client_id' => CLIENT_ID,
		'redirect_uri' => REDIRECT_URI,
		'response_type' => 'code',
		'scope' => SCOPE,
	);
	$_SESSION['WPDA']['STATE'] = $params['state'];
	$url = URL_AUTH . http_build_query($params);
	header("Location: $url");
	exit;
}

function get_oauth_token($wpda) {
	$params = array(
		'grant_type' => 'authorization_code',
		'client_id' => CLIENT_ID,
		'client_secret' => CLIENT_SECRET,
		'code' => $_GET['code'],
		'redirect_uri' => REDIRECT_URI,
	);
	$url_params = http_build_query($params);
	$url = rtrim(URL_TOKEN, "?");
	$opts = array('http' =>
		array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $url_params,
		)
	);
	$context = $context  = stream_context_create($opts);
	$result = @file_get_contents($url, false, $context);
	if ($result === false) {
		$wpda->wpda_end_login("Stream context error.");
	}
	$result_obj = json_decode($result, true);
	$access_token = $result_obj['access_token'];
	if (!$access_token) {
		$wpda->wpda_end_login("Token error.");
	}
	else {
		$_SESSION['WPDA']['ACCESS_TOKEN'] = $access_token;
		$oauth_identity = array();
		$oauth_identity['provider'] = $_SESSION['WPDA']['PROVIDER'];
		$oauth_identity['refresh_token'] = $result_obj['access_token']; 
		if (!$oauth_identity['refresh_token']) {
			$wpda->wpda_end_login("Identity error");
		}
		return $oauth_identity;
	}
}

function get_oauth_identity($wpda) {
	$params = array(
		'access_token' => $_SESSION['WPDA']['ACCESS_TOKEN'],
	);
	$url_params = http_build_query($params);
	$url = rtrim(URL_USER, "?");
	$opts = array('http' =>
		array(
			'method'  => 'GET',
			'header'  => "Authorization: Bearer " . $_SESSION['WPDA']['ACCESS_TOKEN'] . "\r\n" . "x-li-format: json\r\n",
		)
	);
	$context = $context  = stream_context_create($opts);
	$result = @file_get_contents($url, false, $context);
	if ($result === false) {
		$wpda->wpda_end_login("Stream context error.");
	}
	$result_obj = json_decode($result, true);
	$_SESSION['WPDA']['USERINFO'] = $result_obj;
	$oauth_identity = array();
	$oauth_identity['provider'] = $_SESSION['WPDA']['PROVIDER'];
	$oauth_identity['id'] = $result_obj['id'];
	$oauth_identity['username'] = $result_obj['username'];
	$oauth_identity['email'] = $result_obj['email'];
	$oauth_identity['avatar'] = $result_obj['avatar'];
	if (!$oauth_identity['id']) {
		$wpda->wpda_end_login("Identity not found");
	}
	return $oauth_identity;
}

?>
