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
add_action( 'wp_ajax_brevo_clear_cache', 'brevo_handle_clear_cache' );

// Handle AJAX requests for lists management
add_action( 'wp_ajax_brevo_refresh_lists', 'brevo_handle_refresh_lists' );
add_action( 'wp_ajax_brevo_update_list_settings', 'brevo_handle_list_settings_update' );

// Handle AJAX requests for debug functionality
add_action( 'wp_ajax_brevo_clear_debug_logs', 'brevo_handle_clear_debug_logs' );
add_action( 'wp_ajax_brevo_download_debug_log', 'brevo_handle_download_debug_log' );

function brevo_handle_refresh_fields() {
	$logger = Brevo_Debug_Logger::get_instance();
	$logger->info( 'Refresh fields request initiated', 'ADMIN', 'refresh_fields' );
	
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'brevo_admin_nonce' ) ) {
		$logger->warning( 'Refresh fields: Security check failed', 'ADMIN', 'refresh_fields' );
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		$logger->warning( 'Refresh fields: Insufficient permissions', 'ADMIN', 'refresh_fields', array(
			'user_id' => get_current_user_id()
		) );
		wp_die( 'Insufficient permissions' );
	}

	$api_key = sanitize_text_field( $_POST['api_key'] );
	
	if ( empty( $api_key ) ) {
		$logger->error( 'Refresh fields: API key is required', 'ADMIN', 'refresh_fields' );
		wp_send_json_error( array( 'message' => 'API key is required' ) );
	}

	$attributes_manager = Brevo_Attributes_Manager::get_instance();
	
	// Clear cache and fetch fresh data
	$attributes_manager->clear_cache( $api_key );
	$attributes = $attributes_manager->fetch_attributes( $api_key );

	if ( is_wp_error( $attributes ) ) {
		$logger->error( 'Refresh fields failed: ' . $attributes->get_error_message(), 'ADMIN', 'refresh_fields', array(
			'error_code' => $attributes->get_error_code(),
			'api_key_hash' => md5( $api_key )
		) );
		wp_send_json_error( array( 'message' => $attributes->get_error_message() ) );
	}

	$logger->info( 'Refresh fields completed successfully', 'ADMIN', 'refresh_fields', array(
		'attributes_count' => count( $attributes ),
		'api_key_hash' => md5( $api_key )
	) );

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

function brevo_handle_clear_cache() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'brevo_admin_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
	
	$attributes_manager = Brevo_Attributes_Manager::get_instance();
	
	if ( empty( $api_key ) ) {
		// Clear all cache
		$attributes_manager->clear_cache();
		$attributes_manager->clear_lists_cache();
		wp_send_json_success( array( 'message' => 'All cache cleared successfully' ) );
	} else {
		// Clear specific API key cache
		$attributes_manager->clear_cache( $api_key );
		$attributes_manager->clear_lists_cache( $api_key );
		wp_send_json_success( array( 'message' => 'Cache cleared successfully for the current API key' ) );
	}
}

function brevo_handle_refresh_lists() {
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
	$attributes_manager->clear_lists_cache( $api_key );
	$lists = $attributes_manager->fetch_lists( $api_key );

	if ( is_wp_error( $lists ) ) {
		wp_send_json_error( array( 'message' => $lists->get_error_message() ) );
	}

	wp_send_json_success( array( 
		'message' => sprintf( 'Successfully refreshed %d lists', count( $lists ) ),
		'lists' => $lists
	) );
}

function brevo_handle_list_settings_update() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'brevo_admin_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$selected_lists = array();
	if ( isset( $_POST['selected_lists'] ) && is_array( $_POST['selected_lists'] ) ) {
		foreach ( $_POST['selected_lists'] as $list_id ) {
			$list_id = intval( $list_id );
			if ( $list_id > 0 ) {
				$selected_lists[] = $list_id;
			}
		}
	}

	update_option( 'brevo_selected_lists', $selected_lists );

	wp_send_json_success( array( 'message' => 'List settings updated successfully' ) );
}

function brevo_handle_clear_debug_logs() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['nonce'], 'brevo_admin_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$logger = Brevo_Debug_Logger::get_instance();
	$logger->clear_all_logs();

	wp_send_json_success( array( 'message' => 'All debug logs cleared successfully' ) );
}

function brevo_handle_download_debug_log() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'brevo_download_log' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$filename = sanitize_file_name( $_GET['file'] ?? '' );
	if ( empty( $filename ) ) {
		wp_die( 'No file specified' );
	}

	$logger = Brevo_Debug_Logger::get_instance();
	$log_files = $logger->get_log_files();
	
	$file_path = '';
	foreach ( $log_files as $log_file ) {
		if ( basename( $log_file ) === $filename ) {
			$file_path = $log_file;
			break;
		}
	}

	if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
		wp_die( 'File not found' );
	}

	// Set headers for download
	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . filesize( $file_path ) );

	// Output file contents
	readfile( $file_path );
	exit;
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
		
		// Get current tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
		?>

		<div class="wrap">
			<h1>ML Brevo for Elementor Pro v2.0</h1>
			
			<?php $this->render_navigation_tabs( $current_tab ); ?>
			
			<div id="brevo-admin-notices"></div>

			<?php if ( $current_tab === 'settings' ): ?>
				<form method="post" action="options.php" id="brevo-settings-form">
					<?php
						settings_fields( 'ml_brevo_option_group' );
						do_settings_sections( 'ml-brevo-admin' );
					?>
					
					<?php $this->render_field_management_section( $api_key ); ?>
					
					<?php $this->render_lists_management_section( $api_key ); ?>
					
					<?php submit_button(); ?>
				</form>
			<?php elseif ( $current_tab === 'debug' ): ?>
				<?php $this->render_debug_tab(); ?>
			<?php endif; ?>
		</div>
		
		<?php wp_nonce_field( 'brevo_admin_nonce', 'brevo_nonce' ); ?>
	<?php }

	/**
	 * Render navigation tabs
	 */
	public function render_navigation_tabs( $current_tab ) {
		$tabs = array(
			'settings' => array(
				'title' => __( 'Settings & Configuration', 'ml-brevo-for-elementor-pro' ),
				'icon' => 'admin-settings'
			),
			'debug' => array(
				'title' => __( 'Debug Logs', 'ml-brevo-for-elementor-pro' ),
				'icon' => 'admin-tools'
			)
		);
		?>
		<nav class="nav-tab-wrapper wp-clearfix brevo-nav-tabs">
			<?php foreach ( $tabs as $tab_key => $tab_data ): ?>
				<a href="<?php echo admin_url( 'options-general.php?page=ml-brevo-free&tab=' . $tab_key ); ?>" 
				   class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-<?php echo esc_attr( $tab_data['icon'] ); ?>"></span>
					<?php echo esc_html( $tab_data['title'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Render debug tab content
	 */
	public function render_debug_tab() {
		$logger = Brevo_Debug_Logger::get_instance();
		
		// Get current parameters
		$current_file = isset( $_GET['file'] ) ? sanitize_text_field( $_GET['file'] ) : '';
		$current_level = isset( $_GET['level'] ) ? sanitize_text_field( $_GET['level'] ) : '';
		$current_component = isset( $_GET['component'] ) ? sanitize_text_field( $_GET['component'] ) : '';
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$entries_per_page = 50;

		// Get log files
		$log_files = $logger->get_log_files();
		$selected_file = $current_file && file_exists( $current_file ) ? $current_file : ( $log_files[0] ?? '' );

		// Get log entries
		$all_entries = array();
		if ( $selected_file ) {
			$all_entries = $logger->read_log_entries( $selected_file, 1000 ); // Get more for filtering
		}

		// Filter entries
		$filtered_entries = $this->filter_debug_entries( $all_entries, $current_level, $current_component );

		// Paginate entries
		$total_entries = count( $filtered_entries );
		$offset = ( $current_page - 1 ) * $entries_per_page;
		$entries = array_slice( $filtered_entries, $offset, $entries_per_page );

		// Calculate pagination
		$total_pages = ceil( $total_entries / $entries_per_page );

		?>
		<div class="brevo-debug-section">
			<?php if ( ! $logger->is_enabled() ): ?>
				<div class="notice notice-warning">
					<p>
						<?php _e( 'Debug logging is currently disabled.', 'ml-brevo-for-elementor-pro' ); ?>
						<a href="<?php echo admin_url( 'options-general.php?page=ml-brevo-free&tab=settings' ); ?>">
							<?php _e( 'Enable it in settings tab', 'ml-brevo-for-elementor-pro' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<!-- Debug Log Controls -->
			<div class="brevo-debug-controls">
				<div class="brevo-debug-info">
					<h3><?php _e( 'Log Information', 'ml-brevo-for-elementor-pro' ); ?></h3>
					<p>
						<strong><?php _e( 'Debug Status:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php echo $logger->is_enabled() ? 
							'<span style="color: green;">' . __( 'Enabled', 'ml-brevo-for-elementor-pro' ) . '</span>' : 
							'<span style="color: red;">' . __( 'Disabled', 'ml-brevo-for-elementor-pro' ) . '</span>'; ?>
					</p>
					<p>
						<strong><?php _e( 'Debug Level:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php echo esc_html( $logger->get_debug_level() ); ?>
					</p>
					<p>
						<strong><?php _e( 'Total Log Size:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php echo size_format( $logger->get_total_log_size() ); ?>
					</p>
					<p>
						<strong><?php _e( 'Log Files:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php echo count( $log_files ); ?>
					</p>
				</div>

				<div class="brevo-debug-actions">
					<h3><?php _e( 'Actions', 'ml-brevo-for-elementor-pro' ); ?></h3>
					<p>
						<button type="button" id="clear-debug-logs-btn" class="button button-secondary">
							<?php _e( 'Clear All Logs', 'ml-brevo-for-elementor-pro' ); ?>
						</button>
						<?php if ( $selected_file ): ?>
							<a href="<?php echo wp_nonce_url( 
								admin_url( 'admin-ajax.php?action=brevo_download_debug_log&file=' . urlencode( basename( $selected_file ) ) ), 
								'brevo_download_log' 
							); ?>" class="button button-secondary">
								<?php _e( 'Download Current Log', 'ml-brevo-for-elementor-pro' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
			</div>

			<!-- Filters -->
			<div class="brevo-debug-filters">
				<form method="get" action="">
					<input type="hidden" name="page" value="ml-brevo-free">
					<input type="hidden" name="tab" value="debug">
					
					<div class="filter-group">
						<label for="file-filter"><?php _e( 'Log File:', 'ml-brevo-for-elementor-pro' ); ?></label>
						<select name="file" id="file-filter">
							<?php foreach ( $log_files as $file ): ?>
								<option value="<?php echo esc_attr( $file ); ?>" <?php selected( $selected_file, $file ); ?>>
									<?php echo esc_html( basename( $file ) ); ?>
									(<?php echo size_format( filesize( $file ) ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="filter-group">
						<label for="level-filter"><?php _e( 'Level:', 'ml-brevo-for-elementor-pro' ); ?></label>
						<select name="level" id="level-filter">
							<option value=""><?php _e( 'All Levels', 'ml-brevo-for-elementor-pro' ); ?></option>
							<option value="ERROR" <?php selected( $current_level, 'ERROR' ); ?>><?php _e( 'ERROR', 'ml-brevo-for-elementor-pro' ); ?></option>
							<option value="WARNING" <?php selected( $current_level, 'WARNING' ); ?>><?php _e( 'WARNING', 'ml-brevo-for-elementor-pro' ); ?></option>
							<option value="INFO" <?php selected( $current_level, 'INFO' ); ?>><?php _e( 'INFO', 'ml-brevo-for-elementor-pro' ); ?></option>
							<option value="DEBUG" <?php selected( $current_level, 'DEBUG' ); ?>><?php _e( 'DEBUG', 'ml-brevo-for-elementor-pro' ); ?></option>
						</select>
					</div>

					<div class="filter-group">
						<label for="component-filter"><?php _e( 'Component:', 'ml-brevo-for-elementor-pro' ); ?></label>
						<select name="component" id="component-filter">
							<option value=""><?php _e( 'All Components', 'ml-brevo-for-elementor-pro' ); ?></option>
							<?php
							$components = $this->get_unique_debug_components( $all_entries );
							foreach ( $components as $component ):
							?>
								<option value="<?php echo esc_attr( $component ); ?>" <?php selected( $current_component, $component ); ?>>
									<?php echo esc_html( $component ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="filter-group">
						<input type="submit" class="button button-primary" value="<?php _e( 'Filter', 'ml-brevo-for-elementor-pro' ); ?>">
						<a href="<?php echo admin_url( 'options-general.php?page=ml-brevo-free&tab=debug' ); ?>" class="button button-secondary">
							<?php _e( 'Reset', 'ml-brevo-for-elementor-pro' ); ?>
						</a>
					</div>
				</form>
			</div>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ): ?>
				<div class="brevo-debug-pagination">
					<?php
					$base_url = add_query_arg( array(
						'page' => 'ml-brevo-free',
						'tab' => 'debug',
						'file' => $current_file,
						'level' => $current_level,
						'component' => $current_component
					), admin_url( 'options-general.php' ) );
					
					$pagination_args = array(
						'base' => add_query_arg( 'paged', '%#%', $base_url ),
						'format' => '',
						'prev_text' => __( '&laquo; Previous' ),
						'next_text' => __( 'Next &raquo;' ),
						'total' => $total_pages,
						'current' => $current_page,
						'show_all' => false,
						'type' => 'plain',
					);
					echo paginate_links( $pagination_args );
					?>
					<p class="brevo-debug-pagination-info">
						<?php printf( 
							__( 'Showing %d-%d of %d entries', 'ml-brevo-for-elementor-pro' ),
							$offset + 1,
							min( $offset + $entries_per_page, $total_entries ),
							$total_entries
						); ?>
					</p>
				</div>
			<?php endif; ?>

			<!-- Log Entries Table -->
			<?php if ( empty( $entries ) ): ?>
				<div class="notice notice-info inline">
					<p><?php _e( 'No log entries found.', 'ml-brevo-for-elementor-pro' ); ?></p>
				</div>
			<?php else: ?>
				<table class="wp-list-table widefat fixed striped brevo-debug-table">
					<thead>
						<tr>
							<th style="width: 140px;"><?php _e( 'Timestamp', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th style="width: 80px;"><?php _e( 'Level', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th style="width: 100px;"><?php _e( 'Component', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th style="width: 120px;"><?php _e( 'Action', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th><?php _e( 'Message', 'ml-brevo-for-elementor-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $entry ): ?>
							<tr class="brevo-log-entry brevo-log-<?php echo esc_attr( strtolower( $entry['level'] ) ); ?>">
								<td><?php echo esc_html( $entry['timestamp'] ); ?></td>
								<td>
									<span class="brevo-log-level brevo-level-<?php echo esc_attr( strtolower( $entry['level'] ) ); ?>">
										<?php echo esc_html( $entry['level'] ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $entry['component'] ); ?></td>
								<td><?php echo esc_html( $entry['action'] ); ?></td>
								<td>
									<div class="brevo-log-message">
										<?php echo esc_html( $entry['message'] ); ?>
										<?php if ( ! empty( $entry['context'] ) ): ?>
											<details class="brevo-log-context">
												<summary><?php _e( 'Context', 'ml-brevo-for-elementor-pro' ); ?></summary>
												<pre><?php echo esc_html( $entry['context'] ); ?></pre>
											</details>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Filter debug log entries
	 */
	private function filter_debug_entries( $entries, $level = '', $component = '' ) {
		if ( empty( $level ) && empty( $component ) ) {
			return $entries;
		}

		return array_filter( $entries, function( $entry ) use ( $level, $component ) {
			$level_match = empty( $level ) || $entry['level'] === $level;
			$component_match = empty( $component ) || $entry['component'] === $component;
			return $level_match && $component_match;
		} );
	}

	/**
	 * Get unique components from debug entries
	 */
	private function get_unique_debug_components( $entries ) {
		$components = array();
		foreach ( $entries as $entry ) {
			if ( ! empty( $entry['component'] ) && ! in_array( $entry['component'], $components ) ) {
				$components[] = $entry['component'];
			}
		}
		sort( $components );
		return $components;
	}

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
			echo '<p><button type="button" id="clear-cache-btn" class="button button-secondary">' . __( 'Clear Cache and Retry', 'ml-brevo-for-elementor-pro' ) . '</button></p>';
			echo '</div>';
			return;
		}

		// Ensure we have a valid attributes array
		if ( ! is_array( $attributes ) || empty( $attributes ) ) {
			echo '<div class="notice notice-warning inline">';
			echo '<p>' . __( 'No fields found. This could be due to an invalid API key or temporary API issues.', 'ml-brevo-for-elementor-pro' ) . '</p>';
			echo '<p><button type="button" id="clear-cache-btn" class="button button-secondary">' . __( 'Clear Cache and Retry', 'ml-brevo-for-elementor-pro' ) . '</button></p>';
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
					// Ensure field_data is an array and has required keys
					if ( ! is_array( $field_data ) ) {
						continue;
					}
					
					$field_name = sanitize_text_field( $field_name );
					$field_type = isset( $field_data['type'] ) ? sanitize_text_field( $field_data['type'] ) : 'text';
					$field_description = isset( $field_data['description'] ) ? sanitize_text_field( $field_data['description'] ) : '';
					$field_enabled_default = isset( $field_data['enabled'] ) ? (bool) $field_data['enabled'] : false;
					
					$is_enabled = isset( $enabled_fields[ $field_name ] ) || $field_enabled_default;
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
						<span class="brevo-field-type brevo-type-<?php echo esc_attr( $field_type ); ?>">
							<?php echo esc_html( ucfirst( $field_type ) ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $field_description ); ?></td>
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

	/**
	 * Render the lists management section
	 */
	public function render_lists_management_section( $api_key ) {
		?>
		<div class="brevo-lists-management">
			<h2><?php _e( 'Available Brevo Lists', 'ml-brevo-for-elementor-pro' ); ?></h2>
			<p><?php _e( 'Select which lists should be available for Elementor forms. Selected lists will be used as default options for form submissions.', 'ml-brevo-for-elementor-pro' ); ?></p>
			
			<div class="brevo-lists-controls">
				<button type="button" id="refresh-lists-btn" class="button button-secondary" 
					<?php echo empty( $api_key ) ? 'disabled' : ''; ?>>
					<?php _e( 'Refresh Lists from Brevo', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				
				<button type="button" id="select-all-lists-btn" class="button button-secondary">
					<?php _e( 'Select All Lists', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				
				<button type="button" id="deselect-all-lists-btn" class="button button-secondary">
					<?php _e( 'Deselect All Lists', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
			</div>

			<div id="lists-management-table-container">
				<?php $this->render_lists_table( $api_key ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the lists table
	 */
	public function render_lists_table( $api_key ) {
		if ( empty( $api_key ) ) {
			echo '<p class="notice notice-warning inline">' . 
				__( 'Please set your Global API Key above and save to manage lists.', 'ml-brevo-for-elementor-pro' ) . 
				'</p>';
			return;
		}

		$attributes_manager = Brevo_Attributes_Manager::get_instance();
		$lists = $attributes_manager->fetch_lists( $api_key );
		$selected_lists = get_option( 'brevo_selected_lists', array() );
		$cache_info = $attributes_manager->get_lists_cache_info( $api_key );

		if ( is_wp_error( $lists ) ) {
			echo '<div class="notice notice-error inline">';
			echo '<p>' . sprintf( __( 'Error fetching lists: %s', 'ml-brevo-for-elementor-pro' ), $lists->get_error_message() ) . '</p>';
			echo '<p><button type="button" id="clear-lists-cache-btn" class="button button-secondary">' . __( 'Clear Lists Cache and Retry', 'ml-brevo-for-elementor-pro' ) . '</button></p>';
			echo '</div>';
			return;
		}

		// Ensure we have a valid lists array
		if ( ! is_array( $lists ) || empty( $lists ) ) {
			echo '<div class="notice notice-warning inline">';
			echo '<p>' . __( 'No lists found. This could be due to an invalid API key or you may not have any lists created in your Brevo account.', 'ml-brevo-for-elementor-pro' ) . '</p>';
			echo '<p><button type="button" id="clear-lists-cache-btn" class="button button-secondary">' . __( 'Clear Lists Cache and Retry', 'ml-brevo-for-elementor-pro' ) . '</button></p>';
			echo '</div>';
			return;
		}

		?>
		<div class="brevo-lists-cache-info">
			<?php if ( $cache_info ): ?>
				<small>
					<?php printf( 
						__( 'Last updated: %s (%d lists found)', 'ml-brevo-for-elementor-pro' ),
						human_time_diff( $cache_info['cached_at'] ) . ' ago',
						$cache_info['count']
					); ?>
				</small>
			<?php endif; ?>
		</div>

		<table class="wp-list-table widefat fixed striped" id="brevo-lists-table">
			<thead>
				<tr>
					<th class="check-column">
						<input type="checkbox" id="select-all-lists-checkbox">
					</th>
					<th><?php _e( 'List ID', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php _e( 'List Name', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php _e( 'Subscribers', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php _e( 'Created', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php _e( 'Status', 'ml-brevo-for-elementor-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $lists as $list_id => $list_data ): 
					// Ensure list_data is an array and has required keys
					if ( ! is_array( $list_data ) ) {
						continue;
					}
					
					$list_id = intval( $list_id );
					$list_name = isset( $list_data['name'] ) ? sanitize_text_field( $list_data['name'] ) : '';
					$total_subscribers = isset( $list_data['totalSubscribers'] ) ? intval( $list_data['totalSubscribers'] ) : 0;
					$unique_subscribers = isset( $list_data['uniqueSubscribers'] ) ? intval( $list_data['uniqueSubscribers'] ) : 0;
					$created_at = isset( $list_data['createdAt'] ) ? sanitize_text_field( $list_data['createdAt'] ) : '';
					
					$is_selected = in_array( $list_id, $selected_lists );
					
					// Format creation date
					$created_display = '';
					if ( ! empty( $created_at ) ) {
						$created_timestamp = strtotime( $created_at );
						if ( $created_timestamp ) {
							$created_display = date_i18n( get_option( 'date_format' ), $created_timestamp );
						}
					}
				?>
				<tr>
					<td class="check-column">
						<input type="checkbox" name="brevo_lists[]" value="<?php echo esc_attr( $list_id ); ?>" 
							   <?php checked( $is_selected ); ?> class="list-checkbox">
					</td>
					<td>
						<strong><?php echo esc_html( $list_id ); ?></strong>
					</td>
					<td>
						<strong><?php echo esc_html( $list_name ); ?></strong>
					</td>
					<td>
						<?php printf( 
							__( '%s total / %s unique', 'ml-brevo-for-elementor-pro' ),
							number_format( $total_subscribers ),
							number_format( $unique_subscribers )
						); ?>
					</td>
					<td><?php echo esc_html( $created_display ); ?></td>
					<td>
						<span class="brevo-list-status <?php echo $is_selected ? 'selected' : 'not-selected'; ?>">
							<?php echo $is_selected ? __( 'Selected', 'ml-brevo-for-elementor-pro' ) : __( 'Not Selected', 'ml-brevo-for-elementor-pro' ); ?>
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

		add_settings_field(
			'debug_enabled_ml_brevo', // id
			'Debug Logging', // title
			array( $this, 'debug_enabled_ml_brevo_callback' ), // callback
			'ml-brevo-admin', // page
			'ml_brevo_setting_section' // section
		);

		add_settings_field(
			'debug_level_ml_brevo', // id
			'Debug Level', // title
			array( $this, 'debug_level_ml_brevo_callback' ), // callback
			'ml-brevo-admin', // page
			'ml_brevo_setting_section' // section
		);

		add_settings_field(
			'debug_retention_ml_brevo', // id
			'Log Retention (days)', // title
			array( $this, 'debug_retention_ml_brevo_callback' ), // callback
			'ml-brevo-admin', // page
			'ml_brevo_setting_section' // section
		);
	}

	public function ml_brevo_sanitize($input) {
		$sanitary_values = array();

		if ( isset( $input['global_api_key_ml_brevo'] ) ) {
			$sanitary_values['global_api_key_ml_brevo'] = sanitize_text_field( $input['global_api_key_ml_brevo'] );
		}

		// Handle debug settings
		$debug_enabled = isset( $input['debug_enabled_ml_brevo'] ) ? (bool) $input['debug_enabled_ml_brevo'] : false;
		$debug_level = isset( $input['debug_level_ml_brevo'] ) ? sanitize_text_field( $input['debug_level_ml_brevo'] ) : 'INFO';
		$debug_retention = isset( $input['debug_retention_ml_brevo'] ) ? intval( $input['debug_retention_ml_brevo'] ) : 7;

		// Validate debug level
		$valid_levels = array( 'ERROR', 'WARNING', 'INFO', 'DEBUG' );
		if ( ! in_array( $debug_level, $valid_levels ) ) {
			$debug_level = 'INFO';
		}

		// Validate retention days (between 1 and 90)
		if ( $debug_retention < 1 || $debug_retention > 90 ) {
			$debug_retention = 7;
		}

		// Update debug options separately
		update_option( 'brevo_debug_enabled', $debug_enabled );
		update_option( 'brevo_debug_level', $debug_level );
		update_option( 'brevo_debug_retention', $debug_retention );

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

		// Handle list settings update
		if ( isset( $_POST['brevo_lists'] ) && is_array( $_POST['brevo_lists'] ) ) {
			$selected_lists = array();
			foreach ( $_POST['brevo_lists'] as $list_id ) {
				$list_id = intval( $list_id );
				if ( $list_id > 0 ) {
					$selected_lists[] = $list_id;
				}
			}
			update_option( 'brevo_selected_lists', $selected_lists );
		} else {
			// No lists selected
			update_option( 'brevo_selected_lists', array() );
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

			// Clear API key button
			$('#clear-api-key-btn').on('click', function() {
				if (confirm('Are you sure you want to clear the API key? This will remove all field mappings.')) {
					$('#global_api_key_ml_brevo').val('').attr('type', 'text').attr('placeholder', 'Enter your Brevo API key');
					$('#clear-api-key-btn, #show-api-key-btn').hide();
					$('#field-management-table-container').html('<p class=\"notice notice-warning inline\">Please set your Global API Key above and save to manage fields.</p>');
				}
			});

			// Show API key button
			$('#show-api-key-btn').on('click', function() {
				var input = $('#global_api_key_ml_brevo');
				if (input.attr('type') === 'password') {
					input.attr('type', 'text');
					$(this).text('Hide');
				} else {
					input.attr('type', 'password');
					$(this).text('Show');
				}
			});

			// Clear cache button
			$(document).on('click', '#clear-cache-btn', function() {
				var button = $(this);
				var apiKey = $('#global_api_key_ml_brevo').val();
				
				button.prop('disabled', true).text('Clearing Cache...');

				$.post(ajaxUrl, {
					action: 'brevo_clear_cache',
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
					showNotice('Cache clear request failed. Please try again.', 'error');
				})
				.always(function() {
					button.prop('disabled', false).text('Clear Cache and Retry');
				});
			});

			// Refresh lists button
			$('#refresh-lists-btn').on('click', function() {
				var button = $(this);
				var apiKey = $('#global_api_key_ml_brevo').val();
				
				if (!apiKey) {
					showNotice('Please enter an API key first.', 'error');
					return;
				}

				button.prop('disabled', true).text('Refreshing Lists...');

				$.post(ajaxUrl, {
					action: 'brevo_refresh_lists',
					api_key: apiKey,
					nonce: nonce
				})
				.done(function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						location.reload(); // Reload to show updated lists
					} else {
						showNotice(response.data.message, 'error');
					}
				})
				.fail(function() {
					showNotice('Lists refresh request failed. Please try again.', 'error');
				})
				.always(function() {
					button.prop('disabled', false).text('Refresh Lists from Brevo');
				});
			});

			// Clear lists cache button
			$(document).on('click', '#clear-lists-cache-btn', function() {
				var button = $(this);
				var apiKey = $('#global_api_key_ml_brevo').val();
				
				button.prop('disabled', true).text('Clearing Lists Cache...');

				$.post(ajaxUrl, {
					action: 'brevo_clear_cache',
					api_key: apiKey,
					nonce: nonce
				})
				.done(function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						location.reload(); // Reload to show updated lists
					} else {
						showNotice(response.data.message, 'error');
					}
				})
				.fail(function() {
					showNotice('Lists cache clear request failed. Please try again.', 'error');
				})
				.always(function() {
					button.prop('disabled', false).text('Clear Lists Cache and Retry');
				});
			});

			// Select all lists checkbox
			$('#select-all-lists-checkbox').on('change', function() {
				$('.list-checkbox').prop('checked', this.checked);
				updateListStatuses();
			});

			// Individual list checkboxes
			$(document).on('change', '.list-checkbox', function() {
				updateListStatuses();
			});

			// Select all lists button
			$('#select-all-lists-btn').on('click', function() {
				$('.list-checkbox').prop('checked', true);
				updateListStatuses();
			});

			// Deselect all lists button
			$('#deselect-all-lists-btn').on('click', function() {
				$('.list-checkbox').prop('checked', false);
				updateListStatuses();
			});

			function updateListStatuses() {
				$('.list-checkbox').each(function() {
					var row = $(this).closest('tr');
					var statusCell = row.find('.brevo-list-status');
					if (this.checked) {
						statusCell.removeClass('not-selected').addClass('selected').text('Selected');
					} else {
						statusCell.removeClass('selected').addClass('not-selected').text('Not Selected');
					}
				});
			}

			// Clear debug logs button
			$('#clear-debug-logs-btn').on('click', function() {
				if (!confirm('Are you sure you want to clear all debug logs? This action cannot be undone.')) {
					return;
				}
				
				var button = $(this);
				button.prop('disabled', true).text('Clearing Logs...');

				$.post(ajaxUrl, {
					action: 'brevo_clear_debug_logs',
					nonce: nonce
				})
				.done(function(response) {
					if (response.success) {
						showNotice(response.data.message, 'success');
						location.reload(); // Reload to show empty logs
					} else {
						showNotice(response.data.message, 'error');
					}
				})
				.fail(function() {
					showNotice('Clear logs request failed. Please try again.', 'error');
				})
				.always(function() {
					button.prop('disabled', false).text('Clear All Logs');
				});
			});

			// Auto-refresh debug logs every 30 seconds if on debug tab
			if (window.location.href.indexOf('tab=debug') !== -1) {
				setInterval(function() {
					var currentUrl = window.location.href;
					if (currentUrl.indexOf('tab=debug') !== -1 && !currentUrl.match(/[?&]paged=/)) {
						// Only auto-refresh if we're on the first page
						location.reload();
					}
				}, 30000);
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
		.brevo-field-management,
		.brevo-lists-management {
			margin-top: 20px;
			padding: 20px;
			background: #fff;
			border: 1px solid #ccd0d4;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}

		.brevo-field-controls,
		.brevo-lists-controls {
			margin: 15px 0;
			padding: 10px;
			background: #f8f9fa;
			border: 1px solid #e1e5e9;
			border-radius: 4px;
		}

		.brevo-field-controls .button,
		.brevo-lists-controls .button {
			margin-right: 10px;
		}

		.brevo-cache-info,
		.brevo-lists-cache-info {
			margin-bottom: 10px;
			color: #666;
		}

		#brevo-fields-table,
		#brevo-lists-table {
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

		.brevo-status.enabled,
		.brevo-list-status.selected {
			color: #0a7c42;
			font-weight: 600;
		}

		.brevo-status.disabled,
		.brevo-list-status.not-selected {
			color: #d93638;
		}

		#brevo-admin-notices {
			margin-bottom: 15px;
		}

		.brevo-field-management h2,
		.brevo-lists-management h2 {
			border-bottom: 1px solid #eee;
			padding-bottom: 10px;
		}

		.brevo-api-key-field {
			margin-bottom: 20px;
		}

		.brevo-api-key-field input[type='text'],
		.brevo-api-key-field input[type='password'] {
			width: 400px;
		}

		.brevo-api-key-field .description {
			margin-top: 8px;
			font-style: italic;
		}

		/* Navigation Tabs */
		.brevo-nav-tabs {
			margin-bottom: 20px;
		}

		.brevo-nav-tabs .nav-tab {
			display: inline-flex;
			align-items: center;
			gap: 8px;
		}

		.brevo-nav-tabs .nav-tab .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
		}

		/* Debug Section */
		.brevo-debug-section {
			background: #fff;
			border: 1px solid #ccd0d4;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
			padding: 20px;
		}

		.brevo-debug-controls {
			display: flex;
			gap: 30px;
			margin-bottom: 20px;
			padding: 15px;
			background: #f8f9fa;
			border: 1px solid #e1e5e9;
			border-radius: 4px;
		}

		.brevo-debug-info h3,
		.brevo-debug-actions h3 {
			margin-top: 0;
			margin-bottom: 10px;
			color: #23282d;
		}

		.brevo-debug-info p {
			margin: 5px 0;
		}

		.brevo-debug-filters {
			margin-bottom: 20px;
			padding: 15px;
			background: #f8f9fa;
			border: 1px solid #e1e5e9;
			border-radius: 4px;
		}

		.brevo-debug-filters form {
			display: flex;
			flex-wrap: wrap;
			gap: 15px;
			align-items: end;
		}

		.brevo-debug-filters .filter-group {
			display: flex;
			flex-direction: column;
			gap: 5px;
		}

		.brevo-debug-filters label {
			font-weight: 600;
			color: #23282d;
		}

		.brevo-debug-filters select {
			min-width: 150px;
		}

		.brevo-debug-table {
			margin-top: 15px;
		}

		.brevo-debug-table th {
			background: #f1f1f1;
		}

		.brevo-log-entry.brevo-log-error {
			background-color: #ffeaea;
		}

		.brevo-log-entry.brevo-log-warning {
			background-color: #fff8e1;
		}

		.brevo-log-entry.brevo-log-info {
			background-color: #e3f2fd;
		}

		.brevo-log-entry.brevo-log-debug {
			background-color: #f3e5f5;
		}

		.brevo-log-level {
			display: inline-block;
			padding: 2px 6px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			color: #fff;
		}

		.brevo-level-error {
			background-color: #d32f2f;
		}

		.brevo-level-warning {
			background-color: #f57c00;
		}

		.brevo-level-info {
			background-color: #1976d2;
		}

		.brevo-level-debug {
			background-color: #7b1fa2;
		}

		.brevo-log-message {
			font-family: 'Courier New', monospace;
			font-size: 13px;
		}

		.brevo-log-context {
			margin-top: 8px;
		}

		.brevo-log-context summary {
			cursor: pointer;
			color: #0073aa;
			font-size: 12px;
		}

		.brevo-log-context pre {
			background: #f6f7f7;
			border: 1px solid #ddd;
			border-radius: 3px;
			padding: 10px;
			margin: 5px 0 0 0;
			font-size: 11px;
			max-height: 200px;
			overflow-y: auto;
		}

		.brevo-debug-pagination {
			margin: 20px 0;
			text-align: center;
		}

		.brevo-debug-pagination-info {
			margin-top: 10px;
			color: #666;
			font-size: 13px;
		}

		/* Responsive design */
		@media (max-width: 768px) {
			.brevo-debug-controls {
				flex-direction: column;
				gap: 15px;
			}

			.brevo-debug-filters form {
				flex-direction: column;
				align-items: stretch;
			}

			.brevo-debug-filters .filter-group {
				flex-direction: row;
				align-items: center;
				justify-content: space-between;
			}

			.brevo-debug-filters select {
				min-width: auto;
				flex: 1;
			}
		}
		";
	}

	public function ml_brevo_section_info() {
		echo "Here you can find all your ml Integration for Elementor Form - brevo settings";
	}

	public function global_api_key_ml_brevo_callback() {
		$api_key = isset( $this->ml_brevo_options['global_api_key_ml_brevo'] ) ? $this->ml_brevo_options['global_api_key_ml_brevo'] : '';
		$has_key = !empty($api_key);
		
		?>
		<div class="brevo-api-key-field">
			<input class="regular-text" 
				   type="<?php echo $has_key ? 'password' : 'text'; ?>" 
				   name="ml_brevo_option_name[global_api_key_ml_brevo]" 
				   id="global_api_key_ml_brevo" 
				   value="<?php echo esc_attr( $api_key ); ?>"
				   placeholder="<?php echo $has_key ? '••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••' : 'Enter your Brevo API key'; ?>">
			
			<?php if ( $has_key ): ?>
				<button type="button" id="clear-api-key-btn" class="button button-secondary" style="margin-left: 10px;">
					<?php _e( 'Clear API Key', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				<button type="button" id="show-api-key-btn" class="button button-secondary" style="margin-left: 5px;">
					<?php _e( 'Show', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
			<?php endif; ?>
			
			<p class="description">
				<?php _e( 'Enter your Brevo API key. You can find it in your Brevo account under Account Settings > API Keys.', 'ml-brevo-for-elementor-pro' ); ?>
				<?php if ( $has_key ): ?>
					<br><strong><?php _e( 'API key is currently set. Use "Clear API Key" to remove it.', 'ml-brevo-for-elementor-pro' ); ?></strong>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	public function debug_enabled_ml_brevo_callback() {
		$debug_enabled = get_option( 'brevo_debug_enabled', false );
		?>
		<div class="brevo-debug-setting">
			<label>
				<input type="checkbox" name="ml_brevo_option_name[debug_enabled_ml_brevo]" value="1" <?php checked( $debug_enabled ); ?>>
				<?php _e( 'Enable debug logging', 'ml-brevo-for-elementor-pro' ); ?>
			</label>
			<p class="description">
				<?php _e( 'When enabled, the plugin will log detailed information about API calls, form submissions, and errors to help with troubleshooting.', 'ml-brevo-for-elementor-pro' ); ?>
				<?php if ( $debug_enabled ): ?>
					<br><strong><?php _e( 'Debug logging is currently ENABLED.', 'ml-brevo-for-elementor-pro' ); ?></strong>
					<a href="<?php echo admin_url( 'options-general.php?page=ml-brevo-debug-viewer' ); ?>" class="button button-small">
						<?php _e( 'View Debug Logs', 'ml-brevo-for-elementor-pro' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	public function debug_level_ml_brevo_callback() {
		$debug_level = get_option( 'brevo_debug_level', 'INFO' );
		$debug_enabled = get_option( 'brevo_debug_enabled', false );
		?>
		<select name="ml_brevo_option_name[debug_level_ml_brevo]" <?php echo $debug_enabled ? '' : 'disabled'; ?>>
			<option value="ERROR" <?php selected( $debug_level, 'ERROR' ); ?>><?php _e( 'ERROR - Only errors', 'ml-brevo-for-elementor-pro' ); ?></option>
			<option value="WARNING" <?php selected( $debug_level, 'WARNING' ); ?>><?php _e( 'WARNING - Errors and warnings', 'ml-brevo-for-elementor-pro' ); ?></option>
			<option value="INFO" <?php selected( $debug_level, 'INFO' ); ?>><?php _e( 'INFO - Errors, warnings, and info', 'ml-brevo-for-elementor-pro' ); ?></option>
			<option value="DEBUG" <?php selected( $debug_level, 'DEBUG' ); ?>><?php _e( 'DEBUG - All messages (verbose)', 'ml-brevo-for-elementor-pro' ); ?></option>
		</select>
		<p class="description">
			<?php _e( 'Select the minimum level of messages to log. DEBUG level will create larger log files.', 'ml-brevo-for-elementor-pro' ); ?>
		</p>
		<?php
	}

	public function debug_retention_ml_brevo_callback() {
		$debug_retention = get_option( 'brevo_debug_retention', 7 );
		$debug_enabled = get_option( 'brevo_debug_enabled', false );
		?>
		<input type="number" name="ml_brevo_option_name[debug_retention_ml_brevo]" 
			   value="<?php echo esc_attr( $debug_retention ); ?>" 
			   min="1" max="90" 
			   <?php echo $debug_enabled ? '' : 'disabled'; ?>>
		<p class="description">
			<?php _e( 'Number of days to keep log files. Older logs will be automatically deleted. Range: 1-90 days.', 'ml-brevo-for-elementor-pro' ); ?>
		</p>
		<?php
	}

}
if ( is_admin() )
	$ml_brevo = new MlbrevoFree();