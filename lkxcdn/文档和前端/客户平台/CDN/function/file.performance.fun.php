﻿<?php

/*CDN下载加速 - 性能分析*/
/*author:hyb*/

require_once('./file.com.fun.php');
require_once('./log.fun.php');
error_reporting(E_ALL^E_NOTICE);
/*一天对应秒数*/
define('ONE_DAY_SEC',86400);

$do_main = 0;

switch( $_POST['get_type'] ) 
{
	case "_init":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) 
		{ 
			$client = $_POST['user']; 
		}
		//print_r($client);
		print_r(get_client_hostname_list($client));print('***');		
		print_r(get_cdn_zone_list());print('***');
		exit;
		break;
		
	case "_performance":
		//print_r($_POST);
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) 
		{ 
			$client = $_POST['user']; 
		}
		$do_main = 1;
		break;    
		
	case "_get_province":
		if( isset($_POST['user']) && strlen($_POST['user']) > 0 ) 
		{ 
			$client = $_POST['user']; 
		}
		$do_main = 0;
		break;
	default :  
		exit; 
		break;
}

if( $client == '' ) 
{ 
	exit; 
}

$begin_day = $end_day = '';
$zones = array('中国大陆');


$post_time = json_decode($_POST['time'], true);
//print_r($post_time);
//check_days_limit($post_time);
if($do_main)
{
	$channels = array();
	$post_channel = json_decode($_POST['channel'], true);
	$client_type = get_client_type($client);

	if( count($post_time) != 2 || count($post_channel) <= 0 ) 
	{ 
		exit; 
	}
	
	if ($client_type != 'cdnfile_hot')
	{
	
		$channels = get_old_user_channels($post_time, $client);
	}	
	else
	{
		$channels = $post_channel;

	}
}

$begin_day = $post_time[0];
$end_day = $post_time[1];

syslog_user_action($client,$_SERVER['SCRIPT_NAME'],$channels,$begin_day,$end_day);

/*
unset($channels);
$channels[] = 'bd';
$channels[] = 'client';
*/
/*TEST*/
/*
$channels = array();
$channels[] = 'adnet';
$channels[] = 'adgame';
$begin_day = "2013-05-15";
$end_day = "2013-05-16";
*/

//print_r($post_time); print_r($post_channel); print($client);

/*先根据要查询的开始结束日期获取以天为单位的天列表*/

$days = array(); //天列表
$days_ret = array();
for( $bday = @strtotime($begin_day); $bday <= @strtotime($end_day); ) 
{
	$day = @date("Y-m-d", $bday);
	$bday += 86400;
	$days[] = $day;
	if($do_main)
	{
		for( $i = 0; $i < 24; $i++ ) 
		{
			$hour = sprintf("%02d", $i);
			foreach($channels as $hostname)
			{
				$days_ret[$day][$hostname]["$hour:00:00"] = 0;
			}
		}
	}
}

$months = days_2_months($days);
//print_r($months); print_r($days);

$mysql_class = new MySQL('newcdnfile');
$mysql_class->opendb("cdn_file_log_general", "utf8");
	
	
if ($do_main)
{
/*下载速度按时间统计曲线图*/
////////////////////////////////////////////

if(count($days) > 6)
{
	unset($days_ret);
	$days_ret = array();
	
	foreach($months as $month => $days)
	{
		foreach( $days as $day)
		{
			foreach($channels as $hostname)
			$days_ret[$day][$hostname]["00:00:00"] = 0;
		}
		$query = gen_select_hostname_avgrate_perweek($month, $days, $channels);
		$result = $mysql_class->query($query);
		$num_rows = mysql_num_rows($result);
		if ($num_rows == NULL){continue;}
		while( ($row = mysql_fetch_array($result)) ) 
		{
			$hostname = $row[0];
			$rate = $row[2]; 
			$rate = round($rate / 1024, 2); //转换KBps
			$date = $row[1];
			$days_ret[$date][$hostname]["00:00:00"]+= $rate;
		}
		mysql_free_result($result);
	}
	
}

else
{
	foreach( $days as $day ) 
	{
		$query = gen_select_hostname_avgrate_perday($day, $channels);
		//print("$query\n");
		$result = $mysql_class->query($query);
		$num_rows = mysql_num_rows($result);
		if ($num_rows == NULL){continue;}
		while( ($row = mysql_fetch_array($result)) ) 
		{
			$hostname = $row[0];
			$rate = $row[2]; 
			$rate = round($rate / 1024, 2); //转换KBps
			$time = $row[1];
			$days_ret[$day][$hostname][$time]+= $rate;
			//print("$day $isp $time $idx $bandwidth <br>");
		}
		mysql_free_result($result);
	}
}

//print_r($days_ret);



/*进行最终结果输出的初始化*/
$days_data = array(); //流量图--保存最终结果
foreach( $days_ret as $day => $hostret ) 
{
	foreach($hostret as $hostname => $dayret)
	{
		foreach( $dayret as $time => $rate ) 
		{
			$data_key = "$day $time";
			$days_data[$hostname][$data_key] = $rate;
		}
	}
}
//print_r($days_data);

$default = array();
$channel_cnt = count($days_data);
$channel_list_arr = array();
$days_key_ret = $days_value_ret = array();
$days_print = array();

$judge = 0;
foreach( $days_data as $host => $host_data ) 
{	
	
	foreach ($host_data as $date => $value)
	{
		if ($judge == '0')
		{
			$days_key_ret[] = $date;
			$default[] = NULL;
		}
		//$days_value_ret[$host][] = $value;
		$days_value_ret[$host][] = $value;
		
	}
	$judge ++;
}


//print_r($days_value_ret);

foreach ($days_value_ret as $host => $values)
{
	$channel_list_arr[] = $host;
	$days_print[] = $values;
}

if ($channel_cnt < 4);
{
	$max = 4 - $channel_cnt;
	for($i = 0; $i < $max; $i++)
	{
		$channel_list_arr[] = NULL;
		$days_print[] = $default;
	}
}

print_r(json_encode($channel_list_arr));
print('***');
print_r(json_encode($days_key_ret));
print('***');
print_r(json_encode($days_print));
print('***');


/*按域名统计平均下载速度排行*/
////////////////////////////////////////////

	$host_rate_ret_arr = array();
	$host_rate_total_arr = array();
	foreach( $months as $month => $mdays ) 
	{
		$query = gensql_hostname_avgrate_cnt($month, $mdays, $channels);
	//print($query);
		$result = $mysql_class->query($query);
		$num_rows = mysql_num_rows($result);
		if ($num_rows == NULL){continue;}
		while( ($row = mysql_fetch_array($result)) ) 
		{
			//print_r($row);
			$hostname = $row[0];
			$rate = $row[1];
			$rate = round($rate / 1024, 2);
			$host_rate_total_arr[$hostname] += $rate;
		
		}
		mysql_free_result($result);
	}

	foreach($host_rate_total_arr as $hostname => $rate)
	{
		$host_rate_ret_arr[] = array('channel' => $hostname, 'rate' => $rate);
	}
	array_multisort($host_rate_total_arr, SORT_DESC, $host_rate_ret_arr);
	print_r(json_encode($host_rate_ret_arr));
	print('***');

	
//按区间平均下载速度排行
////////////////////////////////////////////
	$speed_rate_ret_arr = array();
	$speed_rate_total_arr = array();
	foreach( $months as $month => $mdays ) 
	{
		$query = gensql_speed_avgrate_cnt($month, $mdays, $channels);
	//print("\n".$query);
		$result = $mysql_class->query($query);
		$num_rows = mysql_num_rows($result);
		if ($num_rows == NULL){continue;}
		while( ($row = mysql_fetch_array($result)) ) 
		{
			//print_r($row);
			$host = $row[0];
			$rate = $row[1];
			$count = $row[2];
			$rate = round($rate / 1024, 2);
			if ($rate <= 50)
				$speed_rate_total_arr[$host]['fir'] += $count;
			else if ($rate <= 100)
				$speed_rate_total_arr[$host]['sed'] += $count;
				else if ($rate <= 200)
					$speed_rate_total_arr[$host]['thr'] += $count;
					else if ($rate <= 500)
						$speed_rate_total_arr[$host]['for'] += $count;
						else if ($rate <= 1024)
							$speed_rate_total_arr[$host]['five'] += $count;
							else
								$speed_rate_total_arr[$host]['six'] += $count;
		
		}
		mysql_free_result($result);
	}

//print_r($speed_rate_total_arr);

	$i = 0;
	foreach($speed_rate_total_arr as $host => $cnt_arr)
	{
	
		$speed_rate_ret_arr[$i] = array('channel' => $host, 'fir' => 0,'sed' => 0,'thr' => 0,'for' => 0,'five' => 0,'six' => 0,);
	
		foreach($cnt_arr as $speed => $value)
		{
			$speed_rate_ret_arr[$i][$speed] = $value;
		}
		$i ++;
	}
//array_multisort($speed_rate_total_arr, SORT_DESC, $speed_rate_ret_arr);
	print_r(json_encode($speed_rate_ret_arr));

}
else
{
//按省份平均下载速度排行
////////////////////////////////////////////
	$area_rate_ret_arr = array();
	$area_rate_total_arr = array();
	foreach( $months as $month => $mdays ) 
	{
		$query = gensql_area_avgrate_cnt($month, $mdays, $_POST['channel']);
		//print($query);
		$result = $mysql_class->query($query);
		$num_rows = mysql_num_rows($result);
		if ($num_rows == NULL){continue;}
		while( ($row = mysql_fetch_array($result)) ) 
		{
			//print_r($row);
			$area = $row[0];
			$rate = $row[1];
			$rate = round($rate / 1024, 2);
			$area_rate_total_arr[$area] += $rate;
		
		}
		mysql_free_result($result);
	}

	foreach($area_rate_total_arr as $area => $rate)
	{
		$area_rate_ret_arr[] = array('province' => $area, 'rate' => $rate);
	}
	array_multisort($area_rate_total_arr, SORT_DESC, $area_rate_ret_arr);
	/*等阿青处理*/
	print_r(json_encode($area_rate_ret_arr));
//print('***');
}

unset($mysql_class);

/*===================================================================================
									function										
====================================================================================*/


 /**
* @brief  检查时间跨度是否符合标准（31天）
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function check_days_limit($time)
{
	$begin_day = $time[0];
	$end_day = $time[1];
	$bday = @strtotime($begin_day);
	$eday = @strtotime($end_day);
	if ($eday - $bday >= (ONE_DAY_SEC * 31))
	{
		print("时间跨度不能大于31天\n");
		exit;
	}
}


/**
* @brief  少于等于6天时，从天表获取精确到小时的数据
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function gen_select_hostname_avgrate_perday($day, $channels)
{
	$query = "select hostname, time, sum(send/r_time)/count(*)  from `$day".'_gen`'. "where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= 'group by `hostname`,`time`;';
	return $query;
}

/**
* @brief  大于6天时，从月表获取精确到天的数据
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function gen_select_hostname_avgrate_perweek($month, $days, $channels)
{
	$query = "select hostname, date, sum(send/r_time)/count(*)  from `$month".'_gen`'. "where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= ' and `date` in (';
	foreach( $days as $day ) {
		$query .= "'$day',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= 'group by `hostname`,`date`;';
	return $query;
}


/**
* @brief  获取每个节点各自的速度和请求数，用以作区间区分
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function gensql_speed_avgrate_cnt($table, $mdays, $channels)
{
	$query = "select hostname,send/r_time,cnt from `$table".'_gen`'." where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';

	$query .= ' and `date` in (';
	foreach( $mdays as $day ) {
		$query .= "'$day',";
	}
	$query[strlen($query)-1] = ')';
	
	//$query .= "group by hostname order by `sum(send/r_time)/count(*)` desc ;";
	return $query;
}

/**
* @brief  获取每个频道的平均速度
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function gensql_hostname_avgrate_cnt($table, $mdays, $channels)
{
	$query = "select hostname,sum(send/r_time)/count(*) from `$table".'_gen`'." where `hostname` in (";
	foreach( $channels as $hostname ) {
		$query .= "'$hostname',";
	}
	$query[strlen($query)-1] = ')';

	$query .= ' and `date` in (';
	foreach( $mdays as $day ) {
		$query .= "'$day',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= "group by hostname order by `sum(send/r_time)/count(*)` desc ;";
	return $query;
}


/**
* @brief  获取每个地区的平均速度
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function gensql_area_avgrate_cnt($table, $mdays, $channel)
{
	$query = "select province,sum(send/r_time)/count(*) from `$table".'_gen`'." where `hostname` in (";
	$query .= "'$channel',";

	$query[strlen($query)-1] = ')';

	$query .= ' and `date` in (';
	foreach( $mdays as $day ) {
		$query .= "'$day',";
	}
	$query[strlen($query)-1] = ')';
	
	$query .= "group by province order by `sum(send/r_time)/count(*)` desc limit 10;";
	return $query;
}


/**
* @brief  对旧客户的所有hostname进行提取
* @return 
* @remark null
* @see     
* @author hyb      @date 2013/05/14
**/
function get_old_user_channels($query_time, $username)
{
	$query_begin = $query_time[0];
	$query_end = $query_time[1];
		
	$begin_month = substr($query_begin, 0, -3);
	$end_month = substr($query_end, 0, -3);

	if ($begin_month != $end_month)
	{
		$sql = "select hostname from (
			select hostname from `${begin_month}_gen`
			where `user` = '$username'
			union all 
			select hostname from `${end_month}_gen` 
			where `user` = '$username' 
			) as total group by `hostname`";
	}
	else
	{
		$sql = "select hostname from `$end_month".'_gen`'." where `user` = '$username' group by `hostname`";
	}
		
	//print($sql);
	$channels = array();
	$mysql_class = new MySQL('newcdnfile');
	$mysql_class -> opendb("cdn_file_log_general", "utf8");
	$result = $mysql_class->query($sql);
	/*modify in 2013/5/15*/
		$num_rows = mysql_num_rows($result);
		if ($num_rows == NULL){return $channels;}
	while( ($row = mysql_fetch_array($result)) ) 
	{
			//print_r($row);
		$channels[] = $row[0];
	}
	mysql_free_result($result);

	return $channels;
}


function days_2_months($days)
{
	$months = array();
	foreach( $days as $day ) {
		$year = $month = $iday= 0;
		sscanf($day, "%d-%d-%d", $year, $month, $iday);
		$ym = sprintf("%d-%02d", $year, $month);
		$months[$ym][] = $day;
	}
	return $months;
}

?>