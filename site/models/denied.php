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
class RantispamModelDenied extends JModelLegacy
{	
	public function getDeniedText()
	{
		$params = JComponentHelper::getParams('com_rantispam');
		return $params->get('denial_text', JText::_("COM_RANTISPAM_DENIED_DEFAULT_TEXT"));
	}
}