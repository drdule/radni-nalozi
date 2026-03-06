<?php
/**
 * AJAX class
 *
 * @package RN_Print_Ponude
 */

namespace RN_Print_Ponude;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX class
 */
class Ajax {

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	private $db;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->db = new Database();

		// AJAX actions
		add_action( 'wp_ajax_rnp_calculate_price', array( $this, 'calculate_price' ) );
		add_action( 'wp_ajax_nopriv_rnp_calculate_price', array( $this, 'calculate_price' ) );

		add_action( 'wp_ajax_rnp_get_finishes', array( $this, 'get_finishes' ) );
		add_action( 'wp_ajax_nopriv_rnp_get_finishes', array( $this, 'get_finishes' ) );

		add_action( 'wp_ajax_rnp_get_material_info', array( $this, 'get_material_info' ) );
		add_action( 'wp_ajax_nopriv_rnp_get_material_info', array( $this, 'get_material_info' ) );

		add_action( 'wp_ajax_rnp_submit_quote', array( $this, 'submit_quote' ) );
		add_action( 'wp_ajax_nopriv_rnp_submit_quote', array( $this, 'submit_quote' ) );

		add_action( 'wp_ajax_rnp_get_quote_status', array( $this, 'get_quote_status' ) );
		add_action( 'wp_ajax_nopriv_rnp_get_quote_status', array( $this, 'get_quote_status' ) );
	}

	/**
	 * Calculate price AJAX handler
	 */
	public function calculate_price() {
		check_ajax_referer( 'rnp_nonce', 'nonce' );

		$material_id = intval( $_POST['material_id'] ?? 0 );
		$quantity    = floatval( $_POST['quantity'] ?? 0 );
		$finish_ids  = isset( $_POST['finish_ids'] ) ? array_map( 'intval', (array) $_POST['finish_ids'] ) : array();

		if ( ! $material_id || ! $quantity ) {
			wp_send_json_error( array( 'message' => 'Nedostaju obavezni parametri.' ) );
		}

		$price = $this->db->calculate_price( $material_id, $quantity, $finish_ids );

		if ( $price === false ) {
			wp_send_json_error( array( 'message' => 'Nisu pronađena cena za date parametre.' ) );
		}

		wp_send_json_success( array(
			'price'   => number_format( $price, 2, ',', '.' ),
			'price_raw' => $price,
			'currency' => 'RSD',
		) );
	}

	/**
	 * Get finishes for a material
	 */
	public function get_finishes() {
		check_ajax_referer( 'rnp_nonce', 'nonce' );

		$material_id = intval( $_POST['material_id'] ?? 0 );

		if ( ! $material_id ) {
			wp_send_json_error( array( 'message' => 'Material ID nije pronađen.' ) );
		}

		$finishes = $this->db->get_material_finishes( $material_id );

		wp_send_json_success( array(
			'finishes' => $finishes,
		) );
	}

	/**
	 * Get material info (unit type, etc.)
	 */
	public function get_material_info() {
		check_ajax_referer( 'rnp_nonce', 'nonce' );

		$material_id = intval( $_POST['material_id'] ?? 0 );

		if ( ! $material_id ) {
			wp_send_json_error( array( 'message' => 'Material ID nije pronađen.' ) );
		}

		$material = $this->db->get_material( $material_id );

		if ( ! $material ) {
			wp_send_json_error( array( 'message' => 'Materijal nije pronađen.' ) );
		}

		// Map unit types to labels
		$unit_labels = array(
			'm2'      => 'm²',
			'piece'   => 'kom',
			'flyer'   => 'tabak',
			'a_format' => 'A format',
		);

		wp_send_json_success( array(
			'material'   => $material,
			'unit_type'  => $material->unit_type,
			'unit_label' => $unit_labels[ $material->unit_type ] ?? $material->unit_type,
		) );
	}

	/**
	 * Submit quote
	 */
	public function submit_quote() {
		check_ajax_referer( 'rnp_nonce', 'nonce' );

		$material_id      = intval( $_POST['material_id'] ?? 0 );
		$quantity         = floatval( $_POST['quantity'] ?? 0 );
		$customer_name    = sanitize_text_field( $_POST['customer_name'] ?? '' );
		$customer_email   = sanitize_email( $_POST['customer_email'] ?? '' );
		$customer_phone   = sanitize_text_field( $_POST['customer_phone'] ?? '' );
		$notes            = sanitize_textarea_field( $_POST['notes'] ?? '' );
		$calculated_price = floatval( $_POST['calculated_price'] ?? 0 );

		// Validate required fields
		if ( ! $material_id || ! $quantity || ! $customer_name || ! $customer_email ) {
			wp_send_json_error( array( 'message' => 'Popunite sve obavezne podatke.' ) );
		}

		// Validate file upload is required
		$has_files = ! empty( $_FILES['file_upload']['name'] ) && is_array( $_FILES['file_upload']['name'] );

		if ( ! $has_files ) {
			wp_send_json_error( array( 'message' => 'Morate upload-ovati bar jedan fajl.' ) );
		}

		// Get material
		$material = $this->db->get_material( $material_id );
		if ( ! $material ) {
			wp_send_json_error( array( 'message' => 'Materijal nije pronađen.' ) );
		}

		// Create quote
		$quote_data = array(
			'material_id'      => $material_id,
			'quantity'         => $quantity,
			'unit_type'        => $material->unit_type,
			'customer_name'    => $customer_name,
			'customer_email'   => $customer_email,
			'customer_phone'   => $customer_phone,
			'notes'            => $notes,
			'calculated_price' => $calculated_price,
		);

		$quote_id = $this->db->create_quote( $quote_data );

		if ( ! $quote_id ) {
			wp_send_json_error( array( 'message' => 'Greška pri kreiranju ponude. Pokušajte ponovo.' ) );
		}

		// Handle file uploads
		if ( $has_files ) {
			$file_count = count( $_FILES['file_upload']['name'] );
			for ( $i = 0; $i < $file_count; $i++ ) {
				$file = array(
					'name'     => $_FILES['file_upload']['name'][ $i ],
					'type'     => $_FILES['file_upload']['type'][ $i ],
					'tmp_name' => $_FILES['file_upload']['tmp_name'][ $i ],
					'error'    => $_FILES['file_upload']['error'][ $i ],
					'size'     => $_FILES['file_upload']['size'][ $i ],
				);
				
				$file_result = $this->handle_file_upload( $quote_id, $file );
				if ( is_wp_error( $file_result ) ) {
					wp_send_json_error( array( 'message' => $file_result->get_error_message() ) );
				}
			}
		}

		// Send email notification to admin
		$this->send_admin_notification( $quote_id );

		wp_send_json_success( array(
			'message' => 'Ponuda je uspešno poslana!',
			'quote_id' => $quote_id,
		) );
	}

	/**
	 * Handle file upload
	 *
	 * @param int   $quote_id
	 * @param array $file
	 * @return true|WP_Error
	 */
	private function handle_file_upload( $quote_id, $file ) {
		// Allowed file types
		$allowed_types = array(
			'application/pdf'                => '.pdf',
			'application/postscript'         => '.ai',
			'application/photoshop'          => '.psd',
			'image/jpeg'                     => '.jpg, .jpeg',
			'image/png'                      => '.png',
			'image/tiff'                     => '.tiff',
		);

		// Check file size
		if ( $file['size'] > 150 * 1024 * 1024 ) {
			return new \WP_Error( 'file_too_large', 'Fajl je prevelik. Maksimalno 150MB.' );
		}

		// Check MIME type
		$file_type = wp_check_filetype( $file['name'] );
		if ( ! isset( $allowed_types[ $file_type['type'] ] ) ) {
			return new \WP_Error( 'invalid_file_type', 'Nedozvoljen tip fajla.' );
		}

		// Generate random filename
		$upload_dir = wp_upload_dir();
		$upload_path = $upload_dir['basedir'] . '/rnp-quotes/' . $quote_id . '/';
		wp_mkdir_p( $upload_path );

		$original_name = sanitize_file_name( wp_basename( $file['name'] ) );
		$original_base = pathinfo( $original_name, PATHINFO_FILENAME );
		$original_ext = pathinfo( $original_name, PATHINFO_EXTENSION );
		$safe_ext = $original_ext ? strtolower( $original_ext ) : strtolower( (string) $file_type['ext'] );
		$safe_base = $original_base ? sanitize_file_name( $original_base ) : 'fajl';

		$random_name = 'quote_' . $quote_id . '_' . wp_generate_password( 8, false ) . '_' . $safe_base . '.' . $safe_ext;
		$file_path = $upload_path . $random_name;

		// Move uploaded file
		if ( ! move_uploaded_file( $file['tmp_name'], $file_path ) ) {
			return new \WP_Error( 'upload_failed', 'Greška pri upload-ovanju fajla.' );
		}

		// Add file record to database
		$file_url = $upload_dir['baseurl'] . '/rnp-quotes/' . $quote_id . '/' . $random_name;
		$this->db->add_quote_file( $quote_id, 'file', array(
			'url'  => $file_url,
			'path' => $file_path,
			'size' => $file['size'],
			'mime' => $file_type['type'],
		) );

		return true;
	}

	/**
	 * Send admin notification email
	 *
	 * @param int $quote_id
	 */
	private function send_admin_notification( $quote_id ) {
		$quote = $this->db->get_quote( $quote_id );
		if ( ! $quote ) {
			return;
		}

		$subject = 'Nova ponuda #' . $quote_id . ' - RN Print';
		$message = sprintf(
			'Nova ponuda je primljena:%1$sKupac: %2$s%1$sEmail: %3$s%1$sMatrijal ID: %4$d%1$sKoličina: %5$f%1$sIzračunata cena: %6$.2f RSD',
			"\n",
			$quote->customer_name,
			$quote->customer_email,
			$quote->material_id,
			$quote->quantity,
			$quote->calculated_price
		);

		wp_mail( get_option( 'admin_email' ), $subject, $message );
	}

	/**
	 * Get quote status AJAX handler
	 */
	public function get_quote_status() {
		check_ajax_referer( 'rnp_nonce', 'nonce' );

		$quote_id = isset( $_POST['quote_id'] ) ? intval( $_POST['quote_id'] ) : 0;

		if ( ! $quote_id ) {
			wp_send_json_error( 'Invalid quote ID' );
		}

		$quote = $this->db->get_quote( $quote_id );

		if ( ! $quote ) {
			wp_send_json_error( 'Quote not found' );
		}

		wp_send_json_success( array(
			'quote_id' => $quote->id,
			'status' => $quote->status,
		) );
	}
}
