<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_categories
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * The Category Controller
 *
 * @since  1.6
 */
class CategoriesControllerCategory extends JControllerForm
{
	/**
	 * The extension for which the categories apply.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $extension;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since  1.6
	 * @see    JControllerLegacy
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// Guess the JText message prefix. Defaults to the option.
		if (empty($this->extension))
		{
			$this->extension = $this->input->get('extension', 'com_content');
		}
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowAdd($data = array())
	{
		$user = JFactory::getUser();

		return ($user->authorise('core.create', $this->extension) || count($user->getAuthorisedCategories($this->extension, 'core.create')));
	}

	/**
	 * Method to check if you can edit a record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowEdit($data = array(), $key = 'parent_id')
	{
		$recordId = (int) isset($data[$key]) ? $data[$key] : 0;
		$user = JFactory::getUser();

		// Check "edit" permission on record asset (explicit or inherited)
		if ($user->authorise('core.edit', $this->extension . '.category.' . $recordId))
		{
			return true;
		}

		// Check "edit own" permission on record asset (explicit or inherited)
		if ($user->authorise('core.edit.own', $this->extension . '.category.' . $recordId))
		{
			// Need to do a lookup from the model to get the owner
			$record = $this->getModel()->getItem($recordId);

			if (empty($record))
			{
				return false;
			}

			$ownerId = $record->created_user_id;

			// If the owner matches 'me' then do the test.
			if ($ownerId == $user->id)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   object  $model  The model.
	 *
	 * @return  boolean  True if successful, false otherwise and internal error is set.
	 *
	 * @since   1.6
	 */
	public function batch($model = null)
	{
		$this->checkToken();

		// Set the model
		/** @var CategoriesModelCategory $model */
		$model = $this->getModel('Category');

		// Preset the redirect
		$this->setRedirect('index.php?option=com_categories&view=categories&extension=' . $this->extension);

		return parent::batch($model);
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId);
		$append .= '&extension=' . $this->extension;

		return $append;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.6
	 */
	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();
		$append .= '&extension=' . $this->extension;

		return $append;
	}

	/**
	 * Function that allows child controller access to model data after the data has been saved.
	 *
	 * @param   JModelLegacy  $model      The data model object.
	 * @param   array         $validData  The validated data.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	protected function postSaveHook(JModelLegacy $model, $validData = array())
	{
		$item = $model->getItem();

		if (isset($item->params) && is_array($item->params))
		{
			$registry = new Registry($item->params);
			$item->params = (string) $registry;
		}

		if (isset($item->metadata) && is_array($item->metadata))
		{
			$registry = new Registry($item->metadata);
			$item->metadata = (string) $registry;
		}
	}


	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.6
	 */
	public function save($key = null, $urlVar = null)
	{
	  /** - My Books Override - **/

	  JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
	  // Gets the jform data.
	  $data = $this->input->post->get('jform', array(), 'array');
	  // Includes the Mybooks helper class.
	  JLoader::register('MybooksHelper', JPATH_ADMINISTRATOR.'/components/com_mybooks/helpers/mybooks.php');

	  if((int)$data['id'] && ($data['published'] == 2 || $data['published'] == -2) && !MybooksHelper::checkMainCategory($data['id']))
	  {
	    $this->setRedirect(JRoute::_('index.php?option=com_categories&view=category'.$this->getRedirectToItemAppend($data['id'],'id'),false));
	    return false;
	  }

	  // Hands over to the com_categories parent method.
	  return parent::save($key, $urlVar);
	}
}
