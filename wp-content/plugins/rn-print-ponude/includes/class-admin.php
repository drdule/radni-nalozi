<?php
/**
 * Admin class
 *
 * @package RN_Print_Ponude
 */

namespace RN_Print_Ponude;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 */
class Admin {

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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_post_rnp_print_quotes', array( $this, 'handle_print_request' ) );
		add_action( 'admin_post_rnp_view_file', array( $this, 'handle_view_file' ) );
	}

	/**
	 * Handle all admin CRUD actions.
	 *
	 * @return void
	 */
	public function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_REQUEST['rnp_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_REQUEST['rnp_action'] ) );

		switch ( $action ) {
			case 'save_material':
				$this->handle_save_material();
				break;
			case 'delete_material':
				$this->handle_delete_material();
				break;
			case 'save_finish':
				$this->handle_save_finish();
				break;
			case 'delete_finish':
				$this->handle_delete_finish();
				break;
			case 'save_unit_type':
				$this->handle_save_unit_type();
				break;
			case 'delete_unit_type':
				$this->handle_delete_unit_type();
				break;
			case 'save_price_rule':
				$this->handle_save_price_rule();
				break;
			case 'update_price_rule':
				$this->handle_update_price_rule();
				break;
			case 'delete_price_rule':
				$this->handle_delete_price_rule();
				break;
			case 'delete_quote':
				$this->handle_delete_quote();
				break;
			case 'print_quote':
				$this->handle_print_quote();
				break;
			case 'download_file':
				$this->handle_download_file();
				break;
			case 'view_file':
				$this->handle_view_file();
				break;
			case 'save_category':
				$this->handle_save_category();
				break;
			case 'delete_category':
				$this->handle_delete_category();
				break;
		}
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			'RN Print Ponude',
			'RN Ponude',
			'manage_options',
			'rn-print-ponude',
			array( $this, 'render_dashboard' ),
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSJub25lIiBzdHJva2U9ImN1cnJlbnRDb2xvciIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBzdHJva2Utd2lkdGg9IjIiIGQ9Ik0zIDkuMTFhOSA5IDAgMCAxIDE4IDB2Ljl2NS45YTkgOSAwIDAgMS0xOCAwdi02LjgiLz48cmVjdCB3aWR0aD0iMiIgaGVpZ2h0PSI0IiB4PSI3IiB5PSI4IiBmaWxsPSJjdXJyZW50Q29sb3IiIHJ4PSIuNSIvPjxyZWN0IHdpZHRoPSIyIiBoZWlnaHQ9IjciIHg9IjExIiB5PSI1IiBmaWxsPSJjdXJyZW50Q29sb3IiIHJ4PSIuNSIvPjxyZWN0IHdpZHRoPSIyIiBoZWlnaHQ9IjQiIHg9IjE1IiB5PSI4IiBmaWxsPSJjdXJyZW50Q29sb3IiIHJ4PSIuNSIvPjwvc3ZnPg=='
		);

		// Submenus
		add_submenu_page(
			'rn-print-ponude',
			'Ponude',
			'Ponude',
			'manage_options',
			'rn-ponude-list',
			array( $this, 'render_quotes' )
		);

		add_submenu_page(
			'rn-print-ponude',
			'Materijali',
			'Materijali',
			'manage_options',
			'rn-materials',
			array( $this, 'render_materials' )
		);

		add_submenu_page(
			'rn-print-ponude',
			'Kategorije materijala',
			'Kategorije',
			'manage_options',
			'rn-categories',
			array( $this, 'render_categories' )
		);

		add_submenu_page(
			'rn-print-ponude',
			'Dorade',
			'Dorade',
			'manage_options',
			'rn-finishes',
			array( $this, 'render_finishes' )
		);

		add_submenu_page(
			'rn-print-ponude',
			'Jedinice mere',
			'Jedinice mere',
			'manage_options',
			'rn-unit-types',
			array( $this, 'render_unit_types' )
		);

		add_submenu_page(
			'rn-print-ponude',
			'Cenovnik',
			'Cenovnik',
			'manage_options',
			'rn-pricing',
			array( $this, 'render_pricing' )
		);
	}

	/**
	 * Enqueue admin styles
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'rnp-admin-style', RNP_PLUGIN_URL . 'assets/css/admin-style.css', array(), RNP_VERSION );
	}

	/**
	 * Render dashboard
	 */
	public function render_dashboard() {
		$materials = $this->db->get_materials();
		$total_materials = count( $materials );

		?>
		<div class="wrap">
			<h1>RN Print Ponude - Dashboard</h1>
			<div class="dashboard-stats">
				<div class="stat-card">
					<h3>Materijali</h3>
					<p class="stat-number"><?php echo esc_html( $total_materials ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render quotes page
	 */
	public function render_quotes() {
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';
		$quote_id = isset( $_GET['quote_id'] ) ? absint( $_GET['quote_id'] ) : 0;

		$this->render_notice();

		if ( 'detail' === $view && $quote_id > 0 ) {
			$this->render_quote_detail( $quote_id );
		} else {
			$this->render_quotes_list();
		}
	}

	/**
	 * Render quotes list
	 */
	private function render_quotes_list() {
		$page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$data = $this->db->get_quotes_paginated( $page, 50 );
		$quotes = $data['quotes'];

		?>
		<div class="wrap">
			<h1>Ponude</h1>
			
			<?php if ( ! empty( $quotes ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom: 12px;">
					<?php wp_nonce_field( 'rnp_print_quotes_bulk' ); ?>
					<input type="hidden" name="action" value="rnp_print_quotes" />
					<button type="submit" class="button button-primary" onclick="return confirm('Štampati označene ponude?');">Štampaj označene</button>
					<span class="description" style="margin-left:8px;">Izaberite čekboksove pa kliknite štampu.</span>
				<table class="widefat striped rnp-quotes-table">
					<thead>
						<tr>
							<th><input type="checkbox" id="rnp-select-all" /></th>
							<th>ID</th>
							<th>Status</th>
							<th>Kupac</th>
							<th>Email</th>
							<th>Materijal</th>
							<th>Količina</th>
							<th>Cena (RSD)</th>
							<th>Fajlovi</th>
							<th>Datum</th>
							<th>Akcije</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $quotes as $quote ) : ?>
							<?php $material = $this->db->get_material( $quote->material_id ); ?>
							<tr>
								<td><input type="checkbox" name="quote_ids[]" value="<?php echo intval( $quote->id ); ?>" class="rnp-select-quote" /></td>
								<td>#<?php echo intval( $quote->id ); ?></td>
								<td>
									<span class="rnp-badge <?php echo esc_attr( $quote->status ); ?>">
										<?php echo esc_html( ucfirst( str_replace( '_', ' ', $quote->status ) ) ); ?>
									</span>
								</td>
								<td><strong><?php echo esc_html( $quote->customer_name ); ?></strong></td>
								<td><a href="mailto:<?php echo esc_attr( $quote->customer_email ); ?>"><?php echo esc_html( $quote->customer_email ); ?></a></td>
								<td><?php echo $material ? esc_html( $material->name ) : 'N/A'; ?></td>
								<td><?php echo floatval( $quote->quantity ); ?> <?php echo esc_html( $quote->unit_type ); ?></td>
								<td><?php echo number_format( floatval( $quote->calculated_price ), 2, ',', '.' ); ?></td>
								<td>
									<?php
									$files = $this->db->get_quote_files( $quote->id );
									if ( ! empty( $files ) ) {
										echo '<span class="rnp-badge">' . count( $files ) . ' fajl(a)</span>';
									} else {
										echo '—';
									}
									?>
								</td>
								<td><?php echo esc_html( substr( $quote->created_at, 0, 10 ) ); ?></td>
								<td>
									<a href="<?php echo add_query_arg( array( 'page' => 'rn-ponude-list', 'view' => 'detail', 'quote_id' => $quote->id ), admin_url( 'admin.php' ) ); ?>" class="button button-small">Detalji</a>
									<a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'rnp_print_quotes', 'quote_id' => $quote->id ), admin_url( 'admin-post.php' ) ), 'rnp_print_quote_' . $quote->id ); ?>" class="button button-small" target="_blank" title="Ispiši ponudu">🖨️ Štampa</a>
									<a href="<?php echo wp_nonce_url( add_query_arg( array( 'page' => 'rn-ponude-list', 'rnp_action' => 'delete_quote', 'quote_id' => $quote->id ), admin_url( 'admin.php' ) ), 'rnp_delete_quote_' . $quote->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('Sigurno obrisati ovu ponudu i sve fajlove?')">Obriši</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				</form>

				<script>
				document.addEventListener('DOMContentLoaded', function () {
					var all = document.getElementById('rnp-select-all');
					var form = all ? all.closest('form') : null;
					if (!all) return;
					all.addEventListener('change', function () {
						document.querySelectorAll('.rnp-select-quote').forEach(function (cb) {
							cb.checked = all.checked;
						});
					});

					if (form) {
						form.addEventListener('submit', function (e) {
							var anyChecked = false;
							document.querySelectorAll('.rnp-select-quote').forEach(function (cb) {
								if (cb.checked) {
									anyChecked = true;
								}
							});
							if (!anyChecked) {
								e.preventDefault();
								alert('Označite bar jednu ponudu za štampu.');
							}
						});
					}
				});
				</script>

				<!-- Pagination -->
				<?php if ( $data['total_pages'] > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php echo esc_html( $data['total'] ) . ' stavki'; ?></span>
							<span class="pagination-links">
								<?php
								// Prethodna stranica
								if ( $data['current_page'] > 1 ) {
									echo '<a class="prev-page button" href="' . add_query_arg( 'paged', $data['current_page'] - 1 ) . '">← Prethodna</a>';
								} else {
									echo '<span class="prev-page button disabled">← Prethodna</span>';
								}

								// Brojevi stranica
								for ( $i = 1; $i <= $data['total_pages']; $i++ ) {
									if ( $i === $data['current_page'] ) {
										echo '<span class="page-numbers current"><span class="screen-reader-text">Stranica </span>' . intval( $i ) . '</span>';
									} else {
										echo '<a class="page-numbers" href="' . add_query_arg( 'paged', $i ) . '">' . intval( $i ) . '</a>';
									}
								}

								// Sledeća stranica
								if ( $data['current_page'] < $data['total_pages'] ) {
									echo '<a class="next-page button" href="' . add_query_arg( 'paged', $data['current_page'] + 1 ) . '">Sledeća →</a>';
								} else {
									echo '<span class="next-page button disabled">Sledeća →</span>';
								}
								?>
							</span>
						</div>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<p>Nema dostupnih ponuda.</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render quote detail page
	 */
	private function render_quote_detail( $quote_id ) {
		$quote = $this->db->get_quote( $quote_id );

		if ( ! $quote ) {
			?>
			<div class="wrap">
				<h1>Ponuda #<?php echo intval( $quote_id ); ?></h1>
				<p style="color: #dc3545;">Ponuda nije pronađena.</p>
				<a href="<?php echo add_query_arg( array( 'page' => 'rn-ponude-list' ), admin_url( 'admin.php' ) ); ?>" class="button">Nazad na listu</a>
			</div>
			<?php
			return;
		}

		$material = $this->db->get_material( $quote->material_id );
		$files = $this->db->get_quote_files( $quote_id );
		$image_files = array_values( array_filter( $files, array( $this, 'is_image_mime_file' ) ) );
		$print_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'rnp_print_quotes',
					'quote_id' => $quote->id,
				),
				admin_url( 'admin-post.php' )
			),
			'rnp_print_quote_' . $quote->id
		);
		?>
		<div class="wrap">
			<div class="rnp-detail-shell">
				<div class="rnp-detail-header">
					<h1>Broj <?php echo intval( $quote->id ); ?> - <?php echo esc_html( $quote->customer_name ); ?></h1>
					<div class="rnp-detail-header-actions">
						<a href="<?php echo add_query_arg( array( 'page' => 'rn-ponude-list' ), admin_url( 'admin.php' ) ); ?>" class="button">← Nazad na listu</a>
						<a href="<?php echo esc_url( $print_url ); ?>" class="button button-primary" target="_blank">🖨️ Štampa</a>
					</div>
				</div>

				<div class="rnp-detail-grid">
					<div class="rnp-detail-card">
						<h2>Podaci poručioca</h2>
						<table class="rnp-detail-table">
							<tr><th>Ime i prezime</th><td><?php echo esc_html( $quote->customer_name ); ?></td></tr>
							<tr><th>Email</th><td><a href="mailto:<?php echo esc_attr( $quote->customer_email ); ?>"><?php echo esc_html( $quote->customer_email ); ?></a></td></tr>
							<tr><th>Telefon</th><td><?php echo esc_html( $quote->customer_phone ); ?></td></tr>
							<tr><th>Status</th><td><span class="rnp-badge <?php echo esc_attr( $quote->status ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $quote->status ) ) ); ?></span></td></tr>
						</table>
					</div>

					<div class="rnp-detail-card">
						<h2>Šta se radi</h2>
						<table class="rnp-detail-table">
							<tr><th>Materijal</th><td><?php echo $material ? esc_html( $material->name ) : 'N/A'; ?></td></tr>
							<tr><th>Količina</th><td><?php echo floatval( $quote->quantity ); ?> <?php echo esc_html( $quote->unit_type ); ?></td></tr>
							<tr><th>Datum</th><td><?php echo esc_html( $quote->created_at ); ?></td></tr>
						</table>
					</div>
				</div>

				<div class="rnp-detail-card">
					<h2>Napomena</h2>
					<div class="rnp-notes-box"><?php echo ! empty( $quote->notes ) ? wp_kses_post( nl2br( $quote->notes ) ) : 'Bez napomene.'; ?></div>
				</div>

				<div class="rnp-detail-card">
					<h2>Cena</h2>
					<div class="rnp-price-big"><?php echo number_format( floatval( $quote->calculated_price ), 2, ',', '.' ); ?> RSD</div>
				</div>

				<div class="rnp-detail-card">
					<h2>Priloženi fajlovi</h2>
					<?php if ( ! empty( $image_files ) ) : ?>
						<div class="rnp-image-gallery <?php echo count( $image_files ) > 1 ? 'multi' : 'single'; ?>">
							<?php foreach ( $image_files as $img_file ) : ?>
								<?php
								$view_url = wp_nonce_url(
									add_query_arg(
										array(
											'action' => 'rnp_view_file',
											'file_id' => $img_file->id,
										),
										admin_url( 'admin-post.php' )
									),
									'rnp_view_file_' . $img_file->id
								);
								?>
								<div class="rnp-image-item">
									<a href="<?php echo esc_url( $view_url ); ?>" target="_blank" title="Otvori sliku">
										<img src="<?php echo esc_url( $view_url ); ?>" alt="<?php echo esc_attr( $this->get_file_download_name( $img_file ) ); ?>" class="rnp-image-preview" />
									</a>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $files ) ) : ?>
						<table class="rnp-detail-table">
							<?php foreach ( $files as $file ) : ?>
								<?php
								$download_url = wp_nonce_url(
									add_query_arg(
										array(
											'page' => 'rn-ponude-list',
											'rnp_action' => 'download_file',
											'file_id' => $file->id,
										),
										admin_url( 'admin.php' )
									),
									'rnp_download_file_' . $file->id
								);
								?>
								<tr>
									<th><?php echo esc_html( $this->get_file_download_name( $file ) ); ?></th>
									<td>
										<small><?php echo esc_html( size_format( $file->file_size ) ); ?> | <?php echo esc_html( $file->mime_type ); ?></small>
										<br />
										<a href="<?php echo esc_url( $download_url ); ?>" class="button button-small">Preuzmi</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					<?php else : ?>
						<p>Nema priloženih fajlova.</p>
					<?php endif; ?>
				</div>

				<div style="margin-top: 16px;">
					<a href="<?php echo wp_nonce_url( add_query_arg( array( 'page' => 'rn-ponude-list', 'rnp_action' => 'delete_quote', 'quote_id' => $quote->id ), admin_url( 'admin.php' ) ), 'rnp_delete_quote_' . $quote->id ); ?>" class="button button-link-delete" onclick="return confirm('Sigurno obrisati ovu ponudu i sve fajlove?')">Obriši ponudu</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render printable quote view.
	 *
	 * @param array $quote_ids Quote IDs.
	 * @return void
	 */
	private function render_print_view( $quote_ids ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nemate dozvolu za ovu radnju.' );
		}

		echo '<!doctype html><html><head><meta charset="utf-8"><title>Štampa ponuda</title>';
		echo '<style>@page{margin:10mm}body{font-family:Arial,sans-serif;color:#111;margin:0} .page{break-after:page;page-break-after:always;padding:6mm 2mm} .page:last-child{break-after:auto;page-break-after:auto} h1{font-size:20px;margin:0 0 12px} h2{font-size:15px;margin:12px 0 8px;border-bottom:1px solid #ddd;padding-bottom:4px} table{width:100%;border-collapse:collapse} th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:12px} .price{font-size:24px;font-weight:700} .print-images{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px} .print-images.single{grid-template-columns:1fr} .print-images img{width:100%;height:auto;border:1px solid #ddd;display:block;max-height:130mm;object-fit:contain;background:#fff}</style>';
		echo '</head><body>';

		foreach ( $quote_ids as $qid ) {
			$this->db->update_quote_status( $qid, 'in_progress' );
			$quote = $this->db->get_quote( $qid );
			if ( ! $quote ) {
				continue;
			}

			$material = $this->db->get_material( $quote->material_id );
			$files = $this->db->get_quote_files( $qid );
			$image_files = array_values( array_filter( $files, array( $this, 'is_image_mime_file' ) ) );
			echo '<div class="page">';
			echo '<h1>Broj ' . intval( $quote->id ) . ' - ' . esc_html( $quote->customer_name ) . '</h1>';
			echo '<h2>Podaci poručioca</h2>';
			echo '<table><tr><th>Ime i prezime</th><td>' . esc_html( $quote->customer_name ) . '</td><th>Email</th><td>' . esc_html( $quote->customer_email ) . '</td></tr><tr><th>Telefon</th><td>' . esc_html( $quote->customer_phone ) . '</td><th>Status</th><td>' . esc_html( $quote->status ) . '</td></tr></table>';
			echo '<h2>Šta se radi</h2>';
			echo '<table><tr><th>Materijal</th><td>' . esc_html( $material ? $material->name : 'N/A' ) . '</td><th>Količina</th><td>' . esc_html( floatval( $quote->quantity ) . ' ' . $quote->unit_type ) . '</td></tr><tr><th>Datum</th><td colspan="3">' . esc_html( $quote->created_at ) . '</td></tr></table>';
			echo '<h2>Napomena</h2>';
			echo '<div style="border:1px solid #ddd;padding:8px;min-height:60px;">' . wp_kses_post( nl2br( $quote->notes ) ) . '</div>';
			if ( ! empty( $image_files ) ) {
				echo '<h2>Priložene slike</h2>';
				echo '<div class="print-images ' . ( count( $image_files ) > 1 ? 'multi' : 'single' ) . '">';
				foreach ( $image_files as $img_file ) {
					$view_url = wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'rnp_view_file',
								'file_id' => $img_file->id,
							),
							admin_url( 'admin-post.php' )
						),
						'rnp_view_file_' . $img_file->id
					);
					echo '<img src="' . esc_url( $view_url ) . '" alt="' . esc_attr( $this->get_file_download_name( $img_file ) ) . '">';
				}
				echo '</div>';
			}
			echo '<h2>Cena</h2>';
			echo '<div class="price">' . esc_html( number_format( floatval( $quote->calculated_price ), 2, ',', '.' ) ) . ' RSD</div>';
			echo '</div>';
		}

		echo '<script>window.addEventListener("load",function(){window.print();});</script>';
		echo '</body></html>';
		exit;
	}

	/**
	 * Handle clean print request through admin-post endpoint.
	 *
	 * @return void
	 */
	public function handle_print_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nemate dozvolu za ovu radnju.' );
		}

		$quote_id = isset( $_REQUEST['quote_id'] ) ? absint( $_REQUEST['quote_id'] ) : 0;
		$quote_ids = array();

		if ( $quote_id > 0 ) {
			check_admin_referer( 'rnp_print_quote_' . $quote_id );
			$quote_ids = array( $quote_id );
		} else {
			check_admin_referer( 'rnp_print_quotes_bulk' );
			$quote_ids = isset( $_REQUEST['quote_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['quote_ids'] ) ) : array();
			$quote_ids = array_filter( $quote_ids );
		}

		if ( empty( $quote_ids ) ) {
			$this->redirect_with_notice( 'Niste izabrali ponude za štampu.', 'error', 'rn-ponude-list' );
			return;
		}

		$this->render_print_view( $quote_ids );
	}

	/**
	 * Render materials page
	 */
	public function render_materials() {
		$materials = $this->db->get_all_materials();
		$finishes = $this->db->get_all_finishes();
		$categories = $this->db->get_all_categories();
		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$editing = null;

		if ( $edit_id > 0 ) {
			$editing = $this->db->get_material( $edit_id );
		}

		$this->render_notice();

		?>
		<div class="wrap">
			<h1>Materijali</h1>

			<h2><?php echo $editing ? 'Izmeni materijal' : 'Dodaj materijal'; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rn-materials' ) ); ?>">
				<?php wp_nonce_field( 'rnp_save_material' ); ?>
				<input type="hidden" name="rnp_action" value="save_material" />
				<input type="hidden" name="id" value="<?php echo $editing ? esc_attr( $editing->id ) : 0; ?>" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="rnp_material_name">Naziv</label></th>
						<td><input name="name" id="rnp_material_name" type="text" class="regular-text" required value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_material_desc">Opis</label></th>
						<td><textarea name="description" id="rnp_material_desc" class="large-text" rows="3"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_material_category">Kategorija</label></th>
						<td>
							<?php $category_id = $editing ? $editing->category_id : 0; ?>
							<select name="category_id" id="rnp_material_category">
								<option value="0">-- Bez kategorije --</option>
								<?php foreach ( $categories as $category ) : ?>
									<option value="<?php echo esc_attr( $category->id ); ?>" <?php selected( $category_id, $category->id ); ?>>
										<?php echo esc_html( $category->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_material_unit">Jedinica</label></th>
						<td>
							<?php $unit_type = $editing ? $editing->unit_type : 'm2'; ?>
							<select name="unit_type" id="rnp_material_unit">
								<option value="m2" <?php selected( $unit_type, 'm2' ); ?>>m2</option>
								<option value="piece" <?php selected( $unit_type, 'piece' ); ?>>komad</option>
								<option value="flyer" <?php selected( $unit_type, 'flyer' ); ?>>tabak</option>
								<option value="a_format" <?php selected( $unit_type, 'a_format' ); ?>>A format</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">Dorade za materijal</th>
						<td>
							<?php
							$selected_finishes = $editing ? $this->db->get_material_finish_ids( $editing->id ) : array();
							if ( empty( $finishes ) ) {
								echo '<p>Nema dorada. Prvo dodajte dorade.</p>';
							} else {
								foreach ( $finishes as $finish ) {
									$checked = in_array( (int) $finish->id, $selected_finishes, true ) ? 'checked' : '';
									echo '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="finish_ids[]" value="' . esc_attr( $finish->id ) . '" ' . esc_attr( $checked ) . ' /> ' . esc_html( $finish->name ) . '</label>';
								}
							}
							?>
						</td>
					</tr>
					<tr>
						<th scope="row">Aktivan</th>
						<td>
							<label><input type="checkbox" name="active" value="1" <?php checked( ! $editing || (int) $editing->active === 1 ); ?> /> Da</label>
						</td>
					</tr>
				</table>

				<?php submit_button( $editing ? 'Sačuvaj izmene' : 'Dodaj materijal' ); ?>
			</form>

			<hr />
			<h2>Lista materijala</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Naziv</th>
						<th>Kategorija</th>
						<th>Jedinica</th>
						<th>Aktivan</th>
						<th>Akcije</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $materials ) ) : ?>
						<tr><td colspan="6">Nema unetih materijala.</td></tr>
					<?php else : ?>
						<?php foreach ( $materials as $material ) : ?>
							<?php
							$category_name = 'Bez kategorije';
							if ( $material->category_id ) {
								foreach ( $categories as $cat ) {
									if ( $cat->id == $material->category_id ) {
										$category_name = $cat->name;
										break;
									}
								}
							}

							$edit_url = add_query_arg(
								array(
									'page' => 'rn-materials',
									'edit' => $material->id,
								),
								admin_url( 'admin.php' )
							);

							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'page' => 'rn-materials',
										'rnp_action' => 'delete_material',
										'id' => $material->id,
									),
									admin_url( 'admin.php' )
								),
								'rnp_delete_material_' . $material->id
							);
							?>
							<tr>
								<td><?php echo esc_html( $material->id ); ?></td>
								<td><?php echo esc_html( $material->name ); ?></td>
								<td><?php echo esc_html( $category_name ); ?></td>
								<td><?php echo esc_html( $material->unit_type ); ?></td>
								<td><?php echo (int) $material->active === 1 ? 'Da' : 'Ne'; ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>">Izmeni</a> |
									<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Obrisati materijal?');">Obriši</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render finishes page
	 */
	public function render_finishes() {
		$finishes = $this->db->get_all_finishes();
		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$editing = null;

		if ( $edit_id > 0 ) {
			foreach ( $finishes as $finish ) {
				if ( (int) $finish->id === $edit_id ) {
					$editing = $finish;
					break;
				}
			}
		}

		$this->render_notice();

		?>
		<div class="wrap">
			<h1>Dorade</h1>

			<h2><?php echo $editing ? 'Izmeni doradu' : 'Dodaj doradu'; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rn-finishes' ) ); ?>">
				<?php wp_nonce_field( 'rnp_save_finish' ); ?>
				<input type="hidden" name="rnp_action" value="save_finish" />
				<input type="hidden" name="id" value="<?php echo $editing ? esc_attr( $editing->id ) : 0; ?>" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="rnp_finish_name">Naziv</label></th>
						<td><input name="name" id="rnp_finish_name" type="text" class="regular-text" required value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_finish_desc">Opis</label></th>
						<td><textarea name="description" id="rnp_finish_desc" class="large-text" rows="3"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea></td>
					</tr>
					<tr>
						<th scope="row">Aktivna</th>
						<td><label><input type="checkbox" name="active" value="1" <?php checked( ! $editing || (int) $editing->active === 1 ); ?> /> Da</label></td>
					</tr>
				</table>

				<?php submit_button( $editing ? 'Sačuvaj izmene' : 'Dodaj doradu' ); ?>
			</form>

			<hr />
			<h2>Lista dorada</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Naziv</th>
						<th>Aktivna</th>
						<th>Akcije</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $finishes ) ) : ?>
						<tr><td colspan="4">Nema unetih dorada.</td></tr>
					<?php else : ?>
						<?php foreach ( $finishes as $finish ) : ?>
							<?php
							$edit_url = add_query_arg(
								array(
									'page' => 'rn-finishes',
									'edit' => $finish->id,
								),
								admin_url( 'admin.php' )
							);

							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'page' => 'rn-finishes',
										'rnp_action' => 'delete_finish',
										'id' => $finish->id,
									),
									admin_url( 'admin.php' )
								),
								'rnp_delete_finish_' . $finish->id
							);
							?>
							<tr>
								<td><?php echo esc_html( $finish->id ); ?></td>
								<td><?php echo esc_html( $finish->name ); ?></td>
								<td><?php echo (int) $finish->active === 1 ? 'Da' : 'Ne'; ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>">Izmeni</a> |
									<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Obrisati doradu?');">Obriši</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render unit types page
	 */
	public function render_unit_types() {
		$unit_types = $this->db->get_all_unit_types();
		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$editing = null;

		if ( $edit_id > 0 ) {
			foreach ( $unit_types as $unit ) {
				if ( (int) $unit->id === $edit_id ) {
					$editing = $unit;
					break;
				}
			}
		}

		$this->render_notice();

		?>
		<div class="wrap">
			<h1>Jedinice mere</h1>

			<h2><?php echo $editing ? 'Izmeni jedinicu' : 'Dodaj novu jedinicu'; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rn-unit-types' ) ); ?>">
				<?php wp_nonce_field( 'rnp_save_unit_type' ); ?>
				<input type="hidden" name="rnp_action" value="save_unit_type" />
				<input type="hidden" name="id" value="<?php echo $editing ? esc_attr( $editing->id ) : 0; ?>" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="rnp_unit_name">Naziv (npr. "Kvadratni metar")</label></th>
						<td><input name="name" id="rnp_unit_name" type="text" class="regular-text" required value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_unit_symbol">Simbol (npr. "m²")</label></th>
						<td><input name="symbol" id="rnp_unit_symbol" type="text" class="regular-text" required value="<?php echo $editing ? esc_attr( $editing->symbol ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th scope="row">Aktivna</th>
						<td><label><input type="checkbox" name="active" value="1" <?php checked( ! $editing || (int) $editing->active === 1 ); ?> /> Da</label></td>
					</tr>
				</table>

				<?php submit_button( $editing ? 'Sačuvaj izmene' : 'Dodaj jedinicu' ); ?>
			</form>

			<hr />
			<h2>Lista jedinica mere</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Naziv</th>
						<th>Simbol</th>
						<th>Aktivna</th>
						<th>Akcije</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $unit_types ) ) : ?>
						<tr><td colspan="5">Nema unetih jedinica mere.</td></tr>
					<?php else : ?>
						<?php foreach ( $unit_types as $unit ) : ?>
							<?php
							$edit_url = add_query_arg(
								array(
									'page' => 'rn-unit-types',
									'edit' => $unit->id,
								),
								admin_url( 'admin.php' )
							);

							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'page' => 'rn-unit-types',
										'rnp_action' => 'delete_unit_type',
										'id' => $unit->id,
									),
									admin_url( 'admin.php' )
								),
								'rnp_delete_unit_type_' . $unit->id
							);
							?>
							<tr>
								<td><?php echo esc_html( $unit->id ); ?></td>
								<td><?php echo esc_html( $unit->name ); ?></td>
								<td><?php echo esc_html( $unit->symbol ); ?></td>
								<td><?php echo (int) $unit->active === 1 ? 'Da' : 'Ne'; ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>">Izmeni</a> |
									<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Obrisati jedinicu?');">Obriši</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render pricing page
	 */
	public function render_pricing() {
		$materials = $this->db->get_all_materials();
		$finishes = $this->db->get_all_finishes();
		$rules = $this->db->get_price_rules();
		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$editing = null;

		if ( $edit_id > 0 ) {
			$editing = $this->db->get_price_rule( $edit_id );
		}

		$this->render_notice();

		?>
		<div class="wrap">
			<h1>Cenovnik</h1>

			<h2><?php echo $editing ? 'Izmeni cenovno pravilo' : 'Dodaj novo cenovno pravilo'; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=rn-pricing' ) ); ?>">
				<?php
				wp_nonce_field( $editing ? 'rnp_update_price_rule' : 'rnp_save_price_rule' );
				?>
				<input type="hidden" name="rnp_action" value="<?php echo $editing ? 'update_price_rule' : 'save_price_rule'; ?>" />
				<input type="hidden" name="id" value="<?php echo $editing ? esc_attr( $editing->id ) : 0; ?>" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="rnp_price_material">Materijal</label></th>
						<td>
							<select name="material_id" id="rnp_price_material" required>
								<option value="">-- Izaberi materijal --</option>
								<?php foreach ( $materials as $material ) : ?>
									<option value="<?php echo esc_attr( $material->id ); ?>" <?php selected( $editing && (int) $editing->material_id === (int) $material->id ); ?>>
										<?php echo esc_html( $material->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_price_finish">Dorada (opciono)</label></th>
						<td>
							<select name="finish_id" id="rnp_price_finish">
								<option value="">Bez dorade (osnovna cena)</option>
								<?php foreach ( $finishes as $finish ) : ?>
									<option value="<?php echo esc_attr( $finish->id ); ?>" <?php selected( $editing && null !== $editing->finish_id && (int) $editing->finish_id === (int) $finish->id ); ?>>
										<?php echo esc_html( $finish->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_price_min">Min količina</label></th>
						<td><input type="number" step="0.01" min="0" name="min_qty" id="rnp_price_min" value="<?php echo $editing ? esc_attr( $editing->min_qty ) : '0'; ?>" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_price_max">Max količina (opciono)</label></th>
						<td><input type="number" step="0.01" min="0" name="max_qty" id="rnp_price_max" value="<?php echo $editing && null !== $editing->max_qty ? esc_attr( $editing->max_qty ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_price_value">Cena po jedinici</label></th>
						<td><input type="number" step="0.01" min="0" name="price_per_unit" id="rnp_price_value" value="<?php echo $editing ? esc_attr( $editing->price_per_unit ) : ''; ?>" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="rnp_price_type">Tip cene</label></th>
						<td>
							<select name="pricing_type" id="rnp_price_type">
								<option value="fixed" <?php selected( ! $editing || 'fixed' === $editing->pricing_type ); ?>>fixed</option>
								<option value="per_m2" <?php selected( $editing && 'per_m2' === $editing->pricing_type ); ?>>per_m2</option>
								<option value="per_piece" <?php selected( $editing && 'per_piece' === $editing->pricing_type ); ?>>per_piece</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">Aktivno</th>
						<td><label><input type="checkbox" name="active" value="1" <?php checked( ! $editing || (int) $editing->active === 1 ); ?> /> Da</label></td>
					</tr>
				</table>

				<?php submit_button( $editing ? 'Sačuvaj izmene' : 'Dodaj pravilo' ); ?>
			</form>

			<hr />
			<h2>Lista cenovnika</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Materijal</th>
						<th>Dorada</th>
						<th>Opseg količine</th>
						<th>Cena</th>
						<th>Tip</th>
						<th>Aktivno</th>
						<th>Akcije</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rules ) ) : ?>
						<tr><td colspan="8">Nema unetih cenovnih pravila.</td></tr>
					<?php else : ?>
						<?php foreach ( $rules as $rule ) : ?>
							<?php
							$edit_url = add_query_arg(
								array(
									'page' => 'rn-pricing',
									'edit' => $rule->id,
								),
								admin_url( 'admin.php' )
							);

							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'page' => 'rn-pricing',
										'rnp_action' => 'delete_price_rule',
										'id' => $rule->id,
									),
									admin_url( 'admin.php' )
								),
								'rnp_delete_price_rule_' . $rule->id
							);
							?>
							<tr>
								<td><?php echo esc_html( $rule->id ); ?></td>
								<td><?php echo esc_html( $rule->material_name ); ?></td>
								<td><?php echo esc_html( $rule->finish_name ? $rule->finish_name : '-' ); ?></td>
								<td><?php echo esc_html( $rule->min_qty . ' - ' . ( null !== $rule->max_qty ? $rule->max_qty : 'bez limita' ) ); ?></td>
								<td><?php echo esc_html( number_format( (float) $rule->price_per_unit, 2, ',', '.' ) ); ?></td>
								<td><?php echo esc_html( $rule->pricing_type ); ?></td>
								<td><?php echo (int) $rule->active === 1 ? 'Da' : 'Ne'; ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>">Izmeni</a> |
									<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Obrisati pravilo?');">Obriši</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Save material from admin form.
	 *
	 * @return void
	 */
	private function handle_save_material() {
		check_admin_referer( 'rnp_save_material' );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$data = array(
			'name'        => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
			'description' => isset( $_POST['description'] ) ? wp_unslash( $_POST['description'] ) : '',
			'category_id' => isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0,
			'unit_type'   => isset( $_POST['unit_type'] ) ? wp_unslash( $_POST['unit_type'] ) : 'm2',
			'active'      => isset( $_POST['active'] ) ? 1 : 0,
		);

		if ( empty( $data['name'] ) ) {
			$this->redirect_with_notice( 'Naziv materijala je obavezan.', 'error', 'rn-materials' );
		}

		if ( $id > 0 ) {
			$this->db->update_material( $id, $data );
			$material_id = $id;
		} else {
			$material_id = $this->db->create_material( $data );
		}

		$finish_ids = isset( $_POST['finish_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['finish_ids'] ) ) : array();
		if ( $material_id ) {
			$this->db->set_material_finishes( $material_id, $finish_ids );
		}

		$this->redirect_with_notice( 'Materijal je sačuvan.', 'success', 'rn-materials' );
	}

	/**
	 * Delete material from admin.
	 *
	 * @return void
	 */
	private function handle_delete_material() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'rnp_delete_material_' . $id );

		if ( $id > 0 ) {
			$this->db->delete_material( $id );
		}

		$this->redirect_with_notice( 'Materijal je obrisan.', 'success', 'rn-materials' );
	}

	/**
	 * Save finish from admin form.
	 *
	 * @return void
	 */
	private function handle_save_finish() {
		check_admin_referer( 'rnp_save_finish' );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$data = array(
			'name'        => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
			'description' => isset( $_POST['description'] ) ? wp_unslash( $_POST['description'] ) : '',
			'active'      => isset( $_POST['active'] ) ? 1 : 0,
		);

		if ( empty( $data['name'] ) ) {
			$this->redirect_with_notice( 'Naziv dorade je obavezan.', 'error', 'rn-finishes' );
		}

		if ( $id > 0 ) {
			$this->db->update_finish( $id, $data );
		} else {
			$this->db->create_finish( $data );
		}

		$this->redirect_with_notice( 'Dorada je sačuvana.', 'success', 'rn-finishes' );
	}

	/**
	 * Delete finish from admin.
	 *
	 * @return void
	 */
	private function handle_delete_finish() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'rnp_delete_finish_' . $id );

		if ( $id > 0 ) {
			$this->db->delete_finish( $id );
		}

		$this->redirect_with_notice( 'Dorada je obrisana.', 'success', 'rn-finishes' );
	}

	/**
	 * Save unit type from admin form.
	 *
	 * @return void
	 */
	private function handle_save_unit_type() {
		check_admin_referer( 'rnp_save_unit_type' );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$data = array(
			'name'   => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'symbol' => isset( $_POST['symbol'] ) ? sanitize_text_field( wp_unslash( $_POST['symbol'] ) ) : '',
			'active' => isset( $_POST['active'] ) ? 1 : 0,
		);

		if ( empty( $data['name'] ) || empty( $data['symbol'] ) ) {
			$this->redirect_with_notice( 'Naziv i simbol su obavezni.', 'error', 'rn-unit-types' );
			return;
		}

		if ( $id > 0 ) {
			$result = $this->db->update_unit_type( $id, $data );
			if ( false === $result ) {
				$this->redirect_with_notice( 'Greška pri ažuriranju jedinice.', 'error', 'rn-unit-types' );
				return;
			}
		} else {
			$result = $this->db->create_unit_type( $data );
			if ( ! $result ) {
				$this->redirect_with_notice( 'Greška pri kreiranju jedinice.', 'error', 'rn-unit-types' );
				return;
			}
		}

		$this->redirect_with_notice( 'Jedinica je sačuvana.', 'success', 'rn-unit-types' );
	}

	/**
	 * Delete unit type from admin.
	 *
	 * @return void
	 */
	private function handle_delete_unit_type() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'rnp_delete_unit_type_' . $id );

		if ( $id > 0 ) {
			$this->db->delete_unit_type( $id );
		}

		$this->redirect_with_notice( 'Jedinica je obrisana.', 'success', 'rn-unit-types' );
	}

	/**
	 * Save price rule from admin form.
	 *
	 * @return void
	 */
	private function handle_save_price_rule() {
		check_admin_referer( 'rnp_save_price_rule' );

		$data = array(
			'material_id'    => isset( $_POST['material_id'] ) ? absint( $_POST['material_id'] ) : 0,
			'finish_id'      => isset( $_POST['finish_id'] ) ? wp_unslash( $_POST['finish_id'] ) : '',
			'min_qty'        => isset( $_POST['min_qty'] ) ? (float) wp_unslash( $_POST['min_qty'] ) : 0,
			'max_qty'        => isset( $_POST['max_qty'] ) ? wp_unslash( $_POST['max_qty'] ) : '',
			'price_per_unit' => isset( $_POST['price_per_unit'] ) ? (float) wp_unslash( $_POST['price_per_unit'] ) : 0,
			'pricing_type'   => isset( $_POST['pricing_type'] ) ? wp_unslash( $_POST['pricing_type'] ) : 'fixed',
			'active'         => isset( $_POST['active'] ) ? 1 : 0,
		);

		if ( $data['material_id'] <= 0 || $data['price_per_unit'] <= 0 ) {
			$this->redirect_with_notice( 'Materijal i cena su obavezni.', 'error', 'rn-pricing' );
		}

		$this->db->create_price_rule( $data );
		$this->redirect_with_notice( 'Cenovno pravilo je sačuvano.', 'success', 'rn-pricing' );
	}

	/**
	 * Update price rule from admin form.
	 *
	 * @return void
	 */
	private function handle_update_price_rule() {
		check_admin_referer( 'rnp_update_price_rule' );

		$rule_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $rule_id <= 0 ) {
			$this->redirect_with_notice( 'Nevažeći ID pravila.', 'error', 'rn-pricing' );
		}

		$data = array(
			'material_id'    => isset( $_POST['material_id'] ) ? absint( $_POST['material_id'] ) : 0,
			'finish_id'      => isset( $_POST['finish_id'] ) ? wp_unslash( $_POST['finish_id'] ) : '',
			'min_qty'        => isset( $_POST['min_qty'] ) ? (float) wp_unslash( $_POST['min_qty'] ) : 0,
			'max_qty'        => isset( $_POST['max_qty'] ) ? wp_unslash( $_POST['max_qty'] ) : '',
			'price_per_unit' => isset( $_POST['price_per_unit'] ) ? (float) wp_unslash( $_POST['price_per_unit'] ) : 0,
			'pricing_type'   => isset( $_POST['pricing_type'] ) ? wp_unslash( $_POST['pricing_type'] ) : 'fixed',
			'active'         => isset( $_POST['active'] ) ? 1 : 0,
		);

		if ( $data['material_id'] <= 0 || $data['price_per_unit'] <= 0 ) {
			$this->redirect_with_notice( 'Materijal i cena su obavezni.', 'error', 'rn-pricing' );
		}

		$this->db->update_price_rule( $rule_id, $data );
		$this->redirect_with_notice( 'Cenovno pravilo je ažurirano.', 'success', 'rn-pricing' );
	}

	/**
	 * Delete price rule from admin.
	 *
	 * @return void
	 */
	private function handle_delete_price_rule() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'rnp_delete_price_rule_' . $id );

		if ( $id > 0 ) {
			$this->db->delete_price_rule( $id );
		}

		$this->redirect_with_notice( 'Cenovno pravilo je obrisano.', 'success', 'rn-pricing' );
	}

	/**
	 * Redirect helper with admin notice.
	 *
	 * @param string $message Notice message.
	 * @param string $type success|error
	 * @param string $page Admin page slug.
	 * @return void
	 */
	private function redirect_with_notice( $message, $type, $page ) {
		$url = add_query_arg(
			array(
				'page' => $page,
				'rnp_notice' => rawurlencode( $message ),
				'rnp_notice_type' => $type,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render notice from URL args.
	 *
	 * @return void
	 */
	private function render_notice() {
		if ( empty( $_GET['rnp_notice'] ) ) {
			return;
		}

		$message = sanitize_text_field( wp_unslash( $_GET['rnp_notice'] ) );
		$type = isset( $_GET['rnp_notice_type'] ) ? sanitize_key( wp_unslash( $_GET['rnp_notice_type'] ) ) : 'success';
		$class = 'success' === $type ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';

		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Delete quote handler
	 */
	private function handle_delete_quote() {
		$quote_id = isset( $_GET['quote_id'] ) ? absint( $_GET['quote_id'] ) : 0;
		check_admin_referer( 'rnp_delete_quote_' . $quote_id );

		if ( $quote_id > 0 ) {
			// Delete quote files first
			global $wpdb;
			$files_table = $wpdb->prefix . 'rnp_quote_files';
			$quotes_table = $wpdb->prefix . 'rnp_quotes';

			// Get files to delete from filesystem
			$files = $this->db->get_quote_files( $quote_id );
			foreach ( $files as $file ) {
				if ( ! empty( $file->file_path ) && file_exists( $file->file_path ) ) {
					unlink( $file->file_path );
				}
			}

			// Delete from database
			$wpdb->delete( $files_table, array( 'quote_id' => $quote_id ) );
			$wpdb->delete( $quotes_table, array( 'id' => $quote_id ) );

			// Clean up empty directory
			$upload_dir = wp_upload_dir();
			$quote_dir = $upload_dir['basedir'] . '/rnp-quotes/' . $quote_id . '/';
			if ( is_dir( $quote_dir ) ) {
				rmdir( $quote_dir );
			}
		}

		$this->redirect_with_notice( 'Ponuda je obrisana.', 'success', 'rn-ponude-list' );
	}

	/**
	 * Handle print quote (change status to "in_progress")
	 */
	private function handle_print_quote() {
		$quote_id = isset( $_GET['quote_id'] ) ? absint( $_GET['quote_id'] ) : 0;
		check_admin_referer( 'rnp_print_quote_' . $quote_id );

		if ( $quote_id > 0 ) {
			$url = add_query_arg(
				array(
					'action' => 'rnp_print_quotes',
					'quote_id' => $quote_id,
				),
				admin_url( 'admin-post.php' )
			);

			wp_safe_redirect( $url );
			exit;
		}

		$this->redirect_with_notice( 'Ponuda je poslata na štampanje. Status: u izradi.', 'success', 'rn-ponude-list' );
	}

	/**
	 * Download quote file with proper original extension.
	 *
	 * @return void
	 */
	private function handle_download_file() {
		$file_id = isset( $_GET['file_id'] ) ? absint( $_GET['file_id'] ) : 0;
		check_admin_referer( 'rnp_download_file_' . $file_id );

		$file = $this->db->get_quote_file( $file_id );
		if ( ! $file || empty( $file->file_path ) || ! file_exists( $file->file_path ) ) {
			wp_die( 'Fajl nije pronađen.' );
		}

		$download_name = $this->get_file_download_name( $file );
		$mime_type = ! empty( $file->mime_type ) ? $file->mime_type : 'application/octet-stream';

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . str_replace( '"', '', $download_name ) . '"' );
		header( 'Content-Length: ' . filesize( $file->file_path ) );
		readfile( $file->file_path );
		exit;
	}

	/**
	 * Stream quote file inline for preview (images/PDF).
	 *
	 * @return void
	 */
	public function handle_view_file() {
		$file_id = isset( $_GET['file_id'] ) ? absint( $_GET['file_id'] ) : 0;
		check_admin_referer( 'rnp_view_file_' . $file_id );

		$file = $this->db->get_quote_file( $file_id );
		if ( ! $file || empty( $file->file_path ) || ! file_exists( $file->file_path ) ) {
			wp_die( 'Fajl nije pronađen.' );
		}

		$mime_type = ! empty( $file->mime_type ) ? $file->mime_type : 'application/octet-stream';
		nocache_headers();
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Length: ' . filesize( $file->file_path ) );
		header( 'Content-Disposition: inline; filename="' . str_replace( '"', '', $this->get_file_download_name( $file ) ) . '"' );
		readfile( $file->file_path );
		exit;
	}

	/**
	 * Check if file is an image by MIME type.
	 *
	 * @param object $file File row object.
	 * @return bool
	 */
	private function is_image_mime_file( $file ) {
		$mime = isset( $file->mime_type ) ? (string) $file->mime_type : '';
		return 0 === strpos( $mime, 'image/' );
	}

	/**
	 * Build filename with proper extension for download.
	 *
	 * @param object $file File row.
	 * @return string
	 */
	private function get_file_download_name( $file ) {
		$name = basename( (string) $file->file_path );
		if ( false !== strrpos( $name, '.' ) ) {
			return $name;
		}

		$mime_map = array(
			'application/pdf' => 'pdf',
			'application/postscript' => 'ai',
			'application/photoshop' => 'psd',
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/tiff' => 'tif',
		);

		$ext = isset( $mime_map[ $file->mime_type ] ) ? $mime_map[ $file->mime_type ] : '';
		return $ext ? ( $name . '.' . $ext ) : $name;
	}

	/**
	 * Save category handler
	 */
	private function handle_save_category() {
		check_admin_referer( 'rnp_save_category' );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$data = array(
			'name'        => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'color'       => isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : '#0073aa',
			'sort_order'  => isset( $_POST['sort_order'] ) ? intval( $_POST['sort_order'] ) : 0,
			'active'      => isset( $_POST['active'] ) ? 1 : 0,
		);

		if ( empty( $data['name'] ) ) {
			$this->redirect_with_notice( 'Naziv kategorije je obavezan.', 'error', 'rn-categories' );
			return;
		}

		if ( $id > 0 ) {
			$this->db->update_category( $id, $data );
		} else {
			$this->db->create_category( $data );
		}

		$this->redirect_with_notice( 'Kategorija je sačuvana.', 'success', 'rn-categories' );
	}

	/**
	 * Delete category handler
	 */
	private function handle_delete_category() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'rnp_delete_category_' . $id );

		if ( $id > 0 ) {
			$this->db->delete_category( $id );
		}

		$this->redirect_with_notice( 'Kategorija je obrisana.', 'success', 'rn-categories' );
	}

	/**
	 * Render categories page
	 */
	public function render_categories() {
		$categories = $this->db->get_all_categories();
		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$editing = null;

		if ( $edit_id > 0 ) {
			$editing = $this->db->get_category( $edit_id );
		}

		$this->render_notice();

		?>
		<div class="wrap">
			<h1>Kategorije materijala</h1>

			<!-- Add/Edit Form -->
			<div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
				<h2><?php echo $editing ? 'Uredi kategoriju' : 'Dodaj novu kategoriju'; ?></h2>
				<form method="post" action="<?php echo admin_url( 'admin.php' ); ?>">
					<?php wp_nonce_field( 'rnp_save_category' ); ?>
					<input type="hidden" name="rnp_action" value="save_category" />
					<?php if ( $editing ) : ?>
						<input type="hidden" name="id" value="<?php echo intval( $editing->id ); ?>" />
					<?php endif; ?>

					<table class="form-table">
						<tr>
							<th><label for="name">Naziv:</label></th>
							<td>
								<input type="text" id="name" name="name" class="regular-text" value="<?php echo $editing ? esc_attr( $editing->name ) : ''; ?>" required />
							</td>
						</tr>
						<tr>
							<th><label for="description">Opis:</label></th>
							<td>
								<textarea id="description" name="description" class="large-text" rows="3"><?php echo $editing ? esc_textarea( $editing->description ) : ''; ?></textarea>
							</td>
						</tr>
						<tr>
							<th><label for="color">Boja:</label></th>
							<td>
								<input type="color" id="color" name="color" value="<?php echo $editing ? esc_attr( $editing->color ) : '#0073aa'; ?>" />
							</td>
						</tr>
						<tr>
							<th><label for="sort_order">Redosled:</label></th>
							<td>
								<input type="number" id="sort_order" name="sort_order" value="<?php echo $editing ? intval( $editing->sort_order ) : 0; ?>" min="0" />
							</td>
						</tr>
						<tr>
							<th><label for="active"><input type="checkbox" id="active" name="active" <?php echo ( ! $editing || $editing->active ) ? 'checked' : ''; ?> /> Aktivna</label></th>
						</tr>
					</table>

					<p>
						<button type="submit" class="button button-primary"><?php echo $editing ? 'Ažuriraj' : 'Dodaj'; ?> kategoriju</button>
						<?php if ( $editing ) : ?>
							<a href="<?php echo admin_url( 'admin.php?page=rn-categories' ); ?>" class="button">Otkaži</a>
						<?php endif; ?>
					</p>
				</form>
			</div>

			<!-- Categories List -->
			<?php if ( ! empty( $categories ) ) : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Naziv</th>
							<th>Opis</th>
							<th>Boja</th>
							<th>Status</th>
							<th>Redosled</th>
							<th>Akcije</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $categories as $cat ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $cat->name ); ?></strong></td>
								<td><?php echo wp_kses_post( wp_trim_words( $cat->description, 10, '...' ) ); ?></td>
								<td><div style="width: 40px; height: 40px; background-color: <?php echo esc_attr( $cat->color ); ?>; border: 1px solid #ccc; border-radius: 3px;"></div></td>
								<td><?php echo $cat->active ? '<span class="rnp-badge success">Aktivna</span>' : '<span class="rnp-badge">Neaktivna</span>'; ?></td>
								<td><?php echo intval( $cat->sort_order ); ?></td>
								<td>
									<a href="<?php echo add_query_arg( array( 'page' => 'rn-categories', 'edit' => $cat->id ), admin_url( 'admin.php' ) ); ?>" class="button button-small">Uredi</a>
									<a href="<?php echo wp_nonce_url( add_query_arg( array( 'page' => 'rn-categories', 'rnp_action' => 'delete_category', 'id' => $cat->id ), admin_url( 'admin.php' ) ), 'rnp_delete_category_' . $cat->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('Sigurno obrisati ovu kategoriju?')">Obriši</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>Nema dostupnih kategorija.</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
