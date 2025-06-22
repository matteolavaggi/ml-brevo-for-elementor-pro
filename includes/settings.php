<?php

// Link to support and pro page from plugins screen
function ml_brevo_filter_action_links( $links ) {

	$links['settings'] = '<a href="' . admin_url( 'options-general.php?page=ml-brevo-free' ) . '">' . __( 'Settings' ) . '</a>';
	$links['support'] = '<a href="https://matteolavaggi.it/wordpress/ml-brevo-for-elementor-pro/" target="_blank">Support</a>';
	return $links;

}
add_filter( 'plugin_action_links_ml-brevo-for-elementor-pro/ml-brevo-for-elementor-pro.php', 'ml_brevo_filter_action_links', 10, 3 );

// Handle AJAX requests for field management
add_action( 'wp_ajax_brevo_refresh_fields', 'brevo_handle_refresh_fields' );
add_action( 'wp_ajax_brevo_update_field_settings', 'brevo_handle_field_settings_update' );

function brevo_handle_refresh_fields() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'brevo_admin_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$api_key = sanitize_text_field( $_POST['api_key'] );
	
	if ( empty( $api_key ) ) {
		wp_send_json_error( array( 'message' => 'API key is required' ) );
	}

	$attributes_manager = Brevo_Attributes_Manager::get_instance();
	
	// Clear cache and fetch fresh data
	$attributes_manager->clear_cache( $api_key );
	$attributes = $attributes_manager->fetch_attributes( $api_key );

	if ( is_wp_error( $attributes ) ) {
		wp_send_json_error( array( 'message' => $attributes->get_error_message() ) );
	}

	wp_send_json_success( array( 
		'message' => sprintf( 'Successfully refreshed %d fields', count( $attributes ) ),
		'fields' => $attributes
	) );
}

function brevo_handle_field_settings_update() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'brevo_admin_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$enabled_fields = array();
	if ( isset( $_POST['enabled_fields'] ) && is_array( $_POST['enabled_fields'] ) ) {
		foreach ( $_POST['enabled_fields'] as $field ) {
			$enabled_fields[ sanitize_text_field( $field ) ] = true;
		}
	}

	update_option( 'brevo_enabled_fields', $enabled_fields );

	wp_send_json_success( array( 'message' => 'Field settings updated successfully' ) );
}

// Add global site setting API Key option
class MlbrevoFree {
	private $ml_brevo_free_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'ml_brevo_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'ml_brevo_page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our settings page
		if ( $hook !== 'settings_page_ml-brevo-free' ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_add_inline_script( 'jquery', $this->get_admin_js() );
		wp_add_inline_style( 'wp-admin', $this->get_admin_css() );
	}

	public function ml_brevo_add_plugin_page() {
		add_options_page(
			'ML Brevo for Elementor Pro', // page_title
			'ML Brevo for Elementor Pro', // menu_title
			'manage_options', // capability
			'ml-brevo-free', // menu_slug
			array( $this, 'ml_brevo_create_admin_page' ) // function
		);
	}

	public function ml_brevo_create_admin_page() {
		$this->ml_brevo_options = get_option( 'ml_brevo_option_name' ); 
		$api_key = $this->ml_brevo_options['global_api_key_ml_brevo'] ?? '';
		?>

		<div class="wrap">
			<h1>ML Brevo for Elementor Pro v2.0</h1>
			
			<div id="brevo-admin-notices"></div>

			<form method="post" action="options.php" id="brevo-settings-form">
				<?php
					settings_fields( 'ml_brevo_option_group' );
					do_settings_sections( 'ml-brevo-admin' );
				?>
				
				<?php $this->render_field_management_section( $api_key ); ?>
				
				<?php submit_button(); ?>
			</form>
		</div>
		
		<?php wp_nonce_field( 'brevo_admin_nonce', 'brevo_nonce' ); ?>
	<?php }

	/**
	 * Render the field management section
	 */
	public function render_field_management_section( $api_key ) {
		?>
		<div class="brevo-field-management">
			<h2><?php _e( 'Available Brevo Fields', 'ml-brevo-for-elementor-pro' ); ?></h2>
			<p><?php _e( 'Enable or disable fields that will be available for mapping in Elementor forms.', 'ml-brevo-for-elementor-pro' ); ?></p>
			
			<div class="brevo-field-controls">
				<button type="button" id="refresh-fields-btn" class="button button-secondary" 
					<?php echo empty( $api_key ) ? 'disabled' : ''; ?>>
					<?php _e( 'Refresh Fields from Brevo', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				
				<button type="button" id="enable-all-btn" class="button button-secondary">
					<?php _e( 'Enable All', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				
				<button type="button" id="disable-all-btn" class="button button-secondary">
					<?php _e( 'Disable All', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				
				<button type="button" id="reset-defaults-btn" class="button button-secondary">
					<?php _e( 'Reset to Defaults', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
			</div>

			<div id="field-management-table-container">
				<?php $this->render_fields_table( $api_key ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the fields table
	 */
	public function render_fields_table( $api_key ) {
		if ( empty( $api_key ) ) {
			echo '<p class="notice notice-warning inline">' . 
				__( 'Please set your Global API Key above and save to manage fields.', 'ml-brevo-for-elementor-pro' ) . 
				'</p>';
			return;
		}

		$attributes_manager = Brevo_Attributes_Manager::get_instance();
		$attributes = $attributes_manager->fetch_attributes( $api_key );
		$enabled_fields = get_option( 'brevo_enabled_fields', array() );
		$cache_info = $attributes_manager->get_cache_info( $api_key );

		if ( is_wp_error( $attributes ) ) {
			echo '<div class="notice notice-error inline">';
			echo '<p>' . sprintf( __( 'Error fetching fields: %s', 'ml-brevo-for-elementor-pro' ), $attributes->get_error_message() ) . '</p>';
			echo '</div>';
			return;
		}

		?>
		<div class="brevo-cache-info">
			<?php if ( $cache_info ): ?>
				<small>
					<?php printf( 
						__( 'Last updated: %s (%d fields found)', 'ml-brevo-for-elementor-pro' ),
						human_time_diff( $cache_info['cached_at'] ) . ' ago',
						$cache_info['count']
					); ?>
				</small>
			<?php endif; ?>
		</div>

		<table class="wp-list-table widefat fixed striped" id="brevo-fields-table">
			<thead>
				<tr>
					<th class="check-column">
						<input type="checkbox" id="select-all-fields">
					</th>
					<th><?php _e( 'Field Name', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php _e( 'Type', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php _e( 'Description', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php _e( 'Status', 'ml-brevo-for-elementor-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $attributes as $field_name => $field_data ): 
					$is_enabled = isset( $enabled_fields[ $field_name ] ) || $field_data['enabled'];
				?>
				<tr>
					<td class="check-column">
						<input type="checkbox" name="brevo_fields[]" value="<?php echo esc_attr( $field_name ); ?>" 
							   <?php checked( $is_enabled ); ?> class="field-checkbox">
					</td>
					<td>
						<strong><?php echo esc_html( $field_name ); ?></strong>
						<?php if ( in_array( $field_name, array( 'FIRSTNAME', 'LASTNAME', 'SMS' ) ) ): ?>
							<span class="brevo-field-badge brevo-default-field"><?php _e( 'Default', 'ml-brevo-for-elementor-pro' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<span class="brevo-field-type brevo-type-<?php echo esc_attr( $field_data['type'] ); ?>">
							<?php echo esc_html( ucfirst( $field_data['type'] ) ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $field_data['description'] ); ?></td>
					<td>
						<span class="brevo-status <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>">
							<?php echo $is_enabled ? __( 'Enabled', 'ml-brevo-for-elementor-pro' ) : __( 'Disabled', 'ml-brevo-for-elementor-pro' ); ?>
						</span>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	public function ml_brevo_page_init() {
		register_setting(
			'ml_brevo_option_group', // option_group
			'ml_brevo_option_name', // option_name
			array( $this, 'ml_brevo_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'ml_brevo_setting_section', // id
			'Settings', // title
			array( $this, 'ml_brevo_section_info' ), // callback
			'ml-brevo-admin' // page
		);

		add_settings_field(
			'global_api_key_ml_brevo', // id
			'Global brevo API key', // title
			array( $this, 'global_api_key_ml_brevo_callback' ), // callback
			'ml-brevo-admin', // page
			'ml_brevo_setting_section' // section
		);
	}

	public function ml_brevo_sanitize($input) {
		$sanitary_values = array();

		if ( isset( $input['global_api_key_ml_brevo'] ) ) {
			$sanitary_values['global_api_key_ml_brevo'] = sanitize_text_field( $input['global_api_key_ml_brevo'] );
		}

		// Handle field settings update
		if ( isset( $_POST['brevo_fields'] ) && is_array( $_POST['brevo_fields'] ) ) {
			$enabled_fields = array();
			foreach ( $_POST['brevo_fields'] as $field ) {
				$enabled_fields[ sanitize_text_field( $field ) ] = true;
			}
			update_option( 'brevo_enabled_fields', $enabled_fields );
		} else {
			// No fields selected, disable all
			update_option( 'brevo_enabled_fields', array() );
		}

		return $sanitary_values;
	}

	/**
	 * Get admin JavaScript
	 */
	public function get_admin_js() {
		return "
		jQuery(document).ready(function($) {
			var ajaxUrl = '" . admin_url('admin-ajax.php') . "';
			var nonce = $('#brevo_nonce').val();

			// Refresh fields button
			$('#refresh-fields-btn').on('click', function() {
				var button = $(this);
				var apiKey = $('#global_api_key_ml_brevo').val();
				
				if (!apiKey) {
					showNotice('Please enter an API key first.', 'error');
					return;
				}

				button.prop('disabled', true).text('Refreshing...');

				$.post(ajaxUrl, {
					action: 'brevo_refresh_fields',
					api_key: apiKey,
					nonce: nonce
				})
				.done(function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						location.reload(); // Reload to show updated fields
					} else {
						showNotice(response.data.message, 'error');
					}
				})
				.fail(function() {
					showNotice('Request failed. Please try again.', 'error');
				})
				.always(function() {
					button.prop('disabled', false).text('Refresh Fields from Brevo');
				});
			});

			// Select all checkbox
			$('#select-all-fields').on('change', function() {
				$('.field-checkbox').prop('checked', this.checked);
				updateFieldStatuses();
			});

			// Individual checkboxes
			$(document).on('change', '.field-checkbox', function() {
				updateFieldStatuses();
			});

			// Enable all button
			$('#enable-all-btn').on('click', function() {
				$('.field-checkbox').prop('checked', true);
				updateFieldStatuses();
			});

			// Disable all button
			$('#disable-all-btn').on('click', function() {
				$('.field-checkbox').prop('checked', false);
				updateFieldStatuses();
			});

			// Reset to defaults button
			$('#reset-defaults-btn').on('click', function() {
				$('.field-checkbox').prop('checked', false);
				$('.field-checkbox').each(function() {
					var fieldName = $(this).val();
					if (['FIRSTNAME', 'LASTNAME', 'SMS'].includes(fieldName)) {
						$(this).prop('checked', true);
					}
				});
				updateFieldStatuses();
			});

			function updateFieldStatuses() {
				$('.field-checkbox').each(function() {
					var row = $(this).closest('tr');
					var statusCell = row.find('.brevo-status');
					if (this.checked) {
						statusCell.removeClass('disabled').addClass('enabled').text('Enabled');
					} else {
						statusCell.removeClass('enabled').addClass('disabled').text('Disabled');
					}
				});
			}

			function showNotice(message, type) {
				var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
				var notice = $('<div class=\"notice ' + noticeClass + ' is-dismissible\"><p>' + message + '</p></div>');
				$('#brevo-admin-notices').html(notice);
				
				// Auto-dismiss after 5 seconds
				setTimeout(function() {
					notice.fadeOut();
				}, 5000);
			}

			// Initial status update
			updateFieldStatuses();
		});
		";
	}

	/**
	 * Get admin CSS
	 */
	public function get_admin_css() {
		return "
		.brevo-field-management {
			margin-top: 20px;
			padding: 20px;
			background: #fff;
			border: 1px solid #ccd0d4;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}

		.brevo-field-controls {
			margin: 15px 0;
			padding: 10px;
			background: #f8f9fa;
			border: 1px solid #e1e5e9;
			border-radius: 4px;
		}

		.brevo-field-controls .button {
			margin-right: 10px;
		}

		.brevo-cache-info {
			margin-bottom: 10px;
			color: #666;
		}

		#brevo-fields-table {
			margin-top: 15px;
		}

		.brevo-field-badge {
			display: inline-block;
			padding: 2px 6px;
			font-size: 11px;
			border-radius: 3px;
			color: #fff;
			margin-left: 5px;
		}

		.brevo-default-field {
			background-color: #0073aa;
		}

		.brevo-field-type {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: 500;
		}

		.brevo-type-text {
			background-color: #e3f2fd;
			color: #1976d2;
		}

		.brevo-type-number {
			background-color: #fff3e0;
			color: #f57c00;
		}

		.brevo-type-date {
			background-color: #f3e5f5;
			color: #7b1fa2;
		}

		.brevo-type-boolean {
			background-color: #e8f5e8;
			color: #388e3c;
		}

		.brevo-type-category {
			background-color: #fce4ec;
			color: #c2185b;
		}

		.brevo-status.enabled {
			color: #0a7c42;
			font-weight: 600;
		}

		.brevo-status.disabled {
			color: #d93638;
		}

		#brevo-admin-notices {
			margin-bottom: 15px;
		}

		.brevo-field-management h2 {
			border-bottom: 1px solid #eee;
			padding-bottom: 10px;
		}
		";
	}

	public function ml_brevo_section_info() {
		echo "Here you can find all your ml Integration for Elementor Form - brevo settings";
	}

	public function global_api_key_ml_brevo_callback() {
		if (empty($this->ml_brevo_options['global_api_key_ml_brevo'])){
			printf(
				'<input class="regular-text" type="text" name="ml_brevo_option_name[global_api_key_ml_brevo]" id="global_api_key_ml_brevo" value="%s">',
				isset( $this->ml_brevo_options['global_api_key_ml_brevo'] ) ? esc_attr( $this->ml_brevo_options['global_api_key_ml_brevo']) : ''
			);
		}
		else{
			printf(
				'<input class="regular-text" type="password" name="ml_brevo_option_name[global_api_key_ml_brevo]" id="global_api_key_ml_brevo" value="%s">',
				isset( $this->ml_brevo_options['global_api_key_ml_brevo'] ) ? esc_attr( $this->ml_brevo_options['global_api_key_ml_brevo']) : ''
			);
		}
	}

}
if ( is_admin() )
	$ml_brevo = new MlbrevoFree();