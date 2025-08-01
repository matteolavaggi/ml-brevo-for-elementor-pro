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
		return __( 'brevo Unsubscribe', 'ml-brevo-for-elementor-pro' );
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
				'label' => __( 'brevo Unsubscribe', 'ml-brevo-for-elementor-pro' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'unsubscribe_note_alert_delete',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => '<b>' . esc_html__('PLEASE NOTE - THIS ACTION DELETES THE INPUT EMAIL IN brevo!', 'ml-brevo-for-elementor-pro') . '</b>',
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_use_global_api_key',
			[
				'label' => __( 'Global brevo API key', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_use_global_api_key_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				// translators: %s is the URL to the settings page
				'raw' => sprintf( __('You can set your global API key <a href="%s" target="_blank">here.</a> this means you only need to set your brevo API key once.', 'ml-brevo-for-elementor-pro'), admin_url( 'options-general.php?page=ml-brevo-free' ) ),
				'condition' => array(
					'brevo_use_global_api_key' => 'yes',
    			),
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_api',
			[
				'label' => __( 'brevo API key', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'xkeysib-xxxxxxxx',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter your V3 API key from brevo', 'ml-brevo-for-elementor-pro' ),
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
				'label' => __( 'GDPR Checkbox', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_unsubscribe_gdpr_checkbox_field',
			[
				'label' => __( 'Acceptance Field ID', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'acceptancefieldid',
				'separator' => 'before',
				'description' => __( 'Enter the acceptance checkbox field id - you can find this under the acceptance field advanced tab - if the acceptance checkbox is not checked then the email and extra information will not be added to the list', 'ml-brevo-for-elementor-pro' ),
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
				'label' => __( 'Email Field ID', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'email',
				'default' => 'email',
				'separator' => 'before',
				'description' => __( 'Enter the email field id - you can find this under the email field advanced tab', 'ml-brevo-for-elementor-pro' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'need_unsubscribe_help_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('Need help? <a href="https://matteolavaggi.it/wordpress/ml-brevo-for-elementor-pro/" target="_blank">Check out our support page.</a>', 'ml-brevo-for-elementor-pro'),
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
				return;
			}
			else {
				$settings['brevo_unsubscribe_api'] = $globalapikey;
			}
		}
		else {
			//  Make sure that there is an brevo API key set
			if ( empty( $settings['brevo_unsubscribe_api'] ) ) {
				return;
			}
		}

		// Make sure that there is a brevo Email field ID
		if ( empty( $settings['brevo_unsubscribe_email_field'] ) ) {
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
			return;
		}

		//GDPR Checkbox
		$gdprcheckbox = $settings['brevo_unsubscribe_gdpr_checkbox'];
		if ($gdprcheckbox == "yes") {
			//  Make sure that there is a acceptence field if switch is set
			if ( empty( $settings['brevo_unsubscribe_gdpr_checkbox_field'] ) ) {
				return;
			}
			// Make sure that checkbox is on
			$gdprcheckboxchecked = $fields[$settings['brevo_unsubscribe_gdpr_checkbox_field']] ?? '';
			if ($gdprcheckboxchecked != "on") {
				return;
			}
		}
		
		// Get the email being unsubscribed
		$email_to_unsubscribe = $fields[$settings['brevo_unsubscribe_email_field']];
		
		// Prepare the request URL
		$requesturl = 'https://api.brevo.com/v3/contacts/'.urlencode($email_to_unsubscribe);
		
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
		
		// Send data to brevo
		$unsubscribe_response = wp_remote_request( $requesturl, $request_args );
	}
}