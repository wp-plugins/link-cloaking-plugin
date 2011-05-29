<?php
/*
	Cloaked link redirection script.
*/
	require_once '../../../wp-load.php';
	
	$url = false;
	
	if( !isset($_GET['name']) ) {
	
		$post_id = intval($_GET['post_id']);
		$link_num = intval($_GET['link_num']);
		
		$this_post = get_post($post_id, OBJECT);
		
		if ( is_object($this_post) ) {
			
			$link_count = preg_match_all( $ws_link_cloaker->url_pattern, $this_post->post_content, $matches, PREG_SET_ORDER );
			
			if($link_count >= $link_num){
				$url = empty($matches[$link_num-1][3])?$matches[$link_num-1][2]:$matches[$link_num-1][3];
				$url = str_replace('&amp;','&',$url);
				$url = ltrim($url);
			} else {
				wp_die(
					sprintf(
						"<h1>Plugin error: Incompatible link</h1>
						<p>
						Can't find the correct target URL for this link.
						
						Most likely, it's because the link in question was inserted 
						from a custom field or created using another plugin or theme feature.
						Those are not supported by the free version of this plugin.
						</p>",
						htmlentities($post_id)
					)			
				);
			}
			
		} else {
			wp_die(
				sprintf(
					"<h1>Plugin error: Post not found</h1>
					<p>
					Can't find the correct target URL for this link.
					Post %s does not to exist.
					</p>",
					htmlentities($post_id)
				)			
			);
		}
	
	} else {
		
		$static_name=$wpdb->escape($_GET['name']);
		$sql="SELECT url FROM $ws_link_cloaker->linkstable_name ".
			 "WHERE name LIKE '$static_name' LIMIT 1";
		$url=$wpdb->get_var($sql);
		if($url){ 
			//Record the hit
			$wpdb->query("UPDATE $ws_link_cloaker->linkstable_name ".
						" SET hits=hits+1 WHERE name LIKE '$static_name' LIMIT 1");
		} else {
			wp_die(
				sprintf(
					"<h1>Plugin error: Link not found</h1>
					<p>
					The link \"%s\" is either invalid or has been removed.
					</p>",
					htmlentities($static_name)
				)			
			);
		};
	}
	
	if ( !empty($url) && is_string($url) ){
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		header("X-Redirect-Src: $ws_link_cloaker->redirector", TRUE);
		exit();
	} else {
		wp_die("An unexpected error occured.");
	}
?>