<?php
/**
 * @component RAntispam
 * @author Ratmil Torres
 * @copyright (C) Ratmil Torres
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

class com_rantispamInstallerScript
{
	/**
	 * Constructor
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function __constructor(JAdapterInstance $adapter)
	{
	}

	/**
	 * Called before any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($route, JAdapterInstance $adapter)
	{
		return true;
	}

	/**
	 * Called after any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, JAdapterInstance $adapter)
	{
		return true;
	}

	/**
	 * Called on installation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function install(JAdapterInstance $adapter)
	{
		$this->updatePlugins();
		$this->init_prob_data();
		return true;
	}

	/**
	 * Called on update
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function update(JAdapterInstance $adapter)
	{
		$this->updatePlugins();
		$this->init_prob_data();
		return true;
	}

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		$this->uninstallPlugins();
		return true;
	}
	
	function uninstallPlugins()
	{
		$this->uninstall_plugin('rantispam', 'system');
		
	}
	
	function install_plugin($component, $element, $folder = 'system', $extra_folders = null, $extra_files = null, $language_files = null)
	{
		$this->uninstall_plugin($element, $folder, $extra_folders);
		$db = JFactory::getDBO();
		$name = $folder . ' - ' . $element;
		$e_name = $db->escape($name);
		$e_element = $db->escape($element);
		$e_folder = $db->escape($folder);
		$version = new JVersion;
		if($version->RELEASE >= "1.6")
		{
			$result = true;
		
			$dest_folder = JPATH_SITE.'/'.'plugins'.'/'.$folder.'/'.$element;
			$dest_file_php = JPATH_SITE.'/'.'plugins'.'/'.$folder.'/'.$element.'/'.$element.'.php';
			$dest_file_xml = JPATH_SITE.'/'.'plugins'.'/'.$folder.'/'.$element.'/'.$element.'.xml';

			if(!JFolder::exists($dest_folder))
			{
				JFolder::create($dest_folder);
			}
			if(is_array($extra_folders))
			{
				foreach($extra_folders as $extra_folder)
				{
					$new_folder = JPATH_ADMINISTRATOR.'/components/'.$component.'/extensions/plugins/'.$folder.'/'.$element.'/'.$extra_folder;
					if(!JFolder::copy($new_folder, $dest_folder . "/" . $extra_folder))
					{
						echo "Error copying folder ($new_folder) to ($dest_folder) folder<br/>";
						$result = false;
					}
				}
			}
			
			$file_php = JPATH_ADMINISTRATOR.'/components/'.$component.'/extensions/plugins/'.$folder.'/'.$element.'/'.$element.'.php';
			if(!JFile::exists($file_php) || !JFile::copy($file_php, $dest_file_php))
			{
				echo "Error copying file ($file_php) to ($dest_file_php)<br/>";
				$result = false;
			}
			$file_xml = JPATH_ADMINISTRATOR.'/components/'.$component.'/extensions/plugins/'.$folder.'/'.$element.'/'.$element.'.xml';
			if(!JFile::exists($file_xml) || !JFile::copy($file_xml, $dest_file_xml))
			{
				echo "Error copying file ($file_xml) to ($dest_file_xml)<br/>";
				$result = false;
			}
			
			if($extra_files)
			{
				foreach($extra_files as $extra_file)
				{
					$source_file = JPATH_ADMINISTRATOR.'/components/'.$component.'/extensions/plugins/'.$folder.'/'.$element.'/'.$extra_file;
					$dest_file = JPATH_SITE.'/plugins/'.$folder.'/'.$element.'/'.$extra_file;
					if(!JFile::exists($source_file) || !JFile::copy($source_file, $dest_file))
					{
						echo "Error copying file ($source_file) to ($dest_file)<br/>";
						$result = false;
					}
				}
			}
			
			if($language_files)
			{
				foreach($language_files as $language_file)
				{
					$dot_pos = strpos($language_file, ".");
					if($dot_pos !== false)
					{
						$language = substr($language_file, 0, $dot_pos);
						$source_file = JPATH_ADMINISTRATOR.'/components/'.$component.'/extensions/plugins/'.$folder.'/'.$element.'/'.$language_file;
						$dest_file = JPATH_ADMINISTRATOR.'/language/'.$language.'/'.$language_file;
						if(JFile::exists($source_file) && JFolder::exists(JPATH_ADMINISTRATOR.'/language/'.$language))
						{
							JFile::copy($source_file, $dest_file);
						}
					}
				}
			}
			
			$query = "INSERT INTO #__extensions(name, type, element, folder, enabled, access) 
				VALUES('$e_name', 'plugin', '$e_element', '$e_folder', 1, 1)";
			$db->setQuery($query);
			if(!$db->query())
			{
				echo "Error inserting plugin record<br/>";
				$result = false;
			}
			if(!$result)
				$this->uninstall_plugin($element, $folder );
			return false;
		}
	}
	
	function uninstall_plugin($element, $folder = 'system', $extra_folders = null, $extra_files = null, $language_files = null)
	{
		$db = JFactory::getDBO();
		$e_element = $db->escape($element);
		$e_folder = $db->escape($folder);
		$version = new JVersion;
		if($version->RELEASE >= "1.6")
		{
			$db = JFactory::getDBO();
			$db->setQuery("DELETE FROM #__extensions WHERE element='$e_element' AND folder='$e_folder' AND type='plugin'");
			$db->query();
			$dest_folder = JPATH_SITE.'/plugins/'.$folder.'/'.$element;
			if(JFolder::exists($dest_folder))
			{
				JFolder::delete($dest_folder);
			}
			if($language_files)
			{
				foreach($language_files as $language_file)
				{
					$dot_pos = strpos($language_file, ".");
					if($dot_pos !== false)
					{
						$language = substr($language_file, 0, $dot_pos);
						$dest_file = JPATH_ADMINISTRATOR.'/language/'.$language.'/'.$language_file;
						if(JFile::exists($dest_file))
						{
							JFile::delete($dest_file);
						}
					}
				}
			}
		}
	}
	
	function updatePlugins()
	{
		$this->uninstall_plugin('rantispam', 'system');
		$this->install_plugin('com_rantispam', 'rantispam', 'system');
	}
	
	function init_prob_data()
	{
		$db = JFactory::getDBO();
		$db->setQuery("SELECT COUNT(*) FROM #__rantispam_token_count");
		$count = $db->loadResult();
		$db->setQuery("SELECT COUNT(*) FROM #__rantispam_tokens_prob");
		$prob_count = $db->loadResult();
		if($count == 0 || $prob_count == 0)
		{
			$db->setQuery("DELETE FROM #__rantispam_token_count");
			$db->query();
			$db->setQuery("DELETE FROM #__rantispam_tokens_prob");
			$db->query();
			$data_file = JPATH_ADMINISTRATOR . "/components/com_rantispam/data/initdata.sav";
			require_once(JPATH_ADMINISTRATOR . "/components/com_rantispam/classes/importprobdata.php");
			$importer = new ProbData_Importer();
			$importer->importFromXml($data_file);
		}
	}

}
?>