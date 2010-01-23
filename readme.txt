=== Link Cloaking Plugin ===
Contributors: whiteshadow
Tags: posts, formatting, html, css, code, cloaking
Requires at least: 2.6
Tested up to: 2.9.1
Stable tag: 1.5

Automatically cloak all (or only selected) links in your posts and pages.

== Description ==

**What Is "Cloaking"**
This plugin will "cloak" external links so they look like they're pointing to your own domain, e.g.  "http://yourdomain.com/blog/more/Link_Text_Here/12/34". You can set your own prefix instead of "more". The links are only rewritten when being displayed to a visitor - the plugin will not modify the actual text of your posts as saved in WordPress database. The cloaked links will redirect seamlessly to the original URL and will work in all browsers (they don't rely on JavaScript).

**Which Links Are Cloaked**
You can choose whether to cloak all links (the default) or only those you mark with the `<!--cloak-->` tag. There is an "Exceptions" list where you can put the domains you don't want cloaked under any conditions (your blog domain is added as an exception automatically). You can enabled/disable link cloaking for either/or posts and pages globally. Relative URLs will never be cloaked. A relative URL is basically one that doesn't contain a domain name, instead pointing to a file/folder on the current site.

**Static Link Cloaking**
You can define "static" cloaked links that are not bound to any post or page. They also don't have any numbers tacked on the end, so they look "cleaner". You can add static cloaked links in the *Manage -> Cloaked Links* tab.

**Notes**
Plugin's options can be set under *Options -> Link Cloaking*. And make sure you read the installation instructions - there are some additional steps in addition to the common upload-activate routine.

== Installation ==

To do a new installation of the plugin, please follow these steps

1. Download the link-cloaking-plugin.zip file to your local machine.
1. Unzip the file 
1. Upload `link-cloaking-plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. (Optional) Go to *Options -> Link Cloaking* and modify the default settings to your liking.
1. Go to *Options -> Permalinks* and click *Update Permalinks*. This ensures the plugin's link redirection code is added to WordPress .htaccess file. **Important:** The code has to be *at the top* of .htaccess. Usually this should be automatic. If not, you will need to move it there, or the cloaked links won't work.

To upgrade your installation

1. De-activate the plugin
1. Get and upload the new files (do steps 1. - 3. from "new installation" instructions)
1. Reactivate the plugin. Your settings should have been retained from the previous version.
1. It is recommended to update permalinks again (see Step 6. above).
