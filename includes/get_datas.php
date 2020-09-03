<?php

/**
 * Set default options, with random securitykey
 */
function jsondi_default_options()
{
	// defaults
	$defaults = array(
		'securitykey' => jsondi_random_securitykey(),
		'transient_timeout' => 1,
		'show_infos' => 'Y',
		'show_updates' => 'Y',
		'show_users' => 'Y',
		'show_comments' => 'Y',
		'show_sizes' => 'Y',
		'show_contents' => 'N',
		'code' => ''
	);
	update_option(JSONDI_OPTIONS, $defaults);
}

/**
 * Get datas, with cache
 * 
 * @global type $wpdb
 * @global type $table_prefix
 * @return type
 */
function jsondi_get_datas($reset = false)
{
	$options = get_option(JSONDI_OPTIONS);
	$now = current_time('timestamp', 0);
	$fileTimestamp = $now;
	$transient_timeout = $options['transient_timeout'] * HOUR_IN_SECONDS;
	$fileRegenerate = false;
	$dataRegenerate = true;
	if ($transient_timeout > 0) {
		$fileTimestamp = jsondi_file_gettime($options["securitykey"]);
		if ($fileTimestamp > 0) {
			if ($fileTimestamp + $transient_timeout < $now) {
				$fileRegenerate = true; // expired
			} else {
				$dataRegenerate = false; // non expired = get cache
			}
		} else {
			$fileRegenerate = true; // file not found
		}
	} else {
		jsondi_file_delete($options["securitykey"]); // delete file, clear cache
	}
	if ($dataRegenerate) {
		$datasRaw = jsondi_update_datas(); // get last data
	} else {
		$datasRaw = jsondi_file_read($options["securitykey"]);
	}
	if ($options['code'] != "") {
		$datas['code'] = $options['code'];
	}
	$datas['timestamps'] = array();
	$datas['datas'] = $datasRaw;
	if ($fileRegenerate == true) {
		// écrire le fichier avec $datas dedans;..
		jsondi_file_write($options["securitykey"], $datasRaw);
		$fileTimestamp = $now;
	}
	$diff = $now - $fileTimestamp;
	$next = $now + ($transient_timeout - $diff);

	if ($transient_timeout > 0) {
		$datas['timestamps'] = array(
			'now' => array(
				'unix' => $now,
				'mysql' => date('Y-m-d H:i:s', $now)
			),
			'cache' => array(
				'unix' => $fileTimestamp,
				'mysql' => date('Y-m-d H:i:s', $fileTimestamp)
			),
			'cache_age' => array(
				'unix' => $diff,
				'mysql' => jsondi_format_time($diff)
			),
			'cache_next' => array(
				'unix' => $next,
				'mysql' => date('Y-m-d H:i:s', $next)
			)
		);
	} else {
		$datas['timestamps'] = array(
			'now' => array(
				'unix' => $now,
				'mysql' => date('Y-m-d H:i:s', $now)
			),
			'cache' => null,
			'cache_age' => null,
			'cache_next' => null
		);
	}

	return $datas;
}

/**
 * Get updated datas, depending on options
 * 
 * @global type $wpdb
 * @global type $table_prefix
 */
function jsondi_update_datas()
{
	$options = get_option(JSONDI_OPTIONS);

	if ($options['show_infos'] == 'Y') {
		$datas['infos'] = jsondi_get_datas_infos();
	}

	if ($options['show_updates'] == 'Y') {
		$datas['updates'] = jsondi_get_datas_updates();
	}

	if ($options['show_comments'] == 'Y') {
		$datas['comments'] = jsondi_get_datas_comments();
	}
	if ($options['show_users'] == 'Y') {
		$datas['users'] = jsondi_get_datas_users();
	}
	if ($options['show_sizes'] == 'Y') {
		$datas['sizes'] = jsondi_get_datas_sizes();
	}

	if ($options['show_contents'] == 'Y') {
		$datas['contents'] = jsondi_get_datas_contents();
	}
	return $datas;
}

/**
 * Get datas "size"
 * 		$to_html == true  : show a table
 * 		$to_html == false : return data
 * 
 * @param type $to_html
 * @return type
 */
function jsondi_get_datas_sizes($to_html = false)
{
	$upload_dir = wp_upload_dir();


	$datas['upload'] = array(
		'size' => jsondi_get_datas_foldersize($upload_dir['basedir']),
		'human' => ''
	);
	$datas['themes'] = array(
		'size' => jsondi_get_datas_foldersize(get_theme_root()),
		'human' => ''
	);

	$plugin = jsondi_get_datas_foldersize(WP_PLUGIN_DIR) + jsondi_get_datas_foldersize(WPMU_PLUGIN_DIR);
	$datas['plugins'] = array(
		'size' => $plugin,
		'human' => ''
	);

	$datas['database'] = array(
		'size' => jsondi_get_datas_databaseSize(),
		'human' => ''
	);

	foreach ($datas as $key => $data) {
		$datas[$key]['human'] = jsondi_get_datas_human_size($datas[$key]['size']);
	}


	if (!$to_html) {
		return $datas;
	}

	print '<table class="jsondi-table jsondi-tablecompact">';
	foreach ($datas as $key => $data) {
		print '<tr>'
			. '<th>' . $key . '</th>'
			. '<td align="right">' . $data['size'] . '</td>'
			. '<td align="right">' . $data['human'] . '</td>'
			. '</tr>';
	}
	print '</table>';
}

/**
 * Get datas "update"
 * 		$to_html == true  : show a table
 * 		$to_html == false : return data
 * 
 * @param type $to_html
 * @return type
 */
function jsondi_get_datas_updates($to_html = false)
{
	$updates = wp_get_update_data();
	$datas = $updates['counts'];

	if (!$to_html) {
		return $datas;
	}

	print '<table class="jsondi-table jsondi-tablecompact">';
	foreach ($datas as $key => $data) {
		print '<tr><th>' . $key . '</th><td align="right">' . $data . '</td></tr>';
	}
	print '</table>';
}

/**
 * Get datas "users"
 * 		$to_html == true  : show a table
 * 		$to_html == false : return data
 * 
 * @param type $to_html
 * @return type
 */
function jsondi_get_datas_users($to_html = false)
{
	global $wpdb;
	global $table_prefix;

	$users_SQL = "
	SELECT
		max(user_registered) as last
	FROM " . $table_prefix . "users
	";
	$users = $wpdb->get_results($users_SQL);

	$datas = count_users();

	$datas['last_registered'] = array(
		'unix' => strtotime($users[0]->last),
		'mysql' => $users[0]->last,
		'human' => jsondi_human_time_diff(strtotime($users[0]->last))
	);


	if (!$to_html) {
		return $datas;
	}

	print '<table class="jsondi-table jsondi-tablecompact">';
	foreach ($datas['avail_roles'] as $key => $data) {
		print '<tr><th>' . $key . '</th><td align="right">' . $data . '</td></tr>';
	}
	print '<tr><td colspan="2">' . __("last registration:", "json-dashboard-infos") . ' ' . $datas['last_registered']['mysql'] . '</td></tr>';
	print '</table>';
}

/**
 * Get datas "comments"
 * 		$to_html == true  : show a table
 * 		$to_html == false : return data
 * 
 * @param type $to_html
 * @global type $wpdb
 * @global type $table_prefix
 * @return type
 */
function jsondi_get_datas_comments($to_html = false)
{
	global $wpdb;
	global $table_prefix;

	$comments_SQL = "
	SELECT
		comment_approved as type,
		count(comment_approved) as count,
		max(comment_date) as last

	FROM " . $table_prefix . "comments
	GROUP BY (comment_approved)
	";
	$comments = $wpdb->get_results($comments_SQL);
	$datas = array();
	foreach ($comments as $comment) {
		switch ($comment->type) {
			case '0':
				$type = 'waiting';
				break;
			case '1':
				$type = 'approuved';
				break;
			default:
				$type = $comment->type;
		}
		$c['count'] = $comment->count;
		$c['last_date'] = $comment->last;

		$c['last_date'] = array(
			'unix' => strtotime($comment->last),
			'mysql' => $comment->last,
			'human' => jsondi_human_time_diff(strtotime($comment->last))
		);


		$datas[$type] = $c;
	}

	$commentsValues = array('waiting', 'approuved', 'spam', 'trash');
	foreach ($commentsValues as $value) {
		if (!isset($datas[$value])) {
			$datas[$value]['count'] = 0;
			$datas[$value]['last_date'] = null;
		}
	}
	if (!$to_html) {
		return $datas;
	}
	print '<table class="jsondi-table jsondi-tablecompact">';
	foreach ($datas as $key => $data) {
		print '<tr><th>' . $key . '</th>'
			. '<td align="right">' . $data['count'] . '</td>'
			. '<td>' . $data['last_date'] . '</td>'
			. '</tr>';
	}
	print '</table>';
}

/**
 * Get datas "contents"
 * 		$to_html == true  : show a table
 * 		$to_html == false : return data
 * 
 * @param type $to_html
 * @global type $wpdb
 * @global type $table_prefix
 * @return type
 */
function jsondi_get_datas_contents($to_html = false)
{
	global $wpdb;
	global $table_prefix;

	$contents_SQL = "
	SELECT 
		post_type,
		post_status,
		count(*) as count,
		max(post_date) as last_date
	FROM " . $table_prefix . "posts
	GROUP BY post_type, post_status
	";
	$contents = $wpdb->get_results($contents_SQL);
	$datas = array();
	foreach ($contents as $content) {
		$datas[$content->post_type][$content->post_status] = array(
			'count' => $content->count,
			'last_date' => $content->last_date,
			'last_date' => array(
				'unix' => strtotime($content->last_date),
				'mysql' => $content->last_date,
				'human' => jsondi_human_time_diff(strtotime($content->last_date))
			)
		);
	}
	if (!$to_html) {
		return $datas;
	}

	print '<table class="jsondi-table jsondi-tablecompact">';
	foreach ($datas as $key => $data) {
		print '<tr><th rowspan="' . sizeof($data) . '">' . $key . '</th>';
		$nb = 0;
		foreach ($data as $k => $d) {
			if ($nb > 0) {
				print '<tr>';
			}
			$nb++;
			print '<th>' . $k . '</th>'
				. '<td align="right">' . $d['count'] . '</td>'
				. '<td>' . $d['last_date']['mysql'] . '</td>'
				. '<td>' . human_time_diff($d['last_date']['unix']) . '</td>'
				. '</tr>';
		}
	}
	print '</table>';
}

/**
 * Get datas "infos"
 * 		$to_html == true  : show a table
 * 		$to_html == false : return data
 * 
 * @param type $to_html
 * @return type
 */
function jsondi_get_datas_infos($to_html = false)
{
	$datas = array(
		"name" => get_bloginfo('name'),
		"favicon" => get_site_icon_url(100),
		"url" => get_home_url(),
		"url_admin" => get_admin_url()
	);
	if (!$to_html) {
		return $datas;
	}

	print '<table class="jsondi-table jsondi-tablecompact">';
	foreach ($datas as $key => $data) {
		print '<tr><th>' . $key . '</th><td>' . $data . '</td></tr>';
	}
	print '</table>';
}
