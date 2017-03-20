<?php
/**
 * @version     3.0.0
 * @package     com_rantispam
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ratmil <ratmil_torres@yahoo.com> - http://www.ratmilwebsolutions.com
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Rantispam records.
 */
class RantispamModelspams extends JModelList
{

    /**
     * Constructor.
     *
     * @param    array    An optional associative array of configuration settings.
     * @see        JController
     * @since    1.6
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                                'id', 'a.id',
                'user_id', 'a.user_id',
                'user_ip', 'a.user_ip',
                'spam_text', 'a.spam_text',
                'spam_score', 'a.spam_score',
                'detect_time', 'a.detect_time',

            );
        }

        parent::__construct($config);
    }


	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context.'.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);
        
        
        
		// Load the parameters.
		$params = JComponentHelper::getParams('com_rantispam');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.detect_time', 'desc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id.= ':' . $this->getState('filter.search');
		$id.= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.*'
			)
		);
		$query->from('`#__rantispam_spams_detected` AS a');


		// Join over the user field 'user'
		$query->select('user.username AS username');
		$query->join('LEFT', '#__users AS user ON user.id = a.user_id');



		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = '.(int) substr($search, 3));
			} else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');
                $query->where('( a.user_ip LIKE '.$search.'  OR  a.spam_text LIKE '.$search.
					' OR username LIKE ' . $search . ')');
			}
		}
        
        
        
        
		// Add the list ordering clause.
        $orderCol	= $this->state->get('list.ordering');
        $orderDirn	= $this->state->get('list.direction');
        if ($orderCol && $orderDirn) {
            $query->order($db->escape($orderCol.' '.$orderDirn));
        }

		return $query;
	}
	
	public function blockUsers($pks)
	{
		foreach($pks as $key => $pk)
		{
			$pks[$key] = (int)$pk;
		}
		$db = JFactory::getDBO();
		$cids = implode(",", $pks);
		$query = "SELECT user_id FROM #__rantispam_spams_detected WHERE spam_id IN ($cids)";
		$db->setQuery( $query );
		$user_ids = $db->loadColumn();
		if($user_ids)
		{
			foreach($user_ids as $key => $user_id)
			{
				$user_ids[$key] = (int)$user_id;
			}
			$user_ids = implode(",", $user_ids);
			$query = "UPDATE #__users SET block = 1 WHERE id IN ($user_ids)";
			$db->setQuery( $query );
			$db->query();
		}
	}
	
	public function banips($pks)
	{
		$db = JFactory::getDBO();
		foreach($pks as $pk)
		{
			$query = "SELECT user_ip FROM #__rantispam_spams_detected WHERE spam_id = " . (int)$pk;
			$db->setQuery( $query );
			$user_ip = $db->loadResult();
			if($user_ip)
			{
				$ban_ip = $db->escape($user_ip);
				$query = "SELECT bannedip FROM #__rantispam_banip WHERE bannedip = '$ban_ip'";
				$db->setQuery( $query );
				if(!$db->loadResult())
				{
					$query = "INSERT INTO #__rantispam_banip(bannedip) VALUES('$ban_ip')";
					$db->setQuery( $query );
					$db->query();
				}
			}
		}
	}
	
	public function deleteAll()
	{
		$db = JFactory::getDBO();
		$query = "DELETE FROM #__rantispam_spams_detected";
		$db->setQuery( $query );
		$db->query();
	}
	
	public function train()
	{
		$params = JComponentHelper::getParams('com_rantispam');
		//Use train with local database only, do not use Akismet
		$spamfilter = SpamFilter::getInstance(true);
		$spamfilter->includeAlways = true;
		$count = 0;
		if($params->get('check_kunena', true))
			$count += KunenaPlugin::train($spamfilter);
		if($params->get('use_plugin', false))
		{
			JPluginHelper::importPlugin("rantispam");
			$dispatcher	= JDispatcher::getInstance();
			$dispatcher->trigger('onTrain', array ($spamfilter, &$c));
			$count += $c;
		}
		return $count;
	}
	
	public function setNotSpam($pks)
	{
		$db = JFactory::getDBO();
		foreach($pks as $key => $pk)
		{
			$pks[$key] = (int)$pk;
		}
		$cids = implode(",", $pks);
		$query = "SELECT * FROM #__rantispam_spams_detected WHERE spam_id IN ($cids)";
		$db->setQuery($query);
		$spams = $db->loadObjectList();
		$spamfilter = SpamFilter::getInstance();
		$dispatcher = null;
		
		foreach($spams as $spam)
		{
			if($spam->provider == 'kunena')
				KunenaPlugin::setHam($spam, $spamfilter);
			else
			{
				if($dispatcher == null)
				{
					JPluginHelper::importPlugin("rantispam");
					$dispatcher	= JDispatcher::getInstance();
				}
				$dispatcher->trigger('onSetHam', array ($spam->provider, $spam, $spamfilter));
			}
		}
	}
}
