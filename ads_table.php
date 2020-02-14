<?php
// The shortcode function
function custom_ad_code_ads_table($atts = []) { 
	session_start();
	$uid="";
	/*if (is_user_logged_in()){
		echo "yes";
		//$uid=get_current_user_id();
	}*/
	global $wpdb;
	$table_name = $wpdb->prefix . 'ad_records';
	$ad_records = $wpdb->get_results("select * from ".$table_name." where is_active=1");
	$table_data='<table class="table table-hover" border="1">
		<tr>
		 <th><center>SL#</center></th>
		 <th><center>ID</center></th>
		 <th><center>CREATOR ID</center></th>
		 <th><center>TITLE</center></th>
		 <th><center>AD_SIZE</center></th>
		 <th><center>TYPE</center></th>
		 <th><center>SHOW_COUNT</center></th>
		 <th><center>CLICK_COUNT</center></th>
		 <th><center>BLOCK_COUNT</center></th>
		 <th><center>DATE</center></th>
		</tr>';
	$c=1;
	foreach($ad_records as $ad_record){
		$table_name = $wpdb->prefix . 'block_ads';
		$ad_block_records = $wpdb->get_results("select * from ".$table_name." where aid=".$ad_record->id);
		$block_count=sizeof($ad_block_records);
		$table_data.='<tr>
		 <td><center>'.$c.'</center></td>
		 <td><center>'.$ad_record->id.'</center></td>
		 <td><center>'.$ad_record->cid.'</center></td>
		 <td><center>'.$ad_record->title.'</center></td>
		 <td><center>'.$ad_record->ad_size.'</center></td>
		 <td><center>'.$ad_record->type.'</center></td>
		 <td><center>'.$ad_record->show_count.'</center></td>
		 <td><center>'.$ad_record->click_count.'</center></td>
		 <td><center>'.$block_count.'</center></td>
		 <td><center>'.$ad_record->record_creation_time.'</center></td>
		</tr>';
		$c++;
	}
	$table_data.='</table>';
	return $table_data;
}

