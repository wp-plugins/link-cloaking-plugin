<?php
/*
Plugin Name: Link Cloaking Plugin
Plugin URI: http://w-shadow.com/blog/2007/07/28/link-cloaking-plugin-for-wordpress/
Description: Automatically cloaks outgoing links in your posts and pages. You can also add static cloaked links manually.
Version: 1.8.4
Author: Janis Elsts
Author URI: http://w-shadow.com/
*/

if (!class_exists('ws_wordpress_link_cloaker')) {

class ws_wordpress_link_cloaker {	
 var $link_number = 0;
 var $options_name = 'wplc_cloaker_options';
 var $options;
 var $myfile = '';
 var $myfolder = '';
 var $post_id = 0;
 var $linkstable_name = 'cloaked_links';
 var $redirector = '';
 
 var $url_pattern = 
		'@
		(<a[\s]+[^>]*href\s*=\s*)				# \1
			(?: "([^">]+)" | \'([^\'>]+)\' )	# \2 or \3 is URL
		([^<>]*>)								# \4
			(.*?)                               # \5 Anchor text 
		(</a>)                                  # \6
		@xsi';
 
 function ws_wordpress_link_cloaker(){
	global $wpdb; 
	
	$this->options = get_option($this->options_name);
	$this->myfile = str_replace('\\', '/',__FILE__);
	$this->myfile = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', $this->myfile);
	$this->myfolder = basename(dirname(__FILE__));
	
	//Figure out the URL of the redirector script
	$this->redirector = WP_PLUGIN_URL . '/' . $this->myfolder . '/wplc_redirector.php';
	
	$this->linkstable_name=$wpdb->prefix . "cloaked_links";
	
	add_action('activate_'.$this->myfile, array(&$this,'activation'));
	add_filter('the_content', array(&$this,'content_filter'), -200); //Run the cloaker before all other plugins or hooks
	add_filter('mod_rewrite_rules', array(&$this,'rewrite_rules'));
	add_action('admin_menu', array(&$this,'options_menu'));
	add_action('admin_print_scripts', array(&$this,'load_admin_scripts'));
	
	//AJAX handlers
	add_action('wp_ajax_wplc_add_link', array(&$this, 'ajax_add_link'));
	add_action('wp_ajax_wplc_delete_link', array(&$this, 'ajax_delete_link'));
 }


 function wplc_escapize($text){
	$text=strip_tags($text);
	$text=preg_replace('/[^a-z0-9_]+/i', '_', $text);
	
	if(strlen($text)<1) { $text='link'; };
	
	return $text;
 }

 function rewrite_link($matches){
	global $post;
	
	$this->link_number++;
	
	//URL is either the 2nd or 3rd subgroup, depending on quote style
	if ( !empty($matches[2]) ){
		$url = $matches[2];
		$quote = '"';
	} else {
		$url = $matches[3];
		$quote = "'";
	}
	$url = ltrim( $url );
	
	$parts = @parse_url($url);
	
	if(!$parts || !isset($parts['scheme']) || !isset($parts['host'])) return $matches[0];
	if( isset($this->options['exclusions']) &&
		array_search(strtolower($parts['host']), $this->options['exclusions'])!==false
	  ) return $matches[0];
	  
	//In selective mode we only cloak links that contain the <!-- cloak --> tag
	if( ( $this->options['mode']=='selective' ) && 
		( !preg_match('/<!--\s*cloak\s*-->/i', $matches[5]) ) 
	  )
	{
		return $matches[0];
	}
	
	//Generate the cloaked URL
	$url = $this->make_cloaked_url($matches[5], $post->ID, $this->link_number);
	
	//Build the new link tag
	$link = $matches[1].$quote.$url.$quote.$matches[4].$matches[5].$matches[6];
	
	if($this->options['nofollow']) {
		$link = str_replace('<a ','<a rel="nofollow" ', $link);
	}
	return $link;
 }
 
 function make_cloaked_url($link_name = '', $post_id = null, $link_num = 0){
	$base = get_option( 'home' );
	if ( $base == '' ) {
		$base = get_option( 'siteurl' );
	}
	
	$url = trailingslashit($base) . $this->options['prefix'] . '/'; 
	if ( !empty($link_name) ){
		$url .= $this->wplc_escapize($link_name) . '/';
		if ( !empty($post_id) ){
			$url .= intval($post_id) . '/';
			if ( !empty($link_num) ){
				$url .= intval($link_num);
			}
		}
	}
	
	return $url;
 }

 function content_filter($content){
	if(is_page()){
		if(!$this->options['is_page']) return $content;
	} else if(!$this->options['is_post']) {
		return $content;
	};
	
	$this->link_number=0;
	$content = preg_replace_callback($this->url_pattern, array(&$this,'rewrite_link'), $content);
	
	return $content;
 }

 function activation(){
	global $wpdb, $wp_rewrite;
	
	if(!is_array($this->options)){
		
		//by default, add the current domain name to exclusion list
		$parts=@parse_url(get_option('siteurl'));
		$exclusions=array();
		if($parts && isset($parts['host'])){
			$exclusions[]=$parts['host'];
		}
		
		//set default options
		$this->options=array(
			'exclusions' => $exclusions,	
			'prefix' => 'goto',
			'mode' => 'everything',
			'is_page' => 'checked',
			'is_post' => 'checked',
			'nofollow' => 'checked'
		);	
		
		update_option($this->options_name, $this->options);
	};
	
	$sql = "CREATE TABLE ".$this->linkstable_name." (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR( 150 ) NOT NULL ,
			url TEXT NOT NULL ,
			hits INT UNSIGNED NOT NULL,
			UNIQUE KEY name (name),
			PRIMARY KEY id (id)
		);";
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	//Force WP to regenerate the .htaccess file and insert the plugin's rules into it.  
	//Behind the scenes, $wp_rewrite->flush_rules() executes the mod_rewrite_rules filter, 
	//which calls the rewrite_rules() method, which generates our special rewrite rules.  
	$wp_rewrite->flush_rules();
 }
 
 function load_admin_scripts(){
		// The jQuery library is used for AJAX and minor UI manipulations.
		wp_enqueue_script( 'jquery' ); //This is probably redundant in modern WP versions
 }

 function rewrite_rules($rules){
	global $wp_rewrite;

	//$redirector='wp-content/plugins/'.basename(dirname(__FILE__)).'/wplc_redirector.php';
	$redirector = $this->redirector;

	$pattern = '^' . $this->options['prefix'].'/([^/]*)/([0-9]+)/([0-9]+)/?$';
	$replacement=$redirector.'?post_id=$2&link_num=$3&cloaked_url=$0';
	
	$pattern_static = '^' . $this->options['prefix'].'/([^/]+)[/]?$';
	$replacement_static=$redirector.'?name=$1&cloaked_url=$0';
	
	$myrules="\n# Link Cloaker Plugin BEGIN\n";
	$myrules.="<IfModule mod_rewrite.c>\n";
	$myrules.="RewriteEngine On\n";
	$myrules.="RewriteRule $pattern $replacement [L]\n";
	$myrules.="RewriteRule $pattern_static $replacement_static [L]\n";
	$myrules.="</IfModule>\n";
	$myrules.="# Link Cloaker Plugin ENDS\n\n";
	
	$rules = $myrules.$rules;
	
	return $rules;
 }

 function options_menu(){
	$settings_hook = add_options_page('Link Cloaking Settings', 'Link Cloaking', 'manage_options',
		'link-cloaking-plugin-options',array(&$this,'options_page'));
	$links_page_hook = add_management_page('View and edit cloaked links', 'Cloaked Links', 'manage_links',
			'link-cloaking-plugin-links',array(&$this, 'cloaked_links_page'));
 }
 
 function mytruncate($str, $max_length=50){
		if(strlen($str)<=$max_length) return $str;
		return (substr($str, 0, $max_length-3).'...');
 }
 
 function ajax_add_link(){
 	global $wpdb;
 	
 	if (!current_user_can('manage_links')) {
		die("Error: You can't do that. Access denied.");
	}
 	
 	/* save a link */
	$name = $_POST['name'];
	$url = $_POST['url'];
	
	$sql="SELECT count(*) FROM {$this->linkstable_name} WHERE name LIKE '".$wpdb->escape($name)."'";
	$link_exists=$wpdb->get_var($sql);
	if($link_exists){
		echo "Error : A link with this name already exists!";
	} else {
		$rez = $wpdb->query(
        	"INSERT INTO {$this->linkstable_name}(name, url) 
        	 VALUES('".$wpdb->escape($name)."', '".$wpdb->escape($url)."')"
        	);
		if ( $rez === false ){
			exit($wpdb->last_error);
		}
        echo "Saved.<!--insert_id:$wpdb->insert_id;-->";
	};
 }
 
 function ajax_delete_link(){
 	global $wpdb;
 	
 	if (!current_user_can('manage_links')) {
		die("Error: You can't do that. Access denied.");
	}
	
	/* delete a cloaked link */
	$id=intval($_POST['id']);
	$wpdb->query("DELETE FROM {$this->linkstable_name} WHERE id={$id} LIMIT 1");
	if ($wpdb->rows_affected>0){
		echo "Link deleted."; //probably
	} else {
		echo "Error: Failed to delete the link!";
	}
 }

 function options_page(){
 	global $wp_rewrite;
	
  if (isset($_GET['updated']) && ($_GET['updated'] == 'true')) {
	if(isset($_POST['Submit']) && current_user_can('manage_options')) {
		
		
		$new_prefix=$this->wplc_escapize(trim($_POST['prefix']));
		if(strlen($new_prefix)<1) $this->options['prefix']='goto';
		
		if($new_prefix != $this->options['prefix']){
			$this->options['prefix'] = $new_prefix;
			//Regenerate the rewrite rules to use the new prefix.
			$wp_rewrite->flush_rules();
		}
		
		$this->options['mode']=$_POST['mode'];
		$this->options['is_post']=isset($_POST['is_post'])?$_POST['is_post']:false;
		$this->options['is_page']=isset($_POST['is_page'])?$_POST['is_page']:false;
		$this->options['nofollow']=isset($_POST['nofollow'])?$_POST['nofollow']:false;
		
		$this->options['exclusions']=array_filter(preg_split('/[\s,\r\n]+/', $_POST['exclusions']));
		
		update_option($this->options_name,$this->options);
	}
	
}
?>
<div class="wrap">
	<?php 
	if (function_exists('screen_icon')) {
		screen_icon();
	} 
	?>
	<h2>Link Cloaking</h2>

<style>
#eclipse-cloaker-ad {
	position: absolute;
	right: 20px;
	
	background-color: white;
	padding: 1px 10px 10px 10px;
	border: 1px solid #ddd;
}

#eclipse-cloaker-ad ul {
	list-style: disc;
}
#eclipse-cloaker-ad li {
	margin-left: 28px;
	margin-right:  6px;
	
	list-style: none;
	list-style-position: outside;
	list-style-image: url('<?php echo plugins_url('tick.png', __FILE__); ?>');
}

#eclipse-cloaker-ad h3 {
	font-variant: small-caps;
}

#eclipse-cloaker-ad a {
	text-decoration: none;
}

</style>
<div id="eclipse-cloaker-ad">
<div>
	<a href="http://eclipsecloaker.com/?from=the-free-version" target="_blank" title="Get Eclipse Link Cloaker">
		<h3>Upgrade To Premium Version</h3>
	</a>
	<ul>
		<li>Cloak links in any part of the site</li>
		<li>Cloak links created with other plugins</li>
		<li>Turn keywords into links</li>
		<li>Use advanced cloaking techniques</li>
	</ul>
</div>
 
</div>

<form name="cloaking_options" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=link-cloaking-plugin-options&amp;updated=true"> 

<table class="form-table"> 

<tr valign="top"> 
<th scope="row">General:</th> 
<td>


<label for="is_post"><input type="checkbox" name="is_post" id="is_post"
<?php if($this->options['is_post']) echo ' checked' ?>/> Cloak links in posts</label>
<p>
<label for="is_page"><input type="checkbox" name="is_page" id="is_page"
<?php if($this->options['is_page']) echo ' checked' ?>/> Cloak links in pages</label>
</p>
<p>
<label for="nofollow"><input type="checkbox" name="nofollow" id="nofollow"
<?php if($this->options['nofollow']) echo ' checked' ?>/> Nofollow cloaked links</label>
</p>

</td> 
</tr> 

<tr valign="top"> 
<th scope="row">Cloaking Mode:</th> 
<td>

<p>
<label><input type="radio" name="mode" id="mode" value="everything"
<?php if($this->options['mode']=='everything') echo ' checked' ?>/> Cloak All Links</label><br/>
All external links will be cloaked.
</p>

<p>
<label><input type="radio" name="mode" id="mode" value="selective" 
<?php if($this->options['mode']=='selective') echo ' checked' ?>/> Selective Cloaking</label><br/>
Only links tagged with <code>&lt;!--cloak--&gt;</code> will be cloaked. Example : <br/>
<code>&lt;a href='http://domain.com/'&gt;&lt;!--cloak--&gt;Visit This Site&lt;/a&gt;</code>
</p>

</td> 
</tr> 

<tr valign="top"> 
<th scope="row">Link Prefix:</th> 
<td><input type='text' name='prefix' id='prefix' value='<?php echo $this->options['prefix']; ?>' size='25' />
<br />
Your cloaked links will look similar to this : 
<code><?php echo get_option('siteurl'); ?>/[prefix]/Link_Text_Here/12/34</code>
</td></tr> 



<tr valign="top"> 
<th scope="row">Exceptions:</th> 
<td>

<textarea name='exclusions' id='exclusions' cols='50' rows='6'>
<?php echo implode("\n", $this->options['exclusions']); ?>
</textarea>

<br/>List one domain per row. Links to these domains will never be cloaked (not even if tagged for cloaking).
Note that <code>www.domain.com</code> and <code>domain.com</code> are treated as separate domains.

</td> 
</tr> 

</table> 

<p class="submit"><input type="submit" name="Submit" class="button-primary" value="Save Changes"></p>
</form>
</div>

<?php 

 }
 
 function cloaked_links_page(){
		global $wpdb;
		$sql="SELECT count(*) FROM $this->linkstable_name";
		$cloaked_links=$wpdb->get_var($sql);
		
		?>
<div class="wrap">
<h2><?php
	echo "Static Cloaked Links";
?></h2>
<br style="clear:both;" />
<?php
	if (current_user_can('manage_links')) {
?>
<table class="optiontable"> 
	<tr valign="top"> 
	<th scope="row">Name</th> 
	<td><input type='text' id='wplc_name' style='width: 60%;'></td> 
	</tr>
	
	<tr valign="top"> 
	<th scope="row">URL</th> 
	<td><input type='text' id='wplc_url' style='width: 100%;'></td> 
	</tr>
	
	<tr valign="top"> 
	<td></td> 
	<td><input type='button' value=' Add ' onclick='saveCloakedLink(); return false;'>
		<div id='wplc_status' style='display: inline;'> &nbsp;</div>
	</td> 
	</tr> 
</table>

		<br/>
<?php
	} //Form for adding links
?>
		
<table class="widefat" id='wplc_links'>
	<thead>
	<tr>
	
	<th scope="col">Name</th>
	<th scope="col">Destination URL</th>
	<th scope="col">Hits</th>
	
	<th scope="col"><div style="text-align: center">Action</div></th>
	</tr>
	</thead>
	<tbody id="the-list">		
<?php
		$sql="SELECT * FROM $this->linkstable_name ORDER BY name ASC";
		$links=$wpdb->get_results($sql, OBJECT);
		if($links && (count($links)>0)){
			?>
			
			<?php
			
			$rowclass = '';
			foreach ($links as $link) {
				$rowclass = ($rowclass == '')?'alternate':'';
				echo "<tr id='link-$link->id' class='$rowclass'>
				<td>$link->name</td>
				<td><a href='$link->url'>".$this->mytruncate($link->url)."</a>
				<small>
				&nbsp;&nbsp;&nbsp;&nbsp; <a href='javascript:toggleLink($link->id);'>show cloaked url</a> 
				</small><br/>
				<input type='text' style='display:none; width:80%; margin-top: 4px;' id='clink-$link->id' 
				value='". esc_attr($this->make_cloaked_url($link->name)) . "'>
				</td>
				<td>$link->hits</td>
				
				<td>";
				if (current_user_can('manage_links')) {
					echo "<a href='javascript:void(0);' class='delete' 
					onclick='deleteCloakedLink($link->id);return false;' );' title='Delete cloaked link'>Delete</a>";
				};
				echo "</td></tr>";
				
			}
			
		};
?>
	</tbody>
</table>

<script type='text/javascript'>
	var wplc_ajax_url='<?php
		echo esc_js(admin_url('admin-ajax.php')); 
		?>';

	jQuery(function($){
		
		window.toggleLink = function(link_id){
			$('#clink-'+link_id).toggle();
			return void(0);
		}
		
		window.saveCloakedLink = function(){
			var clname = $('#wplc_name').val();
			var clurl = $('#wplc_url').val();
			
			if ((clname=='') || (clurl=='')) {
				alert('You need to fill in both fields!');
				return false;
			}
			
			clname = clname.replace(/\s+/g, '-');
			$('#wplc_name').val(clname);
			
			
			$('#wplc_status').html('Adding link...');
			
			$.post(
				wplc_ajax_url,
				{
					action: 'wplc_add_link',
					name: clname,
					url: clurl
				},
				function(data, textStatus){
					if ( data.match(/saved/i) ){
						//Clear the input fields
						$('#wplc_name').val('');
						$('#wplc_url').val('');
						
						//Get the ID of the newly saved link
						var results = data.match(/insert_id:(\d+);/);
						insert_id = results[1];
						
						var cloaked_url = '<?php echo esc_js($this->make_cloaked_url()); ?>'+clname+'/';
						
						//Add a new table row
						$('#wplc_links').append(
							'<tr id="link-' +insert_id + '">' +
								'<td>' + clname + '</td>' +
								'<td>'+
									'<a href="' + clurl + '">'+ clurl +'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+
									'<small><a href="javascript:toggleLink('+insert_id+');">show cloaked url</a></small>'+
									'<br><input type="text" id="clink-'+insert_id+'" value="'+cloaked_url+'" style="display:none;width:80%;margin:4px;">' +
								'</td>' +
								'<td>0</td>'+
								'<td><a href="javascript:void(0);" onclick="deleteCloakedLink('+insert_id+'); return false;" title="Delete this cloaked link">Delete</a></td>' +
							'</tr>'
						);
						
						$('#wplc_status').html('Link added.');
					} else {
						alert(data);
						$('#wplc_status').html('');
					}
				}
			);
		}
		
		window.deleteCloakedLink = function(link_id){
			if (!confirm('Do you really want to delete this link?')) { return false; };
			
			$('#wplc_status').html('Deleting link...');
			
			$.post(
				wplc_ajax_url,
				{
					action: 'wplc_delete_link',
					id: link_id,
				},
				function(data, textStatus){
					if(data.match(/deleted/i)){
						/* Remove the row */
						$('#link-'+link_id).remove();
						$('#wplc_status').html('Link deleted.');
					} else {
						alert(data);
						$('#wplc_status').html('Error deleting the link.');
					};
				}
			);
		}
	});
</script>
</div>
		<?php
	}

} //end class

} //if class_exists()...

$ws_link_cloaker = new ws_wordpress_link_cloaker();

require 'plugin-updates/plugin-update-checker.php';
$ws_lc_update_checker = new PluginUpdateChecker(
    'http://w-shadow.com/files/link-cloaking-plugin.json',
    __FILE__,
    'link-cloaking-plugin'
);

?>