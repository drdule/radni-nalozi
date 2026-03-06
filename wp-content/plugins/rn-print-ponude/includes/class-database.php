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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();

		$unit_types_table = $wpdb->prefix . 'rnp_unit_types';
		$categories_table = $wpdb->prefix . 'rnp_material_categories';
		$materials_table = $wpdb->prefix . 'rnp_materials';
		$finishes_table = $wpdb->prefix . 'rnp_finishes';
		$material_finishes_table = $wpdb->prefix . 'rnp_material_finishes';
		$price_rules_table = $wpdb->prefix . 'rnp_price_rules';
		$quotes_table = $wpdb->prefix . 'rnp_quotes';
		$quote_files_table = $wpdb->prefix . 'rnp_quote_files';

		dbDelta( "CREATE TABLE {$unit_types_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL UNIQUE,
			symbol VARCHAR(20) NOT NULL UNIQUE,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY active (active)
		) {$charset_collate};" );

		// Create categories table
		dbDelta( "CREATE TABLE {$categories_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL UNIQUE,
			description TEXT NULL,
			color VARCHAR(7) NULL DEFAULT '#0073aa',
			active TINYINT(1) NOT NULL DEFAULT 1,
			sort_order INT(11) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY active (active),
			KEY sort_order (sort_order)
		) {$charset_collate};" );

		// Create default categories if they don't exist
		$default_categories = array(
			array( 'name' => 'Štampa velikog formata', 'color' => '#0073aa' ),
			array( 'name' => 'Tabačna štampa', 'color' => '#28a745' ),
			array( 'name' => 'Štampa na tekstilu', 'color' => '#ffc107' ),
			array( 'name' => 'Digitalna štampa', 'color' => '#dc3545' ),
		);

		foreach ( $default_categories as $idx => $category ) {
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT id FROM {$categories_table} WHERE name = %s", $category['name'] )
			);
			if ( ! $row ) {
				$wpdb->insert(
					$categories_table,
					array(
						'name'       => $category['name'],
						'color'      => $category['color'],
						'active'     => 1,
						'sort_order' => $idx,
					),
					array( '%s', '%s', '%d', '%d' )
				);
			}
		}

		dbDelta( "CREATE TABLE {$materials_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT NULL,
			category_id BIGINT(20) UNSIGNED NULL,
			unit_type VARCHAR(50) NOT NULL DEFAULT 'm2',
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY active (active),
			KEY category_id (category_id)
		) {$charset_collate};" );

		dbDelta( "CREATE TABLE {$finishes_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT NULL,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY active (active)
		) {$charset_collate};" );

		dbDelta( "CREATE TABLE {$material_finishes_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			material_id BIGINT(20) UNSIGNED NOT NULL,
			finish_id BIGINT(20) UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY material_finish (material_id, finish_id),
			KEY material_id (material_id),
			KEY finish_id (finish_id)
		) {$charset_collate};" );

		dbDelta( "CREATE TABLE {$price_rules_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			material_id BIGINT(20) UNSIGNED NOT NULL,
			finish_id BIGINT(20) UNSIGNED NULL,
			min_qty DECIMAL(10,2) NOT NULL DEFAULT 0,
			max_qty DECIMAL(10,2) NULL,
			price_per_unit DECIMAL(10,2) NOT NULL,
			pricing_type VARCHAR(50) NOT NULL DEFAULT 'fixed',
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY material_id (material_id),
			KEY finish_id (finish_id),
			KEY active (active)
		) {$charset_collate};" );

		dbDelta( "CREATE TABLE {$quotes_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			material_id BIGINT(20) UNSIGNED NOT NULL,
			quantity DECIMAL(10,2) NOT NULL,
			unit_type VARCHAR(50) NOT NULL DEFAULT 'm2',
			customer_name VARCHAR(255) NOT NULL,
			customer_email VARCHAR(255) NOT NULL,
			customer_phone VARCHAR(40) NULL,
			notes TEXT NULL,
			calculated_price DECIMAL(10,2) NULL,
			confirmed_price DECIMAL(10,2) NULL,
			status VARCHAR(50) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY material_id (material_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};" );

		dbDelta( "CREATE TABLE {$quote_files_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			quote_id BIGINT(20) UNSIGNED NOT NULL,
			file_type VARCHAR(50) NOT NULL DEFAULT 'file',
			file_url VARCHAR(1024) NULL,
			file_path VARCHAR(1024) NULL,
			file_size BIGINT(20) NULL,
			mime_type VARCHAR(100) NULL,
			scan_status VARCHAR(50) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY quote_id (quote_id)
		) {$charset_collate};" );
	}

	/**
	 * Get all unit types for admin.
	 *
	 * @return array
	 */
	public function get_all_unit_types() {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_unit_types';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
	}

	/**
	 * Get active unit types.
	 *
	 * @return array
	 */
	public function get_unit_types() {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_unit_types';
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE active = 1 ORDER BY name ASC" );
	}

	/**
	 * Get unit type by ID.
	 *
	 * @param int $unit_id Unit ID.
	 * @return object|null
	 */
	public function get_unit_type( $unit_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_unit_types';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $unit_id ) );
	}

	/**
	 * Create unit type.
	 *
	 * @param array $data Unit type data.
	 * @return int|false
	 */
	public function create_unit_type( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_unit_types';

		$insert_data = array(
			'name'   => sanitize_text_field( $data['name'] ),
			'symbol' => sanitize_text_field( $data['symbol'] ),
			'active' => ! empty( $data['active'] ) ? 1 : 0,
		);

		$formats = array( '%s', '%s', '%d' );

		$result = $wpdb->insert( $table, $insert_data, $formats );

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update unit type.
	 *
	 * @param int   $unit_id Unit ID.
	 * @param array $data Unit type data.
	 * @return int|false
	 */
	public function update_unit_type( $unit_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_unit_types';

		$update_data = array(
			'name'   => sanitize_text_field( $data['name'] ),
			'symbol' => sanitize_text_field( $data['symbol'] ),
			'active' => ! empty( $data['active'] ) ? 1 : 0,
		);

		$formats = array( '%s', '%s', '%d' );

		return $wpdb->update(
			$table,
			$update_data,
			array( 'id' => (int) $unit_id ),
			$formats,
			array( '%d' )
		);
	}

	/**
	 * Delete unit type.
	 *
	 * @param int $unit_id Unit ID.
	 * @return bool
	 */
	public function delete_unit_type( $unit_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_unit_types';
		return (bool) $wpdb->delete( $table, array( 'id' => (int) $unit_id ), array( '%d' ) );
	}

	/**
	 * Get all materials for admin.
	 *
	 * @return array
	 */
	public function get_all_materials() {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_materials';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
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
	 * Create material.
	 *
	 * @param array $data Material data.
	 * @return int|false
	 */
	public function create_material( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_materials';

		$result = $wpdb->insert(
			$table,
			array(
				'name'        => sanitize_text_field( $data['name'] ),
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'category_id' => ! empty( $data['category_id'] ) ? absint( $data['category_id'] ) : 0,
				'unit_type'   => sanitize_text_field( $data['unit_type'] ),
				'active'      => ! empty( $data['active'] ) ? 1 : 0,
			),
			array( '%s', '%s', '%d', '%s', '%d' )
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update material.
	 *
	 * @param int   $material_id Material ID.
	 * @param array $data Material data.
	 * @return bool
	 */
	public function update_material( $material_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_materials';

		$result = $wpdb->update(
			$table,
			array(
				'name'        => sanitize_text_field( $data['name'] ),
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'category_id' => ! empty( $data['category_id'] ) ? absint( $data['category_id'] ) : 0,
				'unit_type'   => sanitize_text_field( $data['unit_type'] ),
				'active'      => ! empty( $data['active'] ) ? 1 : 0,
			),
			array( 'id' => (int) $material_id ),
			array( '%s', '%s', '%d', '%s', '%d' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete material.
	 *
	 * @param int $material_id Material ID.
	 * @return bool
	 */
	public function delete_material( $material_id ) {
		global $wpdb;
		$materials_table = $wpdb->prefix . 'rnp_materials';
		$material_finishes_table = $wpdb->prefix . 'rnp_material_finishes';
		$price_rules_table = $wpdb->prefix . 'rnp_price_rules';

		$wpdb->delete( $material_finishes_table, array( 'material_id' => (int) $material_id ), array( '%d' ) );
		$wpdb->delete( $price_rules_table, array( 'material_id' => (int) $material_id ), array( '%d' ) );

		return (bool) $wpdb->delete( $materials_table, array( 'id' => (int) $material_id ), array( '%d' ) );
	}

	/**
	 * Get all finishes for admin.
	 *
	 * @return array
	 */
	public function get_all_finishes() {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_finishes';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
	}

	/**
	 * Create finish.
	 *
	 * @param array $data Finish data.
	 * @return int|false
	 */
	public function create_finish( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_finishes';

		$result = $wpdb->insert(
			$table,
			array(
				'name'        => sanitize_text_field( $data['name'] ),
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'active'      => ! empty( $data['active'] ) ? 1 : 0,
			),
			array( '%s', '%s', '%d' )
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update finish.
	 *
	 * @param int   $finish_id Finish ID.
	 * @param array $data Finish data.
	 * @return bool
	 */
	public function update_finish( $finish_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_finishes';

		$result = $wpdb->update(
			$table,
			array(
				'name'        => sanitize_text_field( $data['name'] ),
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'active'      => ! empty( $data['active'] ) ? 1 : 0,
			),
			array( 'id' => (int) $finish_id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete finish.
	 *
	 * @param int $finish_id Finish ID.
	 * @return bool
	 */
	public function delete_finish( $finish_id ) {
		global $wpdb;
		$finishes_table = $wpdb->prefix . 'rnp_finishes';
		$material_finishes_table = $wpdb->prefix . 'rnp_material_finishes';
		$price_rules_table = $wpdb->prefix . 'rnp_price_rules';

		$wpdb->delete( $material_finishes_table, array( 'finish_id' => (int) $finish_id ), array( '%d' ) );
		$wpdb->delete( $price_rules_table, array( 'finish_id' => (int) $finish_id ), array( '%d' ) );

		return (bool) $wpdb->delete( $finishes_table, array( 'id' => (int) $finish_id ), array( '%d' ) );
	}

	/**
	 * Get finish IDs mapped to a material.
	 *
	 * @param int $material_id Material ID.
	 * @return array
	 */
	public function get_material_finish_ids( $material_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_material_finishes';
		return array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare( "SELECT finish_id FROM {$table} WHERE material_id = %d", $material_id )
			)
		);
	}

	/**
	 * Replace finish mapping for material.
	 *
	 * @param int   $material_id Material ID.
	 * @param array $finish_ids Finish IDs.
	 * @return void
	 */
	public function set_material_finishes( $material_id, $finish_ids ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_material_finishes';

		$wpdb->delete( $table, array( 'material_id' => (int) $material_id ), array( '%d' ) );

		foreach ( (array) $finish_ids as $finish_id ) {
			$finish_id = (int) $finish_id;
			if ( $finish_id <= 0 ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'material_id' => (int) $material_id,
					'finish_id'   => $finish_id,
				),
				array( '%d', '%d' )
			);
		}
	}

	/**
	 * Get all price rules for admin.
	 *
	 * @return array
	 */
	public function get_price_rules() {
		global $wpdb;
		$price_rules_table = $wpdb->prefix . 'rnp_price_rules';
		$materials_table = $wpdb->prefix . 'rnp_materials';
		$finishes_table = $wpdb->prefix . 'rnp_finishes';

		return $wpdb->get_results(
			"SELECT pr.*, m.name AS material_name, f.name AS finish_name
			FROM {$price_rules_table} pr
			INNER JOIN {$materials_table} m ON m.id = pr.material_id
			LEFT JOIN {$finishes_table} f ON f.id = pr.finish_id
			ORDER BY pr.id DESC"
		);
	}

	/**
	 * Get price rule by ID.
	 *
	 * @param int $rule_id Rule ID.
	 * @return object|null
	 */
	public function get_price_rule( $rule_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_price_rules';
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $rule_id )
		);
	}

	/**
	 * Create price rule.
	 *
	 * @param array $data Rule data.
	 * @return int|false
	 */
	public function create_price_rule( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_price_rules';

		$finish_id = isset( $data['finish_id'] ) && '' !== $data['finish_id'] ? (int) $data['finish_id'] : null;
		$max_qty = isset( $data['max_qty'] ) && '' !== $data['max_qty'] ? (float) $data['max_qty'] : null;

		$insert_data = array(
			'material_id'    => (int) $data['material_id'],
			'min_qty'        => (float) $data['min_qty'],
			'price_per_unit' => (float) $data['price_per_unit'],
			'pricing_type'   => sanitize_text_field( $data['pricing_type'] ),
			'active'         => ! empty( $data['active'] ) ? 1 : 0,
		);
		$formats = array( '%d', '%f', '%f', '%s', '%d' );

		if ( null === $finish_id ) {
			$insert_data['finish_id'] = null;
		} else {
			$insert_data['finish_id'] = $finish_id;
			$formats[] = '%d';
		}

		if ( null === $max_qty ) {
			$insert_data['max_qty'] = null;
		} else {
			$insert_data['max_qty'] = $max_qty;
			$formats[] = '%f';
		}

		$result = $wpdb->insert( $table, $insert_data, $formats );

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update price rule.
	 *
	 * @param int   $rule_id Rule ID.
	 * @param array $data Rule data.
	 * @return int|false
	 */
	public function update_price_rule( $rule_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_price_rules';

		$finish_id = isset( $data['finish_id'] ) && '' !== $data['finish_id'] ? (int) $data['finish_id'] : null;
		$max_qty = isset( $data['max_qty'] ) && '' !== $data['max_qty'] ? (float) $data['max_qty'] : null;

		$update_data = array(
			'material_id'    => (int) $data['material_id'],
			'min_qty'        => (float) $data['min_qty'],
			'price_per_unit' => (float) $data['price_per_unit'],
			'pricing_type'   => sanitize_text_field( $data['pricing_type'] ),
			'active'         => ! empty( $data['active'] ) ? 1 : 0,
		);

		if ( null === $finish_id ) {
			$update_data['finish_id'] = null;
		} else {
			$update_data['finish_id'] = $finish_id;
		}

		if ( null === $max_qty ) {
			$update_data['max_qty'] = null;
		} else {
			$update_data['max_qty'] = $max_qty;
		}

		return $wpdb->update(
			$table,
			$update_data,
			array( 'id' => (int) $rule_id ),
			array( '%d', '%f', '%f', '%s', '%d', '%d', '%f' ),
			array( '%d' )
		);
	}

	/**
	 * Delete price rule.
	 *
	 * @param int $rule_id Rule ID.
	 * @return bool
	 */
	public function delete_price_rule( $rule_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_price_rules';
		return (bool) $wpdb->delete( $table, array( 'id' => (int) $rule_id ), array( '%d' ) );
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
	 * Get all quotes
	 *
	 * @return array
	 */
	public function get_all_quotes() {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_quotes';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
	}

	/**
	 * Get files for a quote
	 *
	 * @param int $quote_id
	 * @return array
	 */
	public function get_quote_files( $quote_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_quote_files';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE quote_id = %d ORDER BY created_at ASC", $quote_id ) );
	}

	/**
	 * Get single quote file by ID.
	 *
	 * @param int $file_id File ID.
	 * @return object|null
	 */
	public function get_quote_file( $file_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_quote_files';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $file_id ) );
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

	/**
	 * Get all material categories
	 */
	public function get_all_categories() {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_material_categories';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY sort_order ASC, name ASC" );
	}

	/**
	 * Get active material categories
	 */
	public function get_active_categories() {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_material_categories';
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE active = 1 ORDER BY sort_order ASC, name ASC" );
	}

	/**
	 * Get category by ID
	 */
	public function get_category( $category_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_material_categories';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $category_id ) );
	}

	/**
	 * Create category
	 */
	public function create_category( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_material_categories';

		return $wpdb->insert(
			$table,
			array(
				'name'       => sanitize_text_field( $data['name'] ?? '' ),
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'color'      => sanitize_text_field( $data['color'] ?? '#0073aa' ),
				'sort_order' => intval( $data['sort_order'] ?? 0 ),
				'active'     => 1,
			),
			array( '%s', '%s', '%s', '%d', '%d' )
		) ? $wpdb->insert_id : false;
	}

	/**
	 * Update category
	 */
	public function update_category( $category_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_material_categories';

		return $wpdb->update(
			$table,
			array(
				'name'        => sanitize_text_field( $data['name'] ?? '' ),
				'description' => sanitize_textarea_field( $data['description'] ?? '' ),
				'color'       => sanitize_text_field( $data['color'] ?? '#0073aa' ),
				'sort_order'  => intval( $data['sort_order'] ?? 0 ),
				'active'      => intval( $data['active'] ?? 1 ),
			),
			array( 'id' => intval( $category_id ) ),
			array( '%s', '%s', '%s', '%d', '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Delete category
	 */
	public function delete_category( $category_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_material_categories';
		return $wpdb->delete( $table, array( 'id' => intval( $category_id ) ) );
	}

	/**
	 * Update quote status
	 */
	public function update_quote_status( $quote_id, $status ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_quotes';

		return $wpdb->update(
			$table,
			array( 'status' => sanitize_text_field( $status ) ),
			array( 'id' => intval( $quote_id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get quotes with pagination
	 */
	public function get_quotes_paginated( $page = 1, $per_page = 50 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rnp_quotes';

		$page = max( 1, intval( $page ) );
		$per_page = intval( $per_page );
		$offset = ( $page - 1 ) * $per_page;

		$quotes = $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			) 
		);

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$total_pages = ceil( $total / $per_page );

		return array(
			'quotes'       => $quotes,
			'total'        => intval( $total ),
			'total_pages'  => intval( $total_pages ),
			'current_page' => $page,
			'per_page'     => $per_page,
		);
	}
}

