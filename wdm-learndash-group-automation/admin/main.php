<?php
/**
 * Add custom metabox on Course page (Page Link) and redirect the course to page link.
 */

if ( ! class_exists( 'WDM_Learndash_Group_automation' ) ) {

	class WDM_Learndash_Group_Automation {

		/**
		 * Class instance.
		 *
		 * @var mixed $instance
		 */
		private static $instance;

		/**
		 * Constructor for initializing the class with various action hooks.
		 *
		 * @return void
		 */
		public function __construct() {
			// Hook for adding custom metabox.
			add_action( 'add_meta_boxes', array( $this, 'wdm_add_custom_metabox_on_course_page' ) );
			// Hook for saving data of custom metabox.
			add_action( 'save_post', array( $this, 'wdm_save_custom_metabox_page_select' ) );
			// Hook for redirecting the course page to page link in custom meta box.
			add_action( 'template_redirect', array( $this, 'wdm_course_page_redirect' ) );
			// Hook for adding group to Learndash group content protect when the group is updated or created.
			add_action( 'save_post', array( $this, 'wdm_adding_group_to_page_associated_to_courses' ), 10, 3 );
			add_action( 'wp_after_insert_post', array( $this, 'wdm_adding_group_to_page_associated_to_courses' ), 10, 3 );
			// Increase the threshold limit of number of items in order for grouping in shipping label [ plugin name - woocommerce shipping and tax | version >= 2.5.7 ].
			add_filter( 'wc_connect_shipment_item_quantity_threshold', array( $this, 'wdm_set_shipment_item_quantity_threshold' ), 10, 1 );
			add_filter( 'learndash_template', array( $this, 'wdm_learndash_update_course_row_template' ), 1000, 5 );
		}

		/**
		 * Add meta box to course page for selecting the page.
		 *
		 * @return void
		 */
		public function wdm_add_custom_metabox_on_course_page() {
			add_meta_box(
				'course_to_page',
				'Page Link',
				array( self::class, 'wdm_add_custom_metebox_page_select' ),
				'sfwd-courses',
				'side'
			);
		}

		/**
		 * Adding the content to the custom metabox.
		 *
		 * @param object $post WordPress post.
		 *
		 * @return void
		 */
		public static function wdm_add_custom_metebox_page_select( $post ) {
			$args  = array(
				'sort_order'  => 'asc',
				'sort_column' => 'post_title',
				'post_type'   => 'page',
				'post_status' => 'publish',
			);
			$pages = get_pages( $args );

			$value = get_post_meta( $post->ID, 'wdm_page_select_for_course', true );
			?>
				<label for="page_select_for_course"></label>
				<select name='page_select_for_course' id="page_select_for_course" style="width:100%">
				<?php
				if ( ! empty( $value ) ) {
					echo "<option value='" . $value . "'>" . get_the_title( $value ) . '</option>';
				}
				?>
				<option value="">Select Page</option>
				<?php
				foreach ( $pages as $page ) {
					if ( $page->ID == $value ) {
						continue;
					} else {
						?>
				<option value=<?php echo $page->ID; ?> ><?php echo $page->post_title; ?></option>
						<?php
					}
				}
				?>
				</select>
			   
			<?php
		}

		/**
		 *  Saving the data of custom metabox
		 *
		 * @param int $post_id
		 *
		 * @return void
		 */
		public static function wdm_save_custom_metabox_page_select( $post_id ) {
			if ( array_key_exists( 'page_select_for_course', $_POST ) ) {
				if ( ! empty( $_POST['page_select_for_course'] ) ) {
					update_post_meta( $post_id, 'wdm_page_select_for_course', $_POST['page_select_for_course'] );
				}
			}
		}

		/**
		 * Updates the filepath of the course row template if a specific condition is met.
		 *
		 * This function checks if the template name is 'shortcodes/profile/course-row.php'
		 * and if the 'wdm_override_template' attribute is set to 'yes' in the shortcode attributes.
		 * If both conditions are true, the function returns a custom template path. Otherwise,
		 * it returns the original template filepath.
		 *
		 * @param string $template_filepath   The original template filepath.
		 * @param string $template_name       The name of the template being loaded.
		 * @param array  $args                Additional arguments passed to the template.
		 * @param bool   $echo                Whether to echo the template.
		 * @param bool   $return_file_path    Whether to return the file path.
		 *
		 * @return string The modified or original template filepath.
		 */
		public function wdm_learndash_update_course_row_template( $template_filepath, $template_name, $args, $echo, $return_file_path ) {
			$is_override_template = isset( $args['shortcode_atts']['wdm_override_template'] ) && 'yes' === $args['shortcode_atts']['wdm_override_template'] ? true : false;
			if ( 'shortcodes/profile/course-row.php' === $template_name && $is_override_template ) {
				return plugin_dir_path( __FILE__ ) . 'templates/course-row.php';
			}
			return $template_filepath;
		}

		/**
		 * Applying the redirection on course page to selected page in metabox.
		 */
		public function wdm_course_page_redirect() {
			$post_id       = get_the_ID();
			$ld_post_types = array( 'sfwd-courses' );
			if ( is_singular( $ld_post_types ) ) {
				$course_id = learndash_get_course_id( get_the_ID() );
				if ( ! empty( $course_id ) ) {
					$steps        = learndash_get_course_steps( $course_id );
					$lesson_id    = ! empty( $steps ) && isset( $steps[0] ) ? $steps[0] : 0;
					$redirect_url = get_permalink( $lesson_id );
					if ( ! empty( $redirect_url ) ) {
						wp_safe_redirect( $redirect_url );
						exit();
					}
				}
			}
		}

		/**
		 * Adding the group in Learndash group content protection on selected page from custom metabox on group update or creation.
		 *
		 * @param int     $post_id post id.
		 *
		 * @param object  $post post.
		 *
		 * @param boolean $update is post update.
		 *
		 * @return void
		 */
		public function wdm_adding_group_to_page_associated_to_courses( $post_id, $post, $update ) {
			if ( $post->post_type == 'groups' ) {
				$courses = learndash_get_group_courses_list( $post_id );
				if ( ! empty( $courses ) ) {
					foreach ( $courses as $course_id ) {
						$page_id = intval( get_post_meta( $course_id, 'wdm_page_select_for_course', true ) );
						if ( ! empty( $page_id ) ) {
							$old_group = learndash_get_post_group_membership_groups( $page_id );

							if ( empty( $old_group ) ) {
								$old_group = array( $post_id );
							} else {
								array_push( $old_group, $post_id );
							}
							$setting = array(
								'groups_membership_enabled' => 'on',
								'groups_membership_compare' => 'ANY',
								'groups_membership_groups' => $old_group,
							);
							learndash_set_post_group_membership_settings( $page_id, $setting );

						}
					}
				}
			}
		}

		/**
		 * Sets the threshold for the number of items in a shipment.
		 *
		 * @param int $threshold The current threshold value.
		 * @return int The new threshold value, which is set to 1000.
		 */
		public function wdm_set_shipment_item_quantity_threshold( $threshold ) {
			return 10000;
		}

		/**
		 * Retrieve the singleton instance of the class.
		 *
		 * Ensures only one instance of the class is loaded or can be loaded.
		 *
		 * @return WDM_Learndash_Group_Automation The singleton instance of the class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
	WDM_Learndash_Group_Automation::get_instance();
}

?>
