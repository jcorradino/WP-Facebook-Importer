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

class facebook_importer_build_query {
	function build($args="") {
		if (empty($args))
			return false;
			
		if ($args->source == "stream") {
			
		} else if ($args->source == "galleries") {
			
		} else if ($args->source == "gallery") {
			
		} else if ($args->source == "photo") {
			
		}
		
		return $query;
	}
	
}