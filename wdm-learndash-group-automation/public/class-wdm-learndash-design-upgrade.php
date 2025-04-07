<?php
/**
 * This file is used for design changes in LearnDash frontend.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wdm_Learndash_Design_Upgrade' ) ) {

	class Wdm_Learndash_Design_Upgrade {

		/**
		 * @var mixed $instance Static class instance.
		 */
		private static $instance;

		/**
		 * Retrieves the static instance of the class which controls all the design
		 * related upgrades for LearnDash.
		 *
		 * Ensures only one instance of the class is loaded or can be loaded.
		 *
		 * @return Wdm_Learndash_Design_Upgrade The static instance of the class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}


		/**
		 * Constructor for initializing the class with action hook
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'learndash_template', array( $this, 'wdm_update_learndash_templates' ), 1000, 3 );
		}

		public function wdm_update_learndash_templates( $template_filepath, $template_name, $args ) {
			if ( 'modules/infobar.php' === $template_name && 'topic' === $args['context'] ) {
				return WDM_LEARNDASH_GROUP_AUTOMATION_DIR . 'public/ld-templates/infobar.php';
			} elseif ( 'widgets/navigation/lesson-row.php' === $template_name ) {
				return WDM_LEARNDASH_GROUP_AUTOMATION_DIR . 'public/ld-templates/lesson-row.php';
			} elseif ( 'modules/course-steps.php' === $template_name ) {
				return WDM_LEARNDASH_GROUP_AUTOMATION_DIR . 'public/ld-templates/course-steps.php';
			} elseif ( 'focus/sidebar.php' === $template_name ) {
				return WDM_LEARNDASH_GROUP_AUTOMATION_DIR . 'public/ld-templates/sidebar.php';
			}
			return $template_filepath;
		}
	}
	Wdm_Learndash_Design_Upgrade::get_instance();
}
