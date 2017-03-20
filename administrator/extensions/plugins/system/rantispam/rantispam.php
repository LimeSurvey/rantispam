<?php
/**
 * @plugin RAntispam
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
// no direct access-
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class  plgSystemRAntiSpam extends JPlugin
{
	var $_config = null;
	var $_advanced_config = null;

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @access	protected
	 * @param	object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since	1.0
	 */
	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}
	
	function loadConfig()
	{
		if($this->_config)
			return;
		$this->_config = new StdClass();
		$params = JComponentHelper::getParams('com_rantispam');
		$this->_config->spam_threshold = $params->get('spam_threshold', 0.95);
		$this->_config->remove_back_link = $params->get('remove_back_link', 1);
		$this->_config->kunena = $params->get('check_kunena', true);
		$this->_config->use_plugin = $params->get('use_plugin', false);
		$this->_config->email_alert = $params->get('alert_address', '');
		$this->_config->ninjaboard = $this->_config->ccboard = $this->_config->ccboard = 
			$this->_config->k2 = false;
		$this->_config->block_spammers = $params->get('block_spammers', false);
		$this->_config->block_ip = $params->get('block_ip', false);
		$this->_config->handle_allinput = $params->get('handle_all_input', false);
	}
	
	function loadAdvancedConfig()
	{
		if($this->_advanced_config)
			return;
		$this->_advanced_config = new StdClass();
		$params = JComponentHelper::getParams('com_rantispam');
		$this->_advanced_config->email_subject = $params->get('alert_subject');
		$this->_advanced_config->email_body = $params->get('alert_body');
	}
	
	function onAfterInitialise()
	{
		JLoader::register('KunenaPlugin', 
			JPATH_ADMINISTRATOR . "/components/com_rantispam/builtinplugins/kunena.php");
		JLoader::register('UsersPlugin', 
			JPATH_ADMINISTRATOR . "/components/com_rantispam/builtinplugins/users.php");
		JLoader::register('SpamFilter', 
			JPATH_ADMINISTRATOR . "/components/com_rantispam/classes/spamfilter.php");
		JLoader::register('AllInputPlugin', 
			JPATH_ADMINISTRATOR . "/components/com_rantispam/builtinplugins/allinput.php");
		JLoader::register('RAntispamFilter', 
			JPATH_ADMINISTRATOR . "/components/com_rantispam/classes/rantispam.class.php");
		JLoader::register('Akismet', 
			JPATH_ADMINISTRATOR . "/components/com_rantispam/classes/Akismet.class.php");
		JLoader::register('AkismetFilter', 
			JPATH_ADMINISTRATOR . "/components/com_rantispam/classes/akismet.php");
	}
	
	function checkBanned()
	{
		$db = JFactory::getDBO();
		$ip = $db->escape($_SERVER['REMOTE_ADDR']);
		$query = "SELECT COUNT(*) FROM #__rantispam_banip WHERE bannedip = '$ip'";
		$db->setQuery($query);
		$result = $db->loadResult();
		if($result)
		{
			$mainframe = JFactory::getApplication();
			$mainframe->redirect("index.php?option=com_rantispam&view=banned");
			exit;
		}
	}
	
	function deleteOldSpam()
	{
		$db = JFactory::getDBO();
		$db->setQuery("DELETE FROM #__rantispam_spams_detected WHERE TO_DAYS(NOW()) - TO_DAYS(detect_time) >= 29");
		$db->query();
	}
	
	function isModerationAllowed()
	{
		return JFactory::getUser()->authorise('forum.manage', 'com_rantispam');
	}
	
	function canSubmitAnything()
	{
		return JFactory::getUser()->authorise('forum.submitanything', 'com_rantispam');
	}
	
	function onAfterRoute()
	{
		$mainframe = JFactory::getApplication();
		if($mainframe->isAdmin())
			return;
		$option = JFactory::getApplication()->input->get('option');
		if($option != 'com_rantispam')
		{
			$this->checkBanned();
		}
		if($this->canSubmitAnything()) // Some users are allowed to send spam
			return;
		$isSpam = false;
		$this->loadConfig();
		if($option == 'com_kunena')
		{
			if($this->_config->kunena)
			{
				$func = JFactory::getApplication()->input->get('func');
				$task = JFactory::getApplication()->input->get('task');
				if($func == 'post' || $task == 'post')
				{
					$message = JRequest::getVar( 'message', '', 'post','string', JREQUEST_ALLOWRAW );
					if($message)
					{
						$isSpam = KunenaPlugin::checkSpam($this->_config, $spamObject, array($this, "spamFilterFunction"));
					}
				}
			}
		}
		else if($option == 'com_users' || $option == 'com_user')
		{
			$task = JFactory::getApplication()->input->get('task');
			if($task == 'registration.register' || $task == 'register_save')
			{
				$isSpam = UsersPlugin::checkSpam($this->_config, $spamObject, array($this, "spamFilterFunction"));
			}
		}
		else 
		{
			if($this->_config->use_plugin)
			{
				JPluginHelper::importPlugin("rantispam");
				$dispatcher	= JDispatcher::getInstance();
				$dispatcher->trigger('onCheckSpam', array ($this->_config, &$isSpam, &$spamObject, array($this, "spamFilterFunction")));
			}
			if($this->_config->handle_allinput)
			{
				$isSpam = AllInputPlugin::checkSpam($this->_config, $spamObject, array($this, "spamFilterFunction"));
			}
		}
		if($isSpam && $spamObject)
		{
			$this->deleteOldSpam();
			if($this->_config->block_spammers && $spamObject->user_id)
			{
				$this->block_user($spamObject->user_id);
				$mainframe->logout();
			}
			if($this->_config->block_ip && $spamObject->user_ip)
			{
				$this->block_ip($spamObject->user_ip);
			}
			$this->saveSpamLog($this->_config, $spamObject);
			if(!isset($spamObject->donotreport) || !$spamObject->donotreport)
				$this->sendEmail($this->_config, $spamObject);
			if($spamObject->output)
			{
				ob_end_clean();
				echo $spamObject->output;
			}
			else
			{
				$mainframe->redirect("index.php?option=com_rantispam&view=denied");
			}
			exit;
		}
	}
	
	function block_ip($user_ip)
	{
		$db = JFactory::getDBO();
		$user_ip = $db->escape($user_ip);
		$query = "INSERT INTO #__rantispam_banip(bannedip) VALUES('$user_ip')";
		$db->setQuery($query);
		$db->query();
	}
	
	function block_user($user_id)
	{
		$db = JFactory::getDBO();
		$db->setQuery("UPDATE #__users SET block=1 WHERE id = " . (int)$user_id);
		$db->query();
	}
	
	function saveSpamLog($config, $spamObject)
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
		$db->query();
	}
	
	function email_replace($text, $spamObject)
	{
		$text = preg_replace("/{user_id}/", $spamObject->user_id, $text);
		$text = preg_replace("/{user_name}/", $spamObject->user_name, $text);
		$text = preg_replace("/{user_fullname}/", $spamObject->user_Fullname, $text);
		$text = preg_replace("/{spam_score}/", $spamObject->spam_score, $text);
		$text = preg_replace("/{message}/", $spamObject->message, $text);
		return $text;
	}
	
	function sendEmail($config, $spamObject)
	{
		if($config->email_alert)
		{
			$this->loadAdvancedConfig();
			$mail = JFactory::getMailer();
			$mail->setSubject($this->email_replace($this->_advanced_config->email_subject, $spamObject));
			$mail->setBody($this->email_replace($this->_advanced_config->email_body, $spamObject));
			$mail->addRecipient($config->email_alert);
			$mail->IsHTML(true);
			$joomla_config = new JConfig();
			$mail->setSender(array($joomla_config->mailfrom, $joomla_config->fromname));
			$mail->send();
		}
	}
	
	function spamFilterFunction($text)
	{
		$spam_filter = SpamFilter::getInstance();
		return $spam_filter->test($text);
	}
	
	function onAfterRender()
	{
		$this->loadConfig();
		$option = JFactory::getApplication()->input->get('option');
		if($option == 'com_kunena')
		{
			if($this->_config->kunena)
			{
				KunenaPlugin::setMark($this->_config);	
			}
		}
		else if($this->_config->use_plugin)
		{
			JPluginHelper::importPlugin("rantispam");
			$dispatcher	= JDispatcher::getInstance();
			$dispatcher->trigger('onSetMark', array ($this->_config));
		}
	}
	
	function isUserInWhiteList()
	{
		$user = JFactory::getUser();
		if($user && $user->id)
		{
			$db = JFactory::getDBO();
			$query = "SELECT COUNT(*) FROM #__rantispam_white_list WHERE user_id = " . (int)$user->id;
			$db->setQuery($query);
			return $db->loadResult() > 0;
		}
		return false;
	}
	
	function isUserInBlackList()
	{
		$user = JFactory::getUser();
		if($user && $user->id)
		{
			$db = JFactory::getDBO();
			$query = "SELECT COUNT(*) FROM #__rantispam_black_list WHERE user_id = " . (int)$user->id;
			$db->setQuery($query);
			return $db->loadResult() > 0;
		}
		return false;
	}
	
}