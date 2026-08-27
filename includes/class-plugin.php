<?php
namespace WCTE;
defined('ABSPATH')||exit;
final class Plugin {
	private static $instance;
	public static function instance(){return self::$instance?:self::$instance=new self();}
	public static function dependency_notice(){if(current_user_can('activate_plugins'))echo '<div class="notice notice-error"><p>'.esc_html__('Training Entitlements requires active WooCommerce and Tutor LMS plugins.','training-entitlements').'</p></div>';}
	public function boot(){if(get_option('wcte_schema_version')!==Installer::SCHEMA_VERSION)Installer::migrate();$b=new Batch_Repository();$r=new Redemption_Repository();$t=new Token_Service();$e=new Enrollment_Service();$s=new Redemption_Service($b,$r,$e);(new Product_Settings())->hooks();(new Provisioner($b,$t))->hooks();(new Frontend($b,$r,$t,$s))->hooks();(new Admin($b,$r,new Audit_Repository(),$t))->hooks();add_action('init',array($this,'routes'));}
	public function routes(){add_rewrite_rule('^training/redeem/([A-Za-z0-9_-]+)/?$','index.php?wcte_token=$matches[1]','top');add_rewrite_endpoint('training-enrollments',EP_ROOT|EP_PAGES);}
}
