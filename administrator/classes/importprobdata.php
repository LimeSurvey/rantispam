<?php
/**
 * @component RAntispam
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

class ProbData_Importer
{
	function endElement($parser, $name) 
	{
	}
	
	function startElement($parser, $name, $attrs)
	{
		if($name == "SAMPLESCOUNT")
		{
			$good_count = (int)$attrs['GOOD'];
			$bad_count = (int)$attrs['BAD'];
			$db = JFactory::getDBO();
			$db->setQuery("INSERT INTO #__rantispam_token_count(good_count, bad_count) 
				VALUES($good_count, $bad_count)");
			$db->query();
		}
		else if($name == "TOKEN")
		{
			$db = JFactory::getDBO();
			$token = $db->escape($attrs['NAME']);
			$prob = $db->escape((float)$attrs['PROB']);
			$in_ham = (int)$attrs['GOOD'];
			$in_spam = (int)$attrs['BAD'];
			$db->setQuery("INSERT INTO 
				#__rantispam_tokens_prob(token, prob, in_ham, in_spam, update_time) 
				VALUES('$token', '$prob', $in_ham, $in_spam, NOW())");
			$db->query();
		}
	}
	
	function importFromXml($filePath)
	{
		$xml_parser = xml_parser_create(); 
		xml_set_object($xml_parser, $this);
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true); 
		xml_set_element_handler($xml_parser, "startElement", "endElement"); 
		if (!($fp = @fopen($filePath, "r"))) { 
		   return false;
		} 

		while ($data = @fread($fp, 4096)) { 
			if (!xml_parse($xml_parser, $data, feof($fp))) { 
				echo (sprintf("XML error: %s at line %d", 
				   xml_error_string(xml_get_error_code($xml_parser)), 
				   xml_get_current_line_number($xml_parser)));
				return false;   
			} 
		} 
		xml_parser_free($xml_parser); 
		return true;
	}
}


?>