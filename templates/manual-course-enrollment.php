<?php
defined( 'ABSPATH' ) || exit;

$course_id = get_the_ID() ?: get_queried_object_id();
$url       = \WCTE\Manual_Enrollment::get_manual_url( $course_id );
if ( ! $url ) return;
?>
<a class="tutor-btn tutor-btn-primary tutor-btn-lg tutor-btn-block" href="<?php echo esc_url( $url ); ?>">
	<?php echo esc_html__( 'Learn More', 'training-entitlements' ); ?>
</a>
