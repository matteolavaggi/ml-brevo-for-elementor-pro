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
	 * Brevo API base URL
	 */
	const API_BASE_URL = 'https://api.brevo.com/v3';

	/**
	 * Fetch all contact attributes from Brevo API
	 *
	 * @param string $api_key Brevo API key
	 * @param int    $limit   Maximum number of attributes to fetch (default: 50, max: 100)
	 * @param int    $offset  Starting point for pagination (default: 0)
	 * @return array|WP_Error Array of attributes or WP_Error on failure
	 */
	public function fetch_attributes( $api_key, $limit = 50, $offset = 0 ) {
		$start_time = microtime( true );

		if ( empty( $api_key ) ) {
			return new WP_Error( 'invalid_api_key', __( 'API key is required', 'ml-brevo-for-elementor-pro' ) );
		}

		// Validate pagination parameters
		$limit  = max( 1, min( 100, intval( $limit ) ) ); // Ensure limit is between 1 and 100
		$offset = max( 0, intval( $offset ) ); // Ensure offset is not negative

		// Build endpoint with pagination parameters
		$endpoint = self::API_BASE_URL . '/contacts/attributes';
		$endpoint = add_query_arg(
			array(
				'limit'  => $limit,
				'offset' => $offset,
			),
			$endpoint
		);

		// Debug logging
		$logger = Brevo_Debug_Logger::get_instance();
		$logger->info(
			'Fetching attributes from API',
			'API',
			'fetch_attributes',
			array(
				'endpoint'     => $endpoint,
				'limit'        => $limit,
				'offset'       => $offset,
				'api_key_hash' => md5( $api_key ),
			)
		);

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout'     => 30,
				'httpversion' => '1.0',
				'headers'     => array(
					'accept'       => 'application/json',
					'api-key'      => $api_key,
					'content-type' => 'application/json',
				),
			)
		);

		// Handle request errors
		if ( is_wp_error( $response ) ) {
			$logger->error(
				'API request failed: ' . $response->get_error_message(),
				'API',
				'fetch_attributes',
				array(
					'endpoint'   => $endpoint,
					'error_code' => $response->get_error_code(),
				)
			);

			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Handle HTTP errors
		if ( $response_code < 200 || $response_code >= 300 ) {
			/* translators: %d is the HTTP response status code */
			$error_message = sprintf( __( 'Brevo API request failed with status %d', 'ml-brevo-for-elementor-pro' ), $response_code );

			// Try to extract error message from response
			$decoded_body = json_decode( $response_body, true );
			if ( isset( $decoded_body['message'] ) ) {
				$error_message .= ': ' . $decoded_body['message'];
			}

			$logger->error(
				'API HTTP error: ' . $error_message,
				'API',
				'fetch_attributes',
				array(
					'response_code' => $response_code,
					'response_body' => $response_body,
					'decoded_body'  => $decoded_body,
				)
			);

			return new WP_Error( 'api_request_failed', $error_message );
		}

		// Parse JSON response
		$data = json_decode( $response_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON response from Brevo API', 'ml-brevo-for-elementor-pro' ) );
		}

		// Normalize attributes
		$normalized_attributes = $this->normalize_attributes( $data );

		$logger->info(
			'Successfully fetched attributes',
			'API',
			'fetch_attributes',
			array(
				'attributes_count' => count( $normalized_attributes ),
				'execution_time'   => microtime( true ) - $start_time,
				'api_key_hash'     => md5( $api_key ),
			)
		);

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

		if ( ! is_array( $raw_data ) ) {

			return $normalized;
		}

		foreach ( $raw_data['attributes'] as $attribute ) {
			if ( ! is_array( $attribute ) || ! isset( $attribute['name'] ) ) {

				continue;
			}

			$field_name = sanitize_text_field( $attribute['name'] );

			// Skip if field name is empty after sanitization
			if ( empty( $field_name ) ) {
				continue;
			}

			$normalized[ $field_name ] = array(
				'name'        => $field_name,
				'type'        => $this->map_field_type( $attribute['type'] ?? 'text' ),
				'category'    => sanitize_text_field( $attribute['category'] ?? 'normal' ),
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
			'FIRSTNAME'  => __( 'Contact first name', 'ml-brevo-for-elementor-pro' ),
			'LASTNAME'   => __( 'Contact last name', 'ml-brevo-for-elementor-pro' ),
			'SMS'        => __( 'Contact phone number', 'ml-brevo-for-elementor-pro' ),
			'EMAIL'      => __( 'Contact email address', 'ml-brevo-for-elementor-pro' ),
			'COMPANY'    => __( 'Contact company name', 'ml-brevo-for-elementor-pro' ),
			'WEBSITE'    => __( 'Contact website URL', 'ml-brevo-for-elementor-pro' ),
			'ADDRESS'    => __( 'Contact address', 'ml-brevo-for-elementor-pro' ),
			'BIRTH_DATE' => __( 'Contact birth date', 'ml-brevo-for-elementor-pro' ),
			'GENDER'     => __( 'Contact gender', 'ml-brevo-for-elementor-pro' ),
			'COUNTRY'    => __( 'Contact country', 'ml-brevo-for-elementor-pro' ),
			'CITY'       => __( 'Contact city', 'ml-brevo-for-elementor-pro' ),
			'ZIPCODE'    => __( 'Contact postal code', 'ml-brevo-for-elementor-pro' ),
		);

		if ( isset( $descriptions[ $field_name ] ) ) {
			return $descriptions[ $field_name ];
		}

		// Generate description from field name
		$formatted_name = ucwords( strtolower( str_replace( '_', ' ', $field_name ) ) );
		/* translators: %s is the formatted field name */
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
			'LASTNAME'  => array(
				'name'        => 'LASTNAME',
				'type'        => 'text',
				'category'    => 'normal',
				'required'    => false,
				'description' => __( 'Contact last name', 'ml-brevo-for-elementor-pro' ),
				'enabled'     => true,
			),
			'SMS'       => array(
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

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout'     => 15,
				'httpversion' => '1.0',
				'headers'     => array(
					'accept'  => 'application/json',
					'api-key' => $api_key,
				),
			)
		);

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

		/* translators: %d is the HTTP response status code */
		return new WP_Error( 'api_validation_failed', sprintf( __( 'API validation failed with status %d', 'ml-brevo-for-elementor-pro' ), $response_code ) );
	}

	/**
	 * Fetch all contact lists from Brevo API
	 *
	 * @param string $api_key Brevo API key
	 * @param int    $limit   Not used - kept for compatibility
	 * @param int    $offset  Not used - kept for compatibility
	 * @return array|WP_Error Array of lists or WP_Error on failure
	 */
	public function fetch_lists( $api_key, $limit = 50, $offset = 0 ) {
		$start_time = microtime( true );

		if ( empty( $api_key ) ) {
			return new WP_Error( 'invalid_api_key', __( 'API key is required', 'ml-brevo-for-elementor-pro' ) );
		}

		// Brevo lists API doesn't support pagination - fetch all lists
		$endpoint = self::API_BASE_URL . '/contacts/lists';

		// Debug logging
		$logger = Brevo_Debug_Logger::get_instance();
		$logger->info(
			'Fetching lists from API',
			'API',
			'fetch_lists',
			array(
				'endpoint'     => $endpoint,
				'api_key_hash' => md5( $api_key ),
			)
		);

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout'     => 30,
				'httpversion' => '1.0',
				'headers'     => array(
					'accept'       => 'application/json',
					'api-key'      => $api_key,
					'content-type' => 'application/json',
				),
			)
		);

		// Handle request errors
		if ( is_wp_error( $response ) ) {
			$logger->error(
				'API request failed: ' . $response->get_error_message(),
				'API',
				'fetch_lists',
				array(
					'endpoint'   => $endpoint,
					'error_code' => $response->get_error_code(),
				)
			);

			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Handle HTTP errors
		if ( $response_code < 200 || $response_code >= 300 ) {
			/* translators: %d is the HTTP response status code */
			$error_message = sprintf( __( 'Brevo Lists API request failed with status %d', 'ml-brevo-for-elementor-pro' ), $response_code );

			// Try to extract error message from response
			$decoded_body = json_decode( $response_body, true );
			if ( isset( $decoded_body['message'] ) ) {
				$error_message .= ': ' . $decoded_body['message'];
			}

			$logger->error(
				'API HTTP error: ' . $error_message,
				'API',
				'fetch_lists',
				array(
					'response_code' => $response_code,
					'response_body' => $response_body,
					'decoded_body'  => $decoded_body,
					'endpoint'      => $endpoint,
				)
			);

			return new WP_Error( 'api_request_failed', $error_message );
		}

		// Parse JSON response
		$data = json_decode( $response_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON response from Brevo Lists API', 'ml-brevo-for-elementor-pro' ) );
		}

		// Normalize lists
		$normalized_lists = $this->normalize_lists( $data );

		$logger->info(
			'Successfully fetched lists',
			'API',
			'fetch_lists',
			array(
				'lists_count'    => count( $normalized_lists ),
				'execution_time' => microtime( true ) - $start_time,
				'api_key_hash'   => md5( $api_key ),
			)
		);

		return $normalized_lists;
	}

	/**
	 * Normalize lists data from Brevo API response
	 *
	 * @param array $raw_data Raw API response data
	 * @return array Normalized lists array
	 */
	public function normalize_lists( $raw_data ) {
		$normalized = array();

		if ( ! is_array( $raw_data ) ) {

			return $normalized;
		}

		foreach ( $raw_data['lists'] as $list ) {
			if ( ! is_array( $list ) || ! isset( $list['id'] ) || ! isset( $list['name'] ) ) {

				continue;
			}

			$list_id   = intval( $list['id'] );
			$list_name = sanitize_text_field( $list['name'] );

			// Skip if list ID or name is invalid
			if ( $list_id <= 0 || empty( $list_name ) ) {
				continue;
			}

			$normalized[ $list_id ] = array(
				'id'                => $list_id,
				'name'              => $list_name,
				'folderIds'         => isset( $list['folderIds'] ) ? array_map( 'intval', (array) $list['folderIds'] ) : array(),
				'totalBlacklisted'  => isset( $list['totalBlacklisted'] ) ? intval( $list['totalBlacklisted'] ) : 0,
				'totalSubscribers'  => isset( $list['totalSubscribers'] ) ? intval( $list['totalSubscribers'] ) : 0,
				'uniqueSubscribers' => isset( $list['uniqueSubscribers'] ) ? intval( $list['uniqueSubscribers'] ) : 0,
				'createdAt'         => isset( $list['createdAt'] ) ? sanitize_text_field( $list['createdAt'] ) : '',
				'modifiedAt'        => isset( $list['modifiedAt'] ) ? sanitize_text_field( $list['modifiedAt'] ) : '',
			);
		}

		return $normalized;
	}

	/**
	 * Fetch all attributes with automatic pagination
	 *
	 * @param string $api_key Brevo API key
	 * @param int    $max_items Maximum items to fetch (0 = unlimited)
	 * @return array|WP_Error Array of all attributes or WP_Error on failure
	 */
	public function fetch_all_attributes( $api_key, $max_items = 0 ) {
		$all_attributes = array();
		$offset         = 0;
		$limit          = 100; // Use maximum limit for efficiency
		$fetched_count  = 0;

		do {
			$attributes = $this->fetch_attributes( $api_key, $limit, $offset );

			if ( is_wp_error( $attributes ) ) {
				return $attributes;
			}

			$batch_count = count( $attributes );
			if ( $batch_count === 0 ) {
				break; // No more data
			}

			$all_attributes = array_merge( $all_attributes, $attributes );
			$fetched_count += $batch_count;
			$offset        += $limit;

			// Check if we've reached the maximum items limit
			if ( $max_items > 0 && $fetched_count >= $max_items ) {
				$all_attributes = array_slice( $all_attributes, 0, $max_items );
				break;
			}

			// If we got less than the limit, we've reached the end
		} while ( $batch_count === $limit );

		return $all_attributes;
	}

	/**
	 * Fetch all lists (no pagination needed for lists API)
	 *
	 * @param string $api_key Brevo API key
	 * @param int    $max_items Maximum items to fetch (0 = unlimited)
	 * @return array|WP_Error Array of all lists or WP_Error on failure
	 */
	public function fetch_all_lists( $api_key, $max_items = 0 ) {
		// Lists API returns all lists in one call - no pagination needed
		$lists = $this->fetch_lists( $api_key );

		if ( is_wp_error( $lists ) ) {
			return $lists;
		}

		// Apply max_items limit if specified
		if ( $max_items > 0 && count( $lists ) > $max_items ) {
			$lists = array_slice( $lists, 0, $max_items );
		}

		return $lists;
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
