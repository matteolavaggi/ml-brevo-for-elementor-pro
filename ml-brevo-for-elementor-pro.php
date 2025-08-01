<?php

/**
 * Plugin Name: ML Brevo for Elementor Pro
 * Description: Integrates Elementor forms with Brevo API. Now supports ALL your Brevo contact attributes with dynamic field mapping! Multilingual support: Italian, French, German, Spanish.
 * Version: 2.2.0
 * Author: Matteo Lavaggi	
 * Author URI: https://matteolavaggi.it/
 * Text Domain: ml-brevo-for-elementor-pro
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

// Plugin constants
define( 'BREVO_ELEMENTOR_VERSION', '2.2.0' );
define( 'BREVO_ELEMENTOR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BREVO_ELEMENTOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Text domain is automatically loaded by WordPress for plugins hosted on WordPress.org

//load plugins functionallity and settings
require dirname(__FILE__).'/includes/class-brevo-attributes-manager.php';
require dirname(__FILE__).'/includes/class-brevo-debug-logger.php';
require dirname(__FILE__).'/includes/class-translation-compiler.php';
require dirname(__FILE__).'/includes/debug-viewer.php';
require dirname(__FILE__).'/init-brevo-integration-action.php';
require dirname(__FILE__).'/includes/settings.php';

//Check if Elementor pro is installed
function brevo_integration_check_elementor_pro_is_active() {

	if ( !is_plugin_active('elementor-pro/elementor-pro.php') ) {
		echo "<div class='error'><p><strong>ML Brevo for Elementor Pro</strong> requires <strong> Elementor Pro plugin to be installed and activated</strong> </p></div>";
	}
}
add_action('admin_notices', 'brevo_integration_check_elementor_pro_is_active');