<?php
/**
 * @plugin RAntispam
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class AllInputPlugin
{
	static function checkSpam($config, &$spamObject, $spamFilterFunction)
	{
		$input = "";
		foreach($_REQUEST as $key => $value)
		{
			if($key != 'option' && $key != 'task' && $key != 'view' && $key != 'layout' && $key != 'Itemid')
			{
				if(is_array($value))
				{
					foreach($value as $_key => $_value)
					{
						if(!is_array($_value) && strlen($_value) > 25 &&
							AllInputPlugin::hasMoreThanOneWord($_value))
							$input .= " " . $_value;
					}
				}
				else if(strlen($value) > 25 && 
					AllInputPlugin::hasMoreThanOneWord($value))
					$input .= " " . $value;
			}
		}
		if(strlen($input) > 1)
		{
			$db = JFactory::getDBO();
			$score = call_user_func($spamFilterFunction, $input);
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
					$spamObject->user_Fullname = 
						$spamObject->user_name = 'Guest';
				}
				$spamObject->user_ip = $_SERVER['REMOTE_ADDR'];
				$spamObject->spam_score = $score;
				$spamObject->message = $input;
				$spamObject->message_id = 0;
				$spamObject->subject = '';
				$spamObject->param2 = '';
				$spamObject->param3 = '';
				$spamObject->param4 = '';
				$spamObject->param1 = 1;
				$spamObject->output = null;
				$spamObject->provider = 'unknown';
				return true;
			}
			else
				return false;
		}
		return false;
	}
	
	static function hasMoreThanOneWord($value)
	{
		$words = explode(" ", $value);
		if(count($words) <= 1)
			return false;
		$count = 0;
		foreach($words as $word)
		{
			if(trim($word) != "")
				$count++;
			if($count > 1)
				return true;
		}
		return false;
	}
}

?>