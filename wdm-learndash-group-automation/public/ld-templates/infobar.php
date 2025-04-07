<?php
/**
 * LearnDash Displays an informational bar
 *
 * Is contextulaized by passing in a $context variable that indicates post type
 *
 * $context       : course, lesson, topic, quiz, etc...
 * $course_id     : Current Course ID
 * $user_id       : Current User ID
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30\Modules
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_type = get_post_type();
if ( ( isset( $post ) ) && ( is_a( $post, 'WP_Post' ) ) ) {
	$post_type = $post->post_type;
} else {
	$post_id = get_the_ID();
	$post    = get_post( $post_id );
}

/**
 * Gets the total number of topics in a course.
 *
 * Uses a transient to cache the result.
 *
 * @since 3.0.0
 *
 * @param int $course_id Course ID.
 *
 * @return int Total number of topics in the course.
 */
function get_total_topics_in_course( $course_id ) {
	$total_topics = get_transient( 'wdm_learndash_total_topics_in_course_' . $course_id );
	if ( ! empty( $total_topics ) ) {
		return $total_topics;
	}

	$total_topics = 0;

	// Get all lesson IDs in the course.
	$lesson_ids = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );

	if ( ! empty( $lesson_ids ) && is_array( $lesson_ids ) ) {
		foreach ( $lesson_ids as $lesson ) {
			$lesson_id = $lesson->ID;

			// Get all topic IDs under the lesson.
			$topics = learndash_get_topic_list( $lesson_id, $course_id );

			if ( ! empty( $topics ) ) {
				$total_topics += count( $topics );
			}
		}
	}
	set_transient( 'wdm_learndash_total_topics_in_course_' . $course_id, $total_topics, 7 * DAY_IN_SECONDS );
	return $total_topics;
}

/**
 * Gets the position of a topic in a course. The position is a sequential number that takes into account the lesson and topic order. Uses a transient to cache the result.
 *
 * @since 3.0.0
 *
 * @param int $topic_id Topic ID.
 *
 * @return int|false Topic position in course. False if topic not found.
 */
function get_topic_position_in_course( $topic_id ) {
	$position = get_transient( 'wdm_learndash_topic_position_in_course_' . $topic_id );
	if ( ! empty( $position ) ) {
		return $position;
	}
	$course_id = learndash_get_course_id( $topic_id );
	$lessons   = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );

	$position = 0;

	foreach ( $lessons as $lesson ) {
		$topics = learndash_get_topic_list( $lesson->ID, $course_id );

		if ( ! empty( $topics ) ) {
			foreach ( $topics as $topic ) {
				++$position;

				if ( intval( $topic->ID ) === intval( $topic_id ) ) {
					set_transient( 'wdm_learndash_topic_position_in_course_' . $topic_id, $position, 7 * DAY_IN_SECONDS );
					return $position;
				}
			}
		}
	}

	return false; // topic not found
}

/**
 * Fires before the infobar.
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $course_id Course ID.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-infobar-before', $post_type, $course_id, $user_id );
/**
 * Fires before the infobar for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $course_id Course ID.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-' . $context . '-infobar-before', $course_id, $user_id ); ?>

<?php
/**
 * Fires inside the infobar (before).
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $course_id Course ID.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-infobar-inside-before', $post_type, $course_id, $user_id );

/**
 * Fires inside the infobar (before) for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-' . $context . '-infobar-inside-before', $course_id, $user_id );

switch ( $context ) {

	case ( 'course' ):
		learndash_get_template_part(
			'modules/infobar/course.php',
			array(
				'has_access'    => $has_access,
				'user_id'       => $user_id,
				'course_id'     => $course_id,
				'course_status' => $course_status,
				'post'          => $post,
			),
			true
		);

		break;

	case ( 'group' ):
		learndash_get_template_part(
			'modules/infobar_group.php',
			array(
				'context'      => 'group',
				'group_id'     => $course_id,
				'user_id'      => $user_id,
				'has_access'   => $has_access,
				'group_status' => $course_status,
				'post'         => $post,
			),
			true
		);

		break;

	case ( 'lesson' ):
		?>

		<div class="ld-lesson-status">
			<div class="ld-breadcrumbs">

				<?php
				learndash_get_template_part(
					'modules/breadcrumbs.php',
					array(
						'context'   => 'lesson',
						'user_id'   => $user_id,
						'course_id' => $course_id,
						'post'      => $post,
					),
					true
				);

				$status = '';
				if ( ( is_user_logged_in() ) && ( true === $has_access ) ) {
					$status = ( learndash_is_item_complete( $post->ID, $user_id, $course_id ) ? 'complete' : 'Subhajit' );
				} else {
					$course_status = '';
					$status        = '';
				}

				learndash_status_bubble( ( ! empty( $course_status ) ? $course_status : $status ) );
				?>

			</div> <!--/.ld-breadcrumbs-->

			<?php
			if ( ( is_user_logged_in() ) && ( true === $has_access ) ) {
				learndash_get_template_part(
					'modules/progress.php',
					array(
						'context'   => 'topic',
						'user_id'   => $user_id,
						'course_id' => $course_id,
						'post'      => $post,
					),
					true
				);
			}
			?>
		</div>

		<?php
		break;

	case ( 'topic' ):
		?>

		<div class="ld-topic-status wdm-topic-status-custom-style ">

			<div class="ld-breadcrumbs wdm-breadcrumbs-topic-custom-style">
				<?php
					$total_topics   = get_total_topics_in_course( $course_id );
					$topic_position = get_topic_position_in_course( get_the_ID() );
				?>
				<?php echo esc_html( sprintf( 'Section %d of %d', $topic_position, $total_topics ) ); ?>
			</div> <!--/.ld-breadcrumbs-->

			<?php
			if ( ( is_user_logged_in() ) && ( true === $has_access ) ) {
				learndash_get_template_part(
					'modules/progress.php',
					array(
						'context'   => 'topic',
						'user_id'   => $user_id,
						'course_id' => $course_id,
						'post'      => $post,
					),
					true
				);
			}
			?>

		</div>

		<?php
		break;

	case 'quiz':
		if ( get_post_type( ( ! empty( $post ) ? $post : '' ) ) === learndash_get_post_type_slug( 'quiz' ) ) {
			?>
			<div class="ld-quiz-status">
				<?php if ( ! empty( $course_id ) ) { ?>
				<div class="ld-breadcrumbs">
					<?php
					learndash_get_template_part(
						'modules/breadcrumbs.php',
						array(
							'context'   => 'quiz',
							'user_id'   => $user_id,
							'course_id' => $course_id,
							'post'      => $post,
						),
						true
					);
					?>
				</div> <!--/.ld-breadcrumbs-->
				<?php } ?>
			</div>
			<?php
		}
		break;

	default:
		// Fail silently.
		break;
}
/**
 * Fires inside the infobar (after).
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $course_id Course ID.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-infobar-inside-after', $post_type, $course_id, $user_id );

/**
 * Fires inside the infobar (after) for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $course_id Course ID.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-' . $context . '-infobar-inside-after', $course_id, $user_id );
?>

<?php
/**
 * Fires after the infobar.
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $course_id Course ID.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-infobar-after', $post_type, $course_id, $user_id );

/**
 * Fires after the infobar for any context.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param string|false $post_type Post type slug.
 * @param int          $course_id Course ID.
 * @param int          $user_id   User ID.
 */
do_action( 'learndash-' . $context . '-infobar-after', $course_id, $user_id );
