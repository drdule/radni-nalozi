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

				<form id="rnp-form" class="rnp-form" enctype="multipart/form-data">
					<?php wp_nonce_field( 'rnp_submit_quote', 'rnp_nonce' ); ?>

					<!-- Material Selection -->
					<div class="form-group">
						<label for="material">Materijal:</label>
						<select id="material" name="material" required>
							<option value="">-- Odaberite materijal --</option>
							<?php foreach ( $materials as $material ) : ?>
								<option value="<?php echo esc_attr( $material->id ); ?>">
									<?php echo esc_html( $material->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Unit Type Display -->
					<div class="form-group">
						<label>Jedinica:</label>
						<p id="unit-display">Odaberite materijal</p>
					</div>

					<!-- Dimensions / Quantity -->
					<div id="dimensions-group" class="form-group" style="display:none;">
						<label for="quantity">Količina:</label>
						<input type="number" id="quantity" name="quantity" step="0.01" placeholder="0.00" />
						<small id="unit-label">m²</small>
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
						<label for="customer-phone">Telefon (opciono):</label>
						<input type="tel" id="customer-phone" name="customer_phone" placeholder="+381..." />
					</div>

					<!-- File Upload / Link -->
					<div class="form-group">
						<label for="file-upload">Fajl (PDF, AI, PSD, JPG, PNG) ili link:</label>
						<input type="file" id="file-upload" name="file_upload" accept=".pdf,.ai,.psd,.jpg,.jpeg,.png,.tiff" />
						<small>Max 150MB</small>
					</div>

					<div class="form-group">
						<label for="file-link">Ili polje za link (WeTransfer, Google Drive):</label>
						<input type="url" id="file-link" name="file_link" placeholder="https://..." />
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
}
