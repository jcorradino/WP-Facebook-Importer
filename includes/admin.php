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
		unset($submenu['edit.php?post_type=facebook_gallery'][10]);
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
		
		if ($this == "") {
			$admin = new facebookImporterAdmin();
		} else {
			$admin = $this;
		}
		
		$nonce = $_REQUEST['_nonce'];
		if (wp_verify_nonce($nonce, 'resync-fb-galleries') && $_GET['resync'] == "true") {
			set_transient( 'resync-fb-galleries', 'true', 60*20 );
			$response = "done";
			ignore_user_abort(true);
			header("Connection: close");
			header("Content-Length: " . mb_strlen($response));
			flush();
			$fql->galleries("true");
			$admin->syncGalleries();
			ignore_user_abort(false);
			echo $response;
		} else if ($_GET['check'] == "resync-fb-galleries") {
			if (get_transient("resync-fb-galleries") != "true") {
				echo "done";
				exit();
			}
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
		if ($this == "") {
			$admin = new facebookImporterAdmin();
		} else {
			$admin = $this;
		}
		
		global $fql;
		
		$options = get_option('facebook_gallery_options');
		if ($_POST['facebook_profile_field'] != "" && $options['facebook_id'] != $_POST['facebook_profile_field']) {
			$id = $fql->lookup_user_id($_POST['facebook_profile_field']);
		}
		
		if ($_POST['selectedGalleries'] != $options['facebook_gallery_selections_field']) {
			$admin->new_gallery(array_diff((array)$_POST['selectedGalleries'], (array)$options['facebook_gallery_selections_field']));
			$admin->delete_gallery(array_diff((array)$options['facebook_gallery_selections_field'], (array)$_POST['selectedGalleries']));
		}
		
		//$admin->syncGalleries();
		
		return array(
			"facebook_id" => $id,
			"facebook_name" => $_POST['facebook_profile_field'],
			"facebook_wall_field" => $_POST['facebook_wall_field'],
			"facebook_gallery_selections_field" => $_POST['selectedGalleries']
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
		wp_register_style( 'plugin-style', WP_PLUGIN_URL."/WP-Facebook-Importer/importer.css");
        wp_enqueue_style( 'plugin-style' );
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
				<form action="options.php" method="post" id="facebookGalleryForm">
					<p>Use this page to configure sync data from facebook, set update timeframes, wall content filters, select galleries to use, and manually update all data.</p>
					<p><input name="Submit" type="submit" class="facebookGallerySubmit" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
					<?php settings_fields('facebook_gallery_options'); ?>
					<?php do_settings_sections('facebook_sync'); ?>
					<p><input name="Submit" type="submit" class="facebookGallerySubmit" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
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
		if (get_transient("resync-fb-galleries") == "true") {
			$message = " <img src='/wp-content/plugins/WP-Facebook-Importer/loading.gif' width='11' /> Resyncing <small> - Page can be reloaded or closed and process will run in background</small>";
		}
		echo "Select galleries you would like to display on your site.";
		echo '<p><a href="'.site_url().'/wp-admin/options-general.php?page=facebook_sync&resync=true&_nonce='.$nonce.'" class="button" id="ajaxButton">Click to force refresh gallery data</a> <span id="resyncLoaderStatus" rel="resync-fb-galleries">'.$message.'</span></p>';
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
		foreach ((array)$fql->galleries() as $gallery) {
			if ($gallery['image'] == "")
				continue;
				
			echo '
				<li class="gallerySelector">
					<div class="checkboxSelector">
						<input type="checkbox" name="selectedGalleries[]" value="'.$gallery['aid'].'"'.
							(in_array($gallery['aid'], (array)$options['facebook_gallery_selections_field']) ? ' checked="checked"' : '')
						.'>
					</div>
					<div class="gallerySelectorItem">
						<img class="left" src="'.$gallery['image'].'" />
						<h4>'.$gallery['name'].' <small>('.$gallery['size']._n(" image", " images", $gallery['size']).')</small></h4>
						<p>'.$gallery['description'].'</p>
					</div>
				</li>
			';
		}
		echo '</ul>';
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
	
	/**
	 * Adds a new gallery
	 *
	 * @param $data [string|array] - gallery id(s) to be imported
	 *
	 * @author Jason Corradino
	 *
	 */
	function new_gallery($data) {
		global $fql;
		if (ALLOW_EXECUTION_TIME_OVERWRITE) {
			$current_max = ini_get('max_execution_time');
			ini_set('max_execution_time', 300);
		}
		if (!empty($data)) {
			$source = "photo";
			$args = array(
				"columns" => array("pid", "aid", "link", "caption", "images"),
				'comparisons' => array(),
				'noTrue'
			);
			foreach((array)$data as $galleryID) {
				array_push($args['comparisons'], array("aid", $galleryID, "equals", "or"));
			}
			$images = $fql->run_query($fql->generate_query($source, $args));
			$new = array();
			foreach ($images->data as $image) {
				$data = array(
					'term' => $image->aid,
					"slug" => $image->pid,
					"link" => $image->link,
					"caption" => $image->caption,
					"image" => $image->images[0]->source
				);
				$this->add_image($data);
			}
		}
		if (ALLOW_EXECUTION_TIME_OVERWRITE) {
			ini_set('max_execution_time', $current_max);
		}
	}
	
	/**
	 * Deletes a gallery
	 *
	 * @param $data [string|array] - gallery id(s) to be deleted
	 *
	 * @author Jason Corradino
	 *
	 */
	function delete_gallery($data) {
		if (!empty($data)) {
			foreach((array)$data as $galleryID) {
				$args = array(
					'numberposts' => "-1",
					"facebook_gallery_category" => $galleryID,
					"post_type" => "facebook_gallery"
				);
				$trash = get_posts( $args );
				foreach ((array)$trash as $post) {
					$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post->ID ); 
					$attachments = get_posts($args);
					if ($attachments) {
						foreach ( $attachments as $attachment ) {
							wp_delete_attachment($attachment->ID, true);
						}
					}
					wp_delete_post($post->ID, true);
				}
				$term = get_term_by("slug", $galleryID, "facebook_gallery", ARRAY_A);
				wp_delete_term( $term["term_id"], "facebook_gallery");
			}
		}
	}
	
	/**
	 * Adds an image
	 *
	 * @param $data [array] - array of image data to add, including slug, aid, pid, caption, and image
	 *
	 * @author Jason Corradino
	 *
	 */
	function add_image($data) {
		global $fql;
		$galleries = $fql->galleries();
		foreach($galleries as $gallery) {
			if ($data['term'] == $gallery["aid"]) {
				$this_gallery = $gallery;
			}
		}
		if (!($term = get_term_by("name", $this_gallery['name'], "facebook_gallery", ARRAY_A))) {
			$term = wp_insert_term($this_gallery['name'], "facebook_gallery", array("description" => $this_gallery['description'], "slug" => $this_gallery['aid']));
		}
		$postID = wp_insert_post(array(
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_name' => "fbImage".$data['slug'],
			'post_status' => 'publish',
			'post_title' => $data['caption'],
			'post_type' => 'facebook_gallery',
			'post_content' => $data['caption'],
			'post_author' => 1
		));
		$term_reference = ($term["slug"] != "") ? $term["slug"] : $term["term_id"];
		wp_set_object_terms($postID, $term_reference, "facebook_gallery");
		update_post_meta($postID, "pid", $data['slug']);
		$this->download_image($data['image'], $postID);
	}
	
	/**
	 * Fetch image from facebook, attach to post
	 *
	 * @param $url [string] - url of the image to fetch
	 * @param $post [string] - post id to attach to
	 *
	 * @return [string] - error (otherwise, no return)
	 *
	 * @author Jason Corradino
	 *
	 */
	function download_image($url, $post) {
		$tmp = download_url($url);
		
		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);
		$file_array['name'] = basename($matches[0]);
		$file_array['tmp_name'] = $tmp;
		
		if ( is_wp_error( $tmp ) ) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
		}
		
		$id = media_handle_sideload( $file_array, $post, $desc );
		
		
		if ( is_wp_error($id) ) {
			@unlink($file_array['tmp_name']);
			return $id;
		}
	}
	
	
	/**
	 * Sync data with facebook
	 *
	 * @author Jason Corradino
	 *
	 */
	function syncGalleries() {
		global $fql;
		
		$categories = get_terms( 'facebook_gallery', 'hide_empty=0' );
		$source = "photo";
		$args = array(
			"columns" => array("pid", "aid", "link", "caption", "images"),
			'comparisons' => array(),
			'noTrue'
		);
		$tax_lookup = array();
		if($categories != "") {
			foreach ((array)$categories as $category) {
				array_push($args['comparisons'], array("aid", $category->slug, "equals", "or"));
				array_push($tax_lookup, $category->slug);
			}
			$images = $fql->run_query($fql->generate_query($source, $args));
			foreach ($images->data as $image) {
				$facebook[$image->aid][$image->pid] = array(
					'term' => $image->aid,
					"slug" => $image->pid,
					"link" => $image->link,
					"caption" => $image->caption,
					"image" => $image->images[0]->source
				);
			}
			foreach ($tax_lookup as $tax) {
				$args = array(
					'numberposts' => "-1",
					"facebook_gallery_category" => $tax,
					"post_type" => "facebook_gallery"
				);
				$post_data = get_posts( $args );
				foreach ($post_data as $post) {
					$wordpress[$tax][str_replace("fbimage", "", $post->post_name)] = $post;
				}
			}


			foreach ($facebook as $gallery) {
				foreach ($gallery as $image) {
					if($wordpress[$image["term"]][$image["slug"]] == "") {
						$this->add_image($image);
					} else {
						if ($wordpress[$image["term"]][$image["slug"]]->post_content != $image['caption']) {
							$post = array();
							$post['ID'] = $wordpress[$image["term"]][$image["slug"]]->ID;
							$post['post_content'] = $image['caption'];
							$post['post_title'] = $image['caption'];
							wp_update_post($post);
						}
					}
				}
			}

			foreach($wordpress as $aid => $post) {
				foreach($post as $pid => $image) {
					if($facebook[$aid][$pid] == "") {
						$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $image->ID ); 
						$attachments = get_posts($args);
						if ($attachments) {
							foreach ( $attachments as $attachment ) {
								wp_delete_attachment($attachment->ID, true);
							}
						}
						wp_delete_post($image->ID, true);
					}
				}
			}
		}
	}
	
	/**
	 * Sync data with facebook
	 *
	 * @author Jason Corradino
	 *
	 */
	function syncGalleries() {
		global $fql;
		
		$categories = get_terms( 'facebook_gallery', 'hide_empty=0' );
		$source = "photo";
		$args = array(
			"columns" => array("pid", "aid", "link", "caption", "images"),
			'comparisons' => array(),
			'noTrue'
		);
		$tax_lookup = array();
		if($categories != "") {
			foreach ((array)$categories as $category) {
				array_push($args['comparisons'], array("aid", $category->slug, "equals", "or"));
				array_push($tax_lookup, $category->slug);
			}
			$images = $fql->run_query($fql->generate_query($source, $args));
			foreach ($images->data as $image) {
				$facebook[$image->aid][$image->pid] = array(
					'term' => $image->aid,
					"slug" => $image->pid,
					"link" => $image->link,
					"caption" => $image->caption,
					"image" => $image->images[0]->source
				);
			}
			foreach ($tax_lookup as $tax) {
				$args = array(
					'numberposts' => "-1",
					"facebook_gallery_category" => $tax,
					"post_type" => "facebook_gallery"
				);
				$post_data = get_posts( $args );
				foreach ($post_data as $post) {
					$wordpress[$tax][str_replace("fbimage", "", $post->post_name)] = $post;
				}
			}


			foreach ($facebook as $gallery) {
				foreach ($gallery as $image) {
					if($wordpress[$image["term"]][$image["slug"]] == "") {
						$this->add_image($image);
					} else {
						if ($wordpress[$image["term"]][$image["slug"]]->post_content != $image['caption']) {
							$post = array();
							$post['ID'] = $wordpress[$image["term"]][$image["slug"]]->ID;
							$post['post_content'] = $image['caption'];
							$post['post_title'] = $image['caption'];
							wp_update_post($post);
						}
					}
				}
			}

			foreach($wordpress as $aid => $post) {
				foreach($post as $pid => $image) {
					if($facebook[$aid][$pid] == "") {
						$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $image->ID ); 
						$attachments = get_posts($args);
						if ($attachments) {
							foreach ( $attachments as $attachment ) {
								wp_delete_attachment($attachment->ID, true);
							}
						}
						wp_delete_post($image->ID, true);
					}
				}
			}
		}
	}
}