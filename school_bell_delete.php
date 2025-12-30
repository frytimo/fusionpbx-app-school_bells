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
 */

// includes files
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";

// check permissions
if (permission_exists('school_bell_delete')) {
	// access granted
} else {
	echo "access denied";
	exit;
}

// add multi-lingual support
$language = new text;
$text = $language->get();

// declare globals
global $database, $settings, $domain_uuid, $user_uuid;
if (!$database) {
	$database = database::new();
}
if (!$settings) {
	$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid ?? $_SESSION['domain_uuid'] ?? '', 'user_uuid' => $user_uuid ?? $_SESSION['user_uuid'] ?? '']);
}

// delete the data
if (isset($_GET["id"]) && is_uuid($_GET["id"])) {
	// get the id
	$school_bell_uuid = $_GET["id"];

	// delete school bell - with domain_uuid check for security
	$sql = "DELETE FROM v_school_bells ";
	$sql .= "WHERE school_bell_uuid = :school_bell_uuid ";
	$sql .= "AND domain_uuid = :domain_uuid";

	$parameters = array(
		':school_bell_uuid' => $school_bell_uuid,
		':domain_uuid' => $domain_uuid
	);

	$database->execute($sql, $parameters);
	unset($sql, $parameters);

	// set message
	message::add($text['message-delete']);

	// redirect the user
	header('Location: school_bells.php');
	exit;
}
