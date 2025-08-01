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
		return __( 'Brevo', 'ml-brevo-for-elementor-pro' );
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
			'section_ml-brevo-for-elementor-pro',
			[
				'label' => __( 'Brevo', 'ml-brevo-for-elementor-pro' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'brevo_use_global_api_key',
			[
				'label' => __( 'Global brevo API key', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_use_global_api_key_note',
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
			'brevo_api',
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
			'brevo_double_optin',
			[
				'label' => __( 'Double Opt-in', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_double_optin_template',
			[
				'label' => __( 'Double Opt-in Template ID', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'placeholder' => '5',
				'separator' => 'before',
				'description' => __( 'Enter your double opt-in template ID - <a href="https://help.brevo.com/hc/en-us/articles/360019540880-Create-a-double-opt-in-DOI-confirmation-template-for-brevo-form" target="_blank">More info here</a>', 'ml-brevo-for-elementor-pro' ),
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
				'label' => __( 'Double Opt-in Redirect URL', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'https://website.com/thank-you',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter the url you want to redirect to after the subscriber confirms double opt-in', 'ml-brevo-for-elementor-pro' ),
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
				'label' => __( 'Check if user already exists - Skip Double Opt-in', 'ml-brevo-for-elementor-pro' ),
				'description' => __( 'Note: This will skip the notification email. This will still update the users fields', 'ml-brevo-for-elementor-pro' ),
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
				'label' => __( 'GDPR Checkbox', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$widget->add_control(
			'brevo_gdpr_checkbox_field',
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
			'brevo_list',
			[
				'label' => __( 'Brevo List', 'ml-brevo-for-elementor-pro' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_brevo_lists_options(),
				'default' => '',
				'separator' => 'before',
				'description' => __( 'Select the Brevo list where contacts will be added', 'ml-brevo-for-elementor-pro' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$widget->add_control(
			'brevo_list_refresh_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => '<p style="margin: 5px 0 0 0; font-size: 12px; color: #666;"><strong>💡 ' . esc_html__( 'Tip:', 'ml-brevo-for-elementor-pro' ) . '</strong> ' . 
					sprintf( 
						// translators: %1$s is the opening link tag, %2$s is the closing link tag
						esc_html__( 'Don\'t see your list? %1$sRefresh lists in Settings%2$s or clear cache if you just created a new list in Brevo.', 'ml-brevo-for-elementor-pro' ), 
						'<a href="' . esc_url( admin_url( 'options-general.php?page=ml-brevo-free' ) ) . '" target="_blank">', 
						'</a>' 
					) . '</p>',
			]
		);

		$widget->add_control(
			'brevo_email_field',
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

		// Add dynamic field mapping section
		$this->add_dynamic_field_mapping_controls( $widget );

		$widget->add_control(
			'v2_upgrade_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				// translators: %s is the URL to the settings page
				'raw' => sprintf( __('<strong>✨ New in v2.0:</strong> Dynamic field mapping now supports ALL your Brevo contact attributes! Configure available fields in <a href="%s" target="_blank">Settings → Brevo</a>.', 'ml-brevo-for-elementor-pro'), admin_url( 'options-general.php?page=ml-brevo-free' ) ),
				'separator' => 'before',
			]
		);

		$widget->add_control(
			'need_help_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __('Need help? <a href="https://matteolavaggi.it/wordpress/ml-brevo-for-elementor-pro/" target="_blank">Check out our support page.</a>', 'ml-brevo-for-elementor-pro'),
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
					'raw' => '<div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;"><strong>⚠️ ' . esc_html__( 'No fields configured:', 'ml-brevo-for-elementor-pro' ) . '</strong> ' . 
						sprintf( 
							// translators: %1$s is the opening link tag, %2$s is the closing link tag
							esc_html__( 'Please configure your Brevo fields in %1$sSettings → Brevo%2$s to enable field mapping.', 'ml-brevo-for-elementor-pro' ), 
							'<a href="' . esc_url( admin_url( 'options-general.php?page=ml-brevo-free' ) ) . '" target="_blank">', 
							'</a>' 
						) . '</div>',
					'separator' => 'before',
				]
			);
			return;
		}

		$widget->add_control(
			'brevo_fields_section_title',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __( '<h3 style="margin: 0 0 10px 0; color: #495057;"><span style="color: #007cba;">🔗</span> Brevo Field Mapping</h3><p style="margin: 0 0 15px 0; color: #6c757d; font-style: italic;">Map your form fields to Brevo contact attributes:</p>', 'ml-brevo-for-elementor-pro' ),
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
	 * Get Brevo lists options for dropdown
	 *
	 * @return array
	 */
	public function get_brevo_lists_options() {
		// Get global API key
		$ml_brevo_options = get_option( 'ml_brevo_option_name', array() );
		$api_key = $ml_brevo_options['global_api_key_ml_brevo'] ?? '';
		
		if ( empty( $api_key ) ) {
			return array(
				'' => __( 'Please set Global API Key in Settings', 'ml-brevo-for-elementor-pro' )
			);
		}

		// Get lists from cache/API
		$attributes_manager = Brevo_Attributes_Manager::get_instance();
		$lists = $attributes_manager->fetch_all_lists( $api_key );
		
		if ( is_wp_error( $lists ) || empty( $lists ) ) {
			return array(
				'' => __( 'No lists available or API error', 'ml-brevo-for-elementor-pro' )
			);
		}

		

		// Format lists for dropdown
		$options = array( '' => __( 'Select a list...', 'ml-brevo-for-elementor-pro' ) );
		
		foreach ( $lists as $list_id => $list_data ) {
			if ( ! is_array( $list_data ) || ! isset( $list_data['name'] ) ) {
				continue;
			}
			
			$list_name = sanitize_text_field( $list_data['name'] );
			
			// Simple display: just list name and ID
			$display_name = sprintf( 
				'%s - ID: %d', 
				$list_name, 
				$list_id 
			);
			
			$options[ $list_id ] = $display_name;
		}

		return $options;
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
		$logger = Brevo_Debug_Logger::get_instance();
		$logger->info( 'Form submission started', 'FORM', 'submit', array(
			'form_id' => $record->get_form_settings( 'form_name' ) ?: 'unknown',
			'timestamp' => current_time( 'timestamp' )
		) );

		$settings = $record->get( 'form_settings' );

		// Validate and set API key
		$api_key = $this->get_api_key( $settings );
		if ( ! $api_key ) {
			$logger->error( 'Form submission failed: No API key available', 'FORM', 'submit' );
			return;
		}

		$logger->debug( 'API key retrieved successfully', 'FORM', 'submit', array(
			'api_key_hash' => md5( $api_key ),
			'uses_global_key' => $settings['brevo_use_global_api_key'] === 'yes'
		) );

		// Validate required settings
		if ( ! $this->validate_required_settings( $settings ) ) {
			$logger->error( 'Form submission failed: Invalid settings', 'FORM', 'submit' );
			return;
		}

		// Get submitted form data
		$raw_fields = $record->get( 'fields' );
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		$logger->debug( 'Form data extracted', 'FORM', 'submit', array(
			'field_count' => count( $fields ),
			'field_ids' => array_keys( $fields )
		) );

		// Validate email field
		$email = $this->get_email_value( $settings, $fields );
		if ( ! $email ) {
			$logger->error( 'Form submission failed: No valid email provided', 'FORM', 'submit' );
			return;
		}

		$logger->info( 'Email validated', 'FORM', 'submit', array(
			'email' => $email,
			'email_field_id' => $settings['brevo_email_field']
		) );

		// Check GDPR compliance
		if ( ! $this->check_gdpr_compliance( $settings, $fields ) ) {
			$logger->warning( 'Form submission stopped: GDPR compliance check failed', 'FORM', 'submit', array(
				'email' => $email,
				'gdpr_enabled' => $settings['brevo_gdpr_checkbox'] === 'yes'
			) );
			return;
		}

		// Build dynamic attributes from form data
		$attributes = $this->build_dynamic_attributes( $settings, $fields );

		$logger->debug( 'Attributes built from form data', 'FORM', 'submit', array(
			'email' => $email,
			'attributes_count' => count( $attributes ),
			'attributes' => $attributes
		) );

		// Check if email exists (if configured)
		$email_exists = $this->check_email_exists( $settings, $email, $api_key );

		// Process double opt-in or direct contact creation
		if ( $settings['brevo_double_optin'] === 'yes' && ! $email_exists ) {
					$logger->info( 'Processing double opt-in', 'FORM', 'submit', array(
			'email' => $email,
			'list_id' => $settings['brevo_list'],
			'list_id_type' => gettype( $settings['brevo_list'] ),
			'list_id_int' => (int) $settings['brevo_list'],
			'template_id' => $settings['brevo_double_optin_template']
		) );
			$this->process_double_optin( $settings, $email, $attributes, $api_key );
		} else {
					$logger->info( 'Processing direct contact creation', 'FORM', 'submit', array(
			'email' => $email,
			'list_id' => $settings['brevo_list'],
			'list_id_type' => gettype( $settings['brevo_list'] ),
			'list_id_int' => (int) $settings['brevo_list'],
			'email_exists' => $email_exists
		) );
			$this->process_contact_creation( $settings, $email, $attributes, $api_key );
		}

		$logger->info( 'Form submission completed', 'FORM', 'submit', array(
			'email' => $email,
			'success' => true
		) );
	}

	/**
	 * Get API key from settings
	 *
	 * @param array $settings
	 * @return string|false
	 */
	private function get_api_key( $settings ) {
		$logger = Brevo_Debug_Logger::get_instance();
		
		if ( $settings['brevo_use_global_api_key'] === 'yes' ) {
			$logger->debug( 'Attempting to use global API key', 'FORM', 'get_api_key' );
			$ml_brevo_options = get_option( 'ml_brevo_option_name', array() );
			$api_key = $ml_brevo_options['global_api_key_ml_brevo'] ?? '';
			
				if ( empty( $api_key ) ) {
					$logger->error( 'Global API key not set in settings', 'FORM', 'get_api_key' );
					return false;
				}
			
			$logger->debug( 'Global API key retrieved successfully', 'FORM', 'get_api_key', array(
				'api_key_hash' => md5( $api_key )
			) );
			return $api_key;
		}

		if ( empty( $settings['brevo_api'] ) ) {
			$logger->error( 'Form-specific API key not set', 'FORM', 'get_api_key' );
			return false;
		}

		$logger->debug( 'Form-specific API key retrieved successfully', 'FORM', 'get_api_key', array(
			'api_key_hash' => md5( $settings['brevo_api'] )
		) );
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
			return false;
		}

		if ( $settings['brevo_double_optin'] === 'yes' ) {
			if ( empty( $settings['brevo_double_optin_template'] ) ) {
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
			return false;
		}

		// Handle Elementor form attribute shortcodes
		if ( strpos( $email_field_id, '[field id=' ) !== false ) {
			$email_field_id = substr( $email_field_id, strpos( $email_field_id, '"' ) + 1 );
			$email_field_id = trim( $email_field_id, '"]' );
		}

		if ( empty( $fields[ $email_field_id ] ) ) {
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
			return false;
		}

		$gdpr_value = $fields[ $settings['brevo_gdpr_checkbox_field'] ] ?? '';
		if ( $gdpr_value !== 'on' ) {
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
					return gmdate( 'Y-m-d', strtotime( $value ) );
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
		$logger = Brevo_Debug_Logger::get_instance();
		
		if ( $settings['brevo_double_optin_check_if_email_exists'] !== 'yes' ) {
			$logger->debug( 'Email exists check skipped (not enabled)', 'API', 'check_email_exists', array(
				'email' => $email
			) );
			return false;
		}

		$request_url = 'https://api.brevo.com/v3/contacts/' . urlencode( $email );
		
		$logger->info( 'Checking if email exists in Brevo', 'API', 'check_email_exists', array(
			'email' => $email,
			'endpoint' => $request_url,
			'api_key_hash' => md5( $api_key )
		) );

		$start_time = microtime( true );
		$response = wp_remote_get( $request_url, array(
			'timeout' => 45,
			'headers' => array(
				'accept' => 'application/json',
				'api-key' => $api_key,
			),
		) );

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$execution_time = microtime( true ) - $start_time;
		$exists = ( $response_code === 200 );

		$logger->info( 'Email exists check completed', 'API', 'check_email_exists', array(
			'email' => $email,
			'response_code' => $response_code,
			'exists' => $exists,
			'execution_time' => $execution_time,
			'response_body' => $response_body
		) );

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
		$logger = Brevo_Debug_Logger::get_instance();
		
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

		$logger->info( 'Sending double opt-in request to Brevo', 'API', 'double_optin', array(
			'email' => $email,
			'list_id' => (int) $settings['brevo_list'],
			'template_id' => (int) $settings['brevo_double_optin_template'],
			'redirect_url' => $redirect_url,
			'attributes_count' => count( $attributes ),
			'api_key_hash' => md5( $api_key )
		) );

		$start_time = microtime( true );
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

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$execution_time = microtime( true ) - $start_time;

		if ( is_wp_error( $response ) ) {
			$logger->error( 'Double opt-in request failed', 'API', 'double_optin', array(
				'email' => $email,
				'error_message' => $response->get_error_message(),
				'error_code' => $response->get_error_code(),
				'execution_time' => $execution_time
			) );
		} else {
			$logger->info( 'Double opt-in request completed', 'API', 'double_optin', array(
				'email' => $email,
				'response_code' => $response_code,
				'execution_time' => $execution_time,
				'response_body' => $response_body,
				'success' => $response_code >= 200 && $response_code < 300
			) );
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
		$logger = Brevo_Debug_Logger::get_instance();
		
		$body = array(
			'attributes' => $attributes,
			'updateEnabled' => true,
			'listIds' => array( (int) $settings['brevo_list'] ),
			'email' => $email,
		);

		$logger->info( 'Sending contact creation request to Brevo', 'API', 'create_contact', array(
			'email' => $email,
			'list_id' => (int) $settings['brevo_list'],
			'attributes_count' => count( $attributes ),
			'attributes' => $attributes,
			'update_enabled' => true,
			'api_key_hash' => md5( $api_key )
		) );

		$start_time = microtime( true );
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

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$execution_time = microtime( true ) - $start_time;

		if ( is_wp_error( $response ) ) {
			$logger->error( 'Contact creation request failed', 'API', 'create_contact', array(
				'email' => $email,
				'error_message' => $response->get_error_message(),
				'error_code' => $response->get_error_code(),
				'execution_time' => $execution_time
			) );
		} else {
			$logger->info( 'Contact creation request completed', 'API', 'create_contact', array(
				'email' => $email,
				'response_code' => $response_code,
				'execution_time' => $execution_time,
				'response_body' => $response_body,
				'success' => $response_code >= 200 && $response_code < 300
			) );
		}
	}
}