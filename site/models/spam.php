<?php
/**
 * @version     3.0.0
 * @package     com_rantispam
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ratmil <ratmil_torres@yahoo.com> - http://www.ratmilwebsolutions.com
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.model');

/**
 * Rantispam model.
 */
class RantispamModelSpam extends JModelLegacy
{	
	public function reportSpam($provider, &$redirect)
	{
		$spamObject = null;
		if($provider == 'kunena')
		{
			JLoader::register('KunenaPlugin', 
				JPATH_ADMINISTRATOR . "/components/com_rantispam/builtinplugins/kunena.php");
			JLoader::register('SpamFilter', 
				JPATH_ADMINISTRATOR . "/components/com_rantispam/classes/spamfilter.php");
			$id = JRequest::getInt('id', 0);
			$db = JFactory::getDBO();
			$query = "SELECT catid FROM #__kunena_messages WHERE id = " . $id;
			$db->setQuery($query);
			$catid = $db->loadResult();
			$user = JFactory::getUser();
			$spamfilter = SpamFilter::getInstance();
			$spamfilter->includeAlways = true;
			KunenaPlugin::setSpam($id, $spamfilter, $spamObject);
			$redirect = "index.php?option=com_kunena&func=view&catid=$catid&id=$id#$id";
		}
		else 
		{
			JPluginHelper::importPlugin("rantispam");
			$dispatcher	= JDispatcher::getInstance();
			$dispatcher->trigger('onSetSpam', array ($provider, &$spamObject));
		}
		if($spamObject)
		{
			$this->saveSpamLog($spamObject);
			$params = JComponentHelper::getParams('com_rantispam');
			if($params->get("block_spammers", false) && $spamObject->user_id)
				$this->block_user($spamObject->user_id);
			if($params->get("block_ip", false) && $spamObject->user_ip)
				$this->block_ip($spamObject->user_ip);
		}
	}
	
	function block_user($user_id)
	{
		$db = JFactory::getDBO();
		$db->setQuery("UPDATE #__users SET block=1 WHERE id = " . (int)$user_id);
		$db->query();
	}
	
	function block_ip($user_ip)
	{
		$db = JFactory::getDBO();
		$user_ip = $db->escape($user_ip);
		$query = "INSERT INTO #__rantispam_banip(bannedip) VALUES('$user_ip')";
		$db->setQuery($query);
		$db->query();
	}
	
	function saveSpamLog($spamObject)
	{
		$db = JFactory::getDBO();
		$user_id = $db->escape($spamObject->user_id);
		$user_ip = $db->escape($spamObject->user_ip);
		$message = $db->escape($spamObject->message);
		$message_id = $db->escape($spamObject->message_id);
		$subject = $db->escape($spamObject->subject);
		$user_name = $db->escape($spamObject->user_name);
		$score = $db->escape((float)$spamObject->spam_score);
		$provider = $db->escape($spamObject->provider);
		$param1 = $db->escape($spamObject->param1);
		$param2 = $db->escape($spamObject->param2);
		$param3 = $db->escape($spamObject->param3);
		$param4 = $db->escape($spamObject->param4);
		$message_id = $db->escape($spamObject->message_id);
		$query = "INSERT INTO 
			#__rantispam_spams_detected(user_name, subject,
				user_id, user_ip, spam_text, spam_score, provider, param1, param2, param3, param4, message_id, detect_time)
			VALUES('$user_name', '$subject',
				'$user_id', '$user_ip', '$message', '$score', '$provider', '$param1', '$param2', '$param3', '$param4', '$message_id', NOW())";
		$db->setQuery($query);
		return $db->query();
	}
}