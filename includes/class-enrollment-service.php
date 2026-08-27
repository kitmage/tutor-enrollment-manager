<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;

/** Isolates version-sensitive Tutor LMS calls and verifies their result. */
final class Enrollment_Service {
	public function course_valid( $course_id ) {
		return 'courses' === get_post_type( $course_id ) && 'publish' === get_post_status( $course_id );
	}
	public function is_enrolled( $course_id, $user_id ) {
		return (bool) \Tutor\Models\EnrollmentModel::is_enrolled( $course_id, $user_id );
	}
	public function enroll( $course_id, $user_id ) {
		if ( ! $this->course_valid( $course_id ) ) return new \WP_Error( 'invalid_course', __( 'The course is unavailable.', 'training-entitlements' ) );
		try {
			$model = '\\Tutor\\Models\\EnrollmentModel';
			$method = new \ReflectionMethod( $model, 'do_enroll' );
			if ( $method->getNumberOfRequiredParameters() > 3 ) return new \WP_Error( 'unsupported_tutor', __( 'This Tutor LMS enrollment API is not supported.', 'training-entitlements' ) );
			$result = $model::do_enroll( $course_id, 0, $user_id );
			// Tutor 3.x exposes update_enrollments($enrollment_ids, $status).
			if ( ! $this->is_enrolled( $course_id, $user_id ) && method_exists( $model, 'update_enrollments' ) ) {
				$enrollment_id = is_numeric( $result ) ? (int) $result : 0;
				if ( $enrollment_id ) $model::update_enrollments( array( $enrollment_id ), 'completed' );
			}
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'tutor_enrollment_failed', $e->getMessage() );
		}
		return $this->is_enrolled( $course_id, $user_id ) ? true : new \WP_Error( 'unverified_enrollment', __( 'Tutor LMS did not confirm an active enrollment.', 'training-entitlements' ) );
	}
}
