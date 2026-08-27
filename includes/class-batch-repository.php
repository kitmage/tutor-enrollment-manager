<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;
final class Batch_Repository {
	private function table() { global $wpdb; return $wpdb->prefix . 'training_entitlement_batches'; }
	public function create( array $data ) { global $wpdb; $ok = $wpdb->insert( $this->table(), $data ); return $ok ? (int) $wpdb->insert_id : 0; }
	public function by_token_hash( $hash ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . ' WHERE token_hash=%s', $hash ) ); }
	public function find( $id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . ' WHERE id=%d', $id ) ); }
	public function for_customer( $user_id ) { global $wpdb; return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->table() . ' WHERE customer_user_id=%d ORDER BY created_at DESC', $user_id ) ); }
	public function query( $where = '1=1', array $args = array(), $limit = 100 ) { global $wpdb; $sql = 'SELECT * FROM ' . $this->table() . " WHERE $where ORDER BY created_at DESC LIMIT %d"; $args[] = $limit; return $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); }
	public function update( $id, array $data ) { global $wpdb; $data['updated_at'] = current_time( 'mysql' ); return false !== $wpdb->update( $this->table(), $data, array( 'id' => $id ) ); }
	public function reserve( $id ) { global $wpdb; return 1 === (int) $wpdb->query( $wpdb->prepare( 'UPDATE ' . $this->table() . " SET entitlements_used=entitlements_used+1,updated_at=%s WHERE id=%d AND status='active' AND expires_at>%s AND entitlements_used<entitlements_total", current_time( 'mysql' ), $id, current_time( 'mysql' ) ) ); }
	public function release( $id ) { global $wpdb; $wpdb->query( $wpdb->prepare( 'UPDATE ' . $this->table() . ' SET entitlements_used=GREATEST(entitlements_used-1,0),status=IF(status=\'exhausted\',\'active\',status),updated_at=%s WHERE id=%d', current_time( 'mysql' ), $id ) ); }
	public function sync_status( $id ) { global $wpdb; $wpdb->query( $wpdb->prepare( 'UPDATE ' . $this->table() . " SET status=CASE WHEN status='revoked' THEN status WHEN expires_at<=%s THEN 'expired' WHEN entitlements_used>=entitlements_total THEN 'exhausted' ELSE 'active' END,updated_at=%s WHERE id=%d", current_time( 'mysql' ), current_time( 'mysql' ), $id ) ); }
}
