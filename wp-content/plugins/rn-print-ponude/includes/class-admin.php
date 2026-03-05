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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
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
			'Dorade',
			'Dorade',
			'manage_options',
			'rn-finishes',
			array( $this, 'render_finishes' )
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
		?>
		<div class="wrap">
			<h1>Ponude</h1>
			<p>Lista ponuda (u razvoju)</p>
		</div>
		<?php
	}

	/**
	 * Render materials page
	 */
	public function render_materials() {
		?>
		<div class="wrap">
			<h1>Materijali</h1>
			<p>CRUD za materijale (u razvoju)</p>
		</div>
		<?php
	}

	/**
	 * Render finishes page
	 */
	public function render_finishes() {
		?>
		<div class="wrap">
			<h1>Dorade</h1>
			<p>CRUD za dorade (u razvoju)</p>
		</div>
		<?php
	}

	/**
	 * Render pricing page
	 */
	public function render_pricing() {
		?>
		<div class="wrap">
			<h1>Cenovnik</h1>
			<p>Upravljanje cenama (u razvoju)</p>
		</div>
		<?php
	}
}
