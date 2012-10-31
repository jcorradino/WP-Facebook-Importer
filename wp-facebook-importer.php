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

// Set your own app here to generate an app token, or just use mine.
// Can be found on your apps page at https://developers.facebook.com/apps
define("FACEBOOK_APP_ID", "");
define("FACEBOOK_APP_SECRET", "");

// Set your own API token here if you would like to override the one I use (if you don't want to use your ID and Secret above)

if (get_option("facebook_api_token") == "" && FACEBOOK_APP_ID != "" && FACEBOOK_APP_SECRET != "") {
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/oauth/access_token?client_id='.FACEBOOK_APP_ID.'&client_secret='.FACEBOOK_APP_SECRET.'&redirect_uri=imyourdeveloper.com&grant_type=client_credentials');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
	$token = str_replace("access_token=", "", curl_exec($ch));
	define("FACEBOOK_APP_TOKEN", $token);
	add_option("facebook_api_token", $token);
} else {
	define("FACEBOOK_APP_TOKEN", "413167725414537|SYI8M2Q788ar_fwjaHU2thqeTLQ");
}

// Include child files
$files = scandir('includes');
includeFiles($files);
foreach ($files as $include) {
	if ($include != '.' && $include != '..') { // ignores self and parent directory
		if (is_dir($include)) { // if a directory, re-run function to get directory contents
			$files = scandir($include);
			$newPath = $currentPath . $include . '/';
			includeFiles($files, $newPath);
		} else {
			if (strstr($include, '.php') && !strstr($include, '.beta.')) { // only grab .php files
				include($currentPath.$include);
			}
		}
	}
}

if (is_admin()) {
	facebookImporterAdmin::init();
}
facebookImporter::init();