<?php
	define( 'SHORTINIT', true );
	require_once $_SERVER['DOCUMENT_ROOT'].'/wp-load.php';
	require_once $_SERVER['DOCUMENT_ROOT'].'/wp-includes/pluggable.php';
    
	if(isset($_POST['adID']) && isset($_POST['blockTypes'])){
		session_start();
		global $current_user;
		echo $current_user->ID;
		$uid="";
		/*if (is_user_logged_in()){
			echo "yes";
			//$uid=get_current_user_id();
		}*/
		global $wpdb;
		$uid=5;
		$adID=$_POST['adID']; 
		$blockTypes=array();
		for($i=0;$i<count($_POST["blockTypes"]);$i++)
		{
			array_push($blockTypes,$_POST["blockTypes"][$i]);
		}
		$table_name = $wpdb->prefix . 'block_ads';
		$block_ads = $wpdb->get_results("select * from ".$table_name." where aid=".$adID);
		if(sizeof($block_ads)==0)
		{
			$wpdb->insert($table_name, array('aid' => $adID, 'uid' => $uid, 'block_type' => json_encode($blockTypes)));
			array_push($_SESSION['blocked_ads'],$adID);
			echo "blocked";
		}
	}
