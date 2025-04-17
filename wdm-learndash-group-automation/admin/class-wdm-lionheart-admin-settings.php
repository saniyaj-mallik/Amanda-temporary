<?php
/**
 * Lionheart Admin Settings
 *
 * Handles the admin settings page for Lionheart plugin.
 *
 * @package WDM_Learndash_Group_Automation
 * @since 2.0.0
 */

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
	 * Email settings options value.
	 *
	 * @var array $email_settings
	 */
	private $email_settings;

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
		
		$email_settings       = get_option( 'wdm_lionheart_email_settings' );
		$this->email_settings = ! empty( $email_settings ) ? $email_settings : array();
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

		// Add Custom Email Template section.
		add_settings_section(
			'wdm_custom_email_template',
			__( 'Shipment Tracking Email Template', 'wdm-learndash-group-automation' ),
			array( $this, 'custom_email_template_section_callback' ),
			'wdm_lionheart_email_settings'
		);
		add_settings_field(
			'wdm_email_subject',
			__( 'Email Subject', 'wdm-learndash-group-automation' ),
			array( $this, 'email_subject_callback' ),
			'wdm_lionheart_email_settings',
			'wdm_custom_email_template'
		);
		add_settings_field(
			'wdm_email_body',
			__( 'Email Body', 'wdm-learndash-group-automation' ),
			array( $this, 'email_body_callback' ),
			'wdm_lionheart_email_settings',
			'wdm_custom_email_template'
		);

		register_setting( 'wdm_lionheart_settings', 'wdm_lionheart_settings' );
		register_setting( 'wdm_lionheart_email_settings', 'wdm_lionheart_email_settings' );
	}

	/**
	 * Output the HTML for the Lionheart settings page.
	 *
	 * @since 2.0.0
	 */
	public function lionheart_settings_page_html() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Lionheart Custom Settings', 'wdm-learndash-group-automation' ); ?></h1>
			
			<h2 class="nav-tab-wrapper">
				<a href="#manual-payment-tab" class="nav-tab nav-tab-active"><?php esc_html_e( 'Manual Payment', 'wdm-learndash-group-automation' ); ?></a>
				<a href="#email-settings-tab" class="nav-tab"><?php esc_html_e( 'Email Settings', 'wdm-learndash-group-automation' ); ?></a>
			</h2>
			
			<div id="manual-payment-tab" class="tab-content">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'wdm_lionheart_settings' );
					do_settings_sections( 'wdm_lionheart_settings' );
					submit_button();
					?>
				</form>
			</div>
			
			<div id="email-settings-tab" class="tab-content" style="display: none;">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'wdm_lionheart_email_settings' );
					do_settings_sections( 'wdm_lionheart_email_settings' );
					submit_button();
					?>
				</form>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			// Tab functionality
			$('.nav-tab-wrapper a').on('click', function(e) {
				e.preventDefault();
				var target = $(this).attr('href');
				
				// Hide all tab content
				$('.tab-content').hide();
				
				// Show the target tab content
				$(target).show();
				
				// Set active class
				$('.nav-tab-wrapper a').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
			});
		});
		</script>
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
	 * Callback function for rendering the "Custom Email Template" settings section.
	 *
	 * @return void Outputs the HTML for the settings section.
	 */
	public function custom_email_template_section_callback() {
		echo '<p>' . esc_html__( 'Configure the email template that will be sent to users for shipment tracking.', 'wdm-learndash-group-automation' ) . '</p>';
	}

	/**
	 * Callback function for rendering the email subject field.
	 *
	 * @return void Outputs the HTML for the text field.
	 */
	public function email_subject_callback() {
		$email_subject = isset( $this->email_settings['wdm_email_subject'] ) ? $this->email_settings['wdm_email_subject'] : '';
		?>
		<input type="text" name="wdm_lionheart_email_settings[wdm_email_subject]" id="wdm_email_subject" value="<?php echo esc_attr( $email_subject ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'Enter the subject for the email.', 'wdm-learndash-group-automation' ); ?></p>
		<?php
	}

	/**
	 * Callback function for rendering the email body field.
	 *
	 * @return void Outputs the HTML for the textarea field.
	 */
	public function email_body_callback() {
		$email_body = isset( $this->email_settings['wdm_email_body'] ) ? $this->email_settings['wdm_email_body'] : '';
		?>
		<textarea name="wdm_lionheart_email_settings[wdm_email_body]" id="wdm_email_body" rows="10" cols="50" class="large-text"><?php echo esc_textarea( $email_body ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Enter the body content for the email. You can use the following variables:', 'wdm-learndash-group-automation' ); ?>
			<br>
			<code>{first_name}</code> - <?php esc_html_e( 'Customer\'s first name', 'wdm-learndash-group-automation' ); ?><br>
			<code>{last_name}</code> - <?php esc_html_e( 'Customer\'s last name', 'wdm-learndash-group-automation' ); ?><br>
			<code>{order_id}</code> - <?php esc_html_e( 'Order ID', 'wdm-learndash-group-automation' ); ?>
		</p>
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
