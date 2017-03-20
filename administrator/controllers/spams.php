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

jimport('joomla.application.component.controlleradmin');

/**
 * Spams list controller class.
 */
class RantispamControllerSpams extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 * @since	1.6
	 */
	public function getModel($name = 'spam', $prefix = 'RantispamModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));
		return $model;
	}
    
    
	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = JFactory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}
    
    public function train()
	{
		$model = $this->getModel('spams');
		$count = $model->train();
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::sprintf("COM_RANTISPAM_TRAIN_MESSAGES_ANALIZED", $count));
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . 
			'&view=' . $this->view_list, false));
	}
	
	public function banip()
	{
		$input = JFactory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$model = $this->getModel('spams');
		$model->banips($pks);
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_("COM_RANTISPAM_IPS_BANNED"));
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
	
	public function block()
	{
		$input = JFactory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$model = $this->getModel('spams');
		$model->blockUsers($pks);
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_("COM_RANTISPAM_USERS_BLOCKED"));
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
	
	public function notspam()
	{
		$input = JFactory::getApplication()->input;
		$pks = $input->post->get('cid', array(), 'array');
		$model = $this->getModel('spams');
		$model->setNotSpam($pks);
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_("COM_RANTISPAM_MESSAGES_WERE_MARKED_AS_NOT_SPAM"));
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
	
	public function deleteall()
	{
		$model = $this->getModel('spams');
		$model->deleteAll();
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_("COM_RANTISPAM_ALL_MESSAGES_DELETED"));
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
    
}