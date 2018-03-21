<?php

if(!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

global $wpdb;
$delete_settings = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'wpda_delete_settings_on_uninstall'");
if ($delete_settings) {
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'wpoa_%';");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'wpoa_%';");
}

?>
