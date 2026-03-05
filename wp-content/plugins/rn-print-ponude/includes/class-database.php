<?php
/**
 * Database class
 *
 * @package RN_Print_Ponude
 */

namespace RN_Print_Ponude;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database class
 */
class Database {

	/**
	 * Create custom tables
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Materials table
		$materials_table = $wpdb->prefix . 'rnp_materials';
		$wpdb->query( $wpdb->prepare(
			"CREATE TABLE IF NOT EXISTS {$materials_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(255) NOT NULL,
				description TEXT,
				unit_type VARCHAR(50) NOT NULL DEFAULT 'm2',
				active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
			) {$charset_collate}"
		) );

		// Finishes table
		$finishes_table = $wpdb->prefix . 'rnp_finishes';
		$wpdb->query( $wpdb->prepare(
			"CREATE TABLE IF NOT EXISTS {$finishes_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(255) NOT NULL,
				description TEXT,
				active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
			) {$charset_collate}"
		) );

		// Material-Finish mapping table
		$material_finishes_table = $wpdb->prefix . 'rnp_material_finishes';
		$wpdb->query( $wpdb->prepare(
			"CREATE TABLE IF NOT EXISTS {$material_finishes_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				material_id BIGINT(20) UNSIGNED NOT NULL,
				finish_id BIGINT(20) UNSIGNED NOT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				UNIQUE KEY material_finish (material_id, finish_id),
				FOREIGN KEY (material_id) REFERENCES {$materials_table}(id) ON DELETE CASCADE,
				FOREIGN KEY (finish_id) REFERENCES {$finishes_table}(id) ON DELETE CASCADE
			) {$charset_collate}"
		) );

		// Price rules table
		$price_rules_table = $wpdb->prefix . 'rnp_price_rules';
		$wpdb->query( $wpdb->prepare(
			"CREATE TABLE IF NOT EXISTS {$price_rules_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				material_id BIGINT(20) UNSIGNED NOT NULL,
				finish_id BIGINT(20) UNSIGNED,
				min_qty DECIMAL(10, 2) NOT NULL DEFAULT 0,
				max_qty DECIMAL(10, 2),
				price_per_unit DECIMAL(10, 2) NOT NULL,
				pricing_type VARCHAR(50) NOT NULL DEFAULT 'fixed',
				active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				FOREIGN KEY (material_id) REFERENCES {$materials_table}(id) ON DELETE CASCADE,
				FOREIGN KEY (finish_id) REFERENCES {$finishes_table}(id) ON DELETE CASCADE
			) {$charset_collate}"
		) );

		// Quotes table
		$quotes_table = $wpdb->prefix . 'rnp_quotes';
		$wpdb->query( $wpdb->prepare(
			"CREATE TABLE IF NOT EXISTS {$quotes_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				material_id BIGINT(20) UNSIGNED NOT NULL,
				quantity DECIMAL(10, 2) NOT NULL,
				unit_type VARCHAR(50) NOT NULL DEFAULT 'm2',
				customer_name VARCHAR(255) NOT NULL,
				customer_email VARCHAR(255) NOT NULL,
				customer_phone VARCHAR(20),
				notes TEXT,
				calculated_price DECIMAL(10, 2),
				confirmed_price DECIMAL(10, 2),
				status VARCHAR(50) NOT NULL DEFAULT 'pending',
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				FOREIGN KEY (material_id) REFERENCES {$materials_table}(id) ON DELETE CASCADE,
				INDEX status (status),
				INDEX created_at (created_at)
			) {$charset_collate}"
		) );

		// Quote files table
		$quote_files_table = $wpdb->prefix . 'rnp_quote_files';
		$wpdb->query( $wpdb->prepare(
			"CREATE TABLE IF NOT EXISTS {$quote_files_table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				quote_id BIGINT(20) UNSIGNED NOT NULL,
				file_type VARCHAR(50) NOT NULL DEFAULT 'file',
				file_url VARCHAR(1024),
				file_path VARCHAR(1024),
				file_size BIGINT(20),
				mime_type VARCHAR(100),
				scan_status VARCHAR(50) DEFAULT 'pending',
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				FOREIGN KEY (quote_id) REFERENCES {$quotes_table}(id) ON DELETE CASCADE,
				INDEX quote_id (quote_id)
			) {$charset_collate}"
		) );
	}

	/**
	 * Get all materials
	 *
	 * @return array
	 */
	public function get_materials() {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_materials';
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE active = 1 ORDER BY name ASC" );
	}

	/**
	 * Get material by ID
	 *
	 * @param int $material_id
	 * @return object|null
	 */
	public function get_material( $material_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_materials';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $material_id ) );
	}

	/**
	 * Get finishes for a material
	 *
	 * @param int $material_id
	 * @return array
	 */
	public function get_material_finishes( $material_id ) {
		global $wpdb;
		$finishes_table = $wpdb->prefix . 'rnp_finishes';
		$material_finishes_table = $wpdb->prefix . 'rnp_material_finishes';

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT f.* FROM {$finishes_table} f
			INNER JOIN {$material_finishes_table} mf ON mf.finish_id = f.id
			WHERE mf.material_id = %d AND f.active = 1
			ORDER BY f.name ASC",
			$material_id
		) );
	}

	/**
	 * Calculate price for material and quantity
	 *
	 * @param int    $material_id
	 * @param float  $quantity
	 * @param array  $finish_ids
	 * @return float|false
	 */
	public function calculate_price( $material_id, $quantity, $finish_ids = array() ) {
		global $wpdb;
		$price_rules_table = $wpdb->prefix . 'rnp_price_rules';

		// Get base price for material
		$base_price = $wpdb->get_var( $wpdb->prepare(
			"SELECT price_per_unit FROM {$price_rules_table}
			WHERE material_id = %d AND finish_id IS NULL
			AND min_qty <= %f AND (max_qty IS NULL OR max_qty >= %f)
			AND active = 1
			LIMIT 1",
			$material_id,
			$quantity,
			$quantity
		) );

		if ( ! $base_price ) {
			return false;
		}

		$total_price = floatval( $base_price ) * floatval( $quantity );

		// Add finish prices if any
		if ( ! empty( $finish_ids ) ) {
			foreach ( $finish_ids as $finish_id ) {
				$finish_price = $wpdb->get_var( $wpdb->prepare(
					"SELECT price_per_unit FROM {$price_rules_table}
					WHERE material_id = %d AND finish_id = %d
					AND min_qty <= %f AND (max_qty IS NULL OR max_qty >= %f)
					AND active = 1
					LIMIT 1",
					$material_id,
					$finish_id,
					$quantity,
					$quantity
				) );

				if ( $finish_price ) {
					$total_price += floatval( $finish_price ) * floatval( $quantity );
				}
			}
		}

		return round( $total_price, 2 );
	}

	/**
	 * Create a quote
	 *
	 * @param array $data
	 * @return int|false
	 */
	public function create_quote( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_quotes';

		$result = $wpdb->insert(
			$table,
			array(
				'material_id'      => intval( $data['material_id'] ),
				'quantity'         => floatval( $data['quantity'] ),
				'unit_type'        => sanitize_text_field( $data['unit_type'] ?? 'm2' ),
				'customer_name'    => sanitize_text_field( $data['customer_name'] ),
				'customer_email'   => sanitize_email( $data['customer_email'] ),
				'customer_phone'   => sanitize_text_field( $data['customer_phone'] ?? '' ),
				'notes'            => sanitize_textarea_field( $data['notes'] ?? '' ),
				'calculated_price' => floatval( $data['calculated_price'] ?? 0 ),
				'status'           => 'pending',
			),
			array( '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%f', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get quote by ID
	 *
	 * @param int $quote_id
	 * @return object|null
	 */
	public function get_quote( $quote_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_quotes';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $quote_id ) );
	}

	/**
	 * Add file to quote
	 *
	 * @param int    $quote_id
	 * @param string $file_type
	 * @param array  $file_data
	 * @return int|false
	 */
	public function add_quote_file( $quote_id, $file_type, $file_data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_quote_files';

		return $wpdb->insert(
			$table,
			array(
				'quote_id'    => intval( $quote_id ),
				'file_type'   => sanitize_text_field( $file_type ),
				'file_url'    => isset( $file_data['url'] ) ? esc_url( $file_data['url'] ) : null,
				'file_path'   => isset( $file_data['path'] ) ? sanitize_text_field( $file_data['path'] ) : null,
				'file_size'   => isset( $file_data['size'] ) ? intval( $file_data['size'] ) : null,
				'mime_type'   => isset( $file_data['mime'] ) ? sanitize_text_field( $file_data['mime'] ) : null,
				'scan_status' => 'pending',
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		) ? $wpdb->insert_id : false;
	}
}
