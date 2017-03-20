<?php
/**
 * @plugin RAntispam
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class UsersPlugin
{
	static function checkSpam($config, &$spamObject, $spamFilterFunction)
	{
		
		$task = JRequest::getVar('task');
		if($task == 'registration.register')
		{
			$requestData = JRequest::getVar('jform', array(), 'post', 'array');
			$name = $requestData["name"];
			$username = $requestData["username"];
		}
		else if($task == 'register_save')
		{
			$name = JRequest::getVar('name');
			$username = JRequest::getVar('username');
		}
		else 
			return false;
		$score = call_user_func($spamFilterFunction, $name . " " . $username);
		if($score >= $config->spam_threshold)
		{
			$spamObject = new stdClass();
			$user = JFactory::getUser();
			$spamObject->user_id = (int)$user->id;
			if($spamObject->user_id)
			{
				$spamObject->user_name = $user->username;
				$spamObject->user_Fullname = $user->name;
			}
			else
			{
				$spamObject->user_name = 'guest';
				$spamObject->user_Fullname = 'guest';
			}
			$spamObject->user_ip = $_SERVER['REMOTE_ADDR'];
			$spamObject->spam_score = $score;
			$spamObject->message = $name;
			$spamObject->message_id = '0';
			$spamObject->subject = $username;
			$spamObject->param2 = 0;
			$spamObject->param3 = 0;
			$spamObject->param4 = 0;
			$spamObject->param1 = 0;
			$spamObject->output = null;
			$spamObject->donotreport = true;
			$spamObject->provider = 'users';
			return true;
		}
		else
			return false;
		return false;
	}
	
	
	static function train($spamFilter)
	{
		return 0;
	}
	
}

?>