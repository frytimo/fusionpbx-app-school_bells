<?php

/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Igor Olhovskiy <igorolhovskiy@gmail.com>
 */

// includes files
	require_once dirname($_SERVER["SCRIPT_FILENAME"], 3) . "/resources/require.php";
	require_once "resources/check_auth.php";

// check permissions
	if (!(permission_exists('school_bell_edit') || permission_exists('school_bell_add'))) {
		echo "access denied";
		exit;
	}

// add multi-lingual support
	$language = new text;
	$text = $language->get();

// declare globals
	global $database, $settings, $domain_uuid, $user_uuid;
	$domain_uuid = $domain_uuid ?? $_SESSION['domain_uuid'] ?? '';
	$user_uuid = $user_uuid ?? $_SESSION['user_uuid'] ?? '';
	if (!$database) {
		$database = database::new();
	}
	if (!$settings) {
		$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);
	}

// set defaults
	$persistformvar = strlen($_POST["persistformvar"] ?? '') != 0;
	$school_bell_name = '';
	$school_bell_leg_a_type = '';
	$school_bell_leg_a_data = '';
	$school_bell_leg_b_type = '';
	$school_bell_leg_b_data = '';
	$school_bell_ring_timeout = '';
	$school_bell_min = '';
	$school_bell_hour = '';
	$school_bell_dom = '';
	$school_bell_mon = '';
	$school_bell_dow = '';
	$school_bell_timezone = '';
	$school_bell_enabled = 'true';
	$school_bell_description = '';
	$destination_id = '';

// set the required fields
	$required_fields = [
		'school_bell_name',
		'school_bell_leg_a_data',
		'school_bell_ring_timeout',
		'school_bell_leg_b_data',
		'school_bell_enabled',
	];

// Get timezones
	$timezone_identifiers_list = timezone_identifiers_list();

// action add or update
	if (isset($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$school_bell_uuid = $_REQUEST["id"];
	} else {
		$action = "add";
	}

// get http post variables and set them to php variables
	if (!empty($_POST)) {
		$school_bell_name = $_POST["school_bell_name"] ?? '';
		$school_bell_leg_a_type = $_POST["school_bell_leg_a_type"] ?? '';
		$school_bell_leg_a_data = $_POST["school_bell_leg_a_data"] ?? '';
		$school_bell_leg_b_type = $_POST["school_bell_leg_b_type"] ?? '';
		$school_bell_leg_b_data = $_POST["school_bell_leg_b_data"] ?? '';
		$school_bell_ring_timeout = intval($_POST["school_bell_ring_timeout"] ?? 3);
		$school_bell_min = $_POST["school_bell_min"] ?? '';
		$school_bell_hour = $_POST["school_bell_hour"] ?? '';
		$school_bell_dom = $_POST["school_bell_dom"] ?? '';
		$school_bell_mon = $_POST["school_bell_mon"] ?? '';
		$school_bell_dow = $_POST["school_bell_dow"] ?? '';
		$school_bell_timezone = $_POST["school_bell_timezone"] ?? '';
		$school_bell_enabled = $_POST["school_bell_enabled"] ?? '';
		$school_bell_description = $_POST["school_bell_description"] ?? '';

		// Filter values:
		if (strlen($school_bell_leg_a_type) == 0) {
			$school_bell_leg_a_type = "loopback/";
		}

		if (strlen($school_bell_leg_b_data) > 0) {
			$school_bell_leg_b_type = $settings->get('switch', 'recordings', '/var/lib/freeswitch/recordings') . "/" . $_SESSION['domain_name'] . "/" . $school_bell_leg_b_data;
		}

		// Set default ring timeout to 3 sec
		if ($school_bell_ring_timeout < 0) {
			$school_bell_ring_timeout = 3;
		}

		// Sanitize cron values - now supports step (*/N) and comma-separated values
		$school_bell_min = school_bell_sanitize_cron_field($school_bell_min, 0, 59);
		$school_bell_hour = school_bell_sanitize_cron_field($school_bell_hour, 0, 23);
		$school_bell_dom = school_bell_sanitize_cron_field($school_bell_dom, 1, 31);
		$school_bell_mon = school_bell_sanitize_cron_field($school_bell_mon, 1, 12);
		$school_bell_dow = school_bell_sanitize_cron_field($school_bell_dow, 0, 6);

		if (!in_array($school_bell_timezone, $timezone_identifiers_list)) {
			$school_bell_timezone = date_default_timezone_get();
		}

		if (strlen($school_bell_enabled) == 0) {
			$school_bell_enabled = 'true';
		}
	}

/**
 * Sanitizes cron field values
 * @access private
 * @param string $value The value to sanitize
 * @param int $min Minimum allowed value
 * @param int $max Maximum allowed value
 * @return string Sanitized cron field value
 */
function school_bell_sanitize_cron_field($value, $min, $max) {
	// Handle empty or wildcard
	if ($value === '' || $value === '-1') {
		return '-1';
	}
	
	// Handle step values (*/N)
	if (strpos($value, '*/') === 0) {
		$step = intval(substr($value, 2));
		if ($step >= 1 && $step <= $max) {
			return '*/' . $step;
		}
		return '-1';
	}
	
	// Handle comma-separated values
	if (strpos($value, ',') !== false) {
		$values = explode(',', $value);
		$sanitized = array();
		foreach ($values as $val) {
			$val = intval(trim($val));
			if ($val >= $min && $val <= $max) {
				$sanitized[] = $val;
			}
		}
		if (count($sanitized) > 0) {
			return implode(',', $sanitized);
		}
		return '-1';
	}
	
	// Handle single value
	$int_value = intval($value);
	if ($int_value >= $min && $int_value <= $max) {
		return strval($int_value);
	}
	
	return '-1';
}
// handle the http post
	if (!empty($_POST) && !$persistformvar) {
		$msg = '';

		// check for all required data
		$submitted_fields = array_keys(array_filter($_POST, function($value) { return !empty($value); }));
		foreach ($required_fields as $required_field) {
			if (!in_array($required_field, $submitted_fields)) {
				$msg .= $text['label-'.$required_field] . "<br>\n";
			}
		}

		if (strlen($msg) > 0) {
			require_once "resources/header.php";
			require_once "resources/persist_form_var.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg . "<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "resources/footer.php";
			return;
		}

		// add or update the database
		if ($action == "add" && permission_exists('school_bell_add')) {
			$array['school_bells'][0]['school_bell_uuid'] = uuid();
		} elseif ($action == "update" && permission_exists('school_bell_edit')) {
			$array['school_bells'][0]['school_bell_uuid'] = $school_bell_uuid;
		} else {
			// missing permissions
			$array = [];
			message::add($text['label-failed'], 'negative');
			header("Location: school_bells.php");
			return;
		}

		//fill the array with the form data
		$array['school_bells'][0]['domain_uuid'] = $domain_uuid;
		$array['school_bells'][0]['school_bell_name'] = $school_bell_name;
		$array['school_bells'][0]['school_bell_leg_a_type'] = $school_bell_leg_a_type;
		$array['school_bells'][0]['school_bell_leg_a_data'] = $school_bell_leg_a_data;
		$array['school_bells'][0]['school_bell_leg_b_type'] = $school_bell_leg_b_type;
		$array['school_bells'][0]['school_bell_leg_b_data'] = $school_bell_leg_b_data;
		$array['school_bells'][0]['school_bell_ring_timeout'] = $school_bell_ring_timeout;
		$array['school_bells'][0]['school_bell_min'] = $school_bell_min;
		$array['school_bells'][0]['school_bell_hour'] = $school_bell_hour;
		$array['school_bells'][0]['school_bell_dom'] = $school_bell_dom;
		$array['school_bells'][0]['school_bell_mon'] = $school_bell_mon;
		$array['school_bells'][0]['school_bell_dow'] = $school_bell_dow;
		$array['school_bells'][0]['school_bell_timezone'] = $school_bell_timezone;
		$array['school_bells'][0]['school_bell_enabled'] = $school_bell_enabled;
		$array['school_bells'][0]['school_bell_description'] = $school_bell_description;

		//save to the database
		$database->save($array);
		unset($array);

		//check for failed save
		if ($database->message['code'] != 200 && !empty($database->message["details"][0]["code"]) && $database->message["details"][0]["code"] != "000") {
			message::add('Failed to save - ' . $database->message['message'], 'negative');
			//
			//testing data
			// require_once "resources/header.php";
			// require_once "resources/persist_form_var.php";
			// echo "<div align='center'>\n";
			// persistformvar($_POST);
			// echo "</div>\n";
			// require_once "resources/footer.php";
			// exit();
			//
		} else {
			//notify user of success
			if ($action == 'add') {
				message::add($text['label-add-complete']);
			} elseif ($action == 'update'){
				if (!empty($database->message["details"][0]["code"]) && $database->message["details"][0]["code"] == "000") {
					message::add($text['label-no_changes'] ?? 'No Changes');
				} else {
					message::add($text['label-update-complete']);
				}
			}
		}

		//redirect to main list
		header("Location: school_bells.php");
		return;
	}  // !empty($_POST) && !$persistformvar

// pre-populate the form
	if (!empty($_GET) && !$persistformvar) {
		$school_bell_uuid = $_GET["id"];
		$sql = "SELECT * FROM v_school_bells";
		$sql .= " WHERE domain_uuid = :domain_uuid";
		$sql .= " AND school_bell_uuid = :school_bell_uuid";
		$sql .= " LIMIT 1";

		$parameters = array(
			'domain_uuid' => $domain_uuid,
			'school_bell_uuid' => $school_bell_uuid
		);

		$result = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);

		if (!empty($result)) {
			$row = $result[0];
			$school_bell_name = $row["school_bell_name"];
			$school_bell_leg_a_type = $row["school_bell_leg_a_type"];
			$school_bell_leg_a_data = $row["school_bell_leg_a_data"];
			$school_bell_leg_b_type = $row["school_bell_leg_b_type"];
			$school_bell_leg_b_data = $row["school_bell_leg_b_data"];
			$school_bell_ring_timeout = $row["school_bell_ring_timeout"];
			$school_bell_min = $row["school_bell_min"];
			$school_bell_hour = $row["school_bell_hour"];
			$school_bell_dom = $row["school_bell_dom"];
			$school_bell_mon = $row["school_bell_mon"];
			$school_bell_dow = $row["school_bell_dow"];
			$school_bell_timezone = $row["school_bell_timezone"];
			$school_bell_enabled = $row["school_bell_enabled"];
			$school_bell_description = $row["school_bell_description"];
		}
	}

// get the recordings
	$sql = "SELECT recording_name, recording_filename FROM v_recordings";
	$sql .= " WHERE domain_uuid = :domain_uuid";
	$sql .= " ORDER BY recording_name ASC";
	$parameters = array(':domain_uuid' => $domain_uuid);
	$recordings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

// get the phrases
// $sql = "SELECT * FROM v_phrases ";
// $sql .= " WHERE (domain_uuid = :domain_uuid OR domain_uuid IS NULL) ";
// $parameters = array(':domain_uuid' => $domain_uuid);
// $phrases = $database->select($sql, $parameters, 'all');
// unset($sql, $parameters);

// get the sound files
	$file = new file;
	$sound_files = $file->sounds();

//create the object
$school_bell_selector = new school_bell_selector($settings->get('domain', 'time_format', '12h'));

//One of defaults
if (strlen($school_bell_timezone) == 0) {
	$school_bell_timezone = date_default_timezone_get();
}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['header-school_bells'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-school_bells']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'school_bells.php']);
	if ($action == 'update' && permission_exists('school_bell_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','name'=>'action','value'=>'save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";
	echo $text['description-school_bells_schedule_templates'];
	echo "<br /> <br />\n";
//main_content
	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	// echo "<tr>\n";
	// if ($action == "add") {
	// 	echo "<td align='left' width='30%' nowrap='nowrap'><b>" . $text['label-school_bells-add'] . "</b></td>\n";
	// }
	// if ($action == "update") {
	// 	echo "<td align='left' width='30%' nowrap='nowrap'><b>" . $text['label-school_bells-edit'] . "</b></td>\n";
	// }
	// echo "</tr>\n";

//show name
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	" . $text['label-school_bell_name'] . "\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='school_bell_name' maxlength='255' value=\"" . escape($school_bell_name) . "\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-school_bell_name'] . "\n";
	echo "</td>\n";
	echo "</tr>\n";

//show school_bell_leg_a_data
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	" . $text['label-school_bell_leg_a_data'] . "\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='school_bell_leg_a_data' maxlength='255' value=\"" . escape($school_bell_leg_a_data) . "\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-school_bell_leg_a_data'] . "\n";
	echo "</td>\n";
	echo "</tr>\n";

//show ring timeout
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	" . $text['label-school_bell_ring_timeout'] . "\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='school_bell_ring_timeout' min='0' max='3600' value=\"" . escape($school_bell_ring_timeout) . "\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-school_bell_ring_timeout'] . "\n";
	echo "</td>\n";
	echo "</tr>\n";

//show school_bell_leg_b_data
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	" . $text['label-school_bell_leg_b_data'] . "\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='school_bell_leg_b_data' id='school_bell_leg_b_data' class='formfld'>\n";
	echo "	<option></option>\n";

//recordings
	$tmp_selected = false;
	if (is_array($recordings)) {
		echo "<optgroup label='Recordings'>\n";
		foreach ($recordings as $row) {
			$recording_name = $row["recording_name"];
			$recording_filename = $row["recording_filename"];
			if ($school_bell_leg_b_data == $settings->get('switch', 'recordings') . "/" . $_SESSION['domain_name'] . "/" . $recording_filename && strlen($school_bell_leg_b_data) > 0) {
				$tmp_selected = true;
				echo "	<option value='" . escape($settings->get('switch', 'recordings')) . "/" . escape($_SESSION['domain_name']) . "/" . escape($recording_filename) . "' selected='selected'>" . escape($recording_name) . "</option>\n";
			} else if ($school_bell_leg_b_data == $recording_filename && strlen($school_bell_leg_b_data) > 0) {
				$tmp_selected = true;
				echo "	<option value='" . escape($recording_filename) . "' selected='selected'>" . escape($recording_name) . "</option>\n";
			} else {
				echo "	<option value='" . escape($recording_filename) . "'>" . escape($recording_name) . "</option>\n";
			}
		}
		echo "</optgroup>\n";
	}

	if (permission_exists("recording_view")) {
		if (!$tmp_selected && strlen($school_bell_leg_b_data) > 0) {
			echo "<optgroup label='Selected'>\n";
			if (file_exists($settings->get('switch', 'recordings') . "/" . $_SESSION['domain_name'] . "/" . $school_bell_leg_b_data)) {
				echo "	<option value='" . escape($settings->get('switch', 'recordings')) . "/" . escape($_SESSION['domain_name']) . "/" . escape($school_bell_leg_b_data) . "' selected='selected'>" . escape($school_bell_leg_b_data) . "</option>\n";
			} else if (substr($school_bell_leg_b_data, -3) == "wav" || substr($school_bell_leg_b_data, -3) == "mp3") {
				echo "	<option value='" . escape($school_bell_leg_b_data) . "' selected='selected'>" . escape($school_bell_leg_b_data) . "</option>\n";
			} else {
				echo "	<option value='" . escape($school_bell_leg_b_data) . "' selected='selected'>" . escape($school_bell_leg_b_data) . "</option>\n";
			}
			echo "</optgroup>\n";
		}
		unset($tmp_selected);
	}

	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_" . escape($destination_id) . "' class='btn' name='' alt='back' onclick='changeToInput" . escape($destination_id) . "(document.getElementById(\"" . escape($destination_id) . "\"));this.style.visibility = \"hidden\";' value='&#9665;'>";
		unset($destination_id);
	}
	echo "	<br />\n";
	echo $text['description-school_bell_leg_b_data'] . "\n";
	echo "</td>\n";
	echo "</tr>\n";

//end school_bell_leg_b_data
//show divider
	echo "<tr><td colspan='2'><br /></td></tr>\n";

//show Schedule Time Picker
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	" . $text['label-school_bell_schedule'] . "\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<div id='school_bell_schedule_time_picker'></div>\n";
	echo "	<input type='hidden' name='school_bell_min' id='school_bell_min' value=\"" . escape($school_bell_min) . "\">\n";
	echo "	<input type='hidden' name='school_bell_hour' id='school_bell_hour' value=\"" . escape($school_bell_hour) . "\">\n";
	echo "	<input type='hidden' name='school_bell_dom' id='school_bell_dom' value=\"" . escape($school_bell_dom) . "\">\n";
	echo "	<input type='hidden' name='school_bell_mon' id='school_bell_mon' value=\"" . escape($school_bell_mon) . "\">\n";
	echo "	<input type='hidden' name='school_bell_dow' id='school_bell_dow' value=\"" . escape($school_bell_dow) . "\">\n";
	echo "	<script src='resources/javascript/school_bell_time_picker.js'></script>\n";
	echo "<br />\n";
	echo $text['description-school_bell_schedule'] . "\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

//show timezone
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	" . $text['label-school_bell_timezone'] . "\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='school_bell_timezone'>\n";
	foreach ($timezone_identifiers_list as $timezone_identifier) {
		echo "		<option value='" . escape($timezone_identifier) . "' " . (($school_bell_timezone == $timezone_identifier) ? "selected" : null) . ">" . escape($timezone_identifier) . "</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-school_bell_timezone'] . "\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

//show divider
	echo "<tr><td colspan='2'><br /></td></tr>\n";

//show enabled
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	" . $text['label-school_bell_enabled'] . "\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='school_bell_enabled' name='school_bell_enabled'>\n";
	echo "			<option value='true' ".($school_bell_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($school_bell_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	// echo "	<select class='formfld' name='school_bell_enabled'>\n";
	// echo "		<option value='true' " . (($school_bell_enabled == "true") ? "selected" : null) . ">" . $text['label-true'] . "</option>\n";
	// echo "		<option value='false' " . (($school_bell_enabled == "false") ? "selected" : null) . ">" . $text['label-false'] . "</option>\n";
	// echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-school_bell_enabled'] . "\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

//show description
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	" . $text['label-school_bell_description'] . "\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='school_bell_description' maxlength='255' value=\"" . escape($school_bell_description) . "\">\n";
	echo "<br />\n";
	echo $text['description-school_bell_description'] . "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "</div>\n";
	echo "<br /><br />\n";

	if (!empty($school_bell_uuid)) {
		echo "<input type='hidden' name='school_bell_uuid' value='".escape($school_bell_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";
