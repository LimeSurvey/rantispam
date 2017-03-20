<?php
/**
 * @component RAntispam Component
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

//Base class for spam detection
class SpamDetector
{
	public function test($text)
	{
		return 0;
	}
	
	public function learn($text, $isSpam)
	{
	}
	
	public function rollback_learning($text, $isSpam)
	{
	}
}

class SpamFilter
{
	static $_spamDetectorObject = null;
	
	static function getInstance($forcelocal = false)
	{	
		if(!self::$_spamDetectorObject)
		{
			if($forcelocal)
				$akismet_key = "";
			else
				$akismet_key = self::useAkismet();
			if($akismet_key)
				self::$_spamDetectorObject = new AkismetFilter($akismet_key);
			else
				self::$_spamDetectorObject = new RAntispamFilter();
		}
		return SpamFilter::$_spamDetectorObject;
	}
	
	static function useAkismet()
	{
		$params = JComponentHelper::getParams('com_rantispam');
		$use_akismet = $params->get('use_akismet', 0);
		if($use_akismet)
			return $params->get('akismet_key', '');
		return "";
	}
}

?>