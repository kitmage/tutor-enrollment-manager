<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;
final class Redemption_Repository {
	private function table() { global $wpdb; return $wpdb->prefix . 'training_entitlement_redemptions'; }
	public function completed( $batch, $user ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . " WHERE batch_id=%d AND user_id=%d AND status='completed' LIMIT 1", $batch, $user ) ); }
	public function create_pending( $batch, $user, $course ) { global $wpdb; $now=current_time('mysql'); $ok=$wpdb->insert( $this->table(), array( 'batch_id'=>$batch,'user_id'=>$user,'status'=>'pending','course_id'=>$course,'created_at'=>$now,'updated_at'=>$now ) ); if($ok)return (int)$wpdb->insert_id; $id=(int)$wpdb->get_var($wpdb->prepare('SELECT id FROM '.$this->table()." WHERE batch_id=%d AND user_id=%d AND status='failed'",$batch,$user)); if($id && 1===$wpdb->update($this->table(),array('status'=>'pending','context'=>null,'updated_at'=>$now),array('id'=>$id,'status'=>'failed')))return $id; return 0; }
	public function complete( $id, \WP_User $user ) { global $wpdb; return $wpdb->update( $this->table(), array( 'status'=>'completed','first_name_snapshot'=>(string)$user->first_name,'last_name_snapshot'=>(string)$user->last_name,'email_snapshot'=>(string)$user->user_email,'redeemed_at'=>current_time('mysql'),'updated_at'=>current_time('mysql') ), array( 'id'=>$id,'status'=>'pending' ) ); }
	public function fail( $id, $message ) { global $wpdb; $wpdb->update( $this->table(), array( 'status'=>'failed','context'=>sanitize_textarea_field($message),'updated_at'=>current_time('mysql') ), array('id'=>$id) ); }
	public function for_batch( $batch ) { global $wpdb; return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . " WHERE batch_id=%d AND status='completed' ORDER BY redeemed_at", $batch ) ); }
	public function completed_count( $batch ) { global $wpdb; return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.$this->table()." WHERE batch_id=%d AND status='completed'",$batch)); }
}
