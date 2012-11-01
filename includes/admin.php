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
		//add_action('admin_enqueue_scripts', array(__CLASS__, "enqueueScript"));
		add_action('admin_head', array(__CLASS__, "adminHeader"));
		add_action('admin_menu', array(__CLASS__, "setupWallPageMenuItem"));
	}
	
	/**
	 * Creates the "Wall Content" menu item and removes "add new" photo
	 *
	 * @author Jason Corradino
	 *
	 */
	function setupWallPageMenuItem() {
		global $submenu;
		unset($submenu['edit.php?post_type=facebook_images'][10]);
		add_submenu_page("edit.php?post_type=facebook_images", "Wall Content", "Wall Content", 'manage_options', "wall-content", array(__CLASS__, "setupWallPage"));
	}
	
	/**
	 * Creates the "Wall Content" view page
	 *
	 * @author Jason Corradino
	 *
	 */
	function setupWallPage() {
		$nonce= wp_create_nonce('resync-wall');
		echo '
			<div class="wrap">
				<div id="icon-edit" class="icon32 icon32-posts-facebook_images">
					<br>
				</div>
			
				<h2>Facebook Wall Content</h2>
			</div>
			
			<p>Below is the content that has been synced from Facebook based on your preferences.</p>
			<p><a href="/wp-admin/edit.php?post_type=facebook_images&page=wall-content&resync=true&_nonce='.$nonce.'" class="button" target="_blank">Click to resync</a></p>
		';
	}
	
	/**
	 * Sets up the header image for the pages next to the page header
	 *
	 * @author Jason Corradino
	 *
	 */
	function adminHeader() {
		global $post_type;
		echo '<style>';
		if (($_GET['post_type'] == 'facebook_images') || ($post_type == 'facebook_images')) :
			echo '#icon-edit { background:transparent url('.WP_PLUGIN_URL.'/WP-Facebook-Importer/largeicon.png) no-repeat; }';
		endif;
		echo '</style>';
	}
}