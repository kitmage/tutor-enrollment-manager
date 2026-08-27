<?php
namespace WCTE;
defined( 'ABSPATH' ) || exit;
final class Product_Settings {
	public function hooks() {
		add_action('woocommerce_product_options_general_product_data',array($this,'fields'));
		add_action('woocommerce_process_product_meta',array($this,'save'));
		add_action('woocommerce_product_after_variable_attributes',array($this,'variation_fields'),10,3);
		add_action('woocommerce_save_product_variation',array($this,'save_variation'),10,2);
	}
	public function fields() {
		echo '<div class="options_group"><p><strong>'.esc_html__('Training Entitlements','training-entitlements').'</strong></p>';
		woocommerce_wp_checkbox(array('id'=>'_wcte_enabled','label'=>__('Enable Training Entitlements','training-entitlements')));
		woocommerce_wp_select(array('id'=>'_wcte_course_id','label'=>__('Tutor LMS Course','training-entitlements'),'class'=>'wc-enhanced-select','options'=>$this->courses()));
		woocommerce_wp_text_input(array('id'=>'_wcte_per_unit','label'=>__('Entitlements Per Unit','training-entitlements'),'type'=>'number','custom_attributes'=>array('min'=>1,'step'=>1)));
		woocommerce_wp_text_input(array('id'=>'_wcte_window_days','label'=>__('Redemption Window (days)','training-entitlements'),'type'=>'number','value'=>get_post_meta(get_the_ID(),'_wcte_window_days',true)?:30,'custom_attributes'=>array('min'=>1,'step'=>1))); echo '</div>';
	}
	private function courses() { $out=array(''=>__('Select a course','training-entitlements')); foreach(get_posts(array('post_type'=>'courses','post_status'=>'publish','numberposts'=>-1,'orderby'=>'title','order'=>'ASC')) as $p) $out[$p->ID]=$p->post_title; return $out; }
	public function save($id) { update_post_meta($id,'_wcte_enabled',isset($_POST['_wcte_enabled'])?'yes':'no'); foreach(array('_wcte_course_id','_wcte_per_unit','_wcte_window_days') as $key) if(isset($_POST[$key])) update_post_meta($id,$key,absint(wp_unslash($_POST[$key]))); }
	public function variation_fields($loop,$data,$variation) { foreach(array('_wcte_course_id'=>'Course ID (inherit when blank)','_wcte_per_unit'=>'Entitlements per unit (inherit when blank)','_wcte_window_days'=>'Window days (inherit when blank)') as $key=>$label) woocommerce_wp_text_input(array('id'=>$key.'_'.$loop,'name'=>$key.'['.$loop.']','value'=>get_post_meta($variation->ID,$key,true),'label'=>$label,'type'=>'number','wrapper_class'=>'form-row form-row-full','custom_attributes'=>array('min'=>1,'step'=>1))); }
	public function save_variation($id,$loop) { foreach(array('_wcte_course_id','_wcte_per_unit','_wcte_window_days') as $key) { $v=isset($_POST[$key][$loop])?absint(wp_unslash($_POST[$key][$loop])):0; $v?update_post_meta($id,$key,$v):delete_post_meta($id,$key); } }
}
