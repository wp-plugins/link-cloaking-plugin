=== Link Cloaking Plugin ===
Contributors: whiteshadow
Tags: posts, formatting, html, css, code, cloaking
Requires at least: 2.8
Tested up to: 3.2
Stable tag: 1.8.4

Automatically cloak all (or only selected) links in your posts and pages.

== Description ==

**What Is "Cloaking"**
This plugin will "cloak" external links so they look like they're pointing to your own domain, e.g.  "http://yourdomain.com/blog/more/Link_Text_Here/12/34". You can set your own prefix instead of "more". The links are only rewritten when being displayed to a visitor - the plugin will not modify the actual text of your posts as saved in WordPress database. The cloaked links will redirect seamlessly to the original URL and will work in all browsers (they don't rely on JavaScript).

**Which Links Are Cloaked**
You can choose whether to cloak all links (the default) or only those you mark with the `<!--cloak-->` tag. There is an "Exceptions" list where you can put the domains you don't want cloaked under any conditions (your blog domain is added as an exception automatically). You can enabled/disable link cloaking for either/or posts and pages globally. Relative URLs will never be cloaked. A relative URL is basically one that doesn't contain a domain name, instead pointing to a file or folder on the current site.

**Static Link Cloaking**
You can define "static" cloaked links that are not bound to any post or page. They also don't have any numbers tacked on the end, so they look "cleaner". You can add static cloaked links in the *Tools -> Cloaked Links* tab.

**Notes**
Plugin's options can be set under *Settings -> Link Cloaking*. And make sure you read the installation instructions - there are some additional steps in addition to the common upload-activate routine.

== Installation ==

To do a new installation of the plugin, please follow these steps

1. Download the link-cloaking-plugin.zip file to your local machine.
1. Unzip the file 
1. Upload `link-cloaking-plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. (Optional) Go to *Settings -> Link Cloaking* and modify the default settings to your liking.
1. Go to *Settings -> Permalinks* and click *Save Changes*. This ensures the plugin's link redirection code is added to WordPress .htaccess file. **Important:** The code has to be *at the top* of .htaccess. Usually this should be automatic. If not, you will need to move it there, or the cloaked links won't work.

To upgrade your installation

1. De-activate the plugin
1. Get and upload the new files (do steps 1. - 3. from "new installation" instructions)
1. Reactivate the plugin. Your settings should have been retained from the previous version.
1. It is recommended to update permalinks again (see Step 6. above).

== Changelog ==

= 1.8.4 =
* Fixed "Add link" not working on hosts access to .php files in /wp-content/ is forbidden.
* Added a basic "Settings" icon to the Settings -> Link Cloaking page.
* Minor wording changes on the settings page.

= 1.8.2 =
* Updated the installation instructions to use the correct menu titles for newer versions of WP.
* WP 3.0.1 compatibility.

= 1.8.1 =
* Fixed static links throwing a 404 when WP is installed in a custom directory.
* Required WP version increased to 2.8 or later.

= 1.8 =
* Fixed a mysterious problem that could make it impossible to manually add links that begin with "http://".

= 1.7 =
* Alternate row colors in the link table.
* Cleaned up some JavaScript.
* When an attempt to add a link fails, the plugin should now display an error message instead of just failing silently.
* WP 3.0 compatibility.

= 1.6 =
* Fixed a bug where cloaked links wouldn't work when WP was installed in a different directory (as opposed to the blog's root dir.).

= 1.5 =
* First release on WordPress.org
* Switched from Prototype to jQuery
* Added a link to the premium version to the plugin's settings page.
* Made the .htaccess updates automatic. You no longer need to re-save your permalinks to make the cloaked links work.
* Minimum compatible version is now 2.6. Tested up to WP 3.0-alpha.
* Removed some pre-2.6 compatibility code. Everyone should upgrade.

