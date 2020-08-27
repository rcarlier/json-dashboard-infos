<?php


/**
 * On activation, update permalinks
 */
function jsondi_activation()
{
	jsondi_default_options();
	jsondi_add_rewrite_rules(null);
	flush_rewrite_rules();
}

/**
 * Add new query var, to accept the JSONDI_ROUTE parameter
 * 
 * @param array $vars
 * @return type
 */
function jsondi_add_query_vars($vars)
{
	$vars[] = JSONDI_ROUTE;
	return $vars;
}


/**
 * Add new permalink rules 
 * 
 * @param type $rules
 */
function jsondi_add_rewrite_rules($rules)
{
	add_rewrite_rule(
		JSONDI_ROUTE . '/([^/]+)/?$',
		'index.php?' . JSONDI_ROUTE . '=$matches[1]',
		"top"
	);
}


/**
 * Analyse query var, and verify security code
 * 
 * @global type $wp_query
 */
function jsondi_get_my_vars()
{
	global $wp_query;
	if (isset($wp_query->query_vars[JSONDI_ROUTE])) {
		$route = get_query_var(JSONDI_ROUTE);
		$options = get_option(JSONDI_OPTIONS);
		if ($options['securitykey'] == $route) {
			jsondi_show_datas();
			die();
		} else {
			jsondi_securitykey_error();
		}
	}
}


/**
 * show datas...
 */
function jsondi_show_datas()
{
	$datas = jsondi_get_datas();
	header("content-type: application/json; charset=utf-8");
	header('Access-Control-Allow-Origin: *');
	print(json_encode($datas));
}

/**
 * Redirect to 404 (if securitykey is wrongà
 */
function jsondi_securitykey_error()
{
	global $wp_query;
	$wp_query->set_404();
	status_header(404);
	nocache_headers();
}
