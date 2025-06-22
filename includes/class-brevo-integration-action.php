<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class brevo_Integration_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'brevo integration';
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
		return __( 'brevo', 'brevo-elementor-integration' );
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
			'section_brevo-elementor-integration',
			[
				'label' => __( 'brevo', 'brevo-elementor-integration' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'brevo_use_global_api_key',
			[
				'label' => __( 'Global brevo API key', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_use_global_api_key_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('You can set your global API key <a href="' . admin_url( 'options-general.php?page=ml-brevo-free' ) . '" target="_blank">here.</a> this means you only need to set your brevo API key once.', 'brevo-elementor-integration'),
				'condition' => array(
					'brevo_use_global_api_key' => 'yes',
    			),
			]
		);

		$widget->add_control(
			'brevo_api',
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
			'brevo_double_optin',
			[
				'label' => __( 'Double Opt-in', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_double_optin_template',
			[
				'label' => __( 'Double Opt-in Template ID', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'placeholder' => '5',
				'separator' => 'before',
				'description' => __( 'Enter your double opt-in template ID - <a href="https://help.brevo.com/hc/en-us/articles/360019540880-Create-a-double-opt-in-DOI-confirmation-template-for-brevo-form" target="_blank">More info here</a>', 'brevo-elementor-integration' ),
    			'condition' => array(
    				'brevo_double_optin' => 'yes',
    			),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'brevo_double_optin_redirect_url',
			[
				'label' => __( 'Double Opt-in Redirect URL', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'https://website.com/thank-you',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter the url you want to redirect to after the subscriber confirms double opt-in', 'brevo-elementor-integration' ),
    			'condition' => array(
    				'brevo_double_optin' => 'yes',
    			),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'brevo_double_optin_check_if_email_exists',
			[
				'label' => __( 'Check if user already exists - Skip Double Opt-in', 'brevo-elementor-integration' ),
				'description' => __( 'Note: This will skip the notification email. This will still update the users fields', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before',
    			'condition' => array(
    				'brevo_double_optin' => 'yes',
    			),
			]
		);

		$widget->add_control(
			'brevo_gdpr_checkbox',
			[
				'label' => __( 'GDPR Checkbox', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_gdpr_checkbox_field',
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
			'brevo_list',
			[
				'label' => __( 'brevo List ID', 'brevo-elementor-integration' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'placeholder' => '5',
				'separator' => 'before',
				'description' => __( 'Enter your list number', 'brevo-elementor-integration' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'brevo_email_field',
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

		// Add dynamic field mapping section
		$this->add_dynamic_field_mapping_controls( $widget );

		$widget->add_control(
			'v2_upgrade_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('<strong>✨ New in v2.0:</strong> Dynamic field mapping now supports ALL your Brevo contact attributes! Configure available fields in <a href="' . admin_url( 'options-general.php?page=ml-brevo-free' ) . '" target="_blank">Settings → Brevo</a>.', 'brevo-elementor-integration'),
				'separator' => 'before',
			]
		);

		$widget->add_control(
			'need_help_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('Need help? <a href="https://plugins.ml.be/support/?ref=plugin-widget" target="_blank">Check out our support page.</a>', 'brevo-elementor-integration'),
			]
		);

		$widget->end_controls_section();

	}

	/**
	 * Add dynamic field mapping controls
	 *
	 * @param \ElementorPro\Modules\Forms\Widgets\Form $widget
	 */
	public function add_dynamic_field_mapping_controls( $widget ) {
		$enabled_fields = $this->get_enabled_brevo_fields();

		if ( empty( $enabled_fields ) ) {
			$widget->add_control(
				'brevo_no_fields_notice',
				[
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( '<div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;"><strong>⚠️ No fields configured:</strong> Please configure your Brevo fields in <a href="' . admin_url( 'options-general.php?page=ml-brevo-free' ) . '" target="_blank">Settings → Brevo</a> to enable field mapping.</div>', 'brevo-elementor-integration' ),
					'separator' => 'before',
				]
			);
			return;
		}

		$widget->add_control(
			'brevo_fields_section_title',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __( '<h3 style="margin: 0 0 10px 0; color: #495057;"><span style="color: #007cba;">🔗</span> Brevo Field Mapping</h3><p style="margin: 0 0 15px 0; color: #6c757d; font-style: italic;">Map your form fields to Brevo contact attributes:</p>', 'brevo-elementor-integration' ),
				'separator' => 'before',
			]
		);

		foreach ( $enabled_fields as $field_name => $field_data ) {
			$this->add_field_mapping_control( $widget, $field_name, $field_data );
		}
	}

	/**
	 * Add individual field mapping control
	 *
	 * @param \ElementorPro\Modules\Forms\Widgets\Form $widget
	 * @param string $field_name
	 * @param array $field_data
	 */
	public function add_field_mapping_control( $widget, $field_name, $field_data ) {
		$control_id = 'brevo_field_' . strtolower( $field_name );
		$is_required = in_array( $field_name, array( 'EMAIL' ) );
		
		$label = sprintf( 
			'%s %s(%s)', 
			$field_name,
			$is_required ? '* ' : '',
			ucfirst( $field_data['type'] )
		);

		$description = $field_data['description'];
		if ( $is_required ) {
			$description .= ' <strong>(Required)</strong>';
		}

		$widget->add_control(
			$control_id,
			[
				'label' => $label,
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => $this->get_field_placeholder( $field_name, $field_data['type'] ),
				'description' => $description,
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
			]
		);
	}

	/**
	 * Get enabled Brevo fields
	 *
	 * @return array
	 */
	public function get_enabled_brevo_fields() {
		$enabled_fields_settings = get_option( 'brevo_enabled_fields', array() );
		
		if ( empty( $enabled_fields_settings ) ) {
			// Return default fields for backwards compatibility
			return array(
				'FIRSTNAME' => array( 'type' => 'text', 'description' => 'Contact first name', 'enabled' => true ),
				'LASTNAME' => array( 'type' => 'text', 'description' => 'Contact last name', 'enabled' => true ), 
				'SMS' => array( 'type' => 'text', 'description' => 'Contact SMS number', 'enabled' => true ),
			);
		}

		// Get API key for fetching field definitions
		$ml_brevo_options = get_option( 'ml_brevo_option_name', array() );
		$api_key = $ml_brevo_options['global_api_key_ml_brevo'] ?? '';

		if ( empty( $api_key ) ) {
			return array();
		}

		$attributes_manager = Brevo_Attributes_Manager::get_instance();
		$all_attributes = $attributes_manager->fetch_attributes( $api_key );

		if ( is_wp_error( $all_attributes ) ) {
			error_log( 'Brevo Integration: Failed to fetch attributes - ' . $all_attributes->get_error_message() );
			return array();
		}

		$enabled_fields = array();
		foreach ( $enabled_fields_settings as $field_name => $enabled ) {
			if ( $enabled && isset( $all_attributes[ $field_name ] ) ) {
				$enabled_fields[ $field_name ] = $all_attributes[ $field_name ];
			}
		}

		return $enabled_fields;
	}

	/**
	 * Get field placeholder based on field name and type
	 *
	 * @param string $field_name
	 * @param string $field_type
	 * @return string
	 */
	public function get_field_placeholder( $field_name, $field_type ) {
		$placeholders = array(
			'FIRSTNAME' => 'first_name',
			'LASTNAME' => 'last_name',
			'SMS' => 'phone',
			'EMAIL' => 'email',
		);

		if ( isset( $placeholders[ $field_name ] ) ) {
			return $placeholders[ $field_name ];
		}

		// Generate placeholder based on field type
		switch ( $field_type ) {
			case 'text':
				return strtolower( str_replace( '_', '', $field_name ) );
			case 'number':
				return 'number_field';
			case 'date':
				return 'date_field';
			case 'boolean':
				return 'checkbox_field';
			default:
				return strtolower( $field_name );
		}
	}

	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @access Public
	 * @param array $element
	 */
	public function on_export( $element ) {
		// Remove basic settings
		unset(
			$element['brevo_use_global_api_key'],
			$element['brevo_api'],
			$element['brevo_double_optin'],
			$element['brevo_double_optin_template'],
			$element['brevo_double_optin_redirect_url'],
			$element['brevo_double_optin_check_if_email_exists'],
			$element['brevo_gdpr_checkbox'],
			$element['brevo_gdpr_checkbox_field'],
			$element['brevo_list'],
			$element['brevo_email_field']
		);

		// Remove legacy field settings for backwards compatibility
		unset(
			$element['brevo_name_attribute_field'],
			$element['brevo_name_field'],
			$element['brevo_last_name_attribute_field'],
			$element['brevo_last_name_field']
		);

		// Remove dynamic Brevo field mappings
		$enabled_fields = $this->get_enabled_brevo_fields();
		foreach ( $enabled_fields as $field_name => $field_data ) {
			$control_id = 'brevo_field_' . strtolower( $field_name );
			unset( $element[ $control_id ] );
		}

		return $element;
	}

	/**
	 * Run
	 *
	 * Runs the action after submit with dynamic field support
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		// Validate and set API key
		$api_key = $this->get_api_key( $settings );
		if ( ! $api_key ) {
			return;
		}

		// Validate required settings
		if ( ! $this->validate_required_settings( $settings ) ) {
			return;
		}

		// Get submitted form data
		$raw_fields = $record->get( 'fields' );
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		// Validate email field
		$email = $this->get_email_value( $settings, $fields );
		if ( ! $email ) {
			return;
		}

		// Check GDPR compliance
		if ( ! $this->check_gdpr_compliance( $settings, $fields ) ) {
			return;
		}

		// Build dynamic attributes from form data
		$attributes = $this->build_dynamic_attributes( $settings, $fields );

		// Check if email exists (if configured)
		$email_exists = $this->check_email_exists( $settings, $email, $api_key );

		// Process double opt-in or direct contact creation
		if ( $settings['brevo_double_optin'] === 'yes' && ! $email_exists ) {
			$this->process_double_optin( $settings, $email, $attributes, $api_key );
		} else {
			$this->process_contact_creation( $settings, $email, $attributes, $api_key );
		}
	}

	/**
	 * Get API key from settings
	 *
	 * @param array $settings
	 * @return string|false
	 */
	private function get_api_key( $settings ) {
		if ( $settings['brevo_use_global_api_key'] === 'yes' ) {
			$ml_brevo_options = get_option( 'ml_brevo_option_name', array() );
			$api_key = $ml_brevo_options['global_api_key_ml_brevo'] ?? '';
			
			if ( empty( $api_key ) ) {
				if ( WP_DEBUG ) {
					error_log( 'Brevo Integration: Global API Key not set.' );
				}
				return false;
			}
			
			return $api_key;
		}

		if ( empty( $settings['brevo_api'] ) ) {
			if ( WP_DEBUG ) {
				error_log( 'Brevo Integration: API Key not set.' );
			}
			return false;
		}

		return $settings['brevo_api'];
	}

	/**
	 * Validate required settings
	 *
	 * @param array $settings
	 * @return bool
	 */
	private function validate_required_settings( $settings ) {
		if ( empty( $settings['brevo_list'] ) ) {
			if ( WP_DEBUG ) {
				error_log( 'Brevo Integration: List ID not set.' );
			}
			return false;
		}

		if ( $settings['brevo_double_optin'] === 'yes' ) {
			if ( empty( $settings['brevo_double_optin_template'] ) ) {
				if ( WP_DEBUG ) {
					error_log( 'Brevo Integration: Double opt-in template ID not set.' );
				}
				return false;
			}
		}

		return true;
	}

	/**
	 * Get email value from form fields
	 *
	 * @param array $settings
	 * @param array $fields
	 * @return string|false
	 */
	private function get_email_value( $settings, $fields ) {
		$email_field_id = $settings['brevo_email_field'];
		
		if ( empty( $email_field_id ) ) {
			if ( WP_DEBUG ) {
				error_log( 'Brevo Integration: Email field ID not set.' );
			}
			return false;
		}

		// Handle Elementor form attribute shortcodes
		if ( strpos( $email_field_id, '[field id=' ) !== false ) {
			$email_field_id = substr( $email_field_id, strpos( $email_field_id, '"' ) + 1 );
			$email_field_id = trim( $email_field_id, '"]' );
		}

		if ( empty( $fields[ $email_field_id ] ) ) {
			if ( WP_DEBUG ) {
				error_log( 'Brevo Integration: Client did not enter an email.' );
			}
			return false;
		}

		return $fields[ $email_field_id ];
	}

	/**
	 * Check GDPR compliance
	 *
	 * @param array $settings
	 * @param array $fields
	 * @return bool
	 */
	private function check_gdpr_compliance( $settings, $fields ) {
		if ( $settings['brevo_gdpr_checkbox'] !== 'yes' ) {
			return true;
		}

		if ( empty( $settings['brevo_gdpr_checkbox_field'] ) ) {
			if ( WP_DEBUG ) {
				error_log( 'Brevo Integration: GDPR checkbox field ID not set.' );
			}
			return false;
		}

		$gdpr_value = $fields[ $settings['brevo_gdpr_checkbox_field'] ] ?? '';
		if ( $gdpr_value !== 'on' ) {
			if ( WP_DEBUG ) {
				error_log( 'Brevo Integration: GDPR checkbox was not checked.' );
			}
			return false;
		}

		return true;
	}

	/**
	 * Build dynamic attributes from form data
	 *
	 * @param array $settings
	 * @param array $fields
	 * @return array
	 */
	private function build_dynamic_attributes( $settings, $fields ) {
		$attributes = array();
		
		// Handle legacy fields for backwards compatibility
		if ( ! empty( $settings['brevo_name_field'] ) ) {
			$name_attribute = $settings['brevo_name_attribute_field'] ?: 'FIRSTNAME';
			$name_field_id = $this->clean_field_id( $settings['brevo_name_field'] );
			if ( ! empty( $fields[ $name_field_id ] ) ) {
				$attributes[ $name_attribute ] = $fields[ $name_field_id ];
			}
		}

		if ( ! empty( $settings['brevo_last_name_field'] ) ) {
			$lastname_attribute = $settings['brevo_last_name_attribute_field'] ?: 'LASTNAME';
			$lastname_field_id = $this->clean_field_id( $settings['brevo_last_name_field'] );
			if ( ! empty( $fields[ $lastname_field_id ] ) ) {
				$attributes[ $lastname_attribute ] = $fields[ $lastname_field_id ];
			}
		}

		// Handle dynamic fields (v2.0)
		$enabled_fields = $this->get_enabled_brevo_fields();
		foreach ( $enabled_fields as $field_name => $field_data ) {
			$control_id = 'brevo_field_' . strtolower( $field_name );
			
			if ( ! empty( $settings[ $control_id ] ) ) {
				$form_field_id = $this->clean_field_id( $settings[ $control_id ] );
				
				if ( ! empty( $fields[ $form_field_id ] ) ) {
					$attributes[ $field_name ] = $this->format_field_value( 
						$fields[ $form_field_id ], 
						$field_data['type'] 
					);
				}
			}
		}

		return $attributes;
	}

	/**
	 * Clean field ID from Elementor shortcodes
	 *
	 * @param string $field_id
	 * @return string
	 */
	private function clean_field_id( $field_id ) {
		if ( strpos( $field_id, '[field id=' ) !== false ) {
			$field_id = substr( $field_id, strpos( $field_id, '"' ) + 1 );
			$field_id = trim( $field_id, '"]' );
		}
		return $field_id;
	}

	/**
	 * Format field value based on type
	 *
	 * @param mixed $value
	 * @param string $type
	 * @return mixed
	 */
	private function format_field_value( $value, $type ) {
		switch ( $type ) {
			case 'number':
				return is_numeric( $value ) ? (float) $value : $value;
			case 'boolean':
				return in_array( strtolower( $value ), array( 'on', 'yes', '1', 'true' ) );
			case 'date':
				// Convert date to YYYY-MM-DD format if needed
				if ( strtotime( $value ) ) {
					return date( 'Y-m-d', strtotime( $value ) );
				}
				return $value;
			default:
				return $value;
		}
	}

	/**
	 * Check if email exists in Brevo
	 *
	 * @param array $settings
	 * @param string $email
	 * @param string $api_key
	 * @return bool
	 */
	private function check_email_exists( $settings, $email, $api_key ) {
		if ( $settings['brevo_double_optin_check_if_email_exists'] !== 'yes' ) {
			return false;
		}

		$request_url = 'https://api.brevo.com/v3/contacts/' . urlencode( $email );
		
		if ( WP_DEBUG ) {
			error_log( 'Brevo Integration: Checking email exists - ' . $request_url );
		}

		$response = wp_remote_get( $request_url, array(
			'timeout' => 45,
			'headers' => array(
				'accept' => 'application/json',
				'api-key' => $api_key,
			),
		) );

		$response_code = wp_remote_retrieve_response_code( $response );
		$exists = ( $response_code === 200 );

		if ( WP_DEBUG ) {
			error_log( 'Brevo Integration: Email exists check - Code: ' . $response_code . ', Exists: ' . ( $exists ? 'yes' : 'no' ) );
		}

		return $exists;
	}

	/**
	 * Process double opt-in
	 *
	 * @param array $settings
	 * @param string $email
	 * @param array $attributes
	 * @param string $api_key
	 */
	private function process_double_optin( $settings, $email, $attributes, $api_key ) {
		$redirect_url = ! empty( $settings['brevo_double_optin_redirect_url'] ) 
			? $settings['brevo_double_optin_redirect_url'] 
			: get_site_url();

		$body = array(
			'attributes' => $attributes,
			'includeListIds' => array( (int) $settings['brevo_list'] ),
			'templateId' => (int) $settings['brevo_double_optin_template'],
			'redirectionUrl' => $redirect_url,
			'email' => $email,
		);

		if ( WP_DEBUG ) {
			error_log( 'Brevo Integration: Double opt-in request - ' . wp_json_encode( $body ) );
		}

		$response = wp_remote_post( 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation', array(
			'method' => 'POST',
			'timeout' => 45,
			'headers' => array(
				'accept' => 'application/json',
				'api-key' => $api_key,
				'content-type' => 'application/json',
			),
			'body' => wp_json_encode( $body ),
		) );

		if ( WP_DEBUG ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			error_log( 'Brevo Integration: Double opt-in response - Code: ' . $response_code . ', Body: ' . $response_body );
		}
	}

	/**
	 * Process direct contact creation
	 *
	 * @param array $settings
	 * @param string $email
	 * @param array $attributes
	 * @param string $api_key
	 */
	private function process_contact_creation( $settings, $email, $attributes, $api_key ) {
		$body = array(
			'attributes' => $attributes,
			'updateEnabled' => true,
			'listIds' => array( (int) $settings['brevo_list'] ),
			'email' => $email,
		);

		if ( WP_DEBUG ) {
			error_log( 'Brevo Integration: Contact creation request - ' . wp_json_encode( $body ) );
		}

		$response = wp_remote_post( 'https://api.brevo.com/v3/contacts', array(
			'method' => 'POST',
			'timeout' => 45,
			'headers' => array(
				'accept' => 'application/json',
				'api-key' => $api_key,
				'content-type' => 'application/json',
			),
			'body' => wp_json_encode( $body ),
		) );

		if ( WP_DEBUG ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			error_log( 'Brevo Integration: Contact creation response - Code: ' . $response_code . ', Body: ' . $response_body );
		}
	}
}