<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

add_action( 'elementor_pro/init', function() {
	// Here its safe to include our action class file
	include_once( dirname(__FILE__).'/includes/class-brevo-integration-action.php' );
	include_once( dirname(__FILE__).'/includes/class-brevo-integration-unsubscribe-action.php' );

	// Instantiate the action class
	$brevo_integration_action = new brevo_Integration_Action_After_Submit();
	$brevo_integration_unsubscribe_action = new brevo_Integration_Unsubscribe_Action_After_Submit();

	// Register the action with form widget
	\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $brevo_integration_action->get_name(), $brevo_integration_action );
	\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $brevo_integration_unsubscribe_action->get_name(), $brevo_integration_unsubscribe_action );
});