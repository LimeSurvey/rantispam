<?php
/**
 * @component RAntispam Component
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

define("GoodTokenWeight", 2);
define("MinTokenCount", 0);
define("MinCountForInclusion", 5);
define("MinScore", 0.011);
define("MaxScore", 0.99);
define("LikelySpamScore", 0.9998);
define("CertainSpamScore", 0.9999);
define("CertainSpamCount", 10);
define("InterestingWordCount", 15);

class RAntispamFilter extends SpamDetector
{

	public function test($text)
	{
		$words_count = preg_match_all('/([a-zA-Z]\w+)\W*/', $text, $words);
		$tokens = $this->getTokensProb($words[1]);
		$tokens_prob = array();
		foreach($words[1] as $word)
		{
			foreach($tokens as $token)
			{
				if($token->token == $word)
				{
					$tokens_prob[] = $token;
					break;
				}
			}
		}
		usort($tokens_prob, array($this, "compare_token"));
		$index = 0;
		$mult = 1.0;
		$comb = 1.0;
		foreach($tokens_prob as $token)
		{
			$prob = $token->prob;
			$mult = $mult * $prob;
			$comb = $comb * (1 - $prob);
			$index++;
			if($index >= InterestingWordCount)
				break;
		}
		if($mult + $comb > 0.0000001)
			return $mult / ($mult + $comb);
		else
			return 0;
	}
	
	function compare_token($token1, $token2)
	{
		$interest1 = 0.5 - abs(0.5 - $token1->prob);
		$interest2 = 0.5 - abs(0.5 - $token2->prob);
		if($interest1 < $interest2)
			return -1;
		else if($interest1 > $interest2)
			return 1;
		else
			return 0;
	}
	
	function getTokensProb($words)
	{
		if(count($words) == 0)
			return null;
		$db = JFactory::getDBO();
		$query = "SELECT token, prob, in_ham, in_spam FROM #__rantispam_tokens_prob WHERE token IN (";
		$notfirst = false;
		foreach($words as $word)
		{
			if($notfirst)	
				$query .= ", ";
			$query .= "'" . $db->escape($word) . "'";
			$notfirst = true;
		}
		$query .= ")";
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	function getTokensCount()
	{
		$db = JFactory::getDBO();
		$query = "SELECT good_count, bad_count FROM #__rantispam_token_count";
		$db->setQuery($query, 0, 1);
		return $db->loadObject();
	}
	
	function isTextCalculated($text)
	{
		$hash = sha1($text);
		$db = JFactory::getDBO();
		$hash = $db->escape($hash);
		$db->setQuery("SELECT COUNT(*) FROM #__rantispam_messages_hash 
			WHERE hash='$hash'");
		$result = $db->loadResult();
		if($result == 0)
		{
			$db->setQuery("INSERT INTO #__rantispam_messages_hash(hash) VALUES('$hash')");
			$db->query();
			return false;
		}
		else
			return true;
	}
	
	function learn($text, $isSpam)
	{
		if($this->isTextCalculated($text))
			return false;
		$words_count = preg_match_all('/([a-zA-Z]\w+)\W*/', $text, $matches);
		$words = array();
		$tokens_count = $this->getTokensCount();
		if(!$tokens_count)
		{
			$tokens_count = new stdClass();
			$tokens_count->good_count = 0;
			$tokens_count->bad_count = 0;
		}
		foreach($matches[1] as $match)
		{
			if(array_key_exists($match, $words))
			{
				$words[$match]++;
			}
			else
				$words[$match] = 1;
		}
		foreach($words as $token => $count)
		{
			if($isSpam)
				$this->calculateTokenProbality($token, 0, $count, $tokens_count);
			else
				$this->calculateTokenProbality($token, $count, 0, $tokens_count);
		}
		return true;
	}
	
	function removeFromCalculatedText($text)
	{
		$hash = sha1($text);
		$db = JFactory::getDBO();
		$hash = $db->escape($hash);
		$db->setQuery("SELECT COUNT(*) FROM #__rantispam_messages_hash 
			WHERE hash='$hash'");
		$result = $db->loadResult();
		if($result == 0)
		{
			return false;
		}
		else
		{
			$db->setQuery("DELETE FROM #__rantispam_messages_hash WHERE hash = '$hash'");
			$db->query();
			return true;
		}
	}
	
	function rollback_learning($text, $isSpam)
	{
		if(!$this->removeFromCalculatedText($text))
			return;
		$words_count = preg_match_all('/([a-zA-Z]\w+)\W*/', $text, $matches);
		$words = array();
		$tokens_count = $this->getTokensCount();
		if(!$tokens_count)
		{
			$tokens_count = new stdClass();
			$tokens_count->good_count = 0;
			$tokens_count->bad_count = 0;
		}
		foreach($matches[1] as $match)
		{
			if(array_key_exists($match, $words))
			{
				$words[$match]++;
			}
			else
				$words[$match] = 1;
		}
		foreach($words as $token => $count)
		{
			if($is_spam)
				$this->calculateTokenProbality($token, 0, -1 * $count, $tokens_count);
			else
				$this->calculateTokenProbality($token, -1 * $count, 0, $tokens_count);
		}
	}
	
	function calculateTokenProbality($token, $good_count, $bad_count, $tokens_count)
	{
		$db = JFactory::getDBO();
		$t = $db->escape($token);
		$query = "SELECT token, prob, in_ham, in_spam FROM #__rantispam_tokens_prob WHERE token = '$t'";
		$db->setQuery($query);
		$g = $good_count;
		$b = $bad_count;
		$found = false;
		$token_prob = $db->loadObject();
		if($token_prob)
		{
			$g += $token_prob->in_ham;
			$b += $token_prob->in_spam;
			$found = true;
		}
		$g *= GoodTokenWeight;
		if($g + $b >= MinCountForInclusion)
		{
			$goodfactor = min(1, ((float)$g)/((float)$tokens_count->good_count));
			$badfactor = min(1, ((float)$b)/((float)$tokens_count->bad_count));
			$prob = max(MinScore, 
							min(MaxScore, $badfactor / ($goodfactor + $badfactor))
						);
			if ($g == 0)
			{
				$prob = ($b > CertainSpamCount) ? CertainSpamScore : LikelySpamScore;
			}
			$prob = $db->escape((float)$prob);
			if(!$found)
			{
				$query = "INSERT INTO #__rantispam_tokens_prob
					(token, in_ham, in_spam, prob, update_time)
					VALUES('$t', $good_count, $bad_count, '$prob', NOW())";
				$db->setQuery($query);
				$db->query();
				$this->increaseTokenCount($good_count, $bad_count);
				
			}
			else
			{
				$query = "UPDATE #__rantispam_tokens_prob
					 SET prev_prob = prob, prob = '$prob', 
						in_ham = in_ham + $good_count, 
						in_spam = in_spam + $bad_count,
						update_time = NOW()
					 WHERE token = '$t'";
				$db->setQuery($query);
				$db->query();
			}
			
		}
	}
	
	function increaseTokenCount($good_count, $bad_count)
	{
		//SELECT good_count, bad_count FROM #__rantispam_token_count
		$db = JFactory::getDBO();
		$good_count = (int)$good_count;
		$bad_count = (int)$bad_count;
		$query = "SELECT count(*) FROM #__rantispam_token_count";
		$db->setQuery($query);
		$result = $db->loadResult();
		if($result == 0)
			$query = "INSERT INTO #__rantispam_token_count(good_count, bad_count) VALUES($good_count, $bad_count)";
		else
			$query = "UPDATE #__rantispam_token_count 
				SET good_count = good_count + $good_count, bad_count = bad_count + $bad_count";
		$db->setQuery($query);
		$db->query();
	}
}

?>