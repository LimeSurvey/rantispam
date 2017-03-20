<?php
/**
 * @plugin RAntispam
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
// no direct access
defined('_JEXEC') or die('Restricted access');

class KunenaPlugin
{
    static function checkSpam($config, &$spamObject, $spamFilterFunction)
    {
        $func = JRequest::getVar('func');
        $task = JRequest::getVar('task');
        if ($func == 'post' || $task == 'post') {
            $db = JFactory::getDBO();
            $message = JRequest::getVar('message', '', 'post', 'string', JREQUEST_ALLOWRAW);
            if (!$message) {
                return false;
            }
            $subject = JRequest::getVar('subject', null, 'POST', 'string', JREQUEST_ALLOWRAW);
            $score = call_user_func($spamFilterFunction, $subject . " " .$message);
            if ($score >= $config->spam_threshold) {
                $spamObject = new stdClass();
                $user = JFactory::getUser();
                $spamObject->user_id = (int)$user->id;
                if ($spamObject->user_id) {
                    $spamObject->user_name = $user->username;
                    $spamObject->user_Fullname = $user->name;
                } else {
                    $spamObject->user_Fullname =
                        $spamObject->user_name = JRequest::getString('authorname');
                }
                $id = JRequest::getInt('id', 0);
                if (! $id) {
                    $id = JRequest::getInt('parentid', 0);
                }
                if (! $id) {
                // Support for old $replyto variable in post reply/quote
                    $id = JRequest::getInt('replyto', 0);
                }
                $spamObject->user_ip = $_SERVER['REMOTE_ADDR'];
                $spamObject->spam_score = $score;
                $spamObject->message = $message;
                $spamObject->message_id = '0';
                $spamObject->subject = $subject;
                $spamObject->param2 = JRequest::getInt('catid');
                $spamObject->param3 = $id;
                $spamObject->param4 = '';
                $spamObject->param1 = 1;
                $spamObject->output = null;
                $spamObject->provider = 'kunena';
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    static function isModerationAllowed()
    {
        return JFactory::getUser()->authorise('forum.manage', 'com_rantispam');
    }

    static function setMark($config)
    {
        $app = JFactory::getApplication();
        if ($app->isAdmin()) {
            return;
        }
        $option = JRequest::getVar('option');
        if ($option == 'com_kunena') {
            $body = JResponse::getBody();

            if (KunenaPlugin::isModerationAllowed()) {
                $func = JRequest::getVar('func');
                $view = JRequest::getVar('view');
                if ($func == 'view' || $view == 'topic') {
                    $lang = JFactory::getLanguage();
                    $lang->load('com_rantispam', JPATH_SITE);
                    /**
                     * (<a id="btn_report" class="btn pull-right" href="#report([0-9]*)" rel="nofollow" title="[^"]*" name="report" [^>]*>\s*<i class="icon icon-flag"></i>[^<]*</a>
                     */


                    $regexp = '/(<a id="btn_report" class="btn pull-right" href="#report([0-9]*)"[^>]*>\s*<i class="icon icon-flag"><\/i>[^<]*<\/a>)/';

                    $body = preg_replace_callback($regexp, array("KunenaPlugin", "replaceCallBack"), $body);
                }
            }
            if (!$config->remove_back_link) {
                $regexp = "/<a href=\"http\:\/\/www\.kunena\.org\".*>Kunena.*<\/a>/";
                $ad = '<a href="http://www.ratmilwebsolutions.com" title="R Antispam" target="_blank" style="display: inline; visibility: visible; text-decoration: none;">Protected by R Antispam</a>';
                if (preg_match($regexp, $body)) {
                    $body = preg_replace($regexp, "$0&nbsp;&nbsp;" . $ad, $body);
                }
            }
            JResponse::setBody($body);
        }
    }

    static function replaceCallBack($s)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $message_id = (int) $s[2];
        $query->select('km.id, km.subject, kt.message')
            ->from('#__kunena_messages km')
            ->innerJoin('#__kunena_messages_text kt ON kt.mesid = km.id')
                  ->where('km.id ='.$db->quote($message_id));
        $db->setQuery($query);
        $message = $db->loadObject();
        $text = $message->subject . " " . $message->message;
        $spamFilter = SpamFilter::getInstance();
        $score = $spamFilter->test($text);
        $button_text = JText::_("COM_RANTISPAM_MARK_AS_SPAM") . " (" . sprintf("%.2f", $score) . ")";
        $targetURL = "index.php?option=com_rantispam&task=spam.report&prov=kunena&id=" . $message_id;
        $button_code = $s[0] . ' <a href="'.$targetURL.'" class="btn pull-right" rel="nofollow" title="'.JText::_(COM_RANTISPAM_MARK_AS_SPAM).'">'.$button_text.'</a>';
        return $button_code;
    }


    static function setSpam($message_id, $spamFilter, &$spamObject)
    {
        $db = JFactory::getDBO();
        $query = "UPDATE #__kunena_messages SET hold=2 WHERE id= " . (int)$message_id;
        $db->setQuery($query);
        $db->query();
        $query = "SELECT #__kunena_messages.id, #__kunena_messages.subject,
            #__kunena_messages_text.message, #__kunena_messages.userid,
            #__kunena_messages.ip, #__kunena_messages.name,
            #__kunena_messages.catid, #__kunena_messages.parent as parent_id
            FROM
            #__kunena_messages
            INNER JOIN #__kunena_messages_text
            ON #__kunena_messages_text.mesid = #__kunena_messages.id
			WHERE #__kunena_messages.id = " . (int)$message_id;
        $db->setQuery($query);
        $message = $db->loadObject();
        if ($message) {
            $spamFilter->learn($message->subject . " " . $message->message, true);
            $spamObject = new stdClass();
            $spamObject->user_ip = $message->ip;
            $spamObject->spam_score = 1.00;
            $spamObject->message = $message->message;
            $spamObject->subject = $message->subject;
            $spamObject->message_id = $message->id;
            $spamObject->user_id = $message->userid;
            $spamObject->user_name = $message->name;
            $spamObject->user_Fullname = $message->name;
            $spamObject->param1 = 0;
            $spamObject->param2 = (int)$message->catid;
            $spamObject->param3 = (int)$message->parent_id;
            $spamObject->param4 = '';
            $spamObject->provider = 'kunena';
            return true;
        }
        return false;
    }

    static function setHam($spam, $spamFilter)
    {
        if ($spam->param1) {
            $spamFilter->learn($spam->subject . " " . $spam->spam_text, false);
        } else {
            $spamFilter->rollback_learning($spam->subject . " " . $spam->spam_text, true);
        }
        $db = JFactory::getDBO();
        if ($spam->message_id) {
            $query = "SELECT id FROM #__kunena_messages WHERE id = " .
                (int)$spam->message_id;
            $db->setQuery($query);
            $mid = (int)$db->loadResult();
            if ($mid) {
                $query = "UPDATE #__kunena_messages SET hold = 0 WHERE id = " .     $mid;
                $db->setQuery($query);
                if ($db->query()) {
                    $query = "DELETE FROM #__rantispam_spams_detected
							WHERE spam_id = " . (int)$spam->spam_id;
                    $db->setQuery($query);
                    return $db->query();
                }
            }
        }

        {
            $user = JFactory::getUser($spam->user_id);
            $email = $user->email;
            $catid = (int)$spam->param2;
            $name = $db->escape($spam->user_name);
            $user_id = (int)$spam->user_id;
            $esc_email = $db->escape($email);
            $subject = $db->escape($spam->subject);
            $ip = $db->escape($spam->user_ip);
            $time = (int)time();
            $reply_id = (int)$spam->param3;
            $message = $db->escape($spam->spam_text);
            $query = "INSERT INTO #__kunena_messages
                (catid, name, userid, email, subject, time, ip)
				VALUES($catid, '$name', $user_id, '$esc_email', '$subject', $time, '$ip')";
            $db->setQuery($query);
            $db->query();
            $id = (int)$db->insertid();
        if ($id) {
            if ($reply_id) {
                $thread = (int)KunenaPlugin::getMessageThread($reply_id);
                if (!$thread) {
                    $reply_id = 0;
                    $thread =
                        (int)KunenaPlugin::createThread(
                            $catid,
                            $spam->subject,
                            $id,
                            $time,
                            $spam->spam_text,
                            $user_id,
                            $spam->user_name
                        );
                }
            } else {
                $reply_id = 0;
                $thread =
                        (int)KunenaPlugin::createThread(
                            $catid,
                            $spam->subject,
                            $id,
                            $time,
                            $spam->spam_text,
                            $user_id,
                            $spam->user_name
                        );
            }
            $query = "UPDATE #__kunena_messages
                    SET parent = $reply_id, thread = $thread
					WHERE id = $id;";
            $db->setQuery($query);
            if ($db->query()) {
                $query = "INSERT INTO #__kunena_messages_text(mesid, message)
						VALUES($id, '$message')";
                $db->setQuery($query);
                if ($db->query()) {
                    $query = "DELETE FROM #__rantispam_spams_detected
							WHERE spam_id = " . (int)$spam->spam_id;
                    $db->setQuery($query);
                    return $db->query();
                }
            }

        }
            return false;
        }
    }

    static function train($spamFilter)
    {
        $db = JFactory::getDBO();
        $query = "SELECT #__kunena_messages.subject, #__kunena_messages_text.message
            FROM #__kunena_messages
            LEFT JOIN #__kunena_messages_text
            ON #__kunena_messages_text.mesid = #__kunena_messages.id
			WHERE #__kunena_messages.hold = 0";
        $db->setQuery($query);
        $messages = $db->loadObjectList();
        $count = 0;
        foreach ($messages as $message) {
            if ($spamFilter->learn($message->subject . " " . $message->message, false)) {
                $count++;
            }
        }
        return $count;
    }

    static function getMessageThread($id)
    {
        $db = JFactory::getDBO();
        $query = "SELECT thread FROM #__kunena_messages WHERE id = " . (int)$id;
        $db->setQuery($query);
        return $db->loadResult();
    }

    static function createThread(
        $catid,
        $subject,
        $postid,
        $posttime,
        $posttext,
        $userid,
        $username
    ) {

        $db = JFactory::getDBO();
        $catid = (int)$catid;
        $subject = $db->escape($subject);
        $postid = (int)$postid;
        $posttime = (int)$posttime;
        $posttext = $db->escape($posttext);
        $userid = (int)$userid;
        $username = $db->escape($username);
        $query = "INSERT INTO
            #__kunena_topics(category_id, subject, posts, first_post_id,
            first_post_time, first_post_userid, first_post_message,
            first_post_guest_name, last_post_id, last_post_time,
            last_post_userid, last_post_message, last_post_guest_name)
            VALUES($catid, '$subject', 1, $postid,
            $posttime, $userid, '$posttext',
            '$username', $postid, $posttime,
			$userid, '$posttext', '$username')";
        $db->setQuery($query);
        $db->query();
        return $db->insertid();
    }
}
