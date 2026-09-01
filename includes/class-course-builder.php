<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;

/** Version-isolated augmentation for Tutor LMS's React course builder. */
final class Course_Builder {
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_ajax_wcte_manual_course_settings', array( $this, 'save' ) );
	}

	public function admin_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! $screen || ( 'courses' !== $screen->post_type && ! in_array( $page, array( 'create-course', 'tutor-course-builder' ), true ) ) ) return;
		$course_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : ( isset( $_GET['course_ID'] ) ? absint( $_GET['course_ID'] ) : 0 );
		wp_enqueue_script( 'wcte-course-builder', plugins_url( 'assets/course-builder.js', WCTE_FILE ), array(), WCTE_VERSION, true );
		wp_localize_script( 'wcte-course-builder', 'wcteManualCourse', array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wcte_manual_course' ),
			'courseId' => $course_id,
			'isManual' => $course_id && Manual_Enrollment::is_manual_course( $course_id ),
			'url'      => $course_id ? Manual_Enrollment::get_manual_url( $course_id ) : '',
			'labels'   => array(
				'manual' => __( 'Manual', 'training-entitlements' ),
				'url' => __( 'Learn More URL', 'training-entitlements' ),
				'required' => __( 'Enter a valid Learn More URL before saving.', 'training-entitlements' ),
				'error' => __( 'Manual enrollment settings could not be saved.', 'training-entitlements' ),
			),
		) );
	}

	public function save() {
		check_ajax_referer( 'wcte_manual_course', 'nonce' );
		$course_id = isset( $_POST['course_id'] ) ? absint( wp_unslash( $_POST['course_id'] ) ) : 0;
		if ( ! $course_id || ! current_user_can( 'edit_post', $course_id ) || 'courses' !== get_post_type( $course_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You cannot edit this course.', 'training-entitlements' ) ), 403 );
		}
		$manual = isset( $_POST['manual'] ) && '1' === wp_unslash( $_POST['manual'] );
		if ( ! $manual ) {
			delete_post_meta( $course_id, Manual_Enrollment::MODE_META );
			wp_send_json_success();
		}
		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ), array( 'http', 'https' ) ) : '';
		if ( ! $url || ! wp_http_validate_url( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'Enter a valid Learn More URL.', 'training-entitlements' ) ), 400 );
		}
		update_post_meta( $course_id, Manual_Enrollment::MODE_META, 'manual' );
		update_post_meta( $course_id, Manual_Enrollment::URL_META, $url );
		wp_send_json_success();
	}
}
