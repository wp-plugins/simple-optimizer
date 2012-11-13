<?php

class Simple_Optimizer_Admin extends Simple_Optimizer {
	/**
	 * Error messages to diplay
	 *
	 * @var array
	 */
	private $_messages = array();
	

	
	/**
	 * Class constructor
	 *
	 */
	public function __construct() {
		$this->_plugin_dir   = DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), null, plugin_basename(__FILE__));
		$this->_settings_url = 'options-general.php?page=' . plugin_basename(__FILE__);
		
		$allowed_options = array(
			
		);
		
		// set  options
		if(array_key_exists('option_name', $_GET) && array_key_exists('option_value', $_GET)
			&& in_array($_GET['option_name'], $allowed_options)) {
			update_option($_GET['option_name'], $_GET['option_value']);
			
			header("Location: " . $this->_settings_url);
			die();	
			
		} else {
			// register installer function
			register_activation_hook(SO_LOADER, array(&$this, 'activateSimpleOptimizer'));
		
			// add plugin "Settings" action on plugin list
			add_action('plugin_action_links_' . plugin_basename(SO_LOADER), array(&$this, 'add_plugin_actions'));
			
			// add links for plugin help, donations,...
			add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);
			
			// push options page link, when generating admin menu
			add_action('admin_menu', array(&$this, 'adminMenu'));
	
			
		}
	}
	
	

		
	/**
	 * Add "Settings" action on installed plugin list
	 */
	public function add_plugin_actions($links) {
		array_unshift($links, '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __('Settings') . '</a>');
		
		return $links;
	}
	
	/**
	 * Add links on installed plugin list
	 */
	public function add_plugin_links($links, $file) {
		if($file == plugin_basename(SO_LOADER)) {
			$links[] = '<a href="http://MyWebsiteAdvisor.com/">Visit Us Online</a>';
		}
		
		return $links;
	}
	
	/**
	 * Add menu entry for Simple Optimizer settings and attach style and script include methods
	 */
	public function adminMenu() {		
		// add option in admin menu, for settings
		$plugin_page = add_options_page('Simple Optimizer Plugin Options', 'Simple Optimizer', 8, __FILE__, array(&$this, 'optionsPage'));

		add_action('admin_print_styles-' . $plugin_page,     array(&$this, 'installStyles'));
	}
	
	/**
	 * Include styles used by Simple Optimizer Plugin
	 */
	public function installStyles() {
		//wp_enqueue_style('simple-optimizer', WP_PLUGIN_URL . $this->_plugin_dir . 'style.css');
	}
	







	function HtmlPrintBoxHeader($id, $title, $right = false) {
		
		?>
		<div id="<?php echo $id; ?>" class="postbox">
			<h3 class="hndle"><span><?php echo $title ?></span></h3>
			<div class="inside">
		<?php
		
		
	}
	
	function HtmlPrintBoxFooter( $right = false) {
		?>
			</div>
		</div>
		<?php
		
	}
	
	
	
	
	public function performWordPressOptimization(){
	
		global $wpdb;
		
		$optimization_queries = array(
			'delete_spam_comments' => "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'",
			'delete_unapproved_comments' => "DELETE FROM $wpdb->comments WHERE comment_approved = '0'",
			'delete_revisions' => "DELETE FROM $wpdb->posts WHERE post_type = 'revision'",
			'delete_auto_drafts' => "DELETE FROM $wpdb->posts WHERE post_status = 'auto-draft'",
			'delete_transient_options' => "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'"
		);
		
		$plugin_options = $this->get_option('simple_optimizer_plugin'); 
		$wp_optimization_methods = $plugin_options['wp_optimization_methods'];
	
		$queries = $optimization_queries;
	
		foreach($queries as $method => $query){
			if($wp_optimization_methods[$method] === "true"){
			
				echo "<p>Performing Optimization: " . $method."<br>";
				$result = $wpdb->query($query);
				echo "$result items deleted.</p>";
						
			}
		}
	}
	
	
	public function performDatabaseCheck(){
	
		$debug_enabled = $this->get_option('debug_enabled');

		echo "Checking Database...<br>";
		
		$local_query = 'SHOW TABLE STATUS FROM `'. DB_NAME.'`';
		$result = mysql_query($local_query);
		if (mysql_num_rows($result)){
			
			while ($row = mysql_fetch_array($result)){
			
				$check_query = "CHECK TABLE ".$row['Name'];
				$check_result = mysql_query($check_query);
				if (mysql_num_rows($check_result)){
					while($rrow = mysql_fetch_assoc($check_result)){
						if( $debug_enabled == "true"){
							echo "Table: " . $row['Name'] ." ". $rrow['Msg_text'];
							echo "<br>";
						}
					}
				}
				
				$initial_table_size += $table_size; 
				
			}
			
			echo "Done!<br>";
			
		}
	
		echo "<br>";
	
	}



	public function performDatabaseRepair(){
	
		$debug_enabled = $this->get_option('debug_enabled');

		echo "Repairing Database...<br>";
		
		$local_query = 'SHOW TABLE STATUS FROM `'. DB_NAME.'`';
		$result = mysql_query($local_query);
		if (mysql_num_rows($result)){
			
			while ($row = mysql_fetch_array($result)){
			
				$check_query = "REPAIR TABLE ".$row['Name'];
				$check_result = mysql_query($check_query);
				if (mysql_num_rows($check_result)){
					while($rrow = mysql_fetch_assoc($check_result)){
						if( $debug_enabled == "true"){
							echo "Table: " . $row['Name'] ." ". $rrow['Msg_text'];
							echo "<br>";
						}
					}
				}
				
			}
			
			echo "Done!<br>";
			
		}
	
		echo "<br>";
	
	}
	
	
	public function performDatabaseOptimization(){
		
		$initial_table_size = 0;
		$final_table_size = 0;
		
		$debug_enabled = $this->get_option('debug_enabled');
		
		
		
		echo "Optimizing Database...<br>";
		
		$local_query = 'SHOW TABLE STATUS FROM `'. DB_NAME.'`';
		$result = mysql_query($local_query);
		if (mysql_num_rows($result)){
			
			while ($row = mysql_fetch_array($result)){
				//var_dump($row);
				
				$table_size = ($row[ "Data_length" ] + $row[ "Index_length" ]) / 1024;
				
				$optimize_query = "OPTIMIZE TABLE ".$row['Name'];
				if(mysql_query($optimize_query)){
				
					if( $debug_enabled == "true"){
						echo "Table: " . $row['Name'] . " optimized!";
						echo "<br>";
					}
				}
				
				$initial_table_size += $table_size; 
				
			}
			
			echo "Done!<br>";
			
		}
		
		
		
		
		$local_query = 'SHOW TABLE STATUS FROM `'. DB_NAME.'`';
		$result = mysql_query($local_query);
		if (mysql_num_rows($result)){
			while ($row = mysql_fetch_array($result)){
				$table_size = ($row[ "Data_length" ] + $row[ "Index_length" ]) / 1024;
				$final_table_size += $table_size;
			}
		}
		
		
		
		echo "<br>";
		echo "Initial DB Size: " . number_format($initial_table_size, 2) . " KB<br>";
		echo "Final DB Size: " . number_format($final_table_size, 2) . " KB<br>";
		
		$space_saved = $initial_table_size - $final_table_size;
		$opt_pctg = 100 * ($space_saved / $initial_table_size);
		echo "Space Saved: " . number_format($space_saved,2) . " KB  (" .  number_format($opt_pctg, 2) . "%)<br>";
		echo "<br>";
	
	}
	



	
	
	



	
	
	
	
	/**
	 * Display options page
	 */
	public function optionsPage() {
		// if user clicked "Save Changes" save them
		if(isset($_POST['Submit'])) {
			foreach($this->_options as $option => $value) {
				if(array_key_exists($option, $_POST)) {
					update_option($option, $_POST[$option]);
				} else {
					update_option($option, $value);
				}
			}

			$this->_messages['updated'][] = 'Options updated!';
		}


		
		
	
		foreach($this->_messages as $namespace => $messages) {
			foreach($messages as $message) {
?>
<div class="<?php echo $namespace; ?>">
	<p>
		<strong><?php echo $message; ?></strong>
	</p>
</div>
<?php
			}
		}
		
		
			
			
				
?>

	
									  
<script type="text/javascript">var wpurl = "<?php bloginfo('wpurl'); ?>";</script>

<style>

.fb_edge_widget_with_comment {
	position: absolute;
	top: 0px;
	right: 200px;
}

</style>

<div  style="height:20px; vertical-align:top; width:50%; float:right; text-align:right; margin-top:5px; padding-right:16px; position:relative;">

	<div id="fb-root"></div>
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=253053091425708";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));</script>
	
	<div class="fb-like" data-href="http://www.facebook.com/MyWebsiteAdvisor" data-send="true" data-layout="button_count" data-width="450" data-show-faces="false"></div>
	
	
	<a href="https://twitter.com/MWebsiteAdvisor" class="twitter-follow-button" data-show-count="false"  >Follow @MWebsiteAdvisor</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>


</div>

<div class="wrap" id="sm_div">

	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>Simple Optimizer Plugin Settings</h2>
	
		
		
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">
			
<?php $this->HtmlPrintBoxHeader('pl_diag',__('Plugin Diagnostic Check','diagnostic'),true); ?>

				<?php
				
				echo "<p>Server OS: ".PHP_OS."</p>";
				
				echo "<p>Required PHP Version: 5.0+<br>";
				echo "Current PHP Version: " . phpversion() . "</p>";
			
							
				echo "<p>Memory Use: " . number_format(memory_get_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
				
				echo "<p>Peak Memory Use: " . number_format(memory_get_peak_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
				
				$lav = sys_getloadavg();
				echo "<p>Server Load Average: ".$lav[0].", ".$lav[1].", ".$lav[2]."</p>";
				
				?>

<?php $this->HtmlPrintBoxFooter(true); ?>



<?php $this->HtmlPrintBoxHeader('pl_resources',__('Plugin Resources','resources'),true); ?>

	<p><a href='http://mywebsiteadvisor.com/wordpress-plugins/simple-optimizer/' target='_blank'>Plugin Homepage</a></p>
	<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Plugin Support</a></p>
	<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Suggest a Feature</a></p>
	<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Contact Us</a></p>
	
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('pl_upgrade',__('Plugin Upgrades','upgrade'),true); ?>
	
	<p>
	<a href='http://mywebsiteadvisor.com/products-page/premium-wordpress-plugin/simple-optimizer-ultra/'  target='_blank'>Upgrade to Simple Optimizer Ultra!</a><br />
	<br />
	<b>Features:</b><br />
	-Automatic Optimizer Function<br />
	-Email Notifications<br />
	-Daily, Weekly or Monthly Schedule<br />
	</p>
	
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('more_plugins',__('More Plugins','more_plugins'),true); ?>
	
	<p><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
	<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
	<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/'  target='_blank'>Free Plugins on Our Website!</a></p>	
				
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('follow',__('Follow MyWebsiteAdvisor','follow'),true); ?>

	<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
	<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
	<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
	<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>	
	
<?php $this->HtmlPrintBoxFooter(true); ?>


</div>
</div>



	<div class="has-sidebar sm-padded" >			
		<div id="post-body-content" class="has-sidebar-content">
			<div class="meta-box-sortabless">
	
	
	
			<?php $this->HtmlPrintBoxHeader('wm_dir',__('Simple Optimizer Plugin Settings','optimizer-settings'),false); ?>	
			
				<form method='post'>
				
					<table width="100%" >
					<tr valign="top">
					<td>
					<?php $this->HtmlPrintBoxHeader('wm_dir',__('Optimize WordPress','optimizer-settings'),false); ?>
					<div style="height:250px;">
				
						<?php global $wpdb; ?>
						<?php $plugin_options = $this->get_option('simple_optimizer_plugin'); ?>
						<?php $wp_optimization_methods = $plugin_options['wp_optimization_methods']; ?>
						

						<?php $selected=($wp_optimization_methods['delete_spam_comments'] === 'true') ? "checked='checked'" : ""; ?>
						<p><input name='simple_optimizer_plugin[wp_optimization_methods][delete_spam_comments]' type='checkbox' value='true' <?php echo $selected; ?> /> Delete Spam Comments <br />
						Currently <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'"); ?> Spam Comments</p>
						
						<?php $selected=($wp_optimization_methods['delete_unapproved_comments'] === 'true') ? "checked='checked'" : ""; ?>
						<p><input name='simple_optimizer_plugin[wp_optimization_methods][delete_unapproved_comments]' type='checkbox' value='true' <?php echo $selected; ?> /> Delete Unapproved Comments <br />
						Currently <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '0'"); ?> Unapproved Comments</p>
						
						<?php $selected=($wp_optimization_methods['delete_revisions'] === 'true') ? "checked='checked'" : ""; ?>
						<p><input name='simple_optimizer_plugin[wp_optimization_methods][delete_revisions]' type='checkbox' value='true' <?php echo $selected; ?> /> Delete Post Revisions <br />
						Currently <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'revision'"); ?> Revisions</p>
						
						<?php $selected=($wp_optimization_methods['delete_auto_drafts'] === 'true') ? "checked='checked'" : ""; ?>
						<p><input name='simple_optimizer_plugin[wp_optimization_methods][delete_auto_drafts]' type='checkbox' value='true' <?php echo $selected; ?> /> Delete Auto Drafts <br />
						Currently <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'auto-draft'"); ?> Drafts</p>
						
						<?php $selected=($wp_optimization_methods['delete_transient_options'] === 'true') ? "checked='checked'" : ""; ?>
						<p><input name='simple_optimizer_plugin[wp_optimization_methods][delete_transient_options]' type='checkbox' value='true' <?php echo $selected; ?> /> Delete Transient Options (Advanced)<br />
						Currently <?php echo $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_transient_%'"); ?> Transient Options</p>
						
						
						
						
					</div>
					<?php $this->HtmlPrintBoxFooter(true); ?>
					</td>
					<td>
					<?php $this->HtmlPrintBoxHeader('wm_dir',__('Optimize Database','optimizer-settings'),false); ?>
					<div style="height:250px;">
					
					
						<p><b>Check Database</b></p>
						<?php $plugin_options = $this->get_option('simple_optimizer_plugin'); ?>
						<?php $check_db_enabled = $plugin_options['check_db_enabled']; ?>
						<?php if($check_db_enabled === "true"){$selected = "checked='checked'";}else{$selected="";}; ?>
						<p><input name='simple_optimizer_plugin[check_db_enabled]' type='checkbox' value='true' <?php echo $selected; ?> /> Database Check </p>
	
						<p><b>Repair Database</b></p>
						<?php $plugin_options = $this->get_option('simple_optimizer_plugin'); ?>
						<?php $repair_db_enabled = $plugin_options['repair_db_enabled']; ?>
						<?php if($repair_db_enabled === "true"){$selected = "checked='checked'";}else{$selected="";}; ?>
						<p><input name='simple_optimizer_plugin[repair_db_enabled]' type='checkbox' value='true' <?php echo $selected; ?> /> Database Repair  (Advanced)</p>
	
			
						<p><b>Optimize Database</b></p>
						<?php $plugin_options = $this->get_option('simple_optimizer_plugin'); ?>
						<?php $optimize_db_enabled = $plugin_options['optimize_db_enabled']; ?>
						<?php if($optimize_db_enabled === "true"){$selected = "checked='checked'";}else{$selected="";}; ?>
						<p><input name='simple_optimizer_plugin[optimize_db_enabled]' type='checkbox' value='true' <?php echo $selected; ?> /> Database Optimization </p>

					</div>
					<?php $this->HtmlPrintBoxFooter(true); ?>
					</td>
					<td>
					
					</table>
					
					
					
					
	
					<p><b>Display Optimizer Details?</b> (Useful for debugging!)</p>
					<?php $plugin_options = $this->get_option('simple_optimizer_plugin'); ?>
					<?php $show_details = $plugin_options['show_details']; ?>
					<?php if($show_details === "true"){$selected = "checked='checked'";}else{$selected="";}; ?>
					<p><input name='simple_optimizer_plugin[show_details]' type='checkbox' value='true' <?php echo $selected; ?> /> Optimizer Details Enabled</p>
	
				
				
					
					<input type="submit" name='Submit' value='Save Settings' />
				
				</form>
			
			
			<?php $this->HtmlPrintBoxFooter(false); ?>
			
			
			
						
			<?php $this->HtmlPrintBoxHeader('wm_dir',__('Optimize System','optimize-system'),false); ?>					
				
		
				

			<?php
			echo "<form method='post'>";
			echo "<input type='hidden' name='simple-optimizer' value='$base_dir'>";
			echo "<input type='submit' value='Optimize System'>";
			echo "</form>";
			
			
			
			
			
			if(array_key_exists('simple-optimizer', $_POST)) {
			
				set_time_limit(0);
			
				echo "<div style='overflow:scroll; height:250px;'>";
				
				
				$plugin_options = $this->get_option('simple_optimizer_plugin');
				
				if(count($plugin_options['wp_optimization_methods']) > 0  ){

					$this->performWordPressOptimization();

				}
				
				if($plugin_options['check_db_enabled'] === "true"){

					$this->performDatabaseCheck();

				}
				
				if($plugin_options['repair_db_enabled'] === "true"){

					$this->performDatabaseRepair();

				}
				
				if($plugin_options['optimize_db_enabled'] === "true"){

					$this->performDatabaseOptimization();

				}
				

				
				echo "</div>";
				
			}
			
			
			
			?>
			
			<?php $this->HtmlPrintBoxFooter(false); ?>
			
			
			
			
			
		
</div></div></div></div>

</div>


<?php
	}
	
}

?>