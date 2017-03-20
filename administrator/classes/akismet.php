<?php
/**
 * @component RAntispam Component
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

class AkismetFilter
{
	static $_AkismetObject = null;
	
	var $key;
	
	public function AkismetFilter($key)
	{
		$this->key = $key;
	}

	public function test($text)
	{
		$akismet = $this->getAkismetObject();
		$akismet->setCommentContent($text);
		return $akismet->isCommentSpam() ? 1.0 : 0.0;
	}
	
	public function learn($text, $isSpam)
	{
		$akismet = $this->getAkismetObject();
		$akismet->setCommentContent($text);
		if($isSpam)
			$akismet->submitSpam();
		else
			$akismet->submitHam();
	}
	
	public function rollback_learning($text, $isSpam)
	{
	}
	
	private function getAkismetObject()
	{
		if(!AkismetFilter::$_AkismetObject)
			AkismetFilter::$_AkismetObject = new Akismet(JUri::root(), $this->key);
		return AkismetFilter::$_AkismetObject;
	}
}