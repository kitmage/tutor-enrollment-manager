<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;

/** Keeps manual acquisition separate from Tutor's programmatic enrollment model. */
final class Manual_Enrollment {
	const MODE_META = '_wcte_enrollment_mode';
	const URL_META  = '_wcte_manual_enrollment_url';

	public function hooks() {
		add_action( 'wp', array( $this, 'replace_entry_box' ), 20 );
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

	public function replace_entry_box() {
		if ( ! is_singular( 'courses' ) ) return;
		$course_id = get_queried_object_id();
		if ( ! self::is_manual_course( $course_id ) || $this->current_user_enrolled( $course_id ) ) return;

		// These public actions are Tutor's intentionally small entry-box extension
		// surface. The native price remains "free"; only acquisition is replaced.
		remove_all_actions( 'tutor_course/single/entry-box/free' );
		remove_all_actions( 'tutor_course/single/entry-box/woocommerce' );
		add_action( 'tutor_course/single/entry-box/free', array( $this, 'render_learn_more' ) );
		add_action( 'tutor_course/single/entry-box/woocommerce', array( $this, 'render_learn_more' ) );
	}

	private function current_user_enrolled( $course_id ) {
		return is_user_logged_in() && (bool) \Tutor\Models\EnrollmentModel::is_enrolled( $course_id, get_current_user_id() );
	}

	public function render_learn_more() {
		$url = self::get_manual_url( get_the_ID() );
		if ( ! $url ) return;
		echo '<a class="tutor-btn tutor-btn-primary tutor-btn-block" href="' . esc_url( $url ) . '">' . esc_html__( 'Learn More', 'training-entitlements' ) . '</a>';
	}

	public function block_public_enrollment() {
		$course_id = isset( $_REQUEST['course_id'] ) ? absint( wp_unslash( $_REQUEST['course_id'] ) ) : 0;
		if ( ! $course_id && isset( $_REQUEST['id'] ) ) $course_id = absint( wp_unslash( $_REQUEST['id'] ) );
		if ( ! self::is_manual_course( $course_id ) ) return;
		wp_send_json_error( array( 'message' => __( 'This course does not allow self-enrollment.', 'training-entitlements' ) ), 403 );
	}
}
