<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Brevo Debug Logger
 * 
 * Handles debug logging for the Brevo plugin with file-based storage,
 * log rotation, and management features.
 * 
 * @since 2.0.0
 */
class Brevo_Debug_Logger {

	/**
	 * Log levels
	 */
	const LEVEL_DEBUG = 'DEBUG';
	const LEVEL_INFO = 'INFO';
	const LEVEL_WARNING = 'WARNING';
	const LEVEL_ERROR = 'ERROR';

	/**
	 * Log directory path
	 */
	private $log_dir;

	/**
	 * Log file path
	 */
	private $log_file;

	/**
	 * Maximum log file size (in bytes) - 5MB
	 */
	const MAX_LOG_SIZE = 5242880;

	/**
	 * Maximum number of log files to keep
	 */
	const MAX_LOG_FILES = 5;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->log_dir = BREVO_ELEMENTOR_PLUGIN_PATH . 'logs/';
		$this->log_file = $this->log_dir . 'brevo-debug.log';
		
		// Ensure log directory exists
		$this->ensure_log_directory();
	}

	/**
	 * Check if debug logging is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) get_option( 'brevo_debug_enabled', false );
	}

	/**
	 * Get current debug level
	 *
	 * @return string
	 */
	public function get_debug_level() {
		return get_option( 'brevo_debug_level', self::LEVEL_INFO );
	}

	/**
	 * Get log retention period in days
	 *
	 * @return int
	 */
	public function get_retention_days() {
		return intval( get_option( 'brevo_debug_retention', 7 ) );
	}

	/**
	 * Log a debug message
	 *
	 * @param string $message Log message
	 * @param string $component Component name (API, FORM, CACHE, etc.)
	 * @param string $action Action being performed
	 * @param array  $context Additional context data
	 */
	public function debug( $message, $component = 'GENERAL', $action = '', $context = array() ) {
		$this->log( self::LEVEL_DEBUG, $message, $component, $action, $context );
	}

	/**
	 * Log an info message
	 *
	 * @param string $message Log message
	 * @param string $component Component name
	 * @param string $action Action being performed
	 * @param array  $context Additional context data
	 */
	public function info( $message, $component = 'GENERAL', $action = '', $context = array() ) {
		$this->log( self::LEVEL_INFO, $message, $component, $action, $context );
	}

	/**
	 * Log a warning message
	 *
	 * @param string $message Log message
	 * @param string $component Component name
	 * @param string $action Action being performed
	 * @param array  $context Additional context data
	 */
	public function warning( $message, $component = 'GENERAL', $action = '', $context = array() ) {
		$this->log( self::LEVEL_WARNING, $message, $component, $action, $context );
	}

	/**
	 * Log an error message
	 *
	 * @param string $message Log message
	 * @param string $component Component name
	 * @param string $action Action being performed
	 * @param array  $context Additional context data
	 */
	public function error( $message, $component = 'GENERAL', $action = '', $context = array() ) {
		$this->log( self::LEVEL_ERROR, $message, $component, $action, $context );
	}

	/**
	 * Write log entry
	 *
	 * @param string $level Log level
	 * @param string $message Log message
	 * @param string $component Component name
	 * @param string $action Action being performed
	 * @param array  $context Additional context data
	 */
	private function log( $level, $message, $component, $action, $context ) {
		// Check if logging is enabled
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Check if this level should be logged
		if ( ! $this->should_log_level( $level ) ) {
			return;
		}

		// Prepare log entry
		$entry = array(
			'timestamp' => current_time( 'Y-m-d H:i:s' ),
			'level'     => $level,
			'component' => strtoupper( $component ),
			'action'    => $action,
			'message'   => $message,
			'context'   => $context,
			'memory'    => memory_get_usage( true ),
			'peak_memory' => memory_get_peak_usage( true ),
		);

		// Add user context if available
		if ( is_user_logged_in() ) {
			$entry['user_id'] = get_current_user_id();
		}

		// Add request context
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$entry['request_uri'] = sanitize_text_field( $_SERVER['REQUEST_URI'] );
		}

		// Format log line
		$log_line = $this->format_log_entry( $entry );

		// Write to file
		$this->write_to_file( $log_line );
	}

	/**
	 * Check if a log level should be logged based on current settings
	 *
	 * @param string $level Log level to check
	 * @return bool
	 */
	private function should_log_level( $level ) {
		$current_level = $this->get_debug_level();
		$levels = array(
			self::LEVEL_ERROR   => 1,
			self::LEVEL_WARNING => 2,
			self::LEVEL_INFO    => 3,
			self::LEVEL_DEBUG   => 4,
		);

		$current_priority = $levels[ $current_level ] ?? 3;
		$message_priority = $levels[ $level ] ?? 3;

		return $message_priority <= $current_priority;
	}

	/**
	 * Format log entry for writing
	 *
	 * @param array $entry Log entry data
	 * @return string Formatted log line
	 */
	private function format_log_entry( $entry ) {
		$formatted = sprintf(
			'[%s] %s %s:%s %s',
			$entry['timestamp'],
			$entry['level'],
			$entry['component'],
			$entry['action'],
			$entry['message']
		);

		// Add context if available
		if ( ! empty( $entry['context'] ) ) {
			$formatted .= ' | Context: ' . json_encode( $entry['context'] );
		}

		// Add memory usage for debug level
		if ( $entry['level'] === self::LEVEL_DEBUG ) {
			$formatted .= sprintf(
				' | Memory: %s / Peak: %s',
				size_format( $entry['memory'] ),
				size_format( $entry['peak_memory'] )
			);
		}

		// Add user context if available
		if ( isset( $entry['user_id'] ) ) {
			$formatted .= ' | User: ' . $entry['user_id'];
		}

		// Add request URI if available
		if ( isset( $entry['request_uri'] ) ) {
			$formatted .= ' | URI: ' . $entry['request_uri'];
		}

		return $formatted . PHP_EOL;
	}

	/**
	 * Write log line to file
	 *
	 * @param string $log_line Formatted log line
	 */
	private function write_to_file( $log_line ) {
		// Check if log rotation is needed
		if ( file_exists( $this->log_file ) && filesize( $this->log_file ) > self::MAX_LOG_SIZE ) {
			$this->rotate_logs();
		}

		// Write to log file
		file_put_contents( $this->log_file, $log_line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Rotate log files
	 */
	private function rotate_logs() {
		// Move current log to .1
		if ( file_exists( $this->log_file ) ) {
			rename( $this->log_file, $this->log_file . '.1' );
		}

		// Rotate existing numbered logs
		for ( $i = self::MAX_LOG_FILES - 1; $i >= 1; $i-- ) {
			$old_file = $this->log_file . '.' . $i;
			$new_file = $this->log_file . '.' . ( $i + 1 );

			if ( file_exists( $old_file ) ) {
				if ( $i + 1 > self::MAX_LOG_FILES ) {
					// Delete old log
					unlink( $old_file );
				} else {
					// Move to next number
					rename( $old_file, $new_file );
				}
			}
		}
	}

	/**
	 * Ensure log directory exists and is protected
	 */
	private function ensure_log_directory() {
		if ( ! file_exists( $this->log_dir ) ) {
			wp_mkdir_p( $this->log_dir );
		}

		// Create .htaccess to protect log files
		$htaccess_file = $this->log_dir . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "Order deny,allow\nDeny from all\n";
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Create index.php to prevent directory listing
		$index_file = $this->log_dir . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, '<?php // Silence is golden' );
		}
	}

	/**
	 * Get all log files
	 *
	 * @return array Array of log file paths
	 */
	public function get_log_files() {
		$files = array();
		
		if ( file_exists( $this->log_file ) ) {
			$files[] = $this->log_file;
		}

		for ( $i = 1; $i <= self::MAX_LOG_FILES; $i++ ) {
			$file = $this->log_file . '.' . $i;
			if ( file_exists( $file ) ) {
				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * Read log entries from file
	 *
	 * @param string $file_path Log file path
	 * @param int    $limit     Maximum number of entries to return
	 * @param int    $offset    Offset for pagination
	 * @return array Array of parsed log entries
	 */
	public function read_log_entries( $file_path = null, $limit = 100, $offset = 0 ) {
		if ( $file_path === null ) {
			$file_path = $this->log_file;
		}

		if ( ! file_exists( $file_path ) ) {
			return array();
		}

		$lines = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $lines === false ) {
			return array();
		}

		// Reverse to show newest first
		$lines = array_reverse( $lines );

		// Apply offset and limit
		$lines = array_slice( $lines, $offset, $limit );

		$entries = array();
		foreach ( $lines as $line ) {
			$entry = $this->parse_log_line( $line );
			if ( $entry ) {
				$entries[] = $entry;
			}
		}

		return $entries;
	}

	/**
	 * Parse a log line into structured data
	 *
	 * @param string $line Log line
	 * @return array|null Parsed entry or null if parsing failed
	 */
	private function parse_log_line( $line ) {
		// Pattern: [timestamp] LEVEL COMPONENT:ACTION message
		$pattern = '/^\[([^\]]+)\]\s+(\w+)\s+([^:]+):([^\s]*)\s+(.+)$/';
		
		if ( ! preg_match( $pattern, $line, $matches ) ) {
			return null;
		}

		$entry = array(
			'timestamp' => $matches[1],
			'level'     => $matches[2],
			'component' => $matches[3],
			'action'    => $matches[4],
			'message'   => $matches[5],
			'context'   => array(),
		);

		// Extract context if present
		if ( strpos( $entry['message'], ' | Context: ' ) !== false ) {
			$parts = explode( ' | Context: ', $entry['message'], 2 );
			$entry['message'] = $parts[0];
			
			// Extract JSON context
			$context_part = explode( ' | ', $parts[1] )[0];
			$context = json_decode( $context_part, true );
			if ( $context !== null ) {
				$entry['context'] = $context;
			}
		}

		return $entry;
	}

	/**
	 * Clear all log files
	 */
	public function clear_logs() {
		$files = $this->get_log_files();
		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
	}

	/**
	 * Get log file size
	 *
	 * @param string $file_path Log file path
	 * @return int File size in bytes
	 */
	public function get_log_file_size( $file_path = null ) {
		if ( $file_path === null ) {
			$file_path = $this->log_file;
		}

		return file_exists( $file_path ) ? filesize( $file_path ) : 0;
	}

	/**
	 * Get total log files size
	 *
	 * @return int Total size in bytes
	 */
	public function get_total_log_size() {
		$total = 0;
		$files = $this->get_log_files();
		
		foreach ( $files as $file ) {
			$total += $this->get_log_file_size( $file );
		}

		return $total;
	}

	/**
	 * Clean up old log files based on retention policy
	 */
	public function cleanup_old_logs() {
		$retention_days = $this->get_retention_days();
		$cutoff_time = time() - ( $retention_days * DAY_IN_SECONDS );

		$files = $this->get_log_files();
		foreach ( $files as $file ) {
			if ( filemtime( $file ) < $cutoff_time ) {
				unlink( $file );
			}
		}
	}

	/**
	 * Get singleton instance
	 *
	 * @return Brevo_Debug_Logger
	 */
	public static function get_instance() {
		static $instance = null;
		
		if ( $instance === null ) {
			$instance = new self();
		}
		
		return $instance;
	}
} 