<?php

/**
 * 
 * @param type $key
 * @return type
 */
function jsondi_file_get_name($key) {
	$wpuploaddir = wp_upload_dir();
	return $wpuploaddir["basedir"] . "/jsonid_" . $key . ".json";
}

/**
 * 
 * @param type $key
 * @return type
 */
function jsondi_file_read($key) {
	$file = jsondi_file_get_name($key);
	if (file_exists($file)) {
		return json_decode(file_get_contents($file));
	} else {
		return null;
	}
}

/**
 * 
 * @param type $key
 * @return type
 */
function jsondi_file_delete($key) {
	$file = jsondi_file_get_name($key);
	if (file_exists($file)) {
		unlink($file);
	}
}

/**
 * 
 * @param type $key
 * @param type $data
 */
function jsondi_file_write($key, $data) {
	$file = jsondi_file_get_name($key);
//
//	dump($key);
//	dump($file);
//	dump($data);

	try {
		$fp = fopen($file, 'w+');
		fwrite($fp, json_encode($data));
		fclose($fp);
	} catch (Exception $exc) {
		echo $exc->getTraceAsString();
		exit;
	}
}

/**
 * Get timestamp of file, with gmt_offset correction...
 * 
 * @param type $key
 * @return type
 */
function jsondi_file_gettime($key) {
	$file = jsondi_file_get_name($key);
	if (file_exists($file)) {
		$timestamp = filectime($file) + (get_option('gmt_offset') * HOUR_IN_SECONDS);
		return $timestamp;
	} else {
		return 0;
	}
}

/**
 * Get wordpress database size
 * 
 * @global type $wpdb
 * @return type
 */
function jsondi_get_datas_databaseSize() {
	global $wpdb;
	$dbsize = 0;

	$rows = $wpdb->get_results("SHOW table STATUS LIKE '" . $wpdb->prefix . "%'");

	foreach ($rows as $row) {
		// print '<pre>'; print_r($row); print '</pre>';
		$dbsize += $row->Data_length + $row->Index_length;
	}
	return $dbsize;
}

/**
 * Calculate folder size, including sub-folders
 * 
 * @param type $directory
 * @return type
 */
function jsondi_get_datas_foldersize($directory) {
	$totalSize = 0;
	if (is_dir($directory)) {
		$directoryArray = scandir($directory);

		foreach ($directoryArray as $key => $fileName) {
			if ($fileName != ".." && $fileName != ".") {
				if (is_dir($directory . "/" . $fileName)) {
					$totalSize = $totalSize + jsondi_get_datas_foldersize($directory . "/" . $fileName);
				} else if (is_file($directory . "/" . $fileName)) {
					$totalSize = $totalSize + filesize($directory . "/" . $fileName);
				}
			}
		}
	}
	return $totalSize;
}

/**
 * Presents the size in a human-readable format
 * 
 * @param type $bytes
 * @param type $decimals
 * @return type
 */
function jsondi_get_datas_human_size($bytes, $decimals = 2) {
	$factor = floor((strlen($bytes) - 1) / 3);
	if ($factor > 0) {
		$sz = 'KMGT';
	}
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$sz[$factor - 1] . 'B';
}

/**
 * Format time
 * 
 * @param type $time
 * @return type
 */
function jsondi_format_time($time) {
	return sprintf("%02d:%02d:%02d", floor($time / 3600), ($time / 60) % 60, $time % 60);
}

/**
 * Create 2 radio-buttons, for yes and for no
 * 
 * @param type $name
 * @param type $value
 */
function jsondi_radio_yesno($name, $value) {
	?>
	<label>
		<input type="radio" name="<?= $name; ?>" value="Y" <?= ($value == 'Y' ? 'checked' : ''); ?>>
		<?php _e("Yes", "json-dashboard-infos"); ?>
	</label>
	<br>
	<label>
		<input type="radio" name="<?= $name; ?>" value="N" <?= ($value == 'N' ? 'checked' : ''); ?>>
		<?php _e("No", "json-dashboard-infos"); ?>
	</label>

	<?php
}

/**
 * Security Key...
 * 
 * @return type
 */
function jsondi_random_securitykey() {
	return md5(uniqid());
}

/**
 * Determines the difference between two timestamps.
 *
 * The difference is returned in a human readable format such as 
 * "1 hour", "5 mins", "2 days"
 * 
 * IN SHORT FORMAT, and always in english...
 * 
 * 
 * @param type $from
 * @param type $to
 * @return type
 */
function jsondi_human_time_diff($from, $to = 0) {
	if (empty($to)) {
		$to = time();
	}
	$diff = (int) abs($to - $from);

	if ($diff < MINUTE_IN_SECONDS) {
		$secs = $diff;
		if ($secs <= 1) {
			$secs = 1;
		}
		$since = sprintf('%s sec', $secs);
	} elseif ($diff < HOUR_IN_SECONDS && $diff >= MINUTE_IN_SECONDS) {
		$mins = round($diff / MINUTE_IN_SECONDS);
		if ($mins <= 1) {
			$mins = 1;
		}
		$since = sprintf('%s min', $mins) . ($mins > 1 ? 's' : '');
	} elseif ($diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS) {
		$hours = round($diff / HOUR_IN_SECONDS);
		if ($hours <= 1) {
			$hours = 1;
		}
		$since = sprintf('%s hour', $hours) . ($hours > 1 ? 's' : '');
	} elseif ($diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS) {
		$days = round($diff / DAY_IN_SECONDS);
		if ($days <= 1) {
			$days = 1;
		}
		$since = sprintf('%s day', $days) . ($days > 1 ? 's' : '');
	} elseif ($diff < MONTH_IN_SECONDS && $diff >= WEEK_IN_SECONDS) {
		$weeks = round($diff / WEEK_IN_SECONDS);
		if ($weeks <= 1) {
			$weeks = 1;
		}
		$since = sprintf('%s week', $weeks) . ($weeks > 1 ? 's' : '');
	} elseif ($diff < YEAR_IN_SECONDS && $diff >= MONTH_IN_SECONDS) {
		$months = round($diff / MONTH_IN_SECONDS);
		if ($months <= 1) {
			$months = 1;
		}
		$since = sprintf('%s month', $months) . ($months > 1 ? 's' : '');
	} elseif ($diff >= YEAR_IN_SECONDS) {
		$years = round($diff / YEAR_IN_SECONDS);
		if ($years <= 1) {
			$years = 1;
		}
		$since = sprintf('%s year', $years) . ($years > 1 ? 's' : '');
	}
	return $since;
}
