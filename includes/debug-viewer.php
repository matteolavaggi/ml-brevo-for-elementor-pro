<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Debug Log Viewer
 * 
 * Provides an interface for viewing and managing debug logs
 * 
 * @since 2.0.0
 */
class Brevo_Debug_Viewer {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Debug menu is now integrated into main settings, so we don't add a separate menu
		// add_action( 'admin_menu', array( $this, 'add_debug_menu' ) );
		
		// AJAX handlers are now handled in settings.php to avoid conflicts
		// add_action( 'wp_ajax_brevo_clear_debug_logs', array( $this, 'handle_clear_logs' ) );
		// add_action( 'wp_ajax_brevo_download_debug_log', array( $this, 'handle_download_log' ) );
	}

	/**
	 * Add debug menu to WordPress admin
	 */
	public function add_debug_menu() {
		add_submenu_page(
			'options-general.php',
			'ML Brevo Debug Logs',
			'ML Brevo Debug',
			'manage_options',
			'ml-brevo-debug-viewer',
			array( $this, 'render_debug_page' )
		);
	}

	/**
	 * Render the debug log viewer page
	 */
	public function render_debug_page() {
		$logger = Brevo_Debug_Logger::get_instance();
		
		// Get current parameters
		$current_file = isset( $_GET['file'] ) ? sanitize_text_field( wp_unslash( $_GET['file'] ) ) : '';
		$current_level = isset( $_GET['level'] ) ? sanitize_text_field( wp_unslash( $_GET['level'] ) ) : '';
		$current_component = isset( $_GET['component'] ) ? sanitize_text_field( wp_unslash( $_GET['component'] ) ) : '';
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$entries_per_page = 50;

		// Nonce verification for actions
		if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'brevo_download_debug_log' ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'brevo_download_log' ) ) {
				wp_die( 'Security check failed' );
			}
		}

		// Get log files
		$log_files = $logger->get_log_files();
		$selected_file = $current_file && file_exists( $current_file ) ? $current_file : ( $log_files[0] ?? '' );

		// Get log entries
		$all_entries = array();
		if ( $selected_file ) {
			$all_entries = $logger->read_log_entries( $selected_file, 1000 ); // Get more for filtering
		}

		// Filter entries
		$filtered_entries = $this->filter_entries( $all_entries, $current_level, $current_component );

		// Paginate entries
		$total_entries = count( $filtered_entries );
		$offset = ( $current_page - 1 ) * $entries_per_page;
		$entries = array_slice( $filtered_entries, $offset, $entries_per_page );

		// Calculate pagination
		$total_pages = ceil( $total_entries / $entries_per_page );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ML Brevo Debug Logs', 'ml-brevo-for-elementor-pro' ); ?></h1>

			<?php if ( ! $logger->is_enabled() ): ?>
				<div class="notice notice-warning">
					<p>
						<?php esc_html_e( 'Debug logging is currently disabled.', 'ml-brevo-for-elementor-pro' ); ?>
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=ml-brevo-free' ) ); ?>">
							<?php esc_html_e( 'Enable it in settings', 'ml-brevo-for-elementor-pro' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<div id="brevo-debug-notices"></div>

			<!-- Debug Log Controls -->
			<div class="brevo-debug-controls">
				<div class="brevo-debug-info">
					<h3><?php esc_html_e( 'Log Information', 'ml-brevo-for-elementor-pro' ); ?></h3>
					<p>
						<strong><?php esc_html_e( 'Debug Status:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php echo $logger->is_enabled() ? 
							'<span style="color: green;">' . esc_html__( 'Enabled', 'ml-brevo-for-elementor-pro' ) . '</span>' : 
							'<span style="color: red;">' . esc_html__( 'Disabled', 'ml-brevo-for-elementor-pro' ) . '</span>'; ?>
					</p>
					<p>
						<strong><?php esc_html_e( 'Debug Level:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php echo esc_html( $logger->get_debug_level() ); ?>
					</p>
					<p>
						<strong><?php esc_html_e( 'Total Log Size:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php echo esc_html( size_format( $logger->get_total_log_size() ) ); ?>
					</p>
					<p>
						<strong><?php esc_html_e( 'Log Files:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php echo count( $log_files ); ?>
					</p>
				</div>

				<div class="brevo-debug-actions">
					<h3><?php esc_html_e( 'Actions', 'ml-brevo-for-elementor-pro' ); ?></h3>
					<p>
						<button type="button" id="clear-debug-logs-btn" class="button button-secondary">
							<?php esc_html_e( 'Clear All Logs', 'ml-brevo-for-elementor-pro' ); ?>
						</button>
						<?php if ( $selected_file ): ?>
							<a href="<?php echo esc_url( wp_nonce_url( 
								admin_url( 'admin-ajax.php?action=brevo_download_debug_log&file=' . urlencode( basename( $selected_file ) ) ), 
								'brevo_download_log' 
							) ); ?>" class="button button-secondary">
								<?php esc_html_e( 'Download Current Log', 'ml-brevo-for-elementor-pro' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
			</div>

			<!-- Filters -->
			<div class="brevo-debug-filters">
				<form method="get" action="">
					<input type="hidden" name="page" value="ml-brevo-debug-viewer">
					
					<div class="filter-group">
						<label for="file-filter"><?php esc_html_e( 'Log File:', 'ml-brevo-for-elementor-pro' ); ?></label>
						<select name="file" id="file-filter">
							<?php foreach ( $log_files as $file ): ?>
								<option value="<?php echo esc_attr( $file ); ?>" <?php selected( $selected_file, $file ); ?>>
									<?php echo esc_html( basename( $file ) ); ?>
									(<?php echo esc_html( size_format( filesize( $file ) ) ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="filter-group">
						<label for="level-filter"><?php esc_html_e( 'Level:', 'ml-brevo-for-elementor-pro' ); ?></label>
						<select name="level" id="level-filter">
							<option value=""><?php esc_html_e( 'All Levels', 'ml-brevo-for-elementor-pro' ); ?></option>
							<option value="ERROR" <?php selected( $current_level, 'ERROR' ); ?>><?php esc_html_e( 'ERROR', 'ml-brevo-for-elementor-pro' ); ?></option>
							<option value="WARNING" <?php selected( $current_level, 'WARNING' ); ?>><?php esc_html_e( 'WARNING', 'ml-brevo-for-elementor-pro' ); ?></option>
							<option value="INFO" <?php selected( $current_level, 'INFO' ); ?>><?php esc_html_e( 'INFO', 'ml-brevo-for-elementor-pro' ); ?></option>
							<option value="DEBUG" <?php selected( $current_level, 'DEBUG' ); ?>><?php esc_html_e( 'DEBUG', 'ml-brevo-for-elementor-pro' ); ?></option>
						</select>
					</div>

					<div class="filter-group">
						<label for="component-filter"><?php esc_html_e( 'Component:', 'ml-brevo-for-elementor-pro' ); ?></label>
						<select name="component" id="component-filter">
							<option value=""><?php esc_html_e( 'All Components', 'ml-brevo-for-elementor-pro' ); ?></option>
							<?php
							$components = $this->get_unique_components( $all_entries );
							foreach ( $components as $component ):
							?>
								<option value="<?php echo esc_attr( $component ); ?>" <?php selected( $current_component, $component ); ?>>
									<?php echo esc_html( $component ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="filter-group">
						<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Filter', 'ml-brevo-for-elementor-pro' ); ?>">
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=ml-brevo-debug-viewer' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Reset', 'ml-brevo-for-elementor-pro' ); ?>
						</a>
					</div>
				</form>
			</div>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ): ?>
				<div class="brevo-debug-pagination">
					<?php
					$pagination_args = array(
						'base' => add_query_arg( 'paged', '%#%' ),
						'format' => '',
						'prev_text' => esc_html__( '<&laquo; Previous', 'ml-brevo-for-elementor-pro' ),
						'next_text' => esc_html__( 'Next &raquo;>', 'ml-brevo-for-elementor-pro' ),
						'total' => $total_pages,
						'current' => $current_page,
						'show_all' => false,
						'type' => 'plain',
					);
					echo wp_kses_post( paginate_links( $pagination_args ) );
					?>
					<p class="pagination-info">
						<?php 
						/* translators: %1$d is the start entry number, %2$d is the end entry number, %3$d is the total number of entries */
						printf( esc_html__( 'Showing %1$d-%2$d of %3$d entries', 'ml-brevo-for-elementor-pro' ), absint( $offset ) + 1, absint( min( absint( $offset ) + absint( $entries_per_page ), absint( $total_entries ) ) ), absint( $total_entries ) ); ?>
					</p>
				</div>
			<?php endif; ?>

			<!-- Log Entries -->
			<div class="brevo-debug-entries">
				<?php if ( empty( $entries ) ): ?>
					<div class="notice notice-info inline">
						<p><?php esc_html_e( 'No log entries found.', 'ml-brevo-for-elementor-pro' ); ?></p>
					</div>
				<?php else: ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th style="width: 150px;"><?php esc_html_e( 'Timestamp', 'ml-brevo-for-elementor-pro' ); ?></th>
								<th style="width: 80px;"><?php esc_html_e( 'Level', 'ml-brevo-for-elementor-pro' ); ?></th>
								<th style="width: 100px;"><?php esc_html_e( 'Component', 'ml-brevo-for-elementor-pro' ); ?></th>
								<th style="width: 100px;"><?php esc_html_e( 'Action', 'ml-brevo-for-elementor-pro' ); ?></th>
								<th><?php esc_html_e( 'Message', 'ml-brevo-for-elementor-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $entries as $entry ): ?>
								<tr class="debug-entry debug-level-<?php echo esc_attr( strtolower( $entry['level'] ) ); ?>">
									<td><?php echo esc_html( $entry['timestamp'] ); ?></td>
									<td>
										<span class="debug-level-badge debug-level-<?php echo esc_attr( strtolower( $entry['level'] ) ); ?>">
											<?php echo esc_html( $entry['level'] ); ?>
										</span>
									</td>
									<td><?php echo esc_html( $entry['component'] ); ?></td>
									<td><?php echo esc_html( $entry['action'] ); ?></td>
									<td>
										<div class="debug-message">
											<?php echo esc_html( $entry['message'] ); ?>
											<?php if ( ! empty( $entry['context'] ) ): ?>
												<details class="debug-context">
													<summary><?php esc_html_e( 'Context', 'ml-brevo-for-elementor-pro' ); ?></summary>
													<pre><?php echo esc_html( json_encode( $entry['context'], JSON_PRETTY_PRINT ) ); ?></pre>
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

			<!-- Pagination (bottom) -->
			<?php if ( $total_pages > 1 ): ?>
				<div class="brevo-debug-pagination">
					<?php echo wp_kses_post( paginate_links( $pagination_args ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<style>
			.brevo-debug-controls {
				display: flex;
				gap: 20px;
				margin: 20px 0;
				padding: 15px;
				background: #f8f9fa;
				border: 1px solid #e1e5e9;
				border-radius: 4px;
			}

			.brevo-debug-info,
			.brevo-debug-actions {
				flex: 1;
			}

			.brevo-debug-filters {
				margin: 20px 0;
				padding: 15px;
				background: #fff;
				border: 1px solid #ccd0d4;
				border-radius: 4px;
			}

			.brevo-debug-filters form {
				display: flex;
				gap: 15px;
				align-items: end;
				flex-wrap: wrap;
			}

			.filter-group {
				display: flex;
				flex-direction: column;
				gap: 5px;
			}

			.filter-group label {
				font-weight: 600;
				font-size: 12px;
			}

			.brevo-debug-pagination {
				margin: 20px 0;
				text-align: center;
			}

			.pagination-info {
				margin-top: 10px;
				color: #666;
				font-size: 12px;
			}

			.debug-level-badge {
				display: inline-block;
				padding: 2px 6px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: 600;
				color: #fff;
			}

			.debug-level-error {
				background-color: #dc3545;
			}

			.debug-level-warning {
				background-color: #ffc107;
				color: #000;
			}

			.debug-level-info {
				background-color: #17a2b8;
			}

			.debug-level-debug {
				background-color: #6c757d;
			}

			.debug-context {
				margin-top: 10px;
			}

			.debug-context summary {
				cursor: pointer;
				font-weight: 600;
				color: #0073aa;
			}

			.debug-context pre {
				background: #f8f9fa;
				padding: 10px;
				border-radius: 4px;
				font-size: 12px;
				max-height: 200px;
				overflow-y: auto;
			}

			.debug-message {
				word-break: break-word;
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				// Clear logs button
				$('#clear-debug-logs-btn').on('click', function() {
					if (!confirm('<?php esc_attr_e( 'Are you sure you want to clear all debug logs? This action cannot be undone.', 'ml-brevo-for-elementor-pro' ); ?>')) {
						return;
					}

					var button = $(this);
					button.prop('disabled', true).text('<?php esc_attr_e( 'Clearing...', 'ml-brevo-for-elementor-pro' ); ?>');

					$.post(ajaxurl, {
						action: 'brevo_clear_debug_logs',
						nonce: '<?php echo esc_js( wp_create_nonce( 'brevo_clear_logs' ) ); ?>'
					})
					.done(function(response) {
						if (response.success) {
							location.reload();
						} else {
							alert('<?php esc_attr_e( 'Error clearing logs:', 'ml-brevo-for-elementor-pro' ); ?> ' + response.data.message);
						}
					})
					.fail(function() {
						alert('<?php esc_attr_e( 'Request failed. Please try again.', 'ml-brevo-for-elementor-pro' ); ?>');
					})
					.always(function() {
						button.prop('disabled', false).text('<?php esc_attr_e( 'Clear All Logs', 'ml-brevo-for-elementor-pro' ); ?>');
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Filter log entries based on criteria
	 *
	 * @param array  $entries   Log entries
	 * @param string $level     Level filter
	 * @param string $component Component filter
	 * @return array Filtered entries
	 */
	private function filter_entries( $entries, $level = '', $component = '' ) {
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
	 * Get unique components from log entries
	 *
	 * @param array $entries Log entries
	 * @return array Unique components
	 */
	private function get_unique_components( $entries ) {
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
	 * Handle AJAX request to clear logs
	 */
	public function handle_clear_logs() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'brevo_clear_logs' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$logger = Brevo_Debug_Logger::get_instance();
		$logger->clear_logs();

		wp_send_json_success( array( 'message' => 'Debug logs cleared successfully' ) );
	}

	/**
	 * Handle log file download
	 */
	public function handle_download_log() {
		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'brevo_download_log' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$filename = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';
		$logger = Brevo_Debug_Logger::get_instance();
		$log_files = $logger->get_log_files();
		
		// Find the requested file
		$file_path = '';
		foreach ( $log_files as $file ) {
			if ( basename( $file ) === $filename ) {
				$file_path = $file;
				break;
			}
		}

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			wp_die( 'Log file not found' );
		}

		// Send file
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		
		// Output file contents using WP_Filesystem
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		$file_contents = $wp_filesystem->get_contents( $file_path );
		if ( $file_contents !== false ) {
			echo $file_contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- File contents are safe
		}
		exit;
	}
}

// Initialize debug viewer
if ( is_admin() ) {
	new Brevo_Debug_Viewer();
} 