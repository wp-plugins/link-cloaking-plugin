<?php
/*
	The AJAX-y part of the link cloaking plugin.
*/
	require_once("../../../wp-config.php");
	require_once("../../../wp-includes/wp-db.php");
	
	//error_reporting(E_ALL);
	
	if (!current_user_can('manage_links')) {
		die("Error: You can't do that. Access denied.");
	}
	
	$siteurl=get_option('siteurl');
	
	$action=isset($_POST['action'])?$_POST['action']:'status';
	
	if($action=='add_link'){
		/* save a link */
		$name = $_POST['name'];
		$url = $_POST['url'];
		
		$sql="SELECT count(*) FROM $ws_link_cloaker->linkstable_name WHERE name LIKE '".$wpdb->escape($name)."'";
		$link_exists=$wpdb->get_var($sql);
		if($link_exists){
			echo "Error : A link with this name already exists!";
		} else {
			$rez = $wpdb->query(
	        	"INSERT INTO $ws_link_cloaker->linkstable_name(name, url) 
	        	 VALUES('".$wpdb->escape($name)."', '".$wpdb->escape($url)."')"
	        	);
  			if ( $rez === false ){
  				exit($wpdb->last_error);
  			}
	        echo "Saved.<!--insert_id:$wpdb->insert_id;-->";
		};
		
	} else if($action=='delete_link'){
		/* delete a cloaked link */
		$id=intval($_POST['id']);
		$wpdb->query("DELETE FROM $ws_link_cloaker->linkstable_name WHERE id=$id LIMIT 1");
		if ($wpdb->rows_affected>0){
			echo "Link deleted."; //probably
		} else {
			echo "Error: Failed to delete the link!";
		}
		
	} else if($action=='status'){
		/* debugging stuff */
		echo '<pre>';
		echo 'GET data : <br/>';
		print_r($_GET);
		echo 'POST data : <br/>';
		print_r($_POST);
		echo '<br/>$ws_link_cloaker : <br/>';
		print_r($ws_link_cloaker);
		
		echo '</pre>';
	};

?>