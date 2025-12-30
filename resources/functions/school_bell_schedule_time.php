<?php

/**
 * Formats school bell schedule time for display in cron format.
 * @access public
 * @param array $row Database row containing schedule fields
 * @param string $output_format Output format type (minutes, hours, seconds, timestamp)
 * @return string Formatted schedule time string
 */
function school_bell_schedule_time($row, $output_format = 'minutes') {
	// Validate input is an array
	if (!is_array($row)) {
		return '';
	}
	
	// Sanitize output format to prevent injection
	$valid_formats = array('minutes', 'hours', 'seconds', 'timestamp', 'cron');
	$output_format = in_array($output_format, $valid_formats, true) ? $output_format : 'minutes';
	
	// Extract and sanitize values from row
	$min = isset($row['school_bell_min']) ? intval($row['school_bell_min']) : -1;
	$hour = isset($row['school_bell_hour']) ? intval($row['school_bell_hour']) : -1;
	$dom = isset($row['school_bell_dom']) ? intval($row['school_bell_dom']) : -1;
	$mon = isset($row['school_bell_mon']) ? intval($row['school_bell_mon']) : -1;
	$dow = isset($row['school_bell_dow']) ? intval($row['school_bell_dow']) : -1;
	
	// Validate ranges to prevent malicious values
	if ($min < -1 || $min > 59) {
		$min = -1;
	}
	if ($hour < -1 || $hour > 23) {
		$hour = -1;
	}
	if ($dom < -1 || $dom > 31) {
		$dom = -1;
	}
	if ($mon < -1 || $mon > 12) {
		$mon = -1;
	}
	if ($dow < -1 || $dow > 6) {
		$dow = -1;
	}
	
	// Format based on output type
	switch ($output_format) {
		case 'cron':
			// Full cron format: min hour dom mon dow
			$schedule_time = ($min == -1) ? '* ' : $min . ' ';
			$schedule_time .= ($hour == -1) ? '* ' : $hour . ' ';
			$schedule_time .= ($dom == -1) ? '* ' : $dom . ' ';
			$schedule_time .= ($mon == -1) ? '* ' : $mon . ' ';
			$schedule_time .= ($dow == -1) ? '*' : $dow;
			break;
			
		case 'minutes':
			// Default format for minute-based scheduling
			$schedule_time = ($min == -1) ? '* ' : $min . ' ';
			$schedule_time .= ($hour == -1) ? '* ' : $hour . ' ';
			$schedule_time .= ($dom == -1) ? '* ' : $dom . ' ';
			$schedule_time .= ($mon == -1) ? '* ' : $mon . ' ';
			$schedule_time .= ($dow == -1) ? '*' : $dow;
			break;
			
		case 'hours':
			// Hour-based format (minutes are wildcarded if not specified)
			$schedule_time = ($min == -1) ? '00 ' : $min . ' ';
			$schedule_time .= ($hour == -1) ? '* ' : $hour . ' ';
			$schedule_time .= ($dom == -1) ? '* ' : $dom . ' ';
			$schedule_time .= ($mon == -1) ? '* ' : $mon . ' ';
			$schedule_time .= ($dow == -1) ? '*' : $dow;
			break;
			
		case 'seconds':
			// Seconds format (adds 00 seconds prefix)
			$schedule_time = '00 ';
			$schedule_time .= ($min == -1) ? '* ' : $min . ' ';
			$schedule_time .= ($hour == -1) ? '* ' : $hour . ' ';
			$schedule_time .= ($dom == -1) ? '* ' : $dom . ' ';
			$schedule_time .= ($mon == -1) ? '* ' : $mon . ' ';
			$schedule_time .= ($dow == -1) ? '*' : $dow;
			break;
			
		case 'timestamp':
			// Timestamp format (Unix timestamp - only if all fields are specific)
			if ($min != -1 && $hour != -1 && $dom != -1 && $mon != -1) {
				$year = date('Y');
				$schedule_time = mktime(intval($hour), intval($min), 0, intval($mon), intval($dom), intval($year));
			} else {
				// Fallback to cron format if not all fields are specified
				$schedule_time = ($min == -1) ? '* ' : $min . ' ';
				$schedule_time .= ($hour == -1) ? '* ' : $hour . ' ';
				$schedule_time .= ($dom == -1) ? '* ' : $dom . ' ';
				$schedule_time .= ($mon == -1) ? '* ' : $mon . ' ';
				$schedule_time .= ($dow == -1) ? '*' : $dow;
			}
			break;
			
		default:
			// Default to minutes format
			$schedule_time = ($min == -1) ? '* ' : $min . ' ';
			$schedule_time .= ($hour == -1) ? '* ' : $hour . ' ';
			$schedule_time .= ($dom == -1) ? '* ' : $dom . ' ';
			$schedule_time .= ($mon == -1) ? '* ' : $mon . ' ';
			$schedule_time .= ($dow == -1) ? '*' : $dow;
			break;
	}
	
	return escape(trim($schedule_time));
}

?>
