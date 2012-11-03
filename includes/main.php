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

class facebookImporterMain {
	
	/**
	 * Initializes Facebook Importer functionality
	 *
	 * @author Jason Corradino
	 *
	 */
	function init() {
		add_action("init", array(__CLASS__, "createType"));
	}
	
	/**
	 * Creates custom post type and taxonomy, called in action init
	 *
	 * @author Jason Corradino
	 *
	 */
	function createType() {
		register_taxonomy(
			"facebook_gallery",
			array("facebook_images"),
			array(
				'hierarchical' => false,
				'show_ui' => true,
				'query_var' => 'facebook_gallery',
				'update_count_callback' => '_update_post_term_count',
				'labels' => array(
					'name'          => __( 'Galleries' ),
					'singular_name' => __( 'Gallery' )
				),
				'rewrite' => array( 
					'slug' => 'facebook-gallery', 
					'with_front' => true
				)
			)
		);
		
		register_post_type( 'facebook_images',
			array(
				'labels' => array(
					'name' => __( 'Facebook Sync' ),
					'all_items' => __( 'Facebook Images' ),
					'singular_name' => __( 'Facebook Image' ),
					'add_new' => __( '' ),
					'edit_item' => __( 'Edit Facebook Image Data' ),
					'new_item' => __( 'New Facebook Image' ),
					'view_item' => __( 'View Facebook Image' ),
					'search_items' => __( 'Search Facebook Image Data' ),
					'not_found' => __( 'No Facebook images found' )
				),
				'supports' => array("title", "custom-fields"),
				'public' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => true,
                'description' => 'Images from facebook albums',
				'menu_position' => 54,
				'has_archive' => true,
				'menu_icon' => plugins_url('WP-Facebook-Importer/icon.png'),
				'taxonomies' => array("facebook_gallery"),
                'rewrite' => array('slug' => 'facebook-images', 'with_front' => true, 'feeds' => false)
			)
		);
	}
}