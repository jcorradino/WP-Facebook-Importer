<?php
/*
Plugin Name: WP Facebook Importer
Plugin URI: http://imyourdeveloper.com/facebook-gallery
Description: Plugin will fetch data from a facebook page, saving galleries as a new custom post type and wall data as a serialized array
Version: 1.0
Author: Jason Corradino
Author URI: http://imyourdeveloper.com
License: GPL2

Copyright 2012  Jason Corradino  (email : Jason@ididntbreak.it)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class facebookImporterAdmin {
	
	/**
	 * Initializes Facebook Importer admin functionality
	 *
	 * @author Jason Corradino
	 *
	 */
	function init() {
		global $fql;
		$fql = new fql();
		
		//add_action('admin_enqueue_scripts', array(__CLASS__, "enqueueScript"));
		add_action('admin_head', array(__CLASS__, "admin_header"));
		add_action('admin_enqueue_scripts', array(__CLASS__, "enqueue_admin_scripts"));
		add_action('admin_menu', array(__CLASS__, "setup_pages"));
		add_action('admin_init', array(__CLASS__, "plugin_init"));
	}
	
	/**
	 * Creates the "Wall Content" menu item and removes "add new" photo
	 *
	 * @author Jason Corradino
	 *
	 */
	function setup_pages() {
		global $submenu;
		unset($submenu['edit.php?post_type=facebook_images'][10]);
		add_submenu_page("edit.php?post_type=facebook_images", "Wall Content", "Wall Content", 'manage_options', "wall-content", array(__CLASS__, "setup_wall_page"));
		add_options_page('Facebook Sync', 'Facebook Sync', 'manage_options', 'facebook_sync', array(__CLASS__, "plugin_options"));
	}
	
	/**
	 * Initializes the plugin settings pages and fields on admin_init
	 *
	 * @author Jason Corradino
	 *
	 */
	function plugin_init() {
		global $fql;
		
		$nonce = $_REQUEST['_nonce'];
		if (wp_verify_nonce($nonce, 'resync-fb-galleries') && $_GET['resync'] == "true") {
			$response = "done";
			ignore_user_abort(true);
			header("Connection: close");
			header("Content-Length: " . mb_strlen($response));
			flush();
			$fql->galleries("true");
			ignore_user_abort(false);
			echo $response;
		}
		
		register_setting( 'facebook_gallery_options', 'facebook_gallery_options', array(__CLASS__, "validate_fields"));
		add_settings_section('facebook_profile_address', 'Profile Setup', array(__CLASS__, "wall_profile_address_text"), 'facebook_sync');
		add_settings_field('facebook_profile_address_field', 'Facebook ID/Profile Name', array(__CLASS__, "wall_profile_address_textbox"), 'facebook_sync', 'facebook_profile_address');
		add_settings_section('facebook_wall_filter', 'Facebook Wall Filter', array(__CLASS__, "wall_filter_text"), 'facebook_sync');
		add_settings_field('facebook_wall_field', 'Filter content by', array(__CLASS__, "wall_filter_textbox"), 'facebook_sync', 'facebook_wall_filter');
		add_settings_section('facebook_gallery_selections', 'Facebook Galleries', array(__CLASS__, "gallery_selection_text"), 'facebook_sync');
		add_settings_field('facebook_gallery_selections_field', 'Select Galleries', array(__CLASS__, "gallery_selection_selector"), 'facebook_sync', 'facebook_gallery_selections');
	}
	
	/**
	 * Validates and saves option information
	 *
	 * @author Jason Corradino
	 *
	 */
	function validate_fields() {
		global $fql;
		
		$options = get_option('facebook_gallery_options');
		if ($_POST['facebook_profile_field'] != "" && $options['facebook_id'] != $_POST['facebook_profile_field']) {
			$id = $fql->lookup_user_id($_POST['facebook_profile_field']);
		}
		
		return array(
			"facebook_id" => $id,
			"facebook_name" => $_POST['facebook_profile_field'],
			"facebook_wall_field" => $_POST['facebook_wall_field'],
			"facebook_gallery_selections_field" => $_POST['facebook_gallery_selections_field']
		);
	}
	
	/**
	 * Creates the "Wall Content" view page
	 *
	 * @author Jason Corradino
	 *
	 */
	function setup_wall_page() {
		$nonce= wp_create_nonce('resync-wall');
		echo '
			<div class="wrap">
				<div id="icon-edit" class="icon32 icon32-posts-facebook_images">
					<br>
				</div>
			
				<h2>Facebook Wall Content</h2>
			
				<p>Below is the content that has been synced from Facebook based on your preferences.</p>
				<p><a href="/wp-admin/edit.php?post_type=facebook_images&page=wall-content&resync=true&_nonce='.$nonce.'" class="button" target="_blank">Click to resync</a></p>
			</div>
		';
	}
	
	/**
	 * Sets up the header image for the pages next to the page header
	 *
	 * @author Jason Corradino
	 *
	 */
	function admin_header() {
		global $post_type;
		echo '<style>';
		if (($_GET['post_type'] == 'facebook_images') || ($post_type == 'facebook_images') || $_GET['page'] == "facebook_sync") :
			echo '#icon-edit { background:transparent url('.WP_PLUGIN_URL.'/WP-Facebook-Importer/largeicon.png) no-repeat; }';
		endif;
		echo '</style>';
	}
	
	/**
	 * Sets up scripts and styles in the header
	 *
	 * @author Jason Corradino
	 *
	 */
	function enqueue_admin_scripts() {
		wp_enqueue_script( 'wp_facebook_importer_admin', WP_PLUGIN_URL.'/WP-Facebook-Importer/importer.jquery.js' );
	}
	
	/**
	 * Sets up options page
	 *
	 * @author Jason Corradino
	 *
	 */
	function plugin_options() {
		?>
			<div class="wrap">
				<div id="icon-edit" class="icon32 icon32-posts-facebook_images">
					<br>
				</div>
				<h2>Facebook Sync</h2>
				<form action="options.php" method="post">
					<p>Use this page to configure sync data from facebook, set update timeframes, wall content filters, select galleries to use, and manually update all data.</p>
					<p><input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
					<?php settings_fields('facebook_gallery_options'); ?>
					<?php do_settings_sections('facebook_sync'); ?>
					<p><input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
				</form>
			</div>
		<?php
	}
	
	/**
	 * Sets text to display on options page when selecting gallery
	 *
	 * @author Jason Corradino
	 *
	 */
	function gallery_selection_text() {
		$nonce= wp_create_nonce('resync-fb-galleries');
		echo "Select galleries you would like to display on your site.";
		echo '<p><a href="'.site_url().'/wp-admin/options-general.php?page=facebook_sync&resync=true&_nonce='.$nonce.'" class="button" id="ajaxButton">Click to force refresh gallery data</a> <span id="resyncLoaderStatus"></span></p>';
		return true;
	}
	
	/**
	 * Displays the facebook gallery
	 *
	 * @author Jason Corradino
	 *
	 */
	function gallery_selection_selector() {
		global $fql;
		$options = get_option('facebook_gallery_options');
		echo '<ul>';
		foreach ($fql->galleries() as $gallery) {
			if ($gallery['image'] == "")
				continue;
				
			echo '
				<style>
					.gallerySelector {width:100%; float: left; border-bottom: 1px solid #cccccc; margin: 15px 0;}
					.checkboxSelector {width: 3%; float: left;}
					.gallerySelectorItem {width: 96%; float: right;}
					.gallerySelectorItem img {padding: 0 10px 10px 0;}
				</style>
				<li class="gallerySelector">
					<div class="checkboxSelector">
						<input type="checkbox" name="selectedGalleries" value="'.$gallery['aid'].'">
					</div>
					<div class="gallerySelectorItem">
						<img style="float:left" src="'.$gallery['image'].'" />
						<h4>'.$gallery['name'].'</h4>
						<p>'.$gallery['description'].'</p>
					</div>
				</li>
			';
		}
		echo '</ul>';
		echo '<input type="text" name="facebook_gallery_selections_field" id="gallerySelectorBox" value="'.$options['facebook_gallery_selections_field'].'" />';
	}
	
	/**
	 * Sets text to display on options page above wall filter
	 *
	 * @author Jason Corradino
	 *
	 */
	function wall_filter_text() {
		echo "<p>Set a filter to sort wall data by.  Filter is case insensitive and can be left blank if no filter is desired.</p>";
		return true;
	}
	
	/**
	 * Sets text to display on options page next to wall filter
	 *
	 * @author Jason Corradino
	 *
	 */
	function wall_filter_textbox() {
		$options = get_option('facebook_gallery_options');
		echo '<input type="text" name="facebook_wall_field" id="wallFilterBox" value="'.$options['facebook_wall_field'].'" />';
	}
	
	/**
	 * Sets text to display on options page above profile selection
	 *
	 * @author Jason Corradino
	 *
	 */
	function wall_profile_address_text() {
		echo "<p>Use any publicly accessible facebook profile username or ID.  This plugin does not yet support private profiles.</p>";
		return true;
	}
	
	/**
	 * Sets text to display on options page next to profile selection
	 *
	 * @author Jason Corradino
	 *
	 */
	function wall_profile_address_textbox() {
		$options = get_option('facebook_gallery_options');
		echo '<input type="text" name="facebook_profile_field" id="profileIDBox" value="'.$options['facebook_name'].'" />';
	}
}