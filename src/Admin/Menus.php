<?php

namespace WooCommerceSerialNumbers\Admin;

use WooCommerceSerialNumbers\Models\Key;
use WooCommerceSerialNumbers\Lib\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Menus.
 *
 * @since   1.0.0
 * @package WooCommerceSerialNumbers\Admin
 */
class Menus extends Singleton {
	/**
	 * Menus constructor.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		//add_action( 'current_screen', array( $this, 'setup_screen' ) );
		//add_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );
		//add_filter( 'set-screen-option', array( __CLASS__, 'save_screen_options' ), 10, 3 );

		// Register the menus.
		add_action( 'admin_menu', array( $this, 'main_menu' ) );
		add_action( 'admin_menu', array( $this, 'activations_menu' ), 40 );
		add_action( 'admin_menu', array( $this, 'tools_menu' ), 50 );
		add_action( 'admin_menu', array( $this, 'reports_menu' ), 60 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 100 );
		add_action( 'admin_menu', array( $this, 'promo_menu' ), PHP_INT_MAX );

		// Add tabs content.
		add_filter( 'wc_serial_numbers_tools_tabs', array( __CLASS__, 'add_tools_status_tab' ), PHP_INT_MAX );
		add_action( 'wc_serial_numbers_tools_tab_import', array( __CLASS__, 'import_tab' ) );
		add_action( 'wc_serial_numbers_tools_tab_export', array( __CLASS__, 'export_tab' ) );
		add_action( 'wc_serial_numbers_tools_tab_status', array( __CLASS__, 'status_tab' ) );
		add_action( 'wc_serial_numbers_tools_tab_api', array( __CLASS__, 'api_validation_section' ) );
		add_action( 'wc_serial_numbers_tools_tab_api', array( __CLASS__, 'api_activation_deactivation_section' ) );
		add_action( 'wc_serial_numbers_reports_tab_stock', array( __CLASS__, 'reports_stock_tab' ) );
	}

	/**
	 * Looks at the current screen and loads the correct list table handler.
	 *
	 * @since 1.4.6
	 */
	public function setup_screen() {
		if ( isset( $_GET['edit'] ) || isset( $_GET['delete'] ) || isset( $_GET['add'] ) || isset( $_GET['generate'] ) ) {
			return;
		}

		$screen_id        = false;
		$plugin_screen_id = sanitize_title( __( 'Serial Numbers', 'wc-serial-numbers' ) );
		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		// switch ( $screen_id ) {
		// case $plugin_screen_id . '-page-wc-serial-numbers':
		// $this->list_table = new ListTables\KeysTable();
		// break;
		// }

		// Ensure the table handler is only loaded once. Prevents multiple loads if a plugin calls check_ajax_referer many times.
		remove_action( 'current_screen', array( $this, 'setup_screen' ) );
		remove_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );
	}

	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string $option The option name.
	 * @param int $value The number of rows to use.
	 */
	public function save_screen_options( $status, $option, $value ) {
		if ( in_array( $option, array( 'wsn_keys_per_page', 'wsn_generators_per_page', 'wsn_activations_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Add menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function main_menu() {
		$role = wcsn_get_manager_role();
		add_menu_page(
			__( 'Serial Numbers', 'wc-serial-numbers' ),
			__( 'Serial Numbers', 'wc-serial-numbers' ),
			$role,
			'wc-serial-numbers',
			null,
			'dashicons-lock',
			'55.9'
		);

		add_submenu_page(
			'wc-serial-numbers',
			__( 'Serial Keys', 'wc-serial-numbers' ),
			__( 'Serial Keys', 'wc-serial-numbers' ),
			$role,
			'wc-serial-numbers',
			array( $this, 'output_main_page' )
		);
	}

	/**
	 * Add activations menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activations_menu() {
		if( ! wcsn_is_software_support_enabled() ) {
			return;
		}
		add_submenu_page(
			'wc-serial-numbers',
			__( 'Activations', 'wc-serial-numbers' ),
			__( 'Activations', 'wc-serial-numbers' ),
			wcsn_get_manager_role(),
			'wc-serial-numbers-activations',
			array( $this, 'output_activations_page' )
		);
	}

	/**
	 * Add tools menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function tools_menu() {
		add_submenu_page(
			'wc-serial-numbers',
			__( 'Tools', 'wc-serial-numbers' ),
			__( 'Tools', 'wc-serial-numbers' ),
			wcsn_get_manager_role(),
			'wc-serial-numbers-tools',
			array( $this, 'output_tools_page' )
		);
	}

	/**
	 * Add reports menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function reports_menu() {
		add_submenu_page(
			'wc-serial-numbers',
			__( 'Reports', 'wc-serial-numbers' ),
			__( 'Reports', 'wc-serial-numbers' ),
			wcsn_get_manager_role(),
			'wc-serial-numbers-reports',
			array( $this, 'output_reports_page' )
		);
	}

	/**
	 * Settings menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function settings_menu() {
		add_submenu_page(
			'wc-serial-numbers',
			__( 'Settings', 'wc-serial-numbers' ),
			__( 'Settings', 'wc-serial-numbers' ),
			wcsn_get_manager_role(),
			'wc-serial-numbers-settings',
			array( Settings::class, 'output' )
		);
	}

	/**
	 * Add promo Menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function promo_menu() {
		$role = wcsn_get_manager_role();
		if ( ! wc_serial_numbers()->is_premium_active() ) {
			add_submenu_page(
				'wc-serial-numbers',
				'',
				'<span style="color:#ff7a03;"><span class="dashicons dashicons-star-filled" style="font-size: 17px"></span> ' . __( 'Go Pro', 'wc-serial-numbers' ) . '</span>',
				$role,
				'go_wcsn_pro',
				array( $this, 'go_pro_redirect' )
			);
		}
	}

	/**
	 * Output keys page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_main_page() {
		if ( isset( $_GET['add'] ) || isset( $_GET['edit'] ) ) {
			$id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
			$key = new Key( $id );
			if ( ! empty( $id ) && ! $key->exists() ) {
				wp_safe_redirect( remove_query_arg( 'edit' ) );
				exit();
			}
			Admin::view( 'html-edit-key.php', array( 'key' => $key ) );
		} else {
			Admin::view( 'html-list-keys.php' );
		}
	}

	/**
	 * Output activations page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_activations_page() {
		Admin::view( 'html-list-activations.php' );
	}


	/**
	 * Output tools page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_tools_page() {
		$tabs = array(
			'import'    => __( 'Import', 'wc-serial-numbers' ),
			'export'    => __( 'Export', 'wc-serial-numbers' ),
			'generators' => __( 'Generators', 'wc-serial-numbers' ),
			'api'       => __( 'API', 'wc-serial-numbers' ),
		);

		// If software support is disabled, remove the activations tab.
		if( ! wcsn_is_software_support_enabled() ) {
			unset( $tabs['api'] );
		}

		$tabs        = apply_filters( 'wc_serial_numbers_tools_tabs', $tabs );
		$tab_ids     = array_keys( $tabs );
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : reset( $tab_ids );
		$page        = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

		Admin::view(
			'html-tools.php',
			array(
				'tabs'        => $tabs,
				'current_tab' => $current_tab,
				'page'        => $page,
			)
		);
	}

	/**
	 * Output reports page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_reports_page() {
		$tabs = array(
			'stock'       => __( 'Stock', 'wc-serial-numbers' ),
//			'sales'       => __( 'Sales', 'wc-serial-numbers' ),
//			'activations' => __( 'Activations', 'wc-serial-numbers' ),
		);

		$tabs        = apply_filters( 'wc_serial_numbers_reports_tabs', $tabs );
		$tab_ids     = array_keys( $tabs );
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : reset( $tab_ids );
		$page        = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

		Admin::view(
			'html-reports.php',
			array(
				'tabs'        => $tabs,
				'current_tab' => $current_tab,
				'page'        => $page,
			)
		);
	}

	/**
	 * Redirect to pro page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function go_pro_redirect() {
		if ( isset( $_GET['page'] ) && 'go_wcsn_pro' === $_GET['page'] ) {
			wp_redirect( 'https://pluginever.com/plugins/woocommerce-serial-numbers-pro/?utm_source=admin-menu&utm_medium=link&utm_campaign=upgrade&utm_id=wc-serial-numbers' );
			die;
		}
	}

	/**
	 * Add status tab.
	 *
	 * @param array $tabs Tabs.
	 *
	 * @return array
	 */
	public static function add_tools_status_tab( $tabs ) {
		$tabs['status'] = __( 'Status', 'wc-serial-numbers' );

		return $tabs;
	}

	/**
	 * Import tab content.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function import_tab() {
		//todo import tab promotional content.

		echo "Promotional content for import tab.";
	}

	/**
	 * Export tab content.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function export_tab() {
		//todo export tab promotional content.

		echo "Promotional content for export tab.";
	}

	/**
	 * Debug tab content.
	 *
	 * @since 1.4.6
	 * @return void
	 */
	public static function status_tab() {
		$statuses = array(
			'Serial Numbers version' => wc_serial_numbers()->get_version(),
		);
		if ( wc_serial_numbers()->is_premium_active() && function_exists( 'wc_serial_numbers_pro' ) ) {
			$statuses['Serial Numbers Pro version'] = wc_serial_numbers_pro()->get_version();
		}

		// Check if required tables exist.
		$required_tables = array(
			'serial_numbers',
			'serial_numbers_activations',
		);
		foreach ( $required_tables as $table ) {
			$exists = $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( 'SHOW TABLES LIKE %s', $GLOBALS['wpdb']->prefix . $table ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $exists ) {
				$statuses[ $table ] = __( 'Table exists', 'wc-serial-numbers' );
			} else {
				$statuses[ $table ] = __( 'Table does not exist', 'wc-serial-numbers' );
			}
		}

		// Cron jobs.
		$cron_jobs = array(
			'wc_serial_numbers_hourly_event' => __( 'Hourly cron', 'wc-serial-numbers' ),
			'wc_serial_numbers_daily_event'  => __( 'Daily cron', 'wc-serial-numbers' ),
		);
		foreach ( $cron_jobs as $cron_job => $cron_job_name ) {
			$next_scheduled = wp_next_scheduled( $cron_job );
			if ( $next_scheduled ) {
				$statuses[ $cron_job_name ] = sprintf( __( 'Next run: %s', 'wc-serial-numbers' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled ) );
			} else {
				$statuses[ $cron_job_name ] = __( 'Not scheduled', 'wc-serial-numbers' );
			}
		}
		$statuses = apply_filters( 'wc_serial_numbers_plugin_statuses', $statuses );
		?>
		<table class="widefat wcsn-status" cellspacing="0" id="wcsn-status">
			<thead>
			<tr>
				<th colspan="3" data-export-label="Serial Numbers"><h2><?php esc_html_e( 'Serial Numbers', 'wc-serial-numbers' ); ?></h2></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $statuses as $name => $value ) : ?>
				<tr>
					<td data-export-label="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></td>
					<td class="help">&dash;</td>
					<td><?php echo esc_html( $value ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>

		</table>

		<?php
	}

	/**
	 * Validation section.
	 *
	 * @since 1.4.6
	 * @return void
	 */
	public static function api_validation_section() {
		$args        = array_merge(
			wcsn_get_products_query_args(),
			array(
				'posts_per_page' => - 1,
				'fields'         => 'ids',
			)
		);
		$the_query   = new \WP_Query( $args );
		$product_ids = $the_query->get_posts();
		$products    = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$products[ $product->get_id() ] = sprintf( '%s (#%d)', $product->get_name(), $product->get_id() );
		}

		Admin::view( 'html-api-validation', array( 'products' => $products ) );
	}

	/**
	 * Activation deactivation section.
	 *
	 * @since 1.4.6
	 * @return void
	 */
	public static function api_activation_deactivation_section() {
		$args        = array_merge(
			wcsn_get_products_query_args(),
			array(
				'posts_per_page' => - 1,
				'fields'         => 'ids',
			)
		);
		$the_query   = new \WP_Query( $args );
		$product_ids = $the_query->get_posts();
		$products    = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$products[ $product->get_id() ] = sprintf( '%s (#%d)', $product->get_name(), $product->get_id() );
		}

		Admin::view( 'html-api-actions', array( 'products' => $products ) );
	}

	/**
	 * Stock section.
	 *
	 * @since 1.4.6
	 * @return void
	 */
	public static function reports_stock_tab() {
		Admin::view( 'html-list-stock' );
	}
}
