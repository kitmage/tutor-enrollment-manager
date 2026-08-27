<?php
namespace WCTE;

defined( 'ABSPATH' ) || exit;

final class Installer {
	const SCHEMA_VERSION = '1.0.0';
	public static function activate() {
		self::migrate();
		add_rewrite_rule( '^training/redeem/([A-Za-z0-9_-]+)/?$', 'index.php?wcte_token=$matches[1]', 'top' );
		add_rewrite_endpoint( 'training-enrollments', EP_ROOT | EP_PAGES );
		flush_rewrite_rules();
	}
	public static function deactivate() { flush_rewrite_rules(); }
	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		$b = $wpdb->prefix . 'training_entitlement_batches';
		$r = $wpdb->prefix . 'training_entitlement_redemptions';
		$a = $wpdb->prefix . 'training_entitlement_audit';
		dbDelta( "CREATE TABLE $b (
			id bigint unsigned NOT NULL AUTO_INCREMENT, token_hash char(64) NOT NULL,
			customer_user_id bigint unsigned NOT NULL, order_id bigint unsigned NOT NULL,
			order_item_id bigint unsigned NOT NULL, subscription_id bigint unsigned NOT NULL DEFAULT 0,
			product_id bigint unsigned NOT NULL, variation_id bigint unsigned NOT NULL DEFAULT 0,
			course_id bigint unsigned NOT NULL, entitlements_total int unsigned NOT NULL,
			entitlements_used int unsigned NOT NULL DEFAULT 0, created_at datetime NOT NULL,
			expires_at datetime NOT NULL, status varchar(20) NOT NULL DEFAULT 'active',
			created_by bigint unsigned NOT NULL DEFAULT 0, updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY order_item (order_item_id), UNIQUE KEY token_hash (token_hash),
			KEY customer (customer_user_id), KEY order_id (order_id), KEY subscription (subscription_id),
			KEY course_status (course_id,status), KEY expires (expires_at)
		) $c;" );
		dbDelta( "CREATE TABLE $r (
			id bigint unsigned NOT NULL AUTO_INCREMENT, batch_id bigint unsigned NOT NULL,
			user_id bigint unsigned NOT NULL, first_name_snapshot varchar(100) NOT NULL DEFAULT '',
			last_name_snapshot varchar(100) NOT NULL DEFAULT '', email_snapshot varchar(190) NOT NULL DEFAULT '',
			redeemed_at datetime NULL, status varchar(20) NOT NULL, course_id bigint unsigned NOT NULL,
			context text NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
			PRIMARY KEY (id), KEY batch_status (batch_id,status), UNIQUE KEY user_batch (user_id,batch_id)
		) $c;" );
		dbDelta( "CREATE TABLE $a (
			id bigint unsigned NOT NULL AUTO_INCREMENT, batch_id bigint unsigned NOT NULL,
			action varchar(50) NOT NULL, previous_value text NULL, new_value text NULL,
			administrator_user_id bigint unsigned NOT NULL, created_at datetime NOT NULL,
			PRIMARY KEY (id), KEY batch_id (batch_id), KEY created_at (created_at)
		) $c;" );
		update_option( 'wcte_schema_version', self::SCHEMA_VERSION );
	}
}
