<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;

/** Keeps manual acquisition separate from Tutor's programmatic enrollment model. */
final class Manual_Enrollment {
	const MODE_META = '_wcte_enrollment_mode';
	const URL_META  = '_wcte_manual_enrollment_url';

	public function hooks() {
		add_filter( 'tutor_get_template_path', array( $this, 'manual_enrollment_template' ), 20, 2 );
		// Tutor's public free-course form posts to this AJAX action. Deliberately do
		// not filter EnrollmentModel::do_enroll(), which WCTE legitimately calls.
		add_action( 'wp_ajax_tutor_enroll_course', array( $this, 'block_public_enrollment' ), 0 );
		add_action( 'wp_ajax_nopriv_tutor_enroll_course', array( $this, 'block_public_enrollment' ), 0 );
	}

	public static function is_manual_course( $course_id ) {
		return 'manual' === get_post_meta( absint( $course_id ), self::MODE_META, true );
	}

	public static function get_manual_url( $course_id ) {
		$url = get_post_meta( absint( $course_id ), self::URL_META, true );
		return wp_http_validate_url( $url ) ? $url : '';
	}

	/**
	 * Replace only Tutor's unenrolled-user template. Tutor chooses its enrolled
	 * template before this point, so Start/Continue/progress remain untouched.
	 */
	public function manual_enrollment_template( $path, $template ) {
		$template = str_replace( '/', '.', (string) $template );
		if ( 'single.course.enrollment' !== $template ) return $path;
		$course_id = get_the_ID() ?: get_queried_object_id();
		if ( ! self::is_manual_course( $course_id ) ) return $path;
		return WCTE_PATH . 'templates/manual-course-enrollment.php';
	}

	public function block_public_enrollment() {
		$course_id = isset( $_REQUEST['course_id'] ) ? absint( wp_unslash( $_REQUEST['course_id'] ) ) : 0;
		if ( ! $course_id && isset( $_REQUEST['id'] ) ) $course_id = absint( wp_unslash( $_REQUEST['id'] ) );
		if ( ! self::is_manual_course( $course_id ) ) return;
		wp_send_json_error( array( 'message' => __( 'This course does not allow self-enrollment.', 'training-entitlements' ) ), 403 );
	}
}
