<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;
final class Audit_Repository {
	public function log( $batch, $action, $old = null, $new = null ) { global $wpdb; return $wpdb->insert( $wpdb->prefix.'training_entitlement_audit', array('batch_id'=>$batch,'action'=>$action,'previous_value'=>is_scalar($old)?(string)$old:wp_json_encode($old),'new_value'=>is_scalar($new)?(string)$new:wp_json_encode($new),'administrator_user_id'=>get_current_user_id(),'created_at'=>current_time('mysql')) ); }
}
