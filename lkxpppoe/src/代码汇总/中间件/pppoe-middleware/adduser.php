<?php
//process the adduser command
//listen on the
ini_set('max_execution_time','0');
require_once("/root/config/share/httpsqs_client.php");
require_once("/root/pppoe-middleware/user.php");
require_once("/root/libpro/libpro.php");

define("QUENE_NAME","pppoe_mgr_user_add");
define("QUENE_SERVER","mid.pppoe.rightgo.net");
define("QUENE_PORT","12001");
define("SLEEP_TIME",500000);

openlog("adduser",  LOG_ODELAY, LOG_LOCAL3);
prostart("adduser",getmypid());	

$httpsqs = new httpsqs(QUENE_SERVER,QUENE_PORT);
while(1)
{
	$user_json = $result = $httpsqs->get(QUENE_NAME);
	if("HTTPSQS_GET_END" == $user_json)
	{
		usleep(SLEEP_TIME);
	}
	else
	{
		$user = new User($config->configs["center-db"]);
		$result = $user->getObject($user_json);
		if(!$result)
		{	
			$user->updateManage(User::COMMAND_ADD,false);
			continue;
		}
		//print_r($user);
		$result = $user->handleCommand(User::COMMAND_ADD);
		if($result)
		{
			$user->limitUser();	
			$user->setDate();
			$user->updateManage(User::COMMAND_ADD,true);
			syslog(LOG_INFO, "add user:".$user->tostring()." succeed");
		}
		else
		{
			$user->updateManage(User::COMMAND_ADD,false);
			syslog(LOG_INFO, "add user:".$user->tostring()." fail");
		}
	}
}
closelog();
?>


