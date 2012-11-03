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
	 * @param $args [array|string] - Arguments to process
	 * @param $comparison [string] - Like (%) or equals (=) comparison
	 * @param $method [string] - The method to use, "and" or "or"
	 *
	 * @return returning string containing "where" comparisons
	 *
	 * @author Jason Corradino
	 *
	 */
	function process_args($args, $comparison, $method) {
		if (sizeof($args) > 0) {
			foreach($args as $arg) {
				if (strtoupper($arg[2]) == $method) {
					if ($where != "") {
						$where .= $method." ";
					}
					if ($method == "OR") {
						$where .= "source_id = {$this->id} AND ";
					}
					if ($comparison == "=") {
						$where .= "{$arg[0]} = '{$arg[1]}' ";
					} else if ($comparison == "%") {
						$where .= "strpos({$arg[0]}, '{$arg[1]}') ";
					}
				}
			}
		} else if (!empty($args)) {
			if ($args[2] == $method) {
				if ($where != "") {
					$where .= $method;
				}
				if ($method == "OR") {
					$where .= "source_id = {$this->id} AND ";
				}
				if ($comparison == "=") {
					$where .= "{$args[0]} = '{$args[1]}' ";
				} else if ($comparison == "%") {
					$where .= "strpos({$args[0]}, '{$args[1]}') ";
				}
			}
		}
		return $where;
	}
	
	/**
	 * Build the FQL query based on the data passed
	 *
	 * @param $source [string] - The Facebook API table to query
	 * @param $args [object] - Arguments: columns (what to select), equals (search using "="), and like (search using something similar to SQL's LIKE "%foo%")
	 *
	 * @return FQL query
	 *
	 * @author Jason Corradino
	 *
	 */
	function build_query($source = "", $args = "") {
		$this->id = get_option("facebook_user_id");
		$this->id = 103817796377295;
		
		if (!$args->columns) {
			$args->columns = "post_id, message, action_links, attachment, impressions, comments, likes, permalink, tagged_ids, description, type";
		} else if (sizeof($args->columns) > 0) {
			$args->columns = implode(", ", $args->columns);
		} else if (!is_string($args->columns)) {
			return false;
		}
		
		if (!$source) {
			return false;
		}
		
		$orWhere = $this->process_args($args->equals, "=", "OR");
		$orWhere = ($orWhere != "") ? $orWhere . $this->process_args($args->like, "%", "OR") : $this->process_args($args->like, "%", "OR");
		
		$andWhere = $this->process_args($args->equals, "=", "AND");
		$andWhere = ($andWhere != "") ? $andWhere . $this->process_args($args->like, "%", "AND") : $this->process_args($args->like, "%", "AND");
		
		$where = "" . (($orWhere != "" || $andWhere != "") ? "" : "1=1 ") . $andWhere . (($orWhere != "") ? "OR " . $orWhere : "");

		$query = "SELECT {$args->columns} FROM $source WHERE source_id = {$this->id} AND $where";
			
		return $query;
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
		
		} else {
			$query = "SELECT page_id FROM page WHERE username = '$user'";
			
			$userIdJson = $self->run_query($query);
			print_r($userIdJson);
			exit();
		}
	}
	
	/**
	 * Lookup galleries on page
	 *
	 * @author Jason Corradino
	 *
	 */
	function galleries() {
		echo true;
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

//fql::lookup_user_id("playtimeanytime");