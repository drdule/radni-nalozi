<?php
/**
 * Frontend class
 *
 * @package RN_Print_Ponude
 */

namespace RN_Print_Ponude;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class
 */
class Frontend {

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
	}

	/**
	 * Render the form
	 */
	public function render_form() {
		$materials = $this->db->get_materials();
		?>
		<div class="rnp-container">
			<div class="rnp-form-wrapper">
				<h2>Kalkulator cena za štampu</h2>

				<!-- Status Message (shown when quote is in progress) -->
				<div class="rnp-status-message"></div>

				<form id="rnp-form" class="rnp-form" enctype="multipart/form-data">
					<?php wp_nonce_field( 'rnp_nonce', 'nonce' ); ?>

					<!-- Material Selection -->
					<div class="form-group">
						<label for="material">Materijal:</label>
						<select id="material" name="material" required>
							<option value="">-- Odaberite materijal --</option>
						<?php $this->render_materials_with_categories(); ?>
						<div id="dimensions-fields" style="display:none;">
							<label>Dimenzije:</label>
							<div class="dimensions-row">
								<div class="dimension-item">
									<label for="width">Širina (mm):</label>
									<input type="number" id="width" name="width" min="0" step="0.01" placeholder="0.00" />
								</div>
								<div class="dimension-item">
									<label for="height">Visina (mm):</label>
									<input type="number" id="height" name="height" min="0" step="0.01" placeholder="0.00" />
								</div>
							</div>
							<p id="calculated-area" style="font-size: 0.9em; color: #666; margin-top: 8px;">
								Površina: <strong id="area-value">-</strong>
							</p>
						</div>
						<div class="dimension-item">
							<label for="quantity">Broj komada:</label>
							<input type="number" id="quantity" name="quantity" min="1" step="1" value="1" required />
						</div>
					</div>

					<!-- Finishes -->
					<div id="finishes-group" class="form-group" style="display:none;">
						<label>Dorade:</label>
						<div id="finishes-list"></div>
					</div>

					<!-- Price Display -->
					<div class="form-group">
						<div class="rnp-price-display">
							<label>Izračunata cena:</label>
							<p class="rnp-price" id="calculated-price">-</p>
						</div>
					</div>

					<!-- Customer Info -->
					<div class="form-group">
						<label for="customer-name">Ime i prezime:</label>
						<input type="text" id="customer-name" name="customer_name" required placeholder="Vaše ime" />
					</div>

					<div class="form-group">
						<label for="customer-email">Email:</label>
						<input type="email" id="customer-email" name="customer_email" required placeholder="vasa@email.com" />
					</div>

					<div class="form-group">
						<label for="customer-phone">Telefon:</label>
						<input type="tel" id="customer-phone" name="customer_phone" required placeholder="+381..." />
					</div>

					<!-- File Upload -->
					<div class="form-group">
						<label for="file-upload">Priložite fajl (PDF, AI, PSD, JPG, PNG) ili link:</label>
						<input type="file" id="file-upload" name="file_upload[]" multiple accept=".pdf,.ai,.psd,.jpg,.jpeg,.png,.tiff" />
						<small>Možete izaberati više fajlova odjednom. Max 150MB po fajlu.</small>
					</div>

					<!-- Notes -->
					<div class="form-group">
						<label for="notes">Napomene:</label>
						<textarea id="notes" name="notes" rows="4" placeholder="dodatne informacije..."></textarea>
					</div>

					<!-- Submit -->
					<button type="submit" class="rnp-btn-submit">Pošalji upit</button>
				</form>

				<!-- Success Message -->
				<div id="rnp-success-message" style="display:none;" class="rnp-message rnp-success">
					<p>Vaša ponuda je uspešno poslana! Uskoro ćemo vas kontaktirati.</p>
				</div>

				<!-- Error Message -->
				<div id="rnp-error-message" style="display:none;" class="rnp-message rnp-error">
					<p id="error-text"></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render materials grouped by categories
	 */
	private function render_materials_with_categories() {
		$categories = $this->db->get_active_categories();
		$materials = $this->db->get_materials();

		// Group materials by category
		$grouped = array();
		foreach ( $materials as $material ) {
			$cat_id = $material->category_id ? $material->category_id : 0;
			if ( ! isset( $grouped[ $cat_id ] ) ) {
				$grouped[ $cat_id ] = array();
			}
			$grouped[ $cat_id ][] = $material;
		}

		// Render optgroups
		foreach ( $categories as $category ) {
			if ( isset( $grouped[ $category->id ] ) ) {
				?>
				<optgroup label="<?php echo esc_attr( $category->name ); ?>">
					<?php foreach ( $grouped[ $category->id ] as $material ) : ?>
						<option value="<?php echo esc_attr( $material->id ); ?>">
							<?php echo esc_html( $material->name ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
				<?php
			}
		}

		// Render materials without category
		if ( isset( $grouped[0] ) ) {
			?>
			<optgroup label="Ostalo">
				<?php foreach ( $grouped[0] as $material ) : ?>
					<option value="<?php echo esc_attr( $material->id ); ?>">
						<?php echo esc_html( $material->name ); ?>
					</option>
				<?php endforeach; ?>
			</optgroup>
			<?php
		}
	}
}
