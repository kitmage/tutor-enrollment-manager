<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;
final class Redemption_Service {
	private $batches; private $redemptions; private $enrollment;
	public function __construct( Batch_Repository $b, Redemption_Repository $r, Enrollment_Service $e ) { $this->batches=$b; $this->redemptions=$r; $this->enrollment=$e; }
	public function validate( $batch, $user_id = 0 ) {
		if ( ! $batch ) return new \WP_Error('invalid','Invalid invitation.');
		$this->batches->sync_status($batch->id); $batch=$this->batches->find($batch->id);
		if ('expired'===$batch->status) return new \WP_Error('expired','This invitation has expired.');
		if ('revoked'===$batch->status) return new \WP_Error('revoked','This invitation has been revoked.');
		if ('exhausted'===$batch->status || $batch->entitlements_used >= $batch->entitlements_total) return new \WP_Error('exhausted','This invitation has no enrollments remaining.');
		if (!$this->enrollment->course_valid($batch->course_id)) return new \WP_Error('invalid_course','The associated course is unavailable.');
		if ($user_id && $this->redemptions->completed($batch->id,$user_id)) return new \WP_Error('already_redeemed','You already redeemed this invitation.');
		if ($user_id && $this->enrollment->is_enrolled($batch->course_id,$user_id)) return new \WP_Error('already_enrolled','You are already enrolled in this course.');
		return true;
	}
	public function redeem( $batch, $user_id ) {
		$valid=$this->validate($batch,$user_id); if (is_wp_error($valid)) return $valid;
		if (!$this->batches->reserve($batch->id)) return new \WP_Error('exhausted','This invitation has no enrollments remaining.');
		$redemption=$this->redemptions->create_pending($batch->id,$user_id,$batch->course_id);
		if (!$redemption) { $this->batches->release($batch->id); return new \WP_Error('failure','Unable to reserve an enrollment.'); }
		$result=$this->enrollment->enroll($batch->course_id,$user_id);
		if (is_wp_error($result)) { $this->redemptions->fail($redemption,$result->get_error_message()); $this->batches->release($batch->id); return $result; }
		$user=get_user_by('id',$user_id);
		if (!$user || !$this->redemptions->complete($redemption,$user)) { $this->redemptions->fail($redemption,'Could not finalize redemption.'); $this->batches->release($batch->id); return new \WP_Error('failure','Could not finalize redemption.'); }
		$this->batches->sync_status($batch->id);
		do_action('wcte_redemption_completed',$batch->id,$user_id,(int)$batch->course_id,(int)$batch->order_id);
		return true;
	}
}
