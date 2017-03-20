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

/**
 * Rantispam helper.
 */
class RantispamHelper
{
	/**
	 * Configure the Linkbar.
	 */
	public static function addSubmenu($vName = '')
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_RANTISPAM_TITLE_SPAMS'),
			'index.php?option=com_rantispam&view=spams',
			$vName == 'spams'
		);
		
		JHtmlSidebar::addEntry(
			JText::_('COM_RANTISPAM_TITLE_BANNEDIPS'),
			'index.php?option=com_rantispam&view=bannedips',
			$vName == 'bannedips'
		);
		
		JHtmlSidebar::addEntry(
			JText::_('COM_RANTISPAM_TITLE_ABOUT'),
			'index.php?option=com_rantispam&view=about',
			$vName == 'about'
		);

	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return	JObject
	 * @since	1.6
	 */
	public static function getActions()
	{
		$user	= JFactory::getUser();
		$result	= new JObject;

		$assetName = 'com_rantispam';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action) {
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}
}
