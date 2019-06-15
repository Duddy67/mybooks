<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access'); 
 

class MybooksControllerBook extends JControllerForm
{
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
    // Gets the jform data.
    //$data = $this->input->post->get('jform', array(), 'array');

    // Gets the current date and time (UTC).
    //$now = JFactory::getDate()->toSql();

    // Saves the modified jform data array 
    //$this->input->post->set('jform', $data);

    // Hands over to the parent function.
    return parent::save($key, $urlVar);
  }


  /**
   * Method to check if you can edit an existing record.
   *
   * Extended classes can override this if necessary.
   *
   * @param   array   $data  An array of input data.
   * @param   string  $key   The name of the key for the primary key; default is id.
   *
   * @return  boolean
   *
   * @since   1.6
   */
  protected function allowEdit($data = array(), $key = 'id')
  {
    $itemId = $data['id'];
    $user = JFactory::getUser();

    // Get the item owner id.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('created_by')
	  ->from('#__mybooks_book')
	  ->where('id='.(int)$itemId);
    $db->setQuery($query);
    $createdBy = $db->loadResult();

    $canEdit = $user->authorise('core.edit', 'com_mybooks');
    $canEditOwn = $user->authorise('core.edit.own', 'com_mybooks') && $createdBy == $user->id;

    // Allow edition. 
    if($canEdit || $canEditOwn) {
      return 1;
    }

    // Hand over to the parent function.
    return parent::allowEdit($data, $key);
  }
}

