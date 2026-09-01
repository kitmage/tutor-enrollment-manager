<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;

/** Keeps manual acquisition separate from Tutor's programmatic enrollment model. */
final class Manual_Enrollment {
	const MODE_META = '_wcte_enrollment_mode';
	const URL_META  = '_wcte_manual_enrollment_url';

	public function hooks() {
		add_filter( 'tutor/course/single/entry-box/free', array( $this, 'replace_free_enrollment' ), 20, 2 );
		// Intercept only Tutor's public Enroll Now form. Deliberately do not filter
		// EnrollmentModel::do_enroll(), which WCTE legitimately calls directly.
		add_action( 'template_redirect', array( $this, 'block_public_enrollment' ), 0 );
	}

	public static function is_manual_course( $course_id ) {
		return 'manual' === get_post_meta( absint( $course_id ), self::MODE_META, true );
	}

	public static function get_manual_url( $course_id ) {
		$url = get_post_meta( absint( $course_id ), self::URL_META, true );
		return wp_http_validate_url( $url ) ? $url : '';
	}

	/** Replace Tutor's complete Free / Enroll Now block for Manual courses. */
	public function replace_free_enrollment( $html, $course_id ) {
		if ( ! self::is_manual_course( $course_id ) ) return $html;
		$url = self::get_manual_url( $course_id );
		if ( ! $url ) return '';
		return sprintf(
			'<a class="tutor-btn tutor-btn-primary tutor-btn-lg tutor-btn-block" href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Learn More', 'training-entitlements' )
		);
	}

	public function block_public_enrollment() {
		$action = isset( $_POST['tutor_course_action'] ) ? sanitize_text_field( wp_unslash( $_POST['tutor_course_action'] ) ) : '';
		if ( '_tutor_course_enroll_now' !== $action ) return;
		$course_id = isset( $_POST['tutor_course_id'] ) ? absint( wp_unslash( $_POST['tutor_course_id'] ) ) : 0;
		if ( ! $course_id || ! self::is_manual_course( $course_id ) ) return;
		wp_die(
			esc_html__( 'This course does not allow self-enrollment.', 'training-entitlements' ),
			'',
			array( 'response' => 403 )
		);
	}
}
