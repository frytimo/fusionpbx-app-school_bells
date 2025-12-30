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

//includes files
	require_once dirname($_SERVER["SCRIPT_FILENAME"], 3) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";
	require_once "resources/functions/school_bell_schedule_time.php";

//check permissions
	if (!permission_exists('school_bell_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text();
	$text = $language->get();

//declare globals
	global $database, $settings, $domain_uuid, $user_uuid;
	$domain_uuid = $domain_uuid ?? $_SESSION['domain_uuid'] ?? '';
	$user_uuid = $user_uuid ?? $_SESSION['user_uuid'] ?? '';
	if (!$database) {
		$database = database::new();
	}
	if (!$settings) {
		$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);
	}

//get the http POST and GET data
	$action = $_REQUEST['action'] ?? '';
	$search = $_REQUEST['search'] ?? '';
	$school_bells = $_REQUEST['school_bells'] ?? [];

//get variables used to control the order
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//process the http post data by action
	if (!empty($action) && !empty($school_bells)) {
		switch ($action) {
			case 'copy':
				if (permission_exists('school_bell_add')) {
					$obj = new school_bells;
					$obj->copy($school_bells);
				}
				break;
			case 'toggle':
				if (permission_exists('school_bell_edit')) {
					$obj = new school_bells;
					$obj->toggle($school_bells);
				}
				break;
			case 'delete':
				if (permission_exists('school_bell_delete')) {
					$obj = new school_bells;
					$obj->delete($school_bells);
				}
				break;
		}

		header('Location: school_bells.php'.(!empty($search) ? '?search='.urlencode($search) : ''));
		exit;
	}

//sanitize order_by and order to prevent SQL injection
	$order_by = database::sanitize($order_by);
	$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

//prepare to page the results
	$sql = "SELECT count(school_bell_uuid) AS num_rows FROM v_school_bells";
	$sql .= " WHERE domain_uuid = :domain_uuid";
	$parameters['domain_uuid'] = $domain_uuid;
	$num_rows = intval($database->select($sql, $parameters, 'column'));
	unset($sql, $parameters);
	$rows_per_page = $settings->get('domain', 'paging', 50);
	$param = "";
	$page = intval($_GET['page'] ?? 0);
	$list = paging($num_rows, $param, $rows_per_page);
	$paging_controls = $list[0];
	$rows_per_page = $list[1] ?? '';
	$var3 = $list[2] ?? '';
	$offset = $rows_per_page * $page;

//get the list
	if ($num_rows > 0) {
		$sql = "SELECT * FROM v_school_bells";
		$sql .= " WHERE domain_uuid = :domain_uuid";
		$parameters['domain_uuid'] = $domain_uuid;
		if (strlen($order_by) > 0) {
			$sql .= " ORDER BY " . $order_by . " " . $order;
		}
		$sql .= " LIMIT :limit OFFSET :offset";
		$parameters['limit'] = intval($rows_per_page);
		$parameters['offset'] = intval($offset);
		$query_result = $database->select($sql, $parameters, 'all');
		if (!empty($query_result)) {
			$school_bells = $query_result;
		}
		unset($sql, $parameters);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-school_bells'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-school_bells']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('school_bell_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','link'=>'school_bell_edit.php']);
	}
	if (permission_exists('school_bell_add') && $school_bells) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('school_bell_edit') && $school_bells) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('school_bell_delete') && $school_bells) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('school_bell_all')) {
		if (isset($show) && $show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$settings->get('theme', 'button_icon_all'),'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
	// if ($paging_controls_mini != '') {
	// 	echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	// }
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n"; //action_bar

//modal dialog boxes
	if (permission_exists('school_bell_add') && $school_bells) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('school_bell_edit') && $school_bells) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('school_bell_delete') && $school_bells) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-school_bells']."\n";
	echo "<br /><br />\n";

//card list
	echo "<div class='card'>\n";
	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('school_bell_add') || permission_exists('school_bell_edit') || permission_exists('school_bell_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($school_bells) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	echo th_order_by('school_bell_name', $text['label-school_bell_name'], $order_by, $order);
	echo th_order_by('school_bell_leg_a_data', $text['label-school_bell_leg_a_data'], $order_by, $order);
	echo th_order_by('school_bell_leg_b_data', $text['label-school_bell_leg_b_data'], $order_by, $order);
	echo "<th>" . $text['label-school_bell_schedule_time'] . "</th>\n";
	echo th_order_by('school_bell_enabled', $text['label-school_bell_enabled'], $order_by, $order);
	echo th_order_by('school_bell_description', $text['label-school_bell_description'], $order_by, $order);
	echo "</tr>\n";
	if (!empty($school_bells)) {
		$x = 0;
		foreach ($school_bells as $row) {
			$row = array_map('escape', $row);

			$tr_link = (permission_exists('school_bell_edit')) ? " href='school_bell_edit.php?id=" . $row['school_bell_uuid'] . "'" : null;
			echo "<tr class='list-row' $tr_link>\n";
			if (permission_exists('school_bell_add') || permission_exists('school_bell_edit') || permission_exists('school_bell_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='school_bells[$x][checked]' id='checkbox_$x' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='school_bells[$x][uuid]' value='".escape($row['school_bell_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td valign='top' class='" . $row_style[$c] . "'>" . $row['school_bell_name'] . "&nbsp;</td>\n";
			echo "	<td valign='top' class='" . $row_style[$c] . "'>" . $row['school_bell_leg_a_data'] . "&nbsp;</td>\n";
			echo "	<td valign='top' class='" . $row_style[$c] . "'>" . $row['school_bell_leg_b_data'] . "&nbsp;</td>\n";

			echo "	<td valign='top' class='" . $row_style[$c] . "'>" . school_bell_schedule_time($row, 'minutes') . "&nbsp;</td>\n";

			echo "	<td valign='top' class='" . $row_style[$c] . "'>" . ($row['school_bell_enabled'] ? $text['label-true'] : $text['label-false']) . "&nbsp;</td>\n";
			echo "	<td valign='top' class='" . $row_style[$c] . "'>" . $row['school_bell_description'] . "&nbsp;</td>\n";

			echo "</tr>\n";
			$x++;
		}  // end foreach
		unset($school_bells);
	}  // end if results
	echo "</table>\n";
//paging controls
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";
	echo "</div>\n";

//include the footer
	require_once "resources/footer.php";
