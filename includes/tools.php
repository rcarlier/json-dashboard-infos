<?php

/**
 * Get wordpress database size
 * 
 * @global type $wpdb
 * @return type
 */
function jsondi_get_datas_databaseSize()
{
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
function jsondi_get_datas_foldersize($directory)
{
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
function jsondi_get_datas_human_size($bytes, $decimals = 2)
{
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
function jsondi_format_time($time)
{
	return sprintf("%02d:%02d:%02d", floor($time / 3600), ($time / 60) % 60, $time % 60);
}



/**
 * Create 2 radio-buttons, for yes and for no
 * 
 * @param type $name
 * @param type $value
 */
function jsondi_radio_yesno($name, $value)
{
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
function jsondi_random_securitykey()
{
	return md5(uniqid());
}
