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
	
	function build_query($source = "", $args = "") {
		$id = get_option("facebook_user_id");
		$id = 12345;
		
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
		
		if (sizeof($args->equals) > 0) {
			foreach ($args->equals as $equals) {
				if ($where != "") {
					$where .= (strtoupper($equals[2]) == "OR") ? "OR " : "AND ";
				}
				
				if (strtoupper($equals[2]) == "OR") {
					$where .= "source_id = $id AND ";
				}
				
				$where .= "{$equals[0]} = '{$equals[1]}' ";
			}
		} else if (!empty($args->equals)) {
			if ($where != "") {
				$where .= (strtoupper($args->equals[2]) == "OR") ? "OR " : "AND ";
			}
			
			if (strtoupper($args->equals[2]) == "OR") {
				$where .= "source_id = $id AND ";
			}
			
			$where .= "{$args->equals[0]} = '{$args->equals[1]}' ";
		}
		
		if (sizeof($args->like) > 0) {
			foreach ($args->like as $like) {
				if ($where != "") {
					$where .= (strtoupper($equals[2]) == "OR") ? "OR " : "AND ";
				}
				
				if (strtoupper($equals[2]) == "OR") {
					$where .= "source_id = $id AND ";
				}
				
				$where .= "strpos({$like[0]}, '{$like[1]}') ";
			}
		} else if (!empty($args->like)) {
			if ($where != "") {
				$where .= (strtoupper($equals[2]) == "OR") ? "OR " : "AND ";
			}
			
			if (strtoupper($equals[2]) == "OR") {
				$where .= "source_id = $id AND ";
			}
			
			$where .= "strpos({$args->like[0]}, '{$args->like[1]}') ";
		}

		$query = "SELECT {$args->columns} FROM $source WHERE source_id = $id AND $where";
			
		return $query;
	}
	
	function lookup_user_id($user) {
		if (is_int($user)) {
		
		} else {
			$query = "SELECT page_id FROM page WHERE username = '$user'";
			
			$userIdJson = $self->run_query($query);
			print_r($userIdJson);
			exit();
		}
	}
	
	function galleries() {
		echo true;
	}
	
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
$args->columns = array("test1", "test2");
$args->equals = array(
	array(
		"testColumn1", "value1"
	),
	array(
		"testColumn2", "value2"
	),
	array(
		"testColumn3", "value3", "or"
	)
);
$fql = new fql();
echo $fql->build_query("testTable", $args);
exit();


//fql::lookup_user_id("playtimeanytime");