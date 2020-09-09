<?php

/**
 * Author: Richard Carlier
 * Author URI: https://carlier.biz
 *
 * Plugin Name: JSON Dashboard Infos
 * Plugin URI: https://github.com/rcarlier/json-dashboard-infos
 * Version: 1.0.2
 *
 * Description: Get infos from your wordpress, in JSON format, to create centralized dashboards
 *
 * Text Domain: json-dashboard-infos
 * Domain Path: /languages
 *
 * License: GPL v3
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Constants
 */
define('JSONDI_ROUTE', 'jsondi-api-v1');
define('JSONDI_OPTIONS', 'jsondi');
define('JSONDI_TITLE', 'JSON Dashboard Infos');
define('JSONDI_TRANSIENT', 'jsondi_cache_system');
define('JSONDI_PLUGIN_URI', 'https://github.com/rcarlier/json-dashboard-infos');
define('JSONDI_PLUGIN_PATH', plugin_dir_path(__FILE__));

define('JSONDI_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Security...
 */
if (!defined('ABSPATH')) {
	die(JSONDI_TITLE . '<br>' . JSONDI_PLUGIN_URI);
}

/**
 * Includes
 */
require_once( JSONDI_PLUGIN_PATH . 'includes/tools.php' );
require_once( JSONDI_PLUGIN_PATH . 'includes/get_datas.php' );
require_once( JSONDI_PLUGIN_PATH . 'includes/rest_api.php' );
require_once( JSONDI_PLUGIN_PATH . 'includes/admin.php' );

/**
 * Hook, action, filters...
 */
register_activation_hook(__FILE__, 'jsondi_activation');
add_action('plugins_loaded', 'jsondi_load_plugin_textdomain');
add_filter('plugin_action_links', 'jsondi_plugin_action_links', 10, 2);
add_filter('query_vars', 'jsondi_add_query_vars');
add_filter('init', 'jsondi_add_rewrite_rules');
add_action('template_redirect', 'jsondi_get_my_vars');

if (is_admin()) {
	add_action('admin_menu', 'jsondi_menu');
	add_action('admin_enqueue_scripts', 'jsondi_admin_css', 10);
}

/**
 * Load translations
 */
function jsondi_load_plugin_textdomain() {
	load_plugin_textdomain('json-dashboard-infos', FALSE, basename(dirname(__FILE__)) . '/languages/');
}

/**
 * Add menu
 */
function jsondi_menu() {
	add_options_page(JSONDI_TITLE, JSONDI_TITLE, 'manage_options', __FILE__, 'jsondi_options_page');
}

/**
 * Add link to settings in "wp-admin/plugins.php" page
 *
 * @param type $links
 * @param type $file
 * @return type
 */
function jsondi_plugin_action_links($links, $file) {
	$chemin = plugin_basename(__FILE__);
	if ($file == $chemin && current_user_can('manage_options')) {
		$sbs_links = '<a href="' . get_admin_url() . 'options-general.php?page=' . $chemin . '">'
				. esc_html__('Settings') . '</a>';

		array_unshift($links, $sbs_links);
	}
	return $links;
}
