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

class fql {
	
	var $id;
	
	/**
	 * Processes arguments to generate the FQL where parameters based on the comparison and method variables
	 *
	 * Example of $args:
	 *
	 * $args = array(
	 *		'comparisons' => array(
	 *			array(
	 *				"and",
	 *				array("group1a", "group1a", "equals"),
	 *				array("group1b", "group1b", "like")
	 *			),
	 *			array(
	 *				"or",
	 *				array("group2a", "group2a", "equals"),
	 *				array("group2b", "group2b", "equals")
	 *			),
	 *			array("nogroup", "nogroup", "equals", "and")
	 *		),
	 *		'columns' => array(
	 *			"message",
	 *			"post_id"
	 *		),
	 *		'limit' => 250
	 *	);
	 *
	 * @param $source [string] - table being used
	 * @param $args [array|string] - Arguments to process
	 *
	 * @return [string] FQL query
	 *
	 * @author Jason Corradino
	 *
	 */
	function generate_query($source = "", $args = "") {
		$this->id = get_option("facebook_user_id");
		$this->id = 103817796377295;
		
		if (!$args['columns']) {
			$args['columns'] = "post_id, message, action_links, attachment, impressions, comments, likes, permalink, tagged_ids, description, type";
		} else if (sizeof($args['columns']) > 0) {
			$args['columns'] = implode(", ", $args['columns']);
		} else if (!is_string($args['columns'])) {
			return false;
		}
		
		if (!$source) {
			return false;
		}
		
		if ($source == "page") {
			$where = "source_id = {$this->id} ";
		} else if (!in_array("noTrue", $args)) {
			$where = "1=1 ";
		}
		
		$where .= $this->process_args($source, $args);
		
		if (substr($where, 0, 3) == "OR ") {
			$where = substr($where, 3);
		} else if (substr($where, 0, 4) == "AND ") {
			$where = substr($where, 4);
		}
		
		$length = strlen($where);
		
		if (substr($where, $length-3, 3) == "OR ") {
			$where = substr($where, 0, $length-3);
		} else if (substr($where, $length-4, 4) == "AND ") {
			$where = substr($where, 0, $length-4);
		}
		
		$where = str_replace("OR AND", "OR", $where);
		
		$limit = ($args["limit"] != "") ? $args["limit"] : "200";
		
		return "SELECT {$args['columns']} FROM $source WHERE $where limit $limit";
	}
	
	/**
	 * Cycle through arguments, process argument groups and single arguments, generate while clauses
	 *
	 * @param $args [array] - all comparisons to be run in the query,
	 *
	 * @return [string] processed args, while clauses for this query
	 *
	 * @author Jason Corradino
	 *
	 */
	function process_args($source, $args) { // group query
		
		foreach($args["comparisons"] as $comparison) {
			if (is_string($comparison[0]) && strtolower($comparison[0]) == "or" && $source == "page") {
				$orQuery .= "OR source_id = {$this->id} ";
			}
			for($i = 0; $i < sizeof($comparison); $i++) {
				if (is_array($comparison[$i])) {
					if (is_string($comparison[0]) && strtolower($comparison[0]) == "or") {
						$orQuery .= "AND " . $this->process_subargs($comparison[$i]);
					} else {
						$andQuery .= "AND " . $this->process_subargs($comparison[$i]);
					}
				}
			}
			if ($source == "page") {
				$preOr = "OR source_id = {$this->id} AND ";
			} else {
				$preOr = "OR ";
			}
			if (is_string($comparison[3]) && strtolower($comparison[3]) == "or" || is_string($comparison[0]) && strtolower($comparison[0]) == "or") {
				$orQuery .= $preOr . $this->process_subargs($comparison);
			} else if (is_string($comparison[3]) && strtolower($comparison[3]) == "and") {
				$andQuery .= "AND " . $this->process_subargs($comparison);
			}
		}
		return $andQuery . $orQuery;
	}
	
	/**
	 * Cycle through sub-arguments to generate the individual while clauses of the query
	 *
	 * @param $args [array] - An individual comparison in the query,
	 *
	 * @return [string] Individual parts of the query
	 *
	 * @author Jason Corradino
	 *
	 */
	function process_subargs($args) {
		if ($args[2] == "equals") {
			return "{$args[0]} = '{$args[1]}' ";
		} else if ($args[2] == "like") {
			return "strpos({$args[0]}, '{$args[1]}') >= 0 ";
		}
	}
	
	/**
	 * Lookup user ID based on username
	 *
	 * @param $user [string] - username to lookup
	 *
	 * @author Jason Corradino
	 *
	 */
	function lookup_user_id($user) {
		if (is_int($user)) {
			return $user;
		} else {
			$query = "SELECT page_id FROM page WHERE username = '$user'";
			
			$userIdJson = $this->run_query($query);
			return $userIdJson->data[0]->page_id;
		}
	}
	
	/**
	 * Lookup galleries on page
	 *
	 * @author Jason Corradino
	 *
	 */
	function galleries($resync = "") {
		$options = get_option("facebook_gallery_options");
		$galleryData = get_option("facebook_gallery_data");
		$this->id = $options['facebook_id'];
		if ($this->id == "") {
			echo "<h3>Please enter a facebook page id to select a gallery</h3>";
			return true;
		} else if ($galleryData != "" && $resync != "true") {
			return $galleryData;
		} else {
			$galleries = array();
			$source = "album";
			$args = array(
				"columns" => array("aid", "name", "cover_pid", "name", "created", "description", "location", "size"),
				"comparisons" => array( array("owner", $this->id, "equals", "and") )
			);
			$query = $this->generate_query($source, $args);
			$data = $this->run_query($query);
			foreach ($data->data as $gallery) {
				$args = array(
					"columns" => array("src_small"),
					"comparisons" => array( array("pid", $gallery->cover_pid, "equals", "and") )
				);
				$query = $this->generate_query("photo", $args);
				$photo_data = $this->run_query($query);
				$gallery_info = array(
					"aid" => $gallery->aid,
					"name" => $gallery->name,
					"created" => $gallery->created,
					"description" => $gallery->description,
					"image" => $photo_data->data[0]->src_small,
					"size" => $gallery->size
				);
				array_push($galleries, $gallery_info);
			}
			update_option( "facebook_gallery_data", $galleries );
			delete_transient("resync-fb-galleries");
			return $galleries;
		}
	}
	
	/**
	 * Sync up wall data
	 *
	 * @author Jason Corradino
	 *
	 */
	function sync_wall($filter = "") {
		$options = get_option('facebook_gallery_options');
		
		$options['facebook_wall_field'] = ($filter != "") ? $filter : $options['facebook_wall_field'];
		
		$source = "stream";
		
		$args = array(
			"columns" => array("post_id", "message", "action_links", "attachment", "impressions", "comments", "likes", "permalink", "tagged_ids", "description", "type"),
			'limit' => 50,
			'noTrue'
		);
		
		if ($options["facebook_wall_field"] != "") {
			$args['comparisons'] = array(
				array(
					"or",
					array("source_id", $options["facebook_id"], "equals"),
					array("message", ucfirst($options['facebook_wall_field']), "like")
				),
				array(
					"or",
					array("source_id", $options["facebook_id"], "equals"),
					array("message", strtolower($options['facebook_wall_field']), "like")
				)
			);
		} else {
			$args['comparisons'] = array(
				array(array("source_id", $options["facebook_id"], "equals"), "and")
			);
		}
		
		$query = $this->generate_query($source, $args);
		$feed = $this->run_query($query);
		
		$return = array();
		
		foreach ($feed->data as $item) {
			$save = array(
				"id" => $item->post_id,
				"message" => $item->message,
				"image" => $item->attachment->media[0]->src,
				"likes" => $item->likes->count,
				"permalink" => $item->permalink
			);
			
			if($save["permalink"] != "") {
				array_push($return, $save);
			}
		}
		update_option( "facebook_wall_data", $return );
	}
	
	/**
	 * Connect to Facebook Graph API and return JSON
	 *
	 * @param $query [string] - query to run
	 *
	 * @return JSON of data retrieved from Facebook
	 *
	 * @author Jason Corradino
	 *
	 */
	function run_query($query) {
		$query = urlencode($query);
		$url = "https://graph.facebook.com/fql?q=$query&access_token=".FACEBOOK_APP_TOKEN;
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		$raw = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
		curl_close($ch);
		$json = json_decode($raw);
		return $json;
	}
	
}