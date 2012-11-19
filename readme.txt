=== WP-Facebook-Importer  ===
Contributors: jcorradino
Donate link: 
Tags: Facebook, Facebook Gallery, FB Gallery, Facebook Wall, Facebook Feed, Facebook Stream, FQL
Requires at least: 3.3
Tested up to: 3.4
Stable tag: 1.02
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Download and Sync facebook albums and wall data.  Wall can be synced with or without a filter

== Description ==

Use facebook's FQL API to sync all selected photo-galleries and wall data from a particular facebook page. Allows filters for the wall, to only select data with specific words, if necessary.

== Installation ==

1. Upload directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use "Facebook Sync" options page to select the page to sync up with, and select the albums to download.  Use <?php display_wall() ?> to display an unordered list of the last nth number of posts.

== Frequently asked questions ==
none so far


== Screenshots ==

1. The options page.  Type in the facebook ID or Profile Name of the page you wish to sync.  Once you choose, you can select a gallery to download.
2. The galleries that I selected, these are saved as a custom taxonomy, and retains the album name, description, and slug (if available)
3. These are the images actually synced, if there is a description set, it will set that as the post content and the title (as there is no other data point attached to an image).  The attached image will be downloaded to wordpress and attached to the post.
4. The wall data that was downloaded.  This likes to be a bit finicky on a new load, so if an error shows up, click "click to resync" to fetch the data.

== Changelog ==
1.02 - general release


== Upgrade notice ==
none so far


== ToDo's ==

- Set up on a background-running WP-Cron
- Push all plugin interaction into ajax calls, allowing the user the ability to navigate around the site without being forced to wait for it to finish.
- Clean up the facebook images page to show not just the title and date, but also show the attached taxonomy and a thumbnail of the image.