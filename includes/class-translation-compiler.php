<?php
/**
 * Translation Compiler Class
 * 
 * Handles automatic compilation of .po files to .mo files
 * 
 * @package ML_Brevo_For_Elementor_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ML_Brevo_Translation_Compiler {
    
    /**
     * Initialize the translation compiler
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'handle_compile_translations' ) );
        add_action( 'admin_notices', array( $this, 'show_compile_notices' ) );
    }
    
    /**
     * Handle the compilation request
     */
    public function handle_compile_translations() {
        if ( ! isset( $_POST['compile_brevo_translations'] ) ) {
            return;
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'compile_brevo_translations' ) ) {
            return;
        }
        
        $result = $this->compile_all_translations();
        
        if ( $result['success'] ) {
            set_transient( 'brevo_translation_compile_success', $result['message'], 30 );
        } else {
            set_transient( 'brevo_translation_compile_error', $result['message'], 30 );
        }
        
        wp_redirect( admin_url( 'options-general.php?page=ml-brevo-free&tab=translations' ) );
        exit;
    }
    
    /**
     * Show compilation notices
     */
    public function show_compile_notices() {
        if ( $success_message = get_transient( 'brevo_translation_compile_success' ) ) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✅ ' . esc_html( $success_message ) . '</strong></p></div>';
            delete_transient( 'brevo_translation_compile_success' );
        }
        
        if ( $error_message = get_transient( 'brevo_translation_compile_error' ) ) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>❌ ' . esc_html( $error_message ) . '</strong></p></div>';
            delete_transient( 'brevo_translation_compile_error' );
        }
    }
    
    /**
     * Compile all translation files
     */
    public function compile_all_translations() {
        $languages_dir = BREVO_ELEMENTOR_PLUGIN_PATH . 'languages/';
        $languages = array( 'it_IT', 'fr_FR', 'de_DE', 'es_ES' );
        
        $compiled = 0;
        $errors = array();
        
        foreach ( $languages as $lang ) {
            $po_file = $languages_dir . "ml-brevo-for-elementor-pro-{$lang}.po";
            $mo_file = $languages_dir . "ml-brevo-for-elementor-pro-{$lang}.mo";
            
            if ( ! file_exists( $po_file ) ) {
                // translators: %s is the language code
                $errors[] = sprintf( __( 'File PO non trovato: %s', 'ml-brevo-for-elementor-pro' ), $lang );
                continue;
            }
            
            if ( $this->compile_po_to_mo( $po_file, $mo_file ) ) {
                $compiled++;
            } else {
                // translators: %s is the language code
                $errors[] = sprintf( __( 'Errore nella compilazione: %s', 'ml-brevo-for-elementor-pro' ), $lang );
            }
        }
        
        if ( ! empty( $errors ) ) {
            return array(
                'success' => false,
                'message' => implode( ', ', $errors )
            );
        }
        
        return array(
            'success' => true,
            // translators: %d is the number of compiled translation files
            'message' => sprintf( __( '%d file di traduzione compilati con successo!', 'ml-brevo-for-elementor-pro' ), $compiled )
        );
    }
    
    /**
     * Compile a single PO file to MO
     */
    private function compile_po_to_mo( $po_file, $mo_file ) {
		// Debug: Check if files are accessible
		if ( ! is_readable( $po_file ) ) {
			return false;
		}
		
		$mo_dir = dirname( $mo_file );
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		if ( ! $wp_filesystem->is_writable( $mo_dir ) ) {
			return false;
		}
		
		// Load WordPress PO/MO classes
		if ( ! class_exists( 'PO' ) ) {
			require_once ABSPATH . WPINC . '/pomo/po.php';
		}
		
		if ( ! class_exists( 'MO' ) ) {
			require_once ABSPATH . WPINC . '/pomo/mo.php';
		}
		
		// Import PO file
		$po = new PO();
		if ( ! $po->import_from_file( $po_file ) ) {
			return false;
		}
		
		// Create MO file
		$mo = new MO();
		$mo->headers = $po->headers;
		$mo->entries = $po->entries;
		
		$result = $mo->export_to_file( $mo_file );
		
		return $result;
	}
    
    /**
     * Get translation statistics
     */
    public function get_translation_stats() {
        $languages_dir = BREVO_ELEMENTOR_PLUGIN_PATH . 'languages/';
        $languages = array(
            'it_IT' => '🇮🇹 Italiano',
            'fr_FR' => '🇫🇷 Francese', 
            'de_DE' => '🇩🇪 Tedesco',
            'es_ES' => '🇪🇸 Spagnolo'
        );
        
        $stats = array();
        
        foreach ( $languages as $code => $name ) {
            $po_file = $languages_dir . "ml-brevo-for-elementor-pro-{$code}.po";
            $mo_file = $languages_dir . "ml-brevo-for-elementor-pro-{$code}.mo";
            
            $stats[$code] = array(
                'name' => $name,
                'po_exists' => file_exists( $po_file ),
                'mo_exists' => file_exists( $mo_file ),
                'po_size' => file_exists( $po_file ) ? size_format( filesize( $po_file ) ) : 'N/A',
                'mo_size' => file_exists( $mo_file ) ? size_format( filesize( $mo_file ) ) : 'N/A',
                'po_modified' => file_exists( $po_file ) ? gmdate( 'Y-m-d H:i:s', filemtime( $po_file ) ) : 'N/A',
				'mo_modified' => file_exists( $mo_file ) ? gmdate( 'Y-m-d H:i:s', filemtime( $mo_file ) ) : 'N/A',
                'needs_compile' => file_exists( $po_file ) && ( ! file_exists( $mo_file ) || filemtime( $po_file ) > filemtime( $mo_file ) )
            );
        }
        
        return $stats;
    }
    
    /**
     * Check if any translations need compilation
     */
    public function needs_compilation() {
        $stats = $this->get_translation_stats();
        
        foreach ( $stats as $stat ) {
            if ( $stat['needs_compile'] ) {
                return true;
            }
        }
        
        return false;
    }
}

// Initialize the translation compiler
new ML_Brevo_Translation_Compiler(); 