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
 * View class for a list of Rantispam.
 */
class RantispamViewSpams extends JViewLegacy
{
	protected $items;
	protected $pagination;
	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors));
		}
        
		RantispamHelper::addSubmenu('spams');
        
		$this->addToolbar();
        
        $this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT.'/helpers/rantispam.php';

		$state	= $this->get('State');
		$canDo	= RantispamHelper::getActions($state->get('filter.category_id'));

		JToolBarHelper::title(JText::_('COM_RANTISPAM_TITLE_SPAMS'), 'spams.png');


		JToolBarHelper::editList('spam.edit','COM_RANTISPAM_VIEW_SPAM');

		if ($canDo->get('core.edit.state')) {

             JToolBarHelper::deleteList('', 'spams.delete','JTOOLBAR_DELETE');
		}

		if ($canDo->get('core.admin')) {
			JToolBarHelper::preferences('com_rantispam');
		}
		JToolBarHelper::custom('spams.train', '', '', 'COM_RANTISPAM_TOOLBAR_TRAIN', false);
		JToolBarHelper::custom('spams.banip', '', '', 'COM_RANTISPAM_TOOLBAR_BANIP');
		JToolBarHelper::custom('spams.block', '', '', 'COM_RANTISPAM_TOOLBAR_BLOCK_SPAMMER');
		JToolBarHelper::custom('spams.notspam', '', '', 'COM_RANTISPAM_TOOLBAR_NOT_SPAM');
		JToolBarHelper::custom('spams.deleteall', '', '', 'COM_RANTISPAM_DELETE_ALL', false);
        
        //Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_rantispam&view=spams');
        
        $this->extra_sidebar = '';
        
        
	}
    
	protected function getSortFields()
	{
		return array(
		'a.id' => JText::_('JGRID_HEADING_ID'),
		'a.username' => JText::_('COM_RANTISPAM_SPAMS_USER'),
		'a.user_ip' => JText::_('COM_RANTISPAM_SPAMS_USER_IP'),
		'a.spam_text' => JText::_('COM_RANTISPAM_SPAMS_SPAM_TEXT'),
		'a.spam_score' => JText::_('COM_RANTISPAM_SPAMS_SPAM_SCORE'),
		'a.detect_time' => JText::_('COM_RANTISPAM_SPAMS_DETECT_TIME'),
		);
	}

    
}
