/**
 * Quote Status Handler - Blocks form when quote is in progress
 */

(function ($) {
	'use strict';

	class QuoteStatusHandler {
		constructor() {
			this.quoteId = this.getQuoteIdFromUrl();
			this.formWrapper = document.querySelector('.rnp-form-wrapper');
			this.form = document.getElementById('rnp-form');
			
			if (this.quoteId && this.formWrapper) {
				this.checkQuoteStatus();
			}
		}

		getQuoteIdFromUrl() {
			const params = new URLSearchParams(window.location.search);
			return params.get('quote_id');
		}

		async checkQuoteStatus() {
			try {
				const response = await fetch(rnpData.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'rnp_get_quote_status',
						nonce: rnpData.nonce,
						quote_id: this.quoteId,
					}),
				});

				const data = await response.json();

				if (data.success && data.data.status !== 'pending') {
					this.blockForm(data.data.status);
				}
			} catch (error) {
				console.error('Error checking quote status:', error);
			}
		}

		blockForm(status) {
			if (!this.formWrapper) return;

			// Add blocked class
			this.formWrapper.classList.add('blocked');

			// Show status message
			const statusMsg = document.querySelector('.rnp-status-message');
			if (statusMsg) {
				statusMsg.classList.add('show', 'blocked');
				const statusText = this.getStatusText(status);
				statusMsg.innerHTML = `<strong>⚠️ Ponuda je ${statusText}</strong><p>Ova ponuda je već u fazi obrade. Promene se ne mogu vršiti. Ako trebate izmene, kontaktirajte nas.</p>`;
			}

			// Disable all form inputs
			const inputs = this.form?.querySelectorAll('input, select, textarea, button');
			if (inputs) {
				inputs.forEach(input => {
					if (input.type !== 'submit') {
						input.disabled = true;
						input.style.cursor = 'not-allowed';
						input.style.opacity = '0.6';
					} else {
						input.style.display = 'none';
					}
				});
			}
		}

		getStatusText(status) {
			const statusMap = {
				'in_progress': 'u izradi',
				'completed': 'završena',
				'cancelled': 'otkazana',
			};
			return statusMap[status] || status;
		}
	}

	// Initialize when DOM is ready
	document.addEventListener('DOMContentLoaded', () => {
		new QuoteStatusHandler();
	});

})(jQuery);
