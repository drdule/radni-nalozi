/**
 * RN Print Ponude Frontend Script
 */

(function ($) {
	'use strict';

	class RNPFormHandler {
		constructor() {
			this.form = document.getElementById('rnp-form');
			this.materialSelect = document.getElementById('material');
			this.quantityInput = document.getElementById('quantity');
			this.calculatedPriceEl = document.getElementById('calculated-price');
			this.successMsg = document.getElementById('rnp-success-message');
			this.errorMsg = document.getElementById('rnp-error-message');
			this.errorText = document.getElementById('error-text');
			this.finishesGroup = document.getElementById('finishes-group');
			this.finishesList = document.getElementById('finishes-list');
			this.dimensionsGroup = document.getElementById('dimensions-group');
			this.unitDisplay = document.getElementById('unit-display');
			this.unitLabel = document.getElementById('unit-label');

			this.init();
		}

		init() {
			if (!this.form) return;

			// Material selection change
			this.materialSelect?.addEventListener('change', (e) => this.onMaterialChange(e));

			// Quantity change
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

		async onMaterialChange(e) {
			const materialId = e.target.value;

			if (!materialId) {
				this.dimensionsGroup.style.display = 'none';
				this.finishesGroup.style.display = 'none';
				this.unitDisplay.textContent = 'Odaberite materijal';
				this.calculatedPriceEl.textContent = '-';
				return;
			}

			// Get material info
			try {
				const response = await this.sendAjax('rnp_get_material_info', {
					material_id: materialId,
				});

				if (response.success) {
					const data = response.data;
					this.unitDisplay.textContent = data.unit_label;
					this.unitLabel.textContent = data.unit_label;
					this.dimensionsGroup.style.display = 'block';

					// Get finishes
					await this.loadFinishes(materialId);

					// Reset price
					this.calculatedPriceEl.textContent = '-';
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

			if (!materialId || !quantity || quantity <= 0) {
				this.calculatedPriceEl.textContent = '-';
				return;
			}

			// Get selected finishes
			const selectedFinishes = Array.from(
				this.finishesList.querySelectorAll('input[type="checkbox"]:checked')
			).map((el) => parseInt(el.value));

			try {
				const response = await this.sendAjax('rnp_calculate_price', {
					material_id: materialId,
					quantity: quantity,
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
			const customerName = document.getElementById('customer-name').value;
			const customerEmail = document.getElementById('customer-email').value;
			const fileUpload = document.getElementById('file-upload').files[0];
			const fileLink = document.getElementById('file-link').value;

			if (!materialId || !quantity || !customerName || !customerEmail) {
				this.showError('Popunite sve obavezne podatke.');
				return;
			}

			if (!fileUpload && !fileLink) {
				this.showError('Morate upload-ovati fajl ili polje link.');
				return;
			}

			// Disable submit button
			const submitBtn = this.form.querySelector('button[type="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Slanje...';

			try {
				const formData = new FormData(this.form);
				formData.append('action', 'rnp_submit_quote');
				formData.append('nonce', rnpData.nonce);
				formData.append('calculated_price', parseFloat(this.calculatedPriceEl.textContent));

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
