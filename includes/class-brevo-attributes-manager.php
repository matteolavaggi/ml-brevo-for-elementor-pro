<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

/**
 * Brevo Attributes Manager
 * 
 * Handles fetching, caching, and managing Brevo contact attributes
 * 
 * @since 2.0.0
 */
class Brevo_Attributes_Manager {

	/**
	 * Cache expiration time (1 hour)
	 */
	const CACHE_EXPIRATION = HOUR_IN_SECONDS;

	/**
	 * Cache key prefix
	 */
	const CACHE_PREFIX = 'brevo_attributes_';

	/**
	 * Brevo API base URL
	 */
	const API_BASE_URL = 'https://api.brevo.com/v3';

	/**
	 * Fetch all contact attributes from Brevo API
	 *
	 * @param string $api_key Brevo API key
	 * @return array|WP_Error Array of attributes or WP_Error on failure
	 */
	public function fetch_attributes( $api_key ) {
		if ( empty( $api_key ) ) {
			return new WP_Error( 'invalid_api_key', __( 'API key is required', 'ml-brevo-for-elementor-pro' ) );
		}

		// Check cache first
		$cached_attributes = $this->get_cached_attributes( $api_key );
		if ( $cached_attributes !== false ) {
			if ( WP_DEBUG === true ) {
				error_log( 'Brevo Attributes Manager - Using cached attributes' );
			}
			return $cached_attributes;
		}

		// Fetch from Brevo API
		$endpoint = self::API_BASE_URL . '/contacts/attributes';
		
		if ( WP_DEBUG === true ) {
			error_log( 'Brevo Attributes Manager - Fetching attributes from API: ' . $endpoint );
		}

		$response = wp_remote_get( $endpoint, array(
			'timeout'     => 30,
			'httpversion' => '1.0',
			'headers'     => array(
				'accept'       => 'application/json',
				'api-key'      => $api_key,
				'content-type' => 'application/json',
			),
		) );

		// Handle request errors
		if ( is_wp_error( $response ) ) {
			if ( WP_DEBUG === true ) {
				error_log( 'Brevo Attributes Manager - API request failed: ' . $response->get_error_message() );
			}
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( WP_DEBUG === true ) {
			error_log( 'Brevo Attributes Manager - API response code: ' . $response_code );
			error_log( 'Brevo Attributes Manager - API response body: ' . $response_body );
		}

		// Handle HTTP errors
		if ( $response_code < 200 || $response_code >= 300 ) {
			$error_message = sprintf( 
				__( 'Brevo API request failed with status %d', 'ml-brevo-for-elementor-pro' ), 
				$response_code 
			);
			
			// Try to extract error message from response
			$decoded_body = json_decode( $response_body, true );
			if ( isset( $decoded_body['message'] ) ) {
				$error_message .= ': ' . $decoded_body['message'];
			}

			return new WP_Error( 'api_request_failed', $error_message );
		}

		// Parse JSON response
		$data = json_decode( $response_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON response from Brevo API', 'ml-brevo-for-elementor-pro' ) );
		}

		// Normalize attributes
		$normalized_attributes = $this->normalize_attributes( $data );

		// Cache the results
		$this->cache_attributes( $api_key, $normalized_attributes );

		if ( WP_DEBUG === true ) {
			error_log( 'Brevo Attributes Manager - Successfully fetched and cached ' . count( $normalized_attributes ) . ' attributes' );
		}

		return $normalized_attributes;
	}

	/**
	 * Normalize attribute data from Brevo API response
	 *
	 * @param array $raw_data Raw API response data
	 * @return array Normalized attributes array
	 */
	public function normalize_attributes( $raw_data ) {
		$normalized = array();

		if ( ! isset( $raw_data['attributes'] ) || ! is_array( $raw_data['attributes'] ) ) {
			if ( WP_DEBUG === true ) {
				error_log( 'Brevo Attributes Manager - No attributes found in API response' );
			}
			return $normalized;
		}

		foreach ( $raw_data['attributes'] as $attribute ) {
			if ( ! isset( $attribute['name'] ) ) {
				continue;
			}

			$field_name = $attribute['name'];
			
			$normalized[ $field_name ] = array(
				'name'        => $field_name,
				'type'        => $this->map_field_type( $attribute['type'] ?? 'text' ),
				'category'    => $attribute['category'] ?? 'normal',
				'required'    => false, // Brevo doesn't specify required fields via API
				'description' => $this->generate_field_description( $field_name, $attribute ),
				'enabled'     => $this->is_field_enabled_by_default( $field_name ),
			);
		}

		// Add default fields if they don't exist in the API response
		$default_fields = $this->get_default_fields();
		foreach ( $default_fields as $field_name => $field_data ) {
			if ( ! isset( $normalized[ $field_name ] ) ) {
				$normalized[ $field_name ] = $field_data;
			}
		}

		if ( WP_DEBUG === true ) {
			error_log( 'Brevo Attributes Manager - Normalized ' . count( $normalized ) . ' attributes' );
		}

		return $normalized;
	}

	/**
	 * Map Brevo field types to our internal field types
	 *
	 * @param string $brevo_type Brevo field type
	 * @return string Our internal field type
	 */
	private function map_field_type( $brevo_type ) {
		$type_mapping = array(
			'text'     => 'text',
			'number'   => 'number',
			'date'     => 'date',
			'boolean'  => 'boolean',
			'category' => 'category',
			'float'    => 'number',
			'id'       => 'number',
		);

		return $type_mapping[ strtolower( $brevo_type ) ] ?? 'text';
	}

	/**
	 * Generate user-friendly field description
	 *
	 * @param string $field_name Field name
	 * @param array  $attribute  Raw attribute data
	 * @return string Field description
	 */
	private function generate_field_description( $field_name, $attribute ) {
		// Common field descriptions
		$descriptions = array(
			'FIRSTNAME'      => __( 'Contact first name', 'ml-brevo-for-elementor-pro' ),
			'LASTNAME'       => __( 'Contact last name', 'ml-brevo-for-elementor-pro' ),
			'SMS'            => __( 'Contact phone number', 'ml-brevo-for-elementor-pro' ),
			'EMAIL'          => __( 'Contact email address', 'ml-brevo-for-elementor-pro' ),
			'COMPANY'        => __( 'Contact company name', 'ml-brevo-for-elementor-pro' ),
			'WEBSITE'        => __( 'Contact website URL', 'ml-brevo-for-elementor-pro' ),
			'ADDRESS'        => __( 'Contact address', 'ml-brevo-for-elementor-pro' ),
			'BIRTH_DATE'     => __( 'Contact birth date', 'ml-brevo-for-elementor-pro' ),
			'GENDER'         => __( 'Contact gender', 'ml-brevo-for-elementor-pro' ),
			'COUNTRY'        => __( 'Contact country', 'ml-brevo-for-elementor-pro' ),
			'CITY'           => __( 'Contact city', 'ml-brevo-for-elementor-pro' ),
			'ZIPCODE'        => __( 'Contact postal code', 'ml-brevo-for-elementor-pro' ),
		);

		if ( isset( $descriptions[ $field_name ] ) ) {
			return $descriptions[ $field_name ];
		}

		// Generate description from field name
		$formatted_name = ucwords( strtolower( str_replace( '_', ' ', $field_name ) ) );
		return sprintf( __( 'Contact %s', 'ml-brevo-for-elementor-pro' ), $formatted_name );
	}

	/**
	 * Check if field should be enabled by default
	 *
	 * @param string $field_name Field name
	 * @return bool True if field should be enabled by default
	 */
	private function is_field_enabled_by_default( $field_name ) {
		$default_enabled_fields = array(
			'FIRSTNAME',
			'LASTNAME',
			'SMS',
		);

		return in_array( $field_name, $default_enabled_fields, true );
	}

	/**
	 * Get default fields that should always be available
	 *
	 * @return array Default fields array
	 */
	private function get_default_fields() {
		return array(
			'FIRSTNAME' => array(
				'name'        => 'FIRSTNAME',
				'type'        => 'text',
				'category'    => 'normal',
				'required'    => false,
				'description' => __( 'Contact first name', 'ml-brevo-for-elementor-pro' ),
				'enabled'     => true,
			),
			'LASTNAME' => array(
				'name'        => 'LASTNAME',
				'type'        => 'text',
				'category'    => 'normal',
				'required'    => false,
				'description' => __( 'Contact last name', 'ml-brevo-for-elementor-pro' ),
				'enabled'     => true,
			),
			'SMS' => array(
				'name'        => 'SMS',
				'type'        => 'text',
				'category'    => 'normal',
				'required'    => false,
				'description' => __( 'Contact phone number', 'ml-brevo-for-elementor-pro' ),
				'enabled'     => true,
			),
		);
	}

	/**
	 * Get cached attributes
	 *
	 * @param string $api_key API key for cache key generation
	 * @return array|false Cached attributes or false if not found
	 */
	private function get_cached_attributes( $api_key ) {
		$cache_key = $this->generate_cache_key( $api_key );
		return get_transient( $cache_key );
	}

	/**
	 * Cache attributes
	 *
	 * @param string $api_key    API key for cache key generation
	 * @param array  $attributes Attributes to cache
	 * @return bool True on success, false on failure
	 */
	private function cache_attributes( $api_key, $attributes ) {
		$cache_key = $this->generate_cache_key( $api_key );
		
		// Also store metadata about the cache
		$cache_data = array(
			'attributes'   => $attributes,
			'cached_at'    => current_time( 'timestamp' ),
			'api_key_hash' => md5( $api_key ),
		);

		return set_transient( $cache_key, $cache_data, self::CACHE_EXPIRATION );
	}

	/**
	 * Generate cache key from API key
	 *
	 * @param string $api_key API key
	 * @return string Cache key
	 */
	private function generate_cache_key( $api_key ) {
		return self::CACHE_PREFIX . md5( $api_key );
	}

	/**
	 * Clear attributes cache
	 *
	 * @param string $api_key API key (optional)
	 * @return bool True on success
	 */
	public function clear_cache( $api_key = null ) {
		if ( $api_key ) {
			// Clear specific cache
			$cache_key = $this->generate_cache_key( $api_key );
			return delete_transient( $cache_key );
		}

		// Clear all attribute caches (this is expensive, use sparingly)
		global $wpdb;
		
		$cache_prefix = '_transient_' . self::CACHE_PREFIX;
		$sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$cache_prefix . '%',
			'_transient_timeout_' . self::CACHE_PREFIX . '%'
		);
		
		$result = $wpdb->query( $sql );

		if ( WP_DEBUG === true ) {
			error_log( 'Brevo Attributes Manager - Cleared ' . intval( $result / 2 ) . ' cache entries' );
		}

		return $result !== false;
	}

	/**
	 * Get cache information
	 *
	 * @param string $api_key API key
	 * @return array|null Cache info or null if no cache
	 */
	public function get_cache_info( $api_key ) {
		$cache_key = $this->generate_cache_key( $api_key );
		$cache_data = get_transient( $cache_key );

		if ( $cache_data === false ) {
			return null;
		}

		$timeout_key = '_transient_timeout_' . $cache_key;
		$expires_at = get_option( $timeout_key, 0 );

		return array(
			'cached_at'  => $cache_data['cached_at'] ?? 0,
			'expires_at' => $expires_at,
			'is_expired' => $expires_at < time(),
			'count'      => count( $cache_data['attributes'] ?? array() ),
		);
	}

	/**
	 * Validate API key by making a test request
	 *
	 * @param string $api_key API key to validate
	 * @return bool|WP_Error True if valid, WP_Error if invalid
	 */
	public function validate_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return new WP_Error( 'empty_api_key', __( 'API key cannot be empty', 'ml-brevo-for-elementor-pro' ) );
		}

		$endpoint = self::API_BASE_URL . '/account';
		
		$response = wp_remote_get( $endpoint, array(
			'timeout'     => 15,
			'httpversion' => '1.0',
			'headers'     => array(
				'accept'   => 'application/json',
				'api-key'  => $api_key,
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code === 200 ) {
			return true;
		}

		if ( $response_code === 401 ) {
			return new WP_Error( 'invalid_api_key', __( 'Invalid API key', 'ml-brevo-for-elementor-pro' ) );
		}

		return new WP_Error( 
			'api_validation_failed', 
			sprintf( __( 'API validation failed with status %d', 'ml-brevo-for-elementor-pro' ), $response_code )
		);
	}

	/**
	 * Get singleton instance
	 *
	 * @return Brevo_Attributes_Manager
	 */
	public static function get_instance() {
		static $instance = null;
		
		if ( $instance === null ) {
			$instance = new self();
		}
		
		return $instance;
	}
} 