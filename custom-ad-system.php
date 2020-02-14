<?php
/**
* Plugin Name: Custom Ad System
* Plugin URI: http://www.shivankit.com/
* Description: This is the plugin to manage and display custom ads.
* Version: 1.0
* Author: Punit Narang
* Author URI: http://www.shivankit.com/
**/

global $custom_ad_plugin_table_db_version;
$custom_ad_plugin_table_db_version = '1.0.3';
global $ad_queue;
global $blocked_ad_queue;
global $db_fetch_time;
global $redis;

include_once "blocked_ads_table.php";

require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/jewel1993/custom-ad-system.git',
	__FILE__,
	'unique-plugin-or-theme-slug'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();
function custom_ad_plugin_table_install() {
	global $wpdb;
	global $custom_ad_plugin_table_db_version;

	$table_name = $wpdb->prefix . 'ad_records';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = array();

	$sql[] = "CREATE TABLE IF NOT EXISTS $table_name (
		  `id` int(11) NOT NULL AUTO_INCREMENT,,
		  `title` text NOT NULL,
		  `info` text NOT NULL,
		  `ad_size` varchar(100) NOT NULL,
		  `type` varchar(15) NOT NULL,
		  `show_count` int(11) NOT NULL DEFAULT '0',
		  `click_count` int(11) NOT NULL DEFAULT '0',
		  `is_active` int(11) NOT NULL DEFAULT '1',
		  `record_creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`)
	) $charset_collate;";

	$table_name = $wpdb->prefix . 'block_ads';

	$sql[] = "CREATE TABLE IF NOT EXISTS $table_name (
		  `id` int(11) NOT NULL AUTO_INCREMENT,,
		  `aid` int(11) NOT NULL,
		  `uid` int(11) NOT NULL,
		  `block_type` varchar(100) NOT NULL,
		  `record_creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	add_option( 'custom_ad_plugin_table_db_version', $custom_ad_plugin_table_db_version );
}

function custom_ad_plugin_table_install_data() {
}

function fetch_all_ads(){
	global $wpdb;
	global $redis;
	$table_name = $wpdb->prefix . 'ad_records';
	$ads=json_decode($redis->get('ad_queue'));
	echo count($ads);
	$table_name = $wpdb->prefix . 'ad_records';
	$ad_queue = $wpdb->get_results("select * from ".$table_name." where is_active=1");
	shuffle($ad_queue);
	$table_name = $wpdb->prefix . 'block_ads';
	$blocked_ad_queue = $wpdb->get_results("select * from ".$table_name);
	date_default_timezone_set('Asia/Kolkata');
	$db_fetch_time=date("Y-m-d H:i:s");
	$redis->set('ad_queue', json_encode($ad_queue));
	$redis->set('blocked_ad_queue', json_encode($blocked_ad_queue));
	$redis->set('db_fetch_time', $db_fetch_time);
}

require "predis/autoload.php";
Predis\Autoloader::register();

try {
	global $redis;
        $redis = new Predis\Client(array(
         "scheme" => "tcp",
         "host" => "127.0.0.1",
         "port" => 6379));

}
catch (Exception $e) {
	die($e->getMessage());
}
function time_Diff_Minutes($startTime, $endTime) {
	$to_time = strtotime($endTime);
	$from_time = strtotime($startTime);
	$minutes = ($to_time - $from_time) / 60; 
	return ($minutes < 0 ? 0 : abs($minutes));   
} 
function compare_id($a, $b)
{
	return strnatcmp($b->id,$a->id);
}
function compare_priority($a, $b)
{
	return strnatcmp($b->priority,$a->priority);
}
function random_strings($length_of_string,$str_result) 
{ 
    return substr(str_shuffle($str_result),0, $length_of_string); 
} 

function get_priority_queue($ads,$print_request){
	foreach ($ads as &$ad)
	{
		if($ad->show_count>$max_show_count)
		{
			$max_show_count=$ad->show_count;
		}
	}
	foreach ($ads as &$ad)
	{
		//$priority=$ad->show_count/$max_show_count; // priority formula
		//$priority=bcmul((1-bcdiv($ad->show_count,$max_show_count,3)),(bcdiv($ad->click_count,$ad->show_count,3)),7);
		$priority=(1-($ad->show_count/$max_show_count))*($ad->click_count/$ad->show_count);
		//$priority=bcmul((1-bcdiv($ad->show_count,$max_show_count+1,3)),100,3)+bcmul((1-bcdiv($ad->click_count,$ad->show_count+1,3)),100,3);
		if(!isset($ad->priority))
			$ad->priority=0;
		$ad->priority=$priority*100000000;
	}
	usort($ads, 'compare_priority');
	return $ads;
}

function display_ad_web($ads)
{
	$c=0;
	$items="";
	$indicators="";
	foreach ($ads as &$ad)
	{
		$id=$ad->id;
		$content=$ad->title;
		$file_url='https://via.placeholder.com/768x160/'.random_strings(6,'0123456789abcdef').'/'.random_strings(6,'0123456789abcdef').'.png?text='.$content;
		//$items.="<div class='item active'><a href='".plugin_dir_url( _FILE_)."custom-ad-system/click_handler.php?ad_id=$id&url=$file_url' id='$id' class='adclick' target='_blank'><img src='$file_url'/></a></div>"; 
		if($c==0)
			$items.="<div class='item active'><a href='javascript:void(0);' id='$id' class='adclick'><img src='$file_url'/></a></div>"; 
		else
			$items.="<div class='item'><a href='javascript:void(0);' id='$id' class='adclick'><img src='$file_url'/></a></div>"; 
		if($c==0)
			$indicators.='<li class="active" data-target="#myCarousel" data-slide-to="'.($c++).'"></li>';   
		else
			$indicators.='<li data-target="#myCarousel" data-slide-to="'.($c++).'"></li>'; 
	}
	return '
			  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
			  <style>.carousel-indicators {bottom:0;}
					body::-webkit-scrollbar {
				  width: 1em;
				}

				body::-webkit-scrollbar-track {
				  box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
				}

				body::-webkit-scrollbar-thumb {
				  background-color: darkgrey;
				  outline: 1px solid slategrey;
				}
			  </style>
			  <script>
				 $(function () {
					$(".carousel-indicators li").click(function(e){
						e.stopPropagation();
						var goTo = $(this).data("slide-to");
						$(".carousel-inner .item").each(function(index){
							if($(this).data("id") == goTo){
								goTo = index;
								return false;
							}
						});

						$("#myCarousel1").carousel(goTo); 
					});
					$(document).on("click","#adRemove",function(){
						$("#myCarousel1").css("display", "none");
						$("#adRemoveDiv").css("display", "block");
					});
					$(document).on("click","#showAd",function(){
						$("#adRemoveDiv").css("display", "none");
						$("#myCarousel1").css("display", "block");
					});
					$(document).on("click","#adBlock",function(){
						var adID=$("#homepageItems1").find(".active").children("a").attr("id");
						var adCheckboxes = new Array();
						$("input:checked").each(function() {
						   adCheckboxes.push($(this).val());
						});
						var apiUrl="/wp-json/adc/v1/block_ad";
						$.ajax({
							url: apiUrl,
							type:"post",
							data: { adID: adID , blockTypes: adCheckboxes}
						})
						.done(function( response ) {
							if(response=="blocked"){
								$("#adRemoveDiv").css("display", "none");
								$("#myCarousel1").css("display", "block");
								$("#myCarousel1").html("");
							}
						});
					});
					$(document).on("click",".adclick",function(){
						var adID=$(this).attr("id");
						var apiUrl="/wp-json/adc/v1/click_ad";
						$.ajax({
							url: apiUrl,
							type:"post",
							data: { adID: adID }
						})
						.success(function( response ) {
							if(response!="")
								window.open(response, "_blank");
						});
					});
				});
			</script>
			<div id="myCarousel1" class="carousel slide" data-ride="carousel">
				<button class="btn btn-default btn-xs pull-left" style="z-index:999;position:absolute;"  id="adRemove"><span class="glyphicon glyphicon-remove"></span></button>
				<!-- Indicators -->
				<ol class="carousel-indicators" id="indicators1">'.$indicators.'</ol>

				<!-- Wrapper for slides -->
				<div class="carousel-inner" id="homepageItems1">'.$items.'</div>
			</div>
			<div id="adRemoveDiv" style="height: 120px;width: 100%;overflow-y: scroll;display: none;" >
				<div id="" style="scroll:auto;background-color:#F5EFE4;" >
					<button class="btn btn-danger btn-xs pull-left" style="z-index:999;position:absolute;"  id="showAd"><span class="glyphicon glyphicon-remove"></span></button>
					<form class="form-validate form-horizontal" id="adBlockForm" onSubmit="return false" style="margin-left:35px;">
						<div class="checkbox">
						  <label><input type="checkbox" name="adCheckboxes[]" value="1">Seen this ad multiple times</label>
						</div>
						<div class="checkbox">
						  <label><input type="checkbox" name="adCheckboxes[]" value="2">Ad was inappropriate</label>
						</div>
						<div class="checkbox">
						  <label><input type="checkbox" name="adCheckboxes[]" value="3">Not interested in this ad</label>
						</div>
						<div class="checkbox">
						  <label><input type="checkbox" name="adCheckboxes[]" value="4">Ad content</label>
						</div>
						<button class="btn btn-primary" id="adBlock" type="submit">Submit</button>
					</form>
				</div>
			</div>';					
}

function get_adds($count,$print_request)
{
	global $redis;
	date_default_timezone_set('Asia/Kolkata');
	$db_fetch_time=date("Y-m-d H:i:s");
	//echo $redis->get('db_fetch_time');
	if(time_Diff_Minutes($redis->get('db_fetch_time'), $db_fetch_time)>1){
		fetch_all_ads();
	}
	$ads=json_decode($redis->get('ad_queue'));
	//$print_request=true;
	if($print_request==true){
		$ads=array_slice($ads, 0, 10);
	}
	$ads=get_priority_queue($ads,$print_request);
	$new_ads_array=array();
	$free_ad_count=round(($count*20)/100);
	$premium_ad_count=$count-$free_ad_count;
	$free_ad_counter=0;
	$premium_ad_counter=0;
	//echo sizeof($blocked_ad_queue);
	foreach ($ads as &$ad)
	{
		if($premium_ad_counter>=$premium_ad_count){break;}
		else if($ad->type=="premium" /*&& !in_array( $ad->id ,$_SESSION['blocked_ads'])*/)
		{
			array_push($new_ads_array,$ad);
			$ad->show_count++;
			$premium_ad_counter++;
		}
	}
	
	foreach ($ads as &$ad)
	{
		if($free_ad_counter>=$free_ad_count){break;}
		else if($ad->type=="free" /*&& !in_array( $ad->id ,$_SESSION['blocked_ads'])*/)
		{
			array_push($new_ads_array,$ad);
			$ad->show_count++;
			$free_ad_counter++;
		}
	}
	$redis->set('ad_queue', json_encode($ads));
	shuffle($new_ads_array);
	//print_r($new_ads_array);
	return $new_ads_array;
}
//API DEFINATIONS
function block_ad_code($request_data){
	/*global $current_user; 
	global $ad_queue;
	$user = wp_get_current_user();
	if ($user->ID == 0) {
       // return array('error' => 'Authentication Required!');
    }
	return sizeof($ad_queue);
	
	echo $current_user->ID;
	
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
	}*/
	//return $current_user->ID;
	//return "hi";
	global $redis;
	return $redis->get('ad_queue');
}

function click_ad_code($request_data){
	$user = wp_get_current_user();
	return 'https://via.placeholder.com/768x160/21e584/7f54c9.png?text=This%20is%20ad%20title%20for%20premium%20ad44';
}

//API ROUTES
function block_ad_init(){
    register_rest_route('adc/v1', 'block_ad', array(
        'methods' => 'POST',
        'callback' => 'block_ad_code'
    ));
}
function click_ad_init(){
    register_rest_route('adc/v1', 'click_ad', array(
        'methods' => 'POST',
        'callback' => 'click_ad_code'
    ));
}

// The shortcode function
function custom_ad_code($atts = []) { 
	// normalize attribute keys, lowercase
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $wporg_atts = shortcode_atts(['value' => 3], $atts);
	$ads_array=get_adds($wporg_atts['value'],false);
	return display_ad_web($ads_array);
}

register_activation_hook( __FILE__, 'custom_ad_plugin_table_install' );
register_activation_hook( __FILE__, 'custom_ad_plugin_table_install_data' );
add_action( 'plugins_loaded', 'get_user_info' );
add_action( init, 'block_ad_init');
add_action( init, 'click_ad_init');
add_shortcode('custom-ad-system', 'custom_ad_code'); 
add_shortcode('get-blocked-ads', 'get_blocked_ads'); 