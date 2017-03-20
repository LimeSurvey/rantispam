<?php
/**
 * @version     3.0.0
 * @package     com_rantispam
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ratmil <ratmil_torres@yahoo.com> - http://www.ratmilwebsolutions.com
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit
 */
class RantispamViewAbout extends JViewLegacy
{
	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
		}
		RantispamHelper::addSubmenu('about');
		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 */
	protected function addToolbar()
	{
		JToolBarHelper::title(JText::_('COM_RANTISPAM_TITLE_ABOUT'), 'spam.png');
		if (JFactory::getUser()->authorise('core.admin', 'com_rantispam')) {
			JToolBarHelper::preferences('com_rantispam');
		}
		JToolBarHelper::title(JText::_('COM_RANTISPAM_TITLE_ABOUT'), 'spam.png');
	}
}
