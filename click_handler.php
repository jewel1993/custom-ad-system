<?php
	session_start(); 
	function add_click_count($ads,$id,$url){
		foreach ($ads as &$ad)
		{
			if($ad->id==$id)
			{
                if (!array_key_exists('click_count',$ad)) {
                    $ad->click_count = 0;
                }
				$ad->click_count++;
				//print("<script>console.log('click count increased to ".$ad['click_count']."');</script>");
				header('Location: '.$url);
			}
		}
		return $ads;
	}
	if(!empty($_REQUEST["ad_id"]) && !empty($_REQUEST["url"]) && !empty($_SESSION["ads"])){
		$ads=$_SESSION['ads'];
		$ads=add_click_count($ads,$_REQUEST["ad_id"],$_REQUEST["url"]);
		$_SESSION['ads']=$ads;
	}
	else{
		echo "Failed ! again";
	}