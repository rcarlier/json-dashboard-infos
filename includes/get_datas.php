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
		'show_contents' => 'Y'
	);
	update_option(JSONDI_OPTIONS, $defaults);
}

/**
 * Update the transient
 */
function jsondi_update_transient()
{
	$options = get_option(JSONDI_OPTIONS);
	$transient_timeout = $options['transient_timeout'] * HOUR_IN_SECONDS;
	$datasRaw = jsondi_update_datas();
	$datasRaw['cache'] = current_time('timestamp', 0);
	set_transient(JSONDI_TRANSIENT, $datasRaw, $transient_timeout);
}

/**
 * Get datas, with transient
 * 
 * @global type $wpdb
 * @global type $table_prefix
 * @return type
 */
function jsondi_get_datas($reset = false)
{

	$options = get_option(JSONDI_OPTIONS);

	$now = current_time('timestamp', 0);
	$cache = $now;

	$transient_timeout = $options['transient_timeout'] * HOUR_IN_SECONDS;

	if ($transient_timeout > 0) {
		// récupère les données
		$datasRaw = get_transient(JSONDI_TRANSIENT);

		// dans les données, on récupère le timestamp du cache
		if (isset($datasRaw['cache'])) {
			$cache = $datasRaw['cache'];
		}
		// need update ?
		if (false === $datasRaw || empty($datasRaw)) {
			jsondi_update_transient();
		}

		if (isset($datasRaw['cache'])) {
			unset($datasRaw['cache']);
		}
	} else {
		// données fraiches
		$datasRaw = jsondi_update_datas();
	}

	$diff = $now - $cache;
	$next = $now + ($transient_timeout - $diff);

	$datas['timestamps'] = array(
		'now' => array(
			'unix' => $now,
			'mysql' => date('Y-m-d H:i:s', $now)
		),
		'cache' => array(
			'unix' => $cache,
			'mysql' => date('Y-m-d H:i:s', $cache)
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

	$datas['datas'] = $datasRaw;
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
	global $wpdb;
	global $table_prefix;

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
		'sizeHuman' => ''
	);
	$datas['themes'] = array(
		'size' => jsondi_get_datas_foldersize(get_theme_root()),
		'sizeHuman' => ''
	);
	$datas['plugins'] = array(
		'size' => jsondi_get_datas_foldersize(WP_PLUGIN_DIR),
		'sizeHuman' => ''
	);
	$datas['mu-plugins'] = array(
		'size' => jsondi_get_datas_foldersize(WPMU_PLUGIN_DIR),
		'sizeHuman' => ''
	);
	$datas['database'] = array(
		'size' => jsondi_get_datas_databaseSize(),
		'sizeHuman' => ''
	);

	foreach ($datas as $key => $data) {
		$datas[$key]['sizeHuman'] = jsondi_get_datas_human_size($datas[$key]['size']);
	}


	if (!$to_html) {
		return $datas;
	}

	print '<table class="jsondi-table jsondi-tablecompact">';
	foreach ($datas as $key => $data) {
		print '<tr>'
			. '<th>' . $key . '</th>'
			. '<td align="right">' . $data['size'] . '</td>'
			. '<td align="right">' . $data['sizeHuman'] . '</td>'
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
	$datas['last_registered'] = $users[0]->last;

	if (!$to_html) {
		return $datas;
	}

	print '<table class="jsondi-table jsondi-tablecompact">';
	foreach ($datas['avail_roles'] as $key => $data) {
		print '<tr><th>' . $key . '</th><td align="right">' . $data . '</td></tr>';
	}
	print '<tr><td colspan="2">' . __("last registration:", "json-dashboard-infos") . ' ' . $datas['last_registered'] . '</td></tr>';
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
			'last_date' => $content->last_date
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
				. '<td>' . $d['last_date'] . '</td>'
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
		"favicon" => get_site_icon_url(),
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
