<?php
/**
 * @version     3.0.0
 * @package     com_rantispam
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ratmil <ratmil_torres@yahoo.com> - http://www.ratmilwebsolutions.com
 */


// no direct access
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_rantispam')) 
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

$controller	= JControllerLegacy::getInstance('Rantispam');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
