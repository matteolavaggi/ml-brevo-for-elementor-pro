<?php

// Link to support and pro page from plugins screen
function ml_brevo_filter_action_links( $links ) {

	$links['settings'] = '<a href="' . esc_url( admin_url( 'options-general.php?page=ml-brevo-free' ) ) . '">' . esc_html__( 'Settings', 'ml-brevo-for-elementor-pro' ) . '</a>';
	$links['support']  = '<a href="https://www.matteolavaggi.it/wordpress/ml-brevo-for-elementor-pro/" target="_blank">Support</a>';
	return $links;
}
add_filter( 'plugin_action_links_ml-brevo-for-elementor-pro/ml-brevo-for-elementor-pro.php', 'ml_brevo_filter_action_links', 10, 3 );

// Handle AJAX requests for field management
add_action( 'wp_ajax_brevo_refresh_fields', 'brevo_handle_refresh_fields' );
add_action( 'wp_ajax_brevo_update_field_settings', 'brevo_handle_field_settings_update' );


// Handle AJAX requests for lists management
add_action( 'wp_ajax_brevo_refresh_lists', 'brevo_handle_refresh_lists' );

// Handle AJAX requests for debug functionality
add_action( 'wp_ajax_brevo_clear_debug_logs', 'brevo_handle_clear_debug_logs' );
add_action( 'wp_ajax_brevo_download_debug_log', 'brevo_handle_download_debug_log' );

function brevo_handle_refresh_fields() {
	$logger = Brevo_Debug_Logger::get_instance();
	$logger->info( 'Refresh fields request initiated', 'ADMIN', 'refresh_fields' );

	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'brevo_admin_nonce' ) ) {
		$logger->warning( 'Refresh fields: Security check failed', 'ADMIN', 'refresh_fields' );
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		$logger->warning(
			'Refresh fields: Insufficient permissions',
			'ADMIN',
			'refresh_fields',
			array(
				'user_id' => get_current_user_id(),
			)
		);
		wp_die( 'Insufficient permissions' );
	}

	$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

	if ( empty( $api_key ) ) {
		$logger->error( 'Refresh fields: API key is required', 'ADMIN', 'refresh_fields' );
		wp_send_json_error( array( 'message' => 'API key is required' ) );
	}

	$attributes_manager = Brevo_Attributes_Manager::get_instance();

	// Fetch fresh data from API
	$attributes = $attributes_manager->fetch_all_attributes( $api_key );

	if ( is_wp_error( $attributes ) ) {
		$logger->error(
			'Refresh fields failed: ' . $attributes->get_error_message(),
			'ADMIN',
			'refresh_fields',
			array(
				'error_code'   => $attributes->get_error_code(),
				'api_key_hash' => md5( $api_key ),
			)
		);
		wp_send_json_error( array( 'message' => $attributes->get_error_message() ) );
	}

	$logger->info(
		'Refresh fields completed successfully',
		'ADMIN',
		'refresh_fields',
		array(
			'attributes_count' => count( $attributes ),
			'api_key_hash'     => md5( $api_key ),
		)
	);

	wp_send_json_success(
		array(
			'message' => sprintf( 'Successfully refreshed %d fields', count( $attributes ) ),
			'fields'  => $attributes,
		)
	);
}

function brevo_handle_field_settings_update() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'brevo_admin_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$enabled_fields = array();
	if ( isset( $_POST['enabled_fields'] ) && is_array( $_POST['enabled_fields'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array will be sanitized in loop
		$enabled_fields_input = wp_unslash( $_POST['enabled_fields'] );
		foreach ( $enabled_fields_input as $field ) {
			$enabled_fields[ sanitize_text_field( $field ) ] = true;
		}
	}

	update_option( 'brevo_enabled_fields', $enabled_fields );

	wp_send_json_success( array( 'message' => 'Field settings updated successfully' ) );
}



function brevo_handle_refresh_lists() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'brevo_admin_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

	if ( empty( $api_key ) ) {
		wp_send_json_error( array( 'message' => 'API key is required' ) );
	}

	$attributes_manager = Brevo_Attributes_Manager::get_instance();

	// Fetch fresh data from API
	$lists = $attributes_manager->fetch_all_lists( $api_key );

	if ( is_wp_error( $lists ) ) {
		$logger = Brevo_Debug_Logger::get_instance();
		$logger->error(
			'Refresh lists failed: ' . $lists->get_error_message(),
			'ADMIN',
			'refresh_lists',
			array(
				'error_code' => $lists->get_error_code(),
				'api_key_hash' => md5( $api_key ),
			)
		);
		wp_send_json_error( array( 'message' => $lists->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message' => sprintf( 'Successfully refreshed %d lists', count( $lists ) ),
			'lists'   => $lists,
		)
	);
}



function brevo_handle_clear_debug_logs() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'brevo_admin_nonce' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$logger = Brevo_Debug_Logger::get_instance();
	$logger->clear_logs();

	wp_send_json_success( array( 'message' => 'All debug logs cleared successfully' ) );
}

function brevo_handle_download_debug_log() {
	// Verify nonce
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'brevo_download_log' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$filename = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';
	if ( empty( $filename ) ) {
		wp_die( 'No file specified' );
	}

	$logger    = Brevo_Debug_Logger::get_instance();
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

// Add global site setting API Key option
class MlbrevoFree {
	private $ml_brevo_free_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'ml_brevo_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'ml_brevo_page_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Remove WordPress footer text on plugin pages
		add_filter( 'admin_footer_text', array( $this, 'remove_admin_footer_text' ) );
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
		$api_key                = $this->ml_brevo_options['global_api_key_ml_brevo'] ?? '';

		// Get current tab
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation doesn't require nonce
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';
		?>

		<div class="wrap">
			<h1>ML Brevo for Elementor Pro</h1>
			
			<?php $this->render_promotional_banner(); ?>
			
			<?php $this->render_navigation_tabs( $current_tab ); ?>
			
			<div id="brevo-admin-notices"></div>

			<?php if ( $current_tab === 'settings' ) : ?>
				<form method="post" action="options.php" id="brevo-settings-form">
					<?php
						settings_fields( 'ml_brevo_option_group' );
						do_settings_sections( 'ml-brevo-admin' );
					?>
					
					<?php $this->render_field_management_section( $api_key ); ?>
					
					<?php $this->render_lists_management_section( $api_key ); ?>
					
					<?php wp_nonce_field( 'brevo_admin_nonce', 'brevo_nonce' ); ?>
					<?php submit_button(); ?>
				</form>
			<?php elseif ( $current_tab === 'translations' ) : ?>
				<?php $this->render_translations_tab(); ?>
			<?php elseif ( $current_tab === 'debug' ) : ?>
				<?php $this->render_debug_tab(); ?>
			<?php elseif ( $current_tab === 'docs' ) : ?>
				<?php $this->render_docs_tab(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render promotional banner
	 */
	public function render_promotional_banner() {
		$utm_params  = array(
			'utm_source'   => 'wordpress',
			'utm_medium'   => 'plugin',
			'utm_campaign' => 'brevo_elementor_pro',
			'utm_content'  => 'admin_banner',
		);
		$contact_url = 'https://www.2wins.agency/help?' . http_build_query( $utm_params );
		?>
		<div class="brevo-promotional-banner">
			<div class="brevo-promo-content">
				<h3>
					<?php esc_html_e( 'Need help with Brevo automations?', 'ml-brevo-for-elementor-pro' ); ?>
				</h3>
				<p>
					<?php esc_html_e( 'Make the most of Brevo\'s (formerly Sendinblue) potential to optimize lead management, email campaigns, and the entire customer journey. With our custom automations and integration of the best artificial intelligence tools, we will help you:', 'ml-brevo-for-elementor-pro' ); ?>
				</p>
				<ul class="brevo-promo-benefits">
										<li><?php esc_html_e( 'Reduce manual work and increase operational efficiency.', 'ml-brevo-for-elementor-pro' ); ?></li>
										<li><?php esc_html_e( 'Centralize customer data for a unified and complete view.', 'ml-brevo-for-elementor-pro' ); ?></li>
					<li><?php esc_html_e( 'Maximize conversions and improve customer engagement.', 'ml-brevo-for-elementor-pro' ); ?></li>
				</ul>
				<p>
					<?php esc_html_e( 'Contact us today to discover how 2wins Agency can support your business growth through Brevo automations and artificial intelligence.', 'ml-brevo-for-elementor-pro' ); ?>
				</p>
				<div class="brevo-promo-cta">
					<a href="<?php echo esc_url( $contact_url ); ?>" target="_blank" class="button button-primary button-hero">
						<?php esc_html_e( 'Contact us now', 'ml-brevo-for-elementor-pro' ); ?>
					</a>
				</div>
			</div>
		</div>

		<style>
		.brevo-promotional-banner {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: #ffffff;
			padding: 25px;
			margin: 20px 0;
			border-radius: 8px;
			box-shadow: 0 4px 15px rgba(0,0,0,0.1);
			position: relative;
			overflow: hidden;
		}
		.brevo-promotional-banner::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.03)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
			pointer-events: none;
		}
		.brevo-promo-content {
			position: relative;
			z-index: 1;
			max-width: 900px;
		}
		.brevo-promotional-banner h3 {
			margin: 0 0 15px 0;
			font-size: 24px;
			font-weight: 600;
			color: #ffffff;
			text-shadow: 0 1px 3px rgba(0,0,0,0.3);
		}
		.brevo-promotional-banner p {
			margin: 0 0 15px 0;
			font-size: 16px;
			line-height: 1.6;
			color: rgba(255,255,255,0.95);
		}
		.brevo-promo-benefits {
			margin: 15px 0;
			padding-left: 0;
			list-style: none;
		}
		.brevo-promo-benefits li {
			margin: 8px 0;
			padding-left: 25px;
			position: relative;
			font-size: 15px;
			line-height: 1.5;
			color: rgba(255,255,255,0.95);
		}
		.brevo-promo-benefits li::before {
			content: '✓';
			position: absolute;
			left: 0;
			top: 0;
			color: #4ade80;
			font-weight: bold;
			font-size: 16px;
		}
		.brevo-promo-cta {
			margin-top: 20px;
		}
		.brevo-promo-cta .button-hero {
			background: #ffffff;
			color: #667eea;
			border: none;
			font-weight: 600;
			text-decoration: none;
			transition: all 0.3s ease;
			box-shadow: 0 2px 10px rgba(0,0,0,0.2);
		}
		.brevo-promo-cta .button-hero:hover,
		.brevo-promo-cta .button-hero:focus {
			background: #f8fafc;
			color: #5a67d8;
			transform: translateY(-1px);
			box-shadow: 0 4px 15px rgba(0,0,0,0.3);
		}
		@media (max-width: 768px) {
			.brevo-promotional-banner {
				padding: 20px 15px;
			}
			.brevo-promotional-banner h3 {
				font-size: 20px;
			}
			.brevo-promotional-banner p,
			.brevo-promo-benefits li {
				font-size: 14px;
			}
		}
		</style>
		<?php
	}

	/**
	 * Render navigation tabs
	 */
	public function render_navigation_tabs( $current_tab ) {
		$tabs = array(
			'settings'     => array(
				'title' => __( 'Settings & Configuration', 'ml-brevo-for-elementor-pro' ),
				'icon'  => 'admin-settings',
			),
			'translations' => array(
				'title' => __( 'Translations', 'ml-brevo-for-elementor-pro' ),
				'icon'  => 'translation',
			),
			'debug'        => array(
				'title' => __( 'Debug Logs', 'ml-brevo-for-elementor-pro' ),
				'icon'  => 'admin-tools',
			),
			'docs'         => array(
				'title' => __( 'Docs', 'ml-brevo-for-elementor-pro' ),
				'icon'  => 'media-document',
			),
		);
		?>
		<nav class="nav-tab-wrapper wp-clearfix brevo-nav-tabs">
			<?php foreach ( $tabs as $tab_key => $tab_data ) : ?>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=ml-brevo-free&tab=' . $tab_key ) ); ?>" 
					class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-<?php echo esc_attr( $tab_data['icon'] ); ?>"></span>
					<?php echo esc_html( $tab_data['title'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Render translations tab content
	 */
	public function render_translations_tab() {
		$compiler          = new ML_Brevo_Translation_Compiler();
		$stats             = $compiler->get_translation_stats();
		$needs_compilation = $compiler->needs_compilation();

		// Debug information
		$current_locale = get_locale();
		$expected_file  = 'ml-brevo-for-elementor-pro-' . $current_locale . '.po';
		$file_path      = BREVO_ELEMENTOR_PLUGIN_PATH . 'languages/' . $expected_file;
		$file_exists    = file_exists( $file_path );

		// Test translation loading
		$test_translation = __( 'Settings & Configuration', 'ml-brevo-for-elementor-pro' );
		$is_translated    = ( $test_translation !== 'Settings & Configuration' );

		?>
		<div class="brevo-translations-section">
			<?php
			// Show debug information only if debug logging is enabled
			$debug_enabled = get_option( 'debug_enabled_ml_brevo', false );
			if ( $debug_enabled ) :
				?>
			<!-- Debug Information -->
			<div class="notice notice-info" style="margin: 20px 0; padding: 15px;">
				<h3 style="margin-top: 0;">🔍 Translation Debug Information</h3>
				<table class="widefat" style="margin-top: 10px;">
					<tr>
						<td><strong>Current WordPress Locale:</strong></td>
						<td><code><?php echo esc_html( $current_locale ); ?></code></td>
					</tr>
					<tr>
						<td><strong>Expected Translation File:</strong></td>
						<td><code><?php echo esc_html( $expected_file ); ?></code></td>
					</tr>
					<tr>
						<td><strong>File Exists:</strong></td>
						<td>
							<?php if ( $file_exists ) : ?>
								<span style="color: green;">✓ YES</span>
							<?php else : ?>
								<span style="color: red;">✗ NO</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong>Translation Active:</strong></td>
						<td>
							<?php if ( $is_translated ) : ?>
								<span style="color: green;">✓ YES</span>
							<?php else : ?>
								<span style="color: red;">✗ NO (showing English)</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong>Test Translation:</strong></td>
						<td><code><?php echo esc_html( $test_translation ); ?></code></td>
					</tr>
					<tr>
						<td><strong>Text Domain:</strong></td>
						<td><code>ml-brevo-for-elementor-pro</code></td>
					</tr>

					<tr>
						<td><strong>Domain Path:</strong></td>
						<td><code><?php echo esc_html( BREVO_ELEMENTOR_PLUGIN_PATH . 'languages/' ); ?></code></td>
					</tr>
				</table>
				<?php
				// Check for MO file
				$mo_file   = str_replace( '.po', '.mo', $file_path );
				$mo_exists = file_exists( $mo_file );
				?>
				<tr>
					<td><strong>MO File Path:</strong></td>
					<td><code><?php echo esc_html( str_replace( BREVO_ELEMENTOR_PLUGIN_PATH, '', $mo_file ) ); ?></code></td>
				</tr>
				<tr>
					<td><strong>MO File Exists:</strong></td>
					<td>
						<?php if ( $mo_exists ) : ?>
							<span style="color: green;">✓ YES</span>
						<?php else : ?>
							<span style="color: red;">✗ NO</span>
						<?php endif; ?>
					</td>
				</tr>
				</table>
				
				<?php if ( ! $is_translated && $file_exists && ! $mo_exists ) : ?>
					<p style="color: #d63638; margin-top: 10px;">
						<strong>⚠️ Issue Detected:</strong> PO file exists but MO file is missing. WordPress only reads MO files for translations.
					</p>
					<form method="post" action="" style="margin-top: 10px;">
						<?php wp_nonce_field( 'compile_brevo_translations' ); ?>
						<input type="submit" name="compile_brevo_translations" class="button button-primary" 
								value="🔧 Compile Translation Now" style="margin-right: 10px;">
						<span class="description">This will create the missing MO file.</span>
					</form>
				<?php elseif ( ! $is_translated && $file_exists && $mo_exists ) : ?>
					<p style="color: #d63638; margin-top: 10px;">
						<strong>⚠️ Issue Detected:</strong> Both PO and MO files exist but translations are not loading. 
						This might be a caching issue or the MO file is corrupted.
					</p>
					<form method="post" action="" style="margin-top: 10px;">
						<?php wp_nonce_field( 'compile_brevo_translations' ); ?>
						<input type="submit" name="compile_brevo_translations" class="button button-primary" 
								value="🔄 Recompile Translation" style="margin-right: 10px;">
						<span class="description">This will recreate the MO file.</span>
					</form>
				<?php elseif ( ! $file_exists ) : ?>
					<p style="color: #d63638; margin-top: 10px;">
						<strong>⚠️ Issue Detected:</strong> No translation file found for your locale (<?php echo esc_html( $current_locale ); ?>).
					</p>
				<?php else : ?>
					<p style="color: #00a32a; margin-top: 10px;">
						<strong>✓ Translations are working correctly!</strong>
					</p>
				<?php endif; ?>
			</div>
			<?php endif; // End debug section ?>
			
			<div class="brevo-translations-header">
				<h2><?php esc_html_e( 'Translation Management', 'ml-brevo-for-elementor-pro' ); ?></h2>
				<p><?php esc_html_e( 'Manage and compile plugin translations for better performance.', 'ml-brevo-for-elementor-pro' ); ?></p>
			</div>

			<?php if ( $needs_compilation ) : ?>
				<div class="notice notice-warning">
					<p>
						<strong>⚠️ <?php esc_html_e( 'Some translations need compilation!', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php esc_html_e( 'Click "Compile Translations" to improve performance.', 'ml-brevo-for-elementor-pro' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<!-- Compilation Actions -->
			<div class="brevo-translations-actions">
				<form method="post" action="">
					<?php wp_nonce_field( 'compile_brevo_translations' ); ?>
					<p>
						<input type="submit" name="compile_brevo_translations" class="button button-primary" 
								value="<?php esc_attr_e( 'Compile All Translations', 'ml-brevo-for-elementor-pro' ); ?>">
						<span class="description">
							<?php esc_html_e( 'Converts .po files to optimized .mo files for better performance.', 'ml-brevo-for-elementor-pro' ); ?>
						</span>
					</p>
				</form>
			</div>

			<!-- Translation Statistics -->
			<div class="brevo-translations-stats">
				<h3><?php esc_html_e( 'Translation Status', 'ml-brevo-for-elementor-pro' ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Language', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th><?php esc_html_e( 'PO File', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th><?php esc_html_e( 'MO File', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th><?php esc_html_e( 'Last Modified', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th><?php esc_html_e( 'Status', 'ml-brevo-for-elementor-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats as $code => $stat ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $stat['name'] ); ?></strong><br>
									<code><?php echo esc_html( $code ); ?></code>
								</td>
								<td>
									<?php if ( $stat['po_exists'] ) : ?>
										<span class="dashicons dashicons-yes-alt" style="color: green;"></span>
										<?php echo esc_html( $stat['po_size'] ); ?>
									<?php else : ?>
										<span class="dashicons dashicons-dismiss" style="color: red;"></span>
										<?php esc_html_e( 'Missing', 'ml-brevo-for-elementor-pro' ); ?>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $stat['mo_exists'] ) : ?>
										<span class="dashicons dashicons-yes-alt" style="color: green;"></span>
										<?php echo esc_html( $stat['mo_size'] ); ?>
									<?php else : ?>
										<span class="dashicons dashicons-dismiss" style="color: red;"></span>
										<?php esc_html_e( 'Missing', 'ml-brevo-for-elementor-pro' ); ?>
									<?php endif; ?>
								</td>
								<td>
									<strong>PO:</strong> <?php echo esc_html( $stat['po_modified'] ); ?><br>
									<strong>MO:</strong> <?php echo esc_html( $stat['mo_modified'] ); ?>
								</td>
								<td>
									<?php if ( $stat['needs_compile'] ) : ?>
										<span class="dashicons dashicons-warning" style="color: orange;"></span>
										<strong style="color: orange;"><?php esc_html_e( 'Needs Compilation', 'ml-brevo-for-elementor-pro' ); ?></strong>
									<?php elseif ( $stat['mo_exists'] ) : ?>
										<span class="dashicons dashicons-yes-alt" style="color: green;"></span>
										<strong style="color: green;"><?php esc_html_e( 'Up to Date', 'ml-brevo-for-elementor-pro' ); ?></strong>
									<?php else : ?>
										<span class="dashicons dashicons-dismiss" style="color: red;"></span>
										<strong style="color: red;"><?php esc_html_e( 'Not Compiled', 'ml-brevo-for-elementor-pro' ); ?></strong>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Translation Information -->
			<div class="brevo-translations-info">
				<h3><?php esc_html_e( 'About Translations', 'ml-brevo-for-elementor-pro' ); ?></h3>
				<div class="brevo-info-cards">
					<div class="brevo-info-card">
						<h4>📄 <?php esc_html_e( 'PO Files', 'ml-brevo-for-elementor-pro' ); ?></h4>
						<p><?php esc_html_e( 'Human-readable translation files that you can edit with text editors or translation tools like Poedit.', 'ml-brevo-for-elementor-pro' ); ?></p>
					</div>
					<div class="brevo-info-card">
						<h4>⚡ <?php esc_html_e( 'MO Files', 'ml-brevo-for-elementor-pro' ); ?></h4>
						<p><?php esc_html_e( 'Compiled binary files that WordPress uses for faster translation loading. Generated from PO files.', 'ml-brevo-for-elementor-pro' ); ?></p>
					</div>
					<div class="brevo-info-card">
						<h4>🔄 <?php esc_html_e( 'Automatic Detection', 'ml-brevo-for-elementor-pro' ); ?></h4>
						<p><?php esc_html_e( 'WordPress automatically loads the correct translation based on your site language setting.', 'ml-brevo-for-elementor-pro' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Current Site Language -->
			<div class="brevo-current-language">
				<h3><?php esc_html_e( 'Current Site Settings', 'ml-brevo-for-elementor-pro' ); ?></h3>
				<p>
					<strong><?php esc_html_e( 'Site Language:', 'ml-brevo-for-elementor-pro' ); ?></strong>
					<?php echo esc_html( get_locale() ); ?>
					<?php
					$current_lang = get_locale();
					if ( isset( $stats[ $current_lang ] ) ) {
						echo ' - ' . esc_html( $stats[ $current_lang ]['name'] );
						if ( $stats[ $current_lang ]['mo_exists'] ) {
							echo ' <span class="dashicons dashicons-yes-alt" style="color: green;" title="' . esc_attr__( 'Translation loaded', 'ml-brevo-for-elementor-pro' ) . '"></span>';
						} else {
							echo ' <span class="dashicons dashicons-warning" style="color: orange;" title="' . esc_attr__( 'Translation not compiled', 'ml-brevo-for-elementor-pro' ) . '"></span>';
						}
					} else {
						echo ' - ' . esc_html__( 'English (default)', 'ml-brevo-for-elementor-pro' );
					}
					?>
				</p>
				<p>
					<em><?php esc_html_e( 'Change your site language in Settings > General > Site Language', 'ml-brevo-for-elementor-pro' ); ?></em>
				</p>
			</div>
		</div>

		<style>
		.brevo-translations-section {
			max-width: 1200px;
		}
		.brevo-translations-actions {
			background: #f9f9f9;
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 4px;
			margin: 20px 0;
		}
		.brevo-info-cards {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
			margin-top: 15px;
		}
		.brevo-info-card {
			background: #fff;
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 4px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		.brevo-info-card h4 {
			margin-top: 0;
			color: #23282d;
		}
		.brevo-current-language {
			background: #e8f4fd;
			padding: 20px;
			border: 1px solid #c3e4f7;
			border-radius: 4px;
			margin-top: 20px;
		}
		</style>
		<?php
	}

	/**
	 * Render debug tab content
	 */
	public function render_debug_tab() {
		$logger = Brevo_Debug_Logger::get_instance();

		// Get current parameters
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filter parameters for display don't require nonce
		$current_file = isset( $_GET['file'] ) ? sanitize_text_field( wp_unslash( $_GET['file'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filter parameters for display don't require nonce
		$current_level = isset( $_GET['level'] ) ? sanitize_text_field( wp_unslash( $_GET['level'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filter parameters for display don't require nonce
		$current_component = isset( $_GET['component'] ) ? sanitize_text_field( wp_unslash( $_GET['component'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pagination parameter for display doesn't require nonce
		$current_page     = isset( $_GET['paged'] ) ? max( 1, intval( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$entries_per_page = 50;

		// Get log files
		$log_files     = $logger->get_log_files();
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
		$offset        = ( $current_page - 1 ) * $entries_per_page;
		$entries       = array_slice( $filtered_entries, $offset, $entries_per_page );

		// Calculate pagination
		$total_pages = ceil( $total_entries / $entries_per_page );

		?>
		<div class="brevo-debug-section">
			<?php if ( ! $logger->is_enabled() ) : ?>
				<div class="notice notice-warning">
					<p>
						<?php esc_html_e( 'Debug logging is currently disabled.', 'ml-brevo-for-elementor-pro' ); ?>
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=ml-brevo-free&tab=settings' ) ); ?>">
							<?php esc_html_e( 'Enable it in settings tab', 'ml-brevo-for-elementor-pro' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<!-- Debug Log Controls -->
			<div class="brevo-debug-controls">
				<div class="brevo-debug-info">
					<h3><?php esc_html_e( 'Log Information', 'ml-brevo-for-elementor-pro' ); ?></h3>
					<p>
						<strong><?php esc_html_e( 'Debug Status:', 'ml-brevo-for-elementor-pro' ); ?></strong>
						<?php
						echo $logger->is_enabled() ?
							'<span style="color: green;">' . esc_html__( 'Enabled', 'ml-brevo-for-elementor-pro' ) . '</span>' :
							'<span style="color: red;">' . esc_html__( 'Disabled', 'ml-brevo-for-elementor-pro' ) . '</span>';
						?>
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
						<?php if ( $selected_file ) : ?>
													<a href="
													<?php
													echo esc_url(
														wp_nonce_url(
															admin_url( 'admin-ajax.php?action=brevo_download_debug_log&file=' . urlencode( basename( $selected_file ) ) ),
															'brevo_download_log'
														)
													);
													?>
						" class="button button-secondary">
								<?php esc_html_e( 'Download Current Log', 'ml-brevo-for-elementor-pro' ); ?>
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
						<label for="file-filter"><?php esc_html_e( 'Log File:', 'ml-brevo-for-elementor-pro' ); ?></label>
						<select name="file" id="file-filter">
							<?php foreach ( $log_files as $file ) : ?>
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
							$components = $this->get_unique_debug_components( $all_entries );
							foreach ( $components as $component ) :
								?>
								<option value="<?php echo esc_attr( $component ); ?>" <?php selected( $current_component, $component ); ?>>
									<?php echo esc_html( $component ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="filter-group">
													<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Filter', 'ml-brevo-for-elementor-pro' ); ?>">
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=ml-brevo-free&tab=debug' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Reset', 'ml-brevo-for-elementor-pro' ); ?>
						</a>
					</div>
				</form>
			</div>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="brevo-debug-pagination">
					<?php
					$base_url = add_query_arg(
						array(
							'page'      => 'ml-brevo-free',
							'tab'       => 'debug',
							'file'      => $current_file,
							'level'     => $current_level,
							'component' => $current_component,
						),
						admin_url( 'options-general.php' )
					);

					$pagination_args = array(
						'base'      => add_query_arg( 'paged', '%#%', $base_url ),
						'format'    => '',
						'prev_text' => __( '&laquo; Previous', 'ml-brevo-for-elementor-pro' ),
						'next_text' => __( 'Next &raquo;', 'ml-brevo-for-elementor-pro' ),
						'total'     => $total_pages,
						'current'   => $current_page,
						'show_all'  => false,
						'type'      => 'plain',
					);
					echo wp_kses_post( paginate_links( $pagination_args ) );
					?>
					<p class="brevo-debug-pagination-info">
						<?php
						// translators: %1$d is the start entry number, %2$d is the end entry number, %3$d is the total number of entries
						printf( esc_html__( 'Showing %1$d-%2$d of %3$d entries', 'ml-brevo-for-elementor-pro' ), absint( $offset ) + 1, absint( min( absint( $offset ) + absint( $entries_per_page ), absint( $total_entries ) ) ), absint( $total_entries ) );
						?>
					</p>
				</div>
			<?php endif; ?>

			<!-- Log Entries Table -->
			<?php if ( empty( $entries ) ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'No log entries found.', 'ml-brevo-for-elementor-pro' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped brevo-debug-table">
					<thead>
						<tr>
							<th style="width: 140px;"><?php esc_html_e( 'Timestamp', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th style="width: 80px;"><?php esc_html_e( 'Level', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th style="width: 100px;"><?php esc_html_e( 'Component', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th style="width: 120px;"><?php esc_html_e( 'Action', 'ml-brevo-for-elementor-pro' ); ?></th>
							<th><?php esc_html_e( 'Message', 'ml-brevo-for-elementor-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $entry ) : ?>
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
										<?php if ( ! empty( $entry['context'] ) ) : ?>
											<details class="brevo-log-context">
												<summary><?php esc_html_e( 'Context', 'ml-brevo-for-elementor-pro' ); ?></summary>
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

		return array_filter(
			$entries,
			function ( $entry ) use ( $level, $component ) {
				$level_match     = empty( $level ) || $entry['level'] === $level;
				$component_match = empty( $component ) || $entry['component'] === $component;
				return $level_match && $component_match;
			}
		);
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
	 * Render docs tab content
	 */
	public function render_docs_tab() {
		$readme_pdf_url  = plugin_dir_url( __DIR__ ) . 'readme.pdf';
		$readme_pdf_path = plugin_dir_path( __DIR__ ) . 'readme.pdf';
		?>
		<div class="brevo-docs-section">
			<h2><?php esc_html_e( 'Documentation', 'ml-brevo-for-elementor-pro' ); ?></h2>
			<p><?php esc_html_e( 'Access the complete documentation for ML Brevo for Elementor Pro plugin.', 'ml-brevo-for-elementor-pro' ); ?></p>
			
			<div class="brevo-docs-content">
				<?php if ( file_exists( $readme_pdf_path ) ) : ?>
					<div class="brevo-docs-download">
						<h3><?php esc_html_e( 'Plugin Documentation', 'ml-brevo-for-elementor-pro' ); ?></h3>
						<p><?php esc_html_e( 'Download the complete documentation PDF file for detailed setup instructions, troubleshooting, and usage examples.', 'ml-brevo-for-elementor-pro' ); ?></p>
						<p>
							<a href="<?php echo esc_url( $readme_pdf_url ); ?>" 
								class="button button-primary" 
								target="_blank" 
								download="ML-Brevo-Elementor-Pro-Documentation.pdf">
								<span class="dashicons dashicons-download" style="vertical-align: text-top;"></span>
								<?php esc_html_e( 'Download Documentation PDF', 'ml-brevo-for-elementor-pro' ); ?>
							</a>
							<a href="<?php echo esc_url( $readme_pdf_url ); ?>" 
								class="button button-secondary" 
								target="_blank">
								<span class="dashicons dashicons-visibility" style="vertical-align: text-top;"></span>
								<?php esc_html_e( 'View Online', 'ml-brevo-for-elementor-pro' ); ?>
							</a>
						</p>
					</div>
				<?php else : ?>
					<div class="notice notice-warning inline">
						<p>
							<strong><?php esc_html_e( 'Documentation file not found', 'ml-brevo-for-elementor-pro' ); ?></strong><br>
							<?php esc_html_e( 'The readme.pdf file is not present in the plugin directory. Please contact support or check the plugin installation.', 'ml-brevo-for-elementor-pro' ); ?>
						</p>
					</div>
				<?php endif; ?>
				
				<div class="brevo-docs-links">
					<h3><?php esc_html_e( 'Quick Links', 'ml-brevo-for-elementor-pro' ); ?></h3>
					<ul>
						<li>
							<a href="https://www.brevo.com/docs/" target="_blank" rel="noopener">
								<span class="dashicons dashicons-external" style="vertical-align: text-top;"></span>
								<?php esc_html_e( 'Brevo API Documentation', 'ml-brevo-for-elementor-pro' ); ?>
							</a>
						</li>
						<li>
							<a href="https://elementor.com/help/" target="_blank" rel="noopener">
								<span class="dashicons dashicons-external" style="vertical-align: text-top;"></span>
								<?php esc_html_e( 'Elementor Pro Documentation', 'ml-brevo-for-elementor-pro' ); ?>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_url( admin_url( 'options-general.php?page=ml-brevo-free&tab=debug' ) ); ?>">
								<span class="dashicons dashicons-admin-tools" style="vertical-align: text-top;"></span>
								<?php esc_html_e( 'Debug Logs (Troubleshooting)', 'ml-brevo-for-elementor-pro' ); ?>
							</a>
						</li>
					</ul>
				</div>
				
				<div class="brevo-docs-support">
					<h3><?php esc_html_e( 'Need Help?', 'ml-brevo-for-elementor-pro' ); ?></h3>
					<p><?php esc_html_e( 'If you need additional support or have questions about the plugin:', 'ml-brevo-for-elementor-pro' ); ?></p>
					<ul>
						<li><?php esc_html_e( '1. Check the documentation PDF for detailed instructions', 'ml-brevo-for-elementor-pro' ); ?></li>
						<li><?php esc_html_e( '2. Enable debug logging to troubleshoot issues', 'ml-brevo-for-elementor-pro' ); ?></li>
						<li><?php esc_html_e( '3. Verify your Brevo API key and account settings', 'ml-brevo-for-elementor-pro' ); ?></li>
						<li><?php esc_html_e( '4. Contact plugin support with debug logs if needed', 'ml-brevo-for-elementor-pro' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the field management section
	 */
	public function render_field_management_section( $api_key ) {
		?>
		<div class="brevo-field-management">
			<h2><?php esc_html_e( 'Available Brevo Fields', 'ml-brevo-for-elementor-pro' ); ?></h2>
			<p><?php esc_html_e( 'Enable or disable fields that will be available for mapping in Elementor forms.', 'ml-brevo-for-elementor-pro' ); ?></p>
			
			<div class="brevo-field-controls">
				<button type="button" id="refresh-fields-btn" class="button button-secondary" 
					<?php echo empty( $api_key ) ? 'disabled' : ''; ?>>
					<?php esc_html_e( 'Refresh Fields from Brevo', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				
				<button type="button" id="enable-all-btn" class="button button-secondary">
					<?php esc_html_e( 'Enable All', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				
				<button type="button" id="disable-all-btn" class="button button-secondary">
					<?php esc_html_e( 'Disable All', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				
				<button type="button" id="reset-defaults-btn" class="button button-secondary">
					<?php esc_html_e( 'Reset to Defaults', 'ml-brevo-for-elementor-pro' ); ?>
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
				esc_html__( 'Please set your Global API Key above and save to manage fields.', 'ml-brevo-for-elementor-pro' ) .
				'</p>';
			return;
		}

		$attributes_manager = Brevo_Attributes_Manager::get_instance();
		$attributes         = $attributes_manager->fetch_all_attributes( $api_key );
		$enabled_fields     = get_option( 'brevo_enabled_fields', array() );

		if ( is_wp_error( $attributes ) ) {
			echo '<div class="notice notice-error inline">';
			// translators: %s is the error message
			echo '<p>' . sprintf( esc_html__( 'Error fetching fields: %s', 'ml-brevo-for-elementor-pro' ), esc_html( $attributes->get_error_message() ) ) . '</p>';
			echo '</div>';
			return;
		}

		// Ensure we have a valid attributes array
		if ( ! is_array( $attributes ) || empty( $attributes ) ) {
			echo '<div class="notice notice-warning inline">';
			echo '<p>' . esc_html__( 'No fields found. This could be due to an invalid API key or temporary API issues.', 'ml-brevo-for-elementor-pro' ) . '</p>';
			echo '</div>';
			return;
		}

		?>

		<table class="wp-list-table widefat fixed striped" id="brevo-fields-table">
			<thead>
				<tr>
					<th class="check-column">
						<input type="checkbox" id="select-all-fields">
					</th>
					<th><?php esc_html_e( 'Field Name', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php esc_html_e( 'Type', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php esc_html_e( 'Description', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ml-brevo-for-elementor-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $attributes as $field_name => $field_data ) :
					// Ensure field_data is an array and has required keys
					if ( ! is_array( $field_data ) ) {
						continue;
					}

					$field_name            = sanitize_text_field( $field_name );
					$field_type            = isset( $field_data['type'] ) ? sanitize_text_field( $field_data['type'] ) : 'text';
					$field_description     = isset( $field_data['description'] ) ? sanitize_text_field( $field_data['description'] ) : '';
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
						<?php if ( in_array( $field_name, array( 'FIRSTNAME', 'LASTNAME', 'SMS' ) ) ) : ?>
							<span class="brevo-field-badge brevo-default-field"><?php esc_html_e( 'Default', 'ml-brevo-for-elementor-pro' ); ?></span>
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
							<?php echo $is_enabled ? esc_html__( 'Enabled', 'ml-brevo-for-elementor-pro' ) : esc_html__( 'Disabled', 'ml-brevo-for-elementor-pro' ); ?>
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
			<h2><?php esc_html_e( 'Available Brevo Lists', 'ml-brevo-for-elementor-pro' ); ?></h2>
			<p><?php esc_html_e( 'This table shows all your Brevo contact lists for reference. You can select specific lists directly in your Elementor forms using the list dropdown selector.', 'ml-brevo-for-elementor-pro' ); ?></p>
			
			<div class="brevo-lists-controls">
				<button type="button" id="refresh-lists-btn" class="button button-secondary" 
					<?php echo empty( $api_key ) ? 'disabled' : ''; ?>>
					<?php esc_html_e( 'Refresh Lists from Brevo', 'ml-brevo-for-elementor-pro' ); ?>
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
				esc_html__( 'Please set your Global API Key above and save to manage lists.', 'ml-brevo-for-elementor-pro' ) .
				'</p>';
			return;
		}

		$attributes_manager = Brevo_Attributes_Manager::get_instance();
		$lists              = $attributes_manager->fetch_all_lists( $api_key );

		if ( is_wp_error( $lists ) ) {
			echo '<div class="notice notice-error inline">';
			// translators: %s is the error message
			echo '<p>' . sprintf( esc_html__( 'Error fetching lists: %s', 'ml-brevo-for-elementor-pro' ), esc_html( $lists->get_error_message() ) ) . '</p>';
			echo '</div>';
			return;
		}

		// Ensure we have a valid lists array
		if ( ! is_array( $lists ) || empty( $lists ) ) {
			echo '<div class="notice notice-warning inline">';
			echo '<p>' . esc_html__( 'No lists found. This could be due to an invalid API key or you may not have any lists created in your Brevo account.', 'ml-brevo-for-elementor-pro' ) . '</p>';
			echo '</div>';
			return;
		}

		?>

		<table class="wp-list-table widefat fixed striped" id="brevo-lists-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'List ID', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php esc_html_e( 'List Name', 'ml-brevo-for-elementor-pro' ); ?></th>
					<th><?php esc_html_e( 'Subscribers', 'ml-brevo-for-elementor-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $lists as $list_id => $list_data ) :
					// Ensure list_data is an array and has required keys
					if ( ! is_array( $list_data ) ) {
						continue;
					}

					$list_id            = intval( $list_id );
					$list_name          = isset( $list_data['name'] ) ? sanitize_text_field( $list_data['name'] ) : '';
					$unique_subscribers = isset( $list_data['uniqueSubscribers'] ) ? intval( $list_data['uniqueSubscribers'] ) : 0;
					?>
				<tr>
					<td>
						<strong><?php echo esc_html( $list_id ); ?></strong>
					</td>
					<td>
						<strong><?php echo esc_html( $list_name ); ?></strong>
					</td>
					<td>
						<?php echo esc_html( number_format( $unique_subscribers ) ); ?>
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
			__( 'Global brevo API key', 'ml-brevo-for-elementor-pro' ), // title
			array( $this, 'global_api_key_ml_brevo_callback' ), // callback
			'ml-brevo-admin', // page
			'ml_brevo_setting_section' // section
		);

		add_settings_field(
			'debug_enabled_ml_brevo', // id
			__( 'Debug Logging', 'ml-brevo-for-elementor-pro' ), // title
			array( $this, 'debug_enabled_ml_brevo_callback' ), // callback
			'ml-brevo-admin', // page
			'ml_brevo_setting_section' // section
		);

		add_settings_field(
			'debug_level_ml_brevo', // id
			__( 'Debug Level', 'ml-brevo-for-elementor-pro' ), // title
			array( $this, 'debug_level_ml_brevo_callback' ), // callback
			'ml-brevo-admin', // page
			'ml_brevo_setting_section' // section
		);

		add_settings_field(
			'debug_retention_ml_brevo', // id
			__( 'Log Retention (days)', 'ml-brevo-for-elementor-pro' ), // title
			array( $this, 'debug_retention_ml_brevo_callback' ), // callback
			'ml-brevo-admin', // page
			'ml_brevo_setting_section' // section
		);
	}

	public function ml_brevo_sanitize( $input ) {
		$sanitary_values = array();

		if ( isset( $input['global_api_key_ml_brevo'] ) ) {
			$sanitary_values['global_api_key_ml_brevo'] = sanitize_text_field( $input['global_api_key_ml_brevo'] );
		}

		// Handle debug settings
		$debug_enabled   = isset( $input['debug_enabled_ml_brevo'] ) ? (bool) $input['debug_enabled_ml_brevo'] : false;
		$debug_level     = isset( $input['debug_level_ml_brevo'] ) ? sanitize_text_field( $input['debug_level_ml_brevo'] ) : 'INFO';
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is part of WordPress settings form with its own nonce
		if ( isset( $_POST['brevo_fields'] ) && is_array( $_POST['brevo_fields'] ) ) {
			$enabled_fields = array();
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- Array will be sanitized in loop, part of WP settings form
			$brevo_fields_input = wp_unslash( $_POST['brevo_fields'] );
			foreach ( $brevo_fields_input as $field ) {
				$enabled_fields[ sanitize_text_field( $field ) ] = true;
			}
			update_option( 'brevo_enabled_fields', $enabled_fields );
		} else {
			// No fields selected, disable all
			update_option( 'brevo_enabled_fields', array() );
		}

		// Handle list settings update
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This is part of WordPress settings form with its own nonce
		if ( isset( $_POST['brevo_lists'] ) && is_array( $_POST['brevo_lists'] ) ) {
			$selected_lists = array();
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- Array will be sanitized in loop, part of WP settings form
			$brevo_lists_input = wp_unslash( $_POST['brevo_lists'] );
			foreach ( $brevo_lists_input as $list_id ) {
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
			var ajaxUrl = '" . admin_url( 'admin-ajax.php' ) . "';
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
		echo esc_html__( 'Here you can find all your ml Integration for Elementor Form - brevo settings', 'ml-brevo-for-elementor-pro' );
	}

	public function global_api_key_ml_brevo_callback() {
		$api_key    = isset( $this->ml_brevo_options['global_api_key_ml_brevo'] ) ? esc_attr( $this->ml_brevo_options['global_api_key_ml_brevo'] ) : '';
		$is_key_set = ! empty( $api_key );
		?>
		<div class="brevo-api-key-field">
			<input type="<?php echo $is_key_set ? 'password' : 'text'; ?>" 
					id="global_api_key_ml_brevo" 
					name="ml_brevo_option_name[global_api_key_ml_brevo]" 
					value="<?php echo esc_attr( $api_key ); ?>" 
					placeholder="<?php esc_attr_e( 'Enter your Brevo API key', 'ml-brevo-for-elementor-pro' ); ?>"
					autocomplete="off">
			
		<?php if ( $is_key_set ) : ?>
				<button type="button" id="show-api-key-btn" class="button button-secondary">
					<?php esc_html_e( 'Show', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
				<button type="button" id="clear-api-key-btn" class="button button-secondary">
					<?php esc_html_e( 'Clear', 'ml-brevo-for-elementor-pro' ); ?>
				</button>
			<?php endif; ?>
			
			<p class="description">
			<?php esc_html_e( 'Enter your Brevo V3 API key. This key will be used for all forms unless overridden in the form settings.', 'ml-brevo-for-elementor-pro' ); ?>
				<a href="https://account.brevo.com/advanced/api" target="_blank">
				<?php esc_html_e( 'Get your API key here.', 'ml-brevo-for-elementor-pro' ); ?>
				</a>
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
				<?php esc_html_e( 'Enable debug logging', 'ml-brevo-for-elementor-pro' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'When enabled, the plugin will log detailed information about API calls, form submissions, and errors to help with troubleshooting.', 'ml-brevo-for-elementor-pro' ); ?>
				<?php if ( $debug_enabled ) : ?>
					<br><strong><?php esc_html_e( 'Debug logging is currently ENABLED.', 'ml-brevo-for-elementor-pro' ); ?></strong>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=ml-brevo-debug-viewer' ) ); ?>" class="button button-small">
						<?php esc_html_e( 'View Debug Logs', 'ml-brevo-for-elementor-pro' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	public function debug_level_ml_brevo_callback() {
		$debug_level   = get_option( 'brevo_debug_level', 'INFO' );
		$debug_enabled = get_option( 'brevo_debug_enabled', false );
		?>
		<select name="ml_brevo_option_name[debug_level_ml_brevo]" <?php echo $debug_enabled ? '' : 'disabled'; ?>>
			<option value="ERROR" <?php selected( $debug_level, 'ERROR' ); ?>><?php esc_html_e( 'ERROR - Only errors', 'ml-brevo-for-elementor-pro' ); ?></option>
			<option value="WARNING" <?php selected( $debug_level, 'WARNING' ); ?>><?php esc_html_e( 'WARNING - Errors and warnings', 'ml-brevo-for-elementor-pro' ); ?></option>
			<option value="INFO" <?php selected( $debug_level, 'INFO' ); ?>><?php esc_html_e( 'INFO - Errors, warnings, and info', 'ml-brevo-for-elementor-pro' ); ?></option>
			<option value="DEBUG" <?php selected( $debug_level, 'DEBUG' ); ?>><?php esc_html_e( 'DEBUG - All messages (verbose)', 'ml-brevo-for-elementor-pro' ); ?></option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the minimum level of messages to log. DEBUG level will create larger log files.', 'ml-brevo-for-elementor-pro' ); ?>
		</p>
		<?php
	}

	public function debug_retention_ml_brevo_callback() {
		$retention_days = get_option( 'brevo_debug_retention', 7 );
		?>
		<input type="number" id="debug_retention_ml_brevo" 
				name="ml_brevo_option_name[debug_retention_ml_brevo]" 
				value="<?php echo esc_attr( $retention_days ); ?>" 
				min="1" max="90" step="1">
		<p class="description">
		<?php esc_html_e( 'Number of days to keep log files (1-90). Older files will be automatically deleted.', 'ml-brevo-for-elementor-pro' ); ?>
		</p>
		<?php
	}

	/**
	 * Remove WordPress footer text on plugin pages
	 */
	public function remove_admin_footer_text( $footer_text ) {
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->id, 'ml-brevo-free' ) !== false ) {
			return '';
		}
		return $footer_text;
	}
}
if ( is_admin() ) {
	$ml_brevo = new MlbrevoFree();
}
