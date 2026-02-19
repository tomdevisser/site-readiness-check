<?php
/**
 * Plugin Name: Site Readiness Check
 * Description: A plugin that checks the readiness of a site.
 * Version: 1.0
 * Author: Tom de Visser
 * Author URI: https://tomdevisser.dev
 * Text Domain: src
 *
 * @package Site_Readiness_Check
 */

defined( 'ABSPATH' ) || exit;

define( 'SRC_VERSION', '1.0' );
define( 'SRC_PLUGIN_FILE', __FILE__ );
define( 'SRC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SRC_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
define( 'SRC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once SRC_PLUGIN_DIR . 'includes/settings.php';
require_once SRC_PLUGIN_DIR . 'includes/checks.php';
require_once SRC_PLUGIN_DIR . 'includes/dashboard.php';
require_once SRC_PLUGIN_DIR . 'includes/admin-page.php';
