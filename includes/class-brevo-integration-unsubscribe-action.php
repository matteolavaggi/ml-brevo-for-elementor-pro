<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class brevo_Integration_Unsubscribe_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'brevo unsubscribe integration';
	}

	/**
	 * Get Label
	 *
	 * Returns the action label
	 *
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return __( 'brevo Unsubscribe', 'brevo-elementor-integration' );
	}

	/**
	 * Register Settings Section
	 *
	 * Registers the Action controls
	 *
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_brevo_unsubscribe-elementor-integration',
			[
				'label' => __( 'brevo Unsubscribe', 'brevo-elementor-integration' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'unsubscribe_note_alert_delete',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('<b>PLEASE NOTE - THIS ACTION DELETES THE INPUT EMAIL IN brevo!</b>', 'brevo-elementor-integration'),
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_use_global_api_key',
			[
				'label' => __( 'Global brevo API key', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_use_global_api_key_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('You can set your global API key <a href="' . admin_url( 'options-general.php?page=ml-brevo-free' ) . '" target="_blank">here.</a> this means you only need to set your brevo API key once.', 'brevo-elementor-integration'),
				'condition' => array(
					'brevo_use_global_api_key' => 'yes',
    			),
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_api',
			[
				'label' => __( 'brevo API key', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'xkeysib-xxxxxxxx',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter your V3 API key from brevo', 'brevo-elementor-integration' ),
				'condition' => array(
					'brevo_use_global_api_key!' => 'yes',
    			),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_gdpr_checkbox',
			[
				'label' => __( 'GDPR Checkbox', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_gdpr_checkbox_field',
			[
				'label' => __( 'Acceptance Field ID', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'acceptancefieldid',
				'separator' => 'before',
				'description' => __( 'Enter the acceptance checkbox field id - you can find this under the acceptance field advanced tab - if the acceptance checkbox is not checked then the email and extra information will not be added to the list', 'brevo-elementor-integration' ),
    			'condition' => array(
    				'brevo_gdpr_checkbox' => 'yes',
    			),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_email_field',
			[
				'label' => __( 'Email Field ID', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'email',
				'default' => 'email',
				'separator' => 'before',
				'description' => __( 'Enter the email field id - you can find this under the email field advanced tab', 'brevo-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'pro_unsubscribe_version_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('Need more attributes? <a href="https://plugins.ml.be/product/brevo-pro-integration-for-elementor-forms/?ref=plugin-widget" target="_blank">Check out our Pro version.</a>', 'brevo-elementor-integration'),
			]
		);

		$widget->add_control(
			'need_unsubscribe_help_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('Need help? <a href="https://plugins.ml.be/support/?ref=plugin-widget" target="_blank">Check out our support page.</a>', 'brevo-elementor-integration'),
			]
		);

		$widget->end_controls_section();

	}

	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @access Public
	 * @param array $element
	 */
	public function on_export( $element ) {
		unset(
			$element['brevo_unsubscribe_use_global_api_key'],
			$element['brevo_unsubscribe_api'],
			$element['brevo_unsubscribe_gdpr_checkbox'],
			$element['brevo_unsubscribe_gdpr_checkbox_field'],
			$element['brevo_unsubscribe_list'],
			$element['brevo_unsubscribe_email_field']
		);

		return $element;
	}

	/**
	 * Run
	 *
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		//Global key
		$useglobalkey = $settings['brevo_unsubscribe_use_global_api_key'];
		if ($useglobalkey == "yes") {
			$ml_brevo_options = get_option( 'ml_brevo_option_name' );
			$globalapikey = $ml_brevo_options['global_api_key_ml_brevo'];
			if ( empty( $globalapikey ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms brevo integration - brevo Global API Key not set.'); }
				return;
			}
			else {
				$settings['brevo_unsubscribe_api'] = $globalapikey;
			}
		}
		else {
			//  Make sure that there is an brevo API key set
			if ( empty( $settings['brevo_unsubscribe_api'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms brevo integration - brevo API Key not set.'); }
				return;
			}
		}

		// Make sure that there is a brevo Email field ID
		if ( empty( $settings['brevo_unsubscribe_email_field'] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms brevo integration - brevo e-mail field ID not set.'); }
			return;
		}

		// Get submitted Form data
		$raw_fields = $record->get( 'fields' );

		// Normalize the Form Data
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		//Check if email field contains the elementor form attribute shortcodes
		if (strpos($settings['brevo_unsubscribe_email_field'], '[field id=') !== false) {
			$settings['brevo_unsubscribe_email_field'] = substr($settings['brevo_unsubscribe_email_field'], strpos($settings['brevo_unsubscribe_email_field'], '"') + 1);
			$settings['brevo_unsubscribe_email_field'] = trim($settings['brevo_unsubscribe_email_field'], '"]');
		}

		// Make sure that the user has an email
		if ( empty( $fields[ $settings['brevo_unsubscribe_email_field'] ] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms brevo integration - Client did not enter an e-mail.'); }
			return;
		}

		//GDPR Checkbox
		$gdprcheckbox = $settings['brevo_unsubscribe_gdpr_checkbox'];
		if ($gdprcheckbox == "yes") {
			//  Make sure that there is a acceptence field if switch is set
			if ( empty( $settings['brevo_unsubscribe_gdpr_checkbox_field'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms brevo integration - Acceptence field ID is not set.'); }
				return;
			}
			// Make sure that checkbox is on
			$gdprcheckboxchecked = $fields[$settings['brevo_unsubscribe_gdpr_checkbox_field']];
			if ($gdprcheckboxchecked != "on") {
				if( WP_DEBUG === true ) { error_log('Elementor forms brevo integration - GDPR Checkbox was not thicked.'); }
				return;
			}
		}
		
		// Get the email being unsubscribed
		$email_to_unsubscribe = $fields[$settings['brevo_unsubscribe_email_field']];
		
		// Prepare the request URL
		$requesturl = 'https://api.brevo.com/v3/contacts/'.urlencode($email_to_unsubscribe);
		
		// Log the unsubscribe request details
		if( WP_DEBUG === true ) { 
			error_log('Elementor forms brevo integration - Beginning unsubscribe process');
			error_log('Elementor forms brevo integration - Unsubscribe request URL: ' . $requesturl); 
			error_log('Elementor forms brevo integration - Unsubscribe email: ' . $email_to_unsubscribe);
		}
		
		// Prepare request parameters
		$request_args = array(
			'method'      => 'DELETE',
			'timeout'     => 45,
			'httpversion' => '1.0',
			'blocking'    => true, // Changed to true to get the response
			'headers'     => [
				'accept' => 'application/json',
				'api-key' => $settings['brevo_unsubscribe_api'],
				'content-Type' => 'application/json',
			],
			'body'        => ''
		);
		
		// Log the request parameters
		if( WP_DEBUG === true ) { 
			error_log('Elementor forms brevo integration - Unsubscribe request parameters: ' . wp_json_encode($request_args)); 
		}
		
		// Send data to brevo
		$unsubscribe_response = wp_remote_request( $requesturl, $request_args );
		
		// Log the response
		if( WP_DEBUG === true ) { 
			$response_code = wp_remote_retrieve_response_code( $unsubscribe_response );
			$response_body = wp_remote_retrieve_body( $unsubscribe_response );
			
			error_log('Elementor forms brevo integration - Unsubscribe response code: ' . $response_code); 
			error_log('Elementor forms brevo integration - Unsubscribe response body: ' . $response_body); 
			
			// Check for errors
			if (is_wp_error($unsubscribe_response)) {
				error_log('Elementor forms brevo integration - Unsubscribe request error: ' . $unsubscribe_response->get_error_message()); 
			}
			
			// Log if the request was successful
			if ($response_code >= 200 && $response_code < 300) {
				error_log('Elementor forms brevo integration - Successfully unsubscribed email: ' . $email_to_unsubscribe);
			} else {
				error_log('Elementor forms brevo integration - Failed to unsubscribe email: ' . $email_to_unsubscribe . ' (Status code: ' . $response_code . ')');
			}
			
			// Log the complete response for detailed debugging
			error_log('Elementor forms brevo integration - Unsubscribe complete response: ' . wp_json_encode($unsubscribe_response)); 
		}
	}
}