<?php
/**
 * @version     3.0.0
 * @package     com_rantispam
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ratmil <ratmil_torres@yahoo.com> - http://www.ratmilwebsolutions.com
 */

// No direct access.
defined('_JEXEC') or die;

require_once JPATH_COMPONENT.'/controller.php';

/**
 * Documents list controller class.
 */
class RantispamControllerSpam extends RantispamController
{
	public function report()
	{
		if (!JFactory::getUser()->authorise('forum.manage', 'com_rantispam')) 
		{
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}
		$provider = JRequest::getVar('prov');
		$redirect = "index.php";
		$message = JText::_("COM_RANTISPAM_MESSAGE_REPORTED_AS_SPAM");
		$model = $this->getModel('spam');
		$model->reportSpam($provider, $redirect);
		$mainframe = JFactory::getApplication();
		$mainframe->redirect($redirect, $message);
		exit;
	}
}