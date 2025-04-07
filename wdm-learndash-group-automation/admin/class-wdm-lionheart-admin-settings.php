<?php

/**
 * Class for admin settings.
 */
class Wdm_Lionheart_Admin_Settings {

	/**
	 * Instance of the class.
	 *
	 * @var mixed $instance
	 */
	private static $instance;

	/**
	 * Manual Payment options value.
	 *
	 * @var array $manual_payment_options
	 */
	private $manual_payment_options;

	/**
	 * Initialize the class.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'lionheart_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'lionheart_admin_enqueue_scripts' ) );

		$manual_payment_options       = get_option( 'wdm_lionheart_settings' );
		$this->manual_payment_options = ! empty( $manual_payment_options ) ? $manual_payment_options : array();
	}

	/**
	 * Create a sub-page under Settings.
	 */
	public function lionheart_settings_page() {
		add_submenu_page(
			'options-general.php',
			__( 'Lionheart Settings', 'wdm-learndash-group-automation' ),
			__( 'Lionheart Settings', 'wdm-learndash-group-automation' ),
			'manage_options',
			'lionheart-settings',
			array( $this, 'lionheart_settings_page_html' )
		);
		// Add a select2 field for selecting user roles.
		add_settings_section(
			'wdm_manual_payment_options',
			__( 'Manual Payment Options', 'wdm-learndash-group-automation' ),
			array( $this, 'manual_payment_section_callback' ),
			'wdm_lionheart_settings'
		);
		add_settings_field( 'wdm_user_roles_for_manual_payment', __( 'Select User Roles for Manual Payment', 'wdm-learndash-group-automation' ), array( $this, 'user_roles_for_manual_payment_callback' ), 'wdm_lionheart_settings', 'wdm_manual_payment_options' );

		register_setting( 'wdm_lionheart_settings', 'wdm_lionheart_settings' );
	}

	/**
	 * Output the HTML for the Lionheart settings page.
	 *
	 * @since 2.0.0
	 */
	public function lionheart_settings_page_html() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Lionheart Custom Settings', ' wdm-learndash-group-automation' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wdm_lionheart_settings' );
				do_settings_sections( 'wdm_lionheart_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles for the Lionheart settings page.
	 *
	 * @param string $hook_suffix The hook suffix.
	 */
	public function lionheart_admin_enqueue_scripts( $hook_suffix ) {
		if ( 'settings_page_lionheart-settings' === $hook_suffix ) {
			wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
			wp_enqueue_script(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
				array( 'jquery' ),
				'4.1.0',
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
			wp_enqueue_script(
				'wdm-lionheart-admin-settings',
				WDM_LEARNDASH_GROUP_AUTOMATION_PLUGIN_URL . '/admin/assets/js/wdm-lionheart-admin-script.js',
				array( 'jquery', 'select2' ),
				'1.0.0',
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
	}

	/**
	 * Callback function for rendering the "Manual Payment Options" settings section.
	 *
	 * @return void Outputs the HTML for the settings section.
	 */
	public function manual_payment_section_callback() {
	}

	/**
	 * Callback function for rendering the user roles selection field for manual payments.
	 *
	 * @return void Outputs the HTML for the multi-select field.
	 */
	public function user_roles_for_manual_payment_callback() {
		global $wp_roles;
		$roles = $wp_roles->roles;
		// Sort the $roles array by the 'name' key while preserving original keys.
		uasort(
			$roles,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);
		$selected_roles = isset( $this->manual_payment_options['wdm_user_roles_for_manual_payment'] ) ? $this->manual_payment_options['wdm_user_roles_for_manual_payment'] : array();
		?>
		<?php if ( ! empty( $roles ) && is_array( $roles ) ) : ?>
			<select name="wdm_lionheart_settings[wdm_user_roles_for_manual_payment][]" id="wdm_user_roles_for_manual_payment" multiple="multiple" class="wdm-select2 wdm-user-roles-settings" style="width: 50%">
			<?php foreach ( $roles as $role_key => $role ) : ?>
				<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $selected_roles, true ) ); ?>><?php echo esc_html( $role['name'] ); ?></option>
			<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<?php
	}

	/**
	 * Retrieve the singleton instance of the class.
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return Wdm_Lionheart_Admin_Settings The singleton instance of the class.
	 */
	public static function get_instance() {
		if ( null === self::$instance || empty( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

Wdm_Lionheart_Admin_Settings::get_instance();
