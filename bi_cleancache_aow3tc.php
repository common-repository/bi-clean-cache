<?php
/**
 * Plugin Name: BI Clean Cache
 * Description: This plugin is used clean autoptimize plugin cache files as well as clean W3TC plugin page cache if W3TC plugin installed. If plugin autoptimize is not installed or active then this plugin will not work.
 * Version: 1.0.0
 * Author: Jayesh Mahajan
 */

define('BI_CRON_EVENT_NAME', 'bi_cleancache_event');
class bi_cleancache_aow3tc{
	/**
	 * This function initilise plugin settings
	 */
	function bi_cleancache_init_system() {

		$blog_id = get_current_blog_id();

		if(class_exists("autoptimizeCache")){
			//default run cron daily
			if(!update_blog_option( $blog_id, 'bi_cron_interval', 86400)){
				add_blog_option( $blog_id, 'bi_cron_interval', 86400);
			}
			//default run cron daily
			if(!update_blog_option( $blog_id, 'bi_cron_datetime', date('Y-m-d H:i'))){
				add_blog_option( $blog_id, 'bi_cron_datetime', date('Y-m-d H:i'));
			}
			if (! wp_next_scheduled ( BI_CRON_EVENT_NAME )) {
				wp_schedule_event(time(), 'daily', BI_CRON_EVENT_NAME);
			}
		}
	}
	/**
	 * function executes after deactivating plugin
	 */
	function bi_cleancache_deactivate(){
		//clear set cron
		wp_clear_scheduled_hook(BI_CRON_EVENT_NAME);
	}
	/**
	 * This function registering js files
	 */
	function bi_cleancache_js() {
		global $post;
		wp_enqueue_script('jquery');
		wp_enqueue_script('bi_cleancache_dtime_js', plugins_url( '/js/jquery.datetimepicker.js', __FILE__ ));
	}
	/**
	 * This function registering css files
	 */
	function bi_cleancache_css() {
		global $post;
		wp_register_style( 'bi_cleancache_dtime_css', plugins_url( '/css/jquery.datetimepicker.css', __FILE__ ) );
		wp_enqueue_style( 'bi_cleancache_dtime_css' );
	}
	/**
	 * function used to call clean cache function
	 */
	function bi_run_cleancache() {
		if(class_exists("autoptimizeCache")){
			autoptimizeCache::clearall();
		}
		if ( function_exists('w3tc_pgcache_flush') ) {
			w3tc_pgcache_flush();
		}
	}
	/**
	 * function is used to show notice message
	 */
	function bi_show_notice(){
		?>
<div class="error notice">
	<p>
		<?php _e( 'Autoptimize Plugin is not active, active it first to make this plugin work.', 'my_plugin_textdomain' ); ?>
	</p>
</div>
<?php
	}
	/**
	 * Function used to add new interval for cron
	 * @param Integer $schedules
	 * @return multitype:NULL
	 */
	function add_new_intervals($schedules){
		$blog_id = get_current_blog_id();
		// add weekly and monthly intervals
		$schedules['bicustomtime'] = array(
				'interval' => get_blog_option($blog_id, 'bi_cron_interval'),
				'display' => __('[BI] Custom Time')
		);
		return $schedules;
	}
	/**
	 * This function show admin page form to save settings of this plugin
	 */
	function bi_cleancache_page(){
		$blog_id = get_current_blog_id();
		if(isset($_POST['bi_cron_interval'])){
			if(isset($_POST['clean_cache'])){
				if(class_exists("autoptimizeCache")){
					autoptimizeCache::clearall();
					if ( function_exists('w3tc_pgcache_flush') ) {
						w3tc_pgcache_flush();
					}
				}
			}elseif($_POST['del_cron']){
				if(isset($_POST['cleancache'])){
					if(class_exists("autoptimizeCache")){
						autoptimizeCache::clearall();
						if ( function_exists('w3tc_pgcache_flush') ) {
							w3tc_pgcache_flush();
						}
					}
				}
				update_blog_option( $blog_id, 'bi_cron_interval',"");
				update_blog_option( $blog_id, 'bi_cron_datetime', "");
				wp_clear_scheduled_hook(BI_CRON_EVENT_NAME);
			}else{
				update_blog_option($blog_id, 'bi_cron_interval',sanitize_text_field($_POST['bi_cron_interval']));
				update_blog_option( $blog_id, 'bi_cron_datetime', sanitize_text_field($_POST['bi_cron_datetime']));
				wp_clear_scheduled_hook(BI_CRON_EVENT_NAME);
				$time = time();
				if($_POST['bi_cron_datetime']){
					$time = strtotime($_POST['bi_cron_datetime']);
				}
				wp_schedule_event( $time, 'bicustomtime', BI_CRON_EVENT_NAME);
			}
		}
		?>
<div class="wrap">
	<h2>BI Clean Cache Settings</h2>
	<form method="post" action="#">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Run cron from <strong>(UTC time)</strong>
				</th>
				<td><input type="text" name="bi_cron_datetime" id="bi_cron_datetime"
					value="<?php echo get_blog_option($blog_id, 'bi_cron_datetime'); ?>" />
					Current time : <?php print date("d-m-Y H:i:s");?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Cron interval <small>(in seconds)</small>
				</th>
				<td><input type="text" name="bi_cron_interval"
					value="<?php echo get_blog_option($blog_id, 'bi_cron_interval'); ?>" />
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" value="Save Changes"
				class="button button-primary" id="submit" name="submit">&nbsp;&nbsp;
			<input type="submit" value="Clean Cache"
				class="button button-primary" id="clean_cache" name="clean_cache"
				style="margin-left: 70px;"> <input type="submit" value="Delete Cron"
				class="button button-danger" id="del_cron" name="del_cron"
				onclick="if( !confirm('Are you sure that you want to delete cron?')){event.preventDefault();}">
		</p>
	</form>
</div>
<script>
		jQuery(document).ready(function() {
			jQuery('#bi_cron_datetime').datetimepicker({format:'d-m-Y H:i:s',minDate:0,step:15});
		}); 
		</script>
<?php
	}
	/**
	 * This function is used to add option to admin nav bar
	 */
	function bi_cleancache_pageinit() {
		add_menu_page('BI Clean Cache', 'BI Clean Cache', 'manage_options', 'bi-clean-cache', array('bi_cleancache_aow3tc', 'bi_cleancache_page'),'',10);
	}
}

//if autoptimize eplugin not install then show notice
if(!class_exists("autoptimizeCache")){
	add_action( 'admin_notices', array('bi_cleancache_aow3tc', 'bi_show_notice') );
}

//Hook will call during plug-in activation
register_activation_hook( __FILE__, array('bi_cleancache_aow3tc', 'bi_cleancache_init_system'));
register_deactivation_hook(__FILE__, array('bi_cleancache_aow3tc', 'bi_cleancache_deactivate'));

add_filter( 'cron_schedules', array('bi_cleancache_aow3tc', 'add_new_intervals'));

add_action( 'admin_enqueue_scripts', array( 'bi_cleancache_aow3tc', 'bi_cleancache_css') );
add_action( 'admin_enqueue_scripts', array( 'bi_cleancache_aow3tc', 'bi_cleancache_js') );

//add menu option in admin navigation
add_action( 'admin_menu', array( 'bi_cleancache_aow3tc', 'bi_cleancache_pageinit') );
add_action( BI_CRON_EVENT_NAME, array('bi_cleancache_aow3tc', 'bi_run_cleancache'));

?>