/**
 * RN Print Ponude Frontend Script
 */

(function ($) {
	'use strict';

	class RNPFormHandler {
		constructor() {
			this.form = document.getElementById('rnp-form');
			this.materialSelect = document.getElementById('material');
			this.widthInput = document.getElementById('width');
			this.heightInput = document.getElementById('height');
			this.quantityInput = document.getElementById('quantity');
			this.areaDisplay = document.getElementById('area-value');
			this.calculatedPriceEl = document.getElementById('calculated-price');
			this.successMsg = document.getElementById('rnp-success-message');
			this.errorMsg = document.getElementById('rnp-error-message');
			this.errorText = document.getElementById('error-text');
			this.finishesGroup = document.getElementById('finishes-group');
			this.finishesList = document.getElementById('finishes-list');
			this.dimensionsGroup = document.getElementById('dimensions-group');
			this.dimensionsFields = document.getElementById('dimensions-fields');
			this.currentUnitType = null;

			this.init();
		}

		init() {
			if (!this.form) return;

			// Material selection change
			this.materialSelect?.addEventListener('change', (e) => this.onMaterialChange(e));

			// Dimension and quantity input changes
			this.widthInput?.addEventListener('input', () => this.onDimensionsChange());
			this.heightInput?.addEventListener('input', () => this.onDimensionsChange());
			this.quantityInput?.addEventListener('input', () => this.calculatePrice());

			// Form submission
			this.form.addEventListener('submit', (e) => this.onFormSubmit(e));

			// Finish checkboxes change event (delegated)
			this.finishesList?.addEventListener('change', (e) => {
				if (e.target.matches('input[type="checkbox"]')) {
					this.calculatePrice();
				}
			});
		}

		onDimensionsChange() {
			const width = parseFloat(this.widthInput.value);
			const height = parseFloat(this.heightInput.value);

			if (width > 0 && height > 0) {
				// Convert mm to m² (mm * mm / 1,000,000 = m²)
				const area = (width * height) / 1000000;
				this.areaDisplay.textContent = area.toFixed(6) + ' m²';
				this.calculatePrice();
			} else {
				this.areaDisplay.textContent = '-';
				this.calculatedPriceEl.textContent = '-';
			}
		}

		async onMaterialChange(e) {
			const materialId = e.target.value;

			if (!materialId) {
				this.dimensionsGroup.style.display = 'none';
				this.finishesGroup.style.display = 'none';
				this.calculatedPriceEl.textContent = '-';
				return;
			}

			// Get material info
			try {
				const response = await this.sendAjax('rnp_get_material_info', {
					material_id: materialId,
				});

				if (response.success) {
					const unitType = response.data.unit_type;
					this.currentUnitType = unitType;
					
					this.dimensionsGroup.style.display = 'block';

					// Show dimensions only for m2
					if (unitType === 'm2') {
						this.dimensionsFields.style.display = 'block';
						this.widthInput.required = true;
						this.heightInput.required = true;
					} else {
						this.dimensionsFields.style.display = 'none';
						this.widthInput.required = false;
						this.heightInput.required = false;
						this.widthInput.value = '';
						this.heightInput.value = '';
					}

					// Get finishes
					await this.loadFinishes(materialId);

					// Reset price
					this.calculatedPriceEl.textContent = '-';
					this.areaDisplay.textContent = '-';
				}
			} catch (error) {
				console.error('Error getting material info:', error);
				this.showError('Greška pri učitavanju materijala');
			}
		}

		async loadFinishes(materialId) {
			try {
				const response = await this.sendAjax('rnp_get_finishes', {
					material_id: materialId,
				});

				if (response.success) {
					const finishes = response.data.finishes;

					if (finishes.length > 0) {
						this.finishesList.innerHTML = '';
						finishes.forEach((finish) => {
							const div = document.createElement('div');
							div.className = 'finish-option';
							div.innerHTML = `
								<input type="checkbox" id="finish_${finish.id}" name="finishes" value="${finish.id}" />
								<label for="finish_${finish.id}">${this.escapeHtml(finish.name)}</label>
							`;
							this.finishesList.appendChild(div);
						});
						this.finishesGroup.style.display = 'block';
					} else {
						this.finishesGroup.style.display = 'none';
					}
				}
			} catch (error) {
				console.error('Error loading finishes:', error);
				this.finishesGroup.style.display = 'none';
			}
		}

		async calculatePrice() {
			const materialId = this.materialSelect.value;
			const quantity = parseFloat(this.quantityInput.value);

			if (!materialId || quantity <= 0) {
				this.calculatedPriceEl.textContent = '-';
				return;
			}

			let totalQuantity;
			
			// For m2, calculate from dimensions
			if (this.currentUnitType === 'm2') {
				const width = parseFloat(this.widthInput.value);
				const height = parseFloat(this.heightInput.value);
				
				if (!width || !height || width <= 0 || height <= 0) {
					this.calculatedPriceEl.textContent = '-';
					return;
				}
				
				// Calculate area in m² from mm: (width * height) / 1,000,000
				const areaSqm = (width * height) / 1000000;
				totalQuantity = areaSqm * quantity;
			} else {
				// For other units (kom, etc), just use quantity
				totalQuantity = quantity;
			}

			// Get selected finishes
			const selectedFinishes = Array.from(
				this.finishesList.querySelectorAll('input[type="checkbox"]:checked')
			).map((el) => parseInt(el.value));

			try {
				const response = await this.sendAjax('rnp_calculate_price', {
					material_id: materialId,
					quantity: totalQuantity,
					finish_ids: selectedFinishes,
				});

				if (response.success) {
					this.calculatedPriceEl.textContent = response.data.price + ' ' + response.data.currency;
				} else {
					this.calculatedPriceEl.textContent = '-';
				}
			} catch (error) {
				console.error('Error calculating price:', error);
				this.calculatedPriceEl.textContent = '-';
			}
		}

		async onFormSubmit(e) {
			e.preventDefault();

			// Validate
			const materialId = this.materialSelect.value;
			const quantity = this.quantityInput.value;
			const customerName = document.getElementById('customer-name').value.trim();
			const customerEmail = document.getElementById('customer-email').value.trim();
			const customerPhone = document.getElementById('customer-phone').value.trim();
			const fileUpload = document.getElementById('file-upload').files;

			// Validate each field individually for better error messages
			if (!materialId) {
				this.showError('Izaberite materijal.');
				return;
			}
			
			if (!quantity || parseFloat(quantity) <= 0) {
				this.showError('Unesite broj komada.');
				return;
			}
			
			if (!customerName) {
				this.showError('Unesite ime i prezime.');
				return;
			}
			
			if (!customerEmail) {
				this.showError('Unesite email adresu.');
				return;
			}
			
			if (!customerPhone) {
				this.showError('Unesite broj telefona.');
				return;
			}

			// For m2, validate dimensions
			if (this.currentUnitType === 'm2') {
				const width = this.widthInput.value;
				const height = this.heightInput.value;
				if (!width || !height) {
					this.showError('Unesite širinu i visinu.');
					return;
				}
			}

			if (fileUpload.length === 0) {
				this.showError('Morate upload-ovati bar jedan fajl.');
				return;
			}
			
			// Check if price has been calculated
			const priceText = this.calculatedPriceEl.textContent.trim();
			if (!priceText || priceText === '-') {
				this.showError('Sačekajte da se cena izračuna.');
				return;
			}

			// Disable submit button
			const submitBtn = this.form.querySelector('button[type="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Slanje...';

			try {
				let finalQuantity;
				
				if (this.currentUnitType === 'm2') {
					const width = parseFloat(this.widthInput.value);
					const height = parseFloat(this.heightInput.value);
					const areaSqm = (width * height) / 1000000;
					finalQuantity = areaSqm * parseFloat(quantity);
				} else {
					finalQuantity = parseFloat(quantity);
				}
				
				// Build FormData with all fields
				const formData = new FormData();
				formData.append('action', 'rnp_submit_quote');
				formData.append('nonce', rnpData.nonce);
				formData.append('material_id', materialId);
				formData.append('quantity', finalQuantity);
				formData.append('customer_name', customerName);
				formData.append('customer_email', customerEmail);
				formData.append('customer_phone', customerPhone);
				formData.append('notes', document.getElementById('notes').value);
				
				// Extract numeric value from price text (e.g., "1.785,00 RSD" -> 1785.00)
				const priceMatch = priceText.match(/[\d.,]+/);
				if (priceMatch) {
					// Convert from Serbian format (1.785,00) to standard (1785.00)
					const priceValue = priceMatch[0].replace(/\./g, '').replace(',', '.');
					formData.append('calculated_price', parseFloat(priceValue));
				} else {
					formData.append('calculated_price', 0);
				}
				
				// Add all files
				for (let i = 0; i < fileUpload.length; i++) {
					formData.append('file_upload[]', fileUpload[i]);
				}

				const response = await fetch(rnpData.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				const result = await response.json();

				if (result.success) {
					this.form.style.display = 'none';
					this.showSuccess(result.data.message);
				} else {
					this.showError(result.data.message || 'Greška pri slanju ponude');
					submitBtn.disabled = false;
					submitBtn.textContent = 'Pošalji upit';
				}
			} catch (error) {
				console.error('Form submission error:', error);
				this.showError('Greška pri slanju ponude. Pokušajte ponovo.');
				submitBtn.disabled = false;
				submitBtn.textContent = 'Pošalji upit';
			}
		}

		async sendAjax(action, data) {
			const formData = new FormData();
			formData.append('action', action);
			formData.append('nonce', rnpData.nonce);

			Object.keys(data).forEach((key) => {
				if (Array.isArray(data[key])) {
					data[key].forEach((val) => {
						formData.append(key + '[]', val);
					});
				} else {
					formData.append(key, data[key]);
				}
			});

			const response = await fetch(rnpData.ajaxUrl, {
				method: 'POST',
				body: formData,
			});

			return response.json();
		}

		showError(message) {
			this.errorText.textContent = message;
			this.errorMsg.style.display = 'block';
			this.successMsg.style.display = 'none';
		}

		showSuccess(message) {
			this.successMsg.style.display = 'block';
			this.errorMsg.style.display = 'none';
		}

		escapeHtml(text) {
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;',
			};
			return text.replace(/[&<>"']/g, (m) => map[m]);
		}
	}

	// Initialize on DOM ready
	document.addEventListener('DOMContentLoaded', () => {
		new RNPFormHandler();
	});
})(jQuery);
