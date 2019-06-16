<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2017 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access'); 


class MybooksModelBook extends JModelItem
{

  protected $_context = 'com_mybooks.book';

  /**
   * Method to auto-populate the model state.
   *
   * Book. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   *
   * @return void
   */
  protected function populateState()
  {
    $app = JFactory::getApplication('site');

    // Load state from the request.
    $pk = $app->input->getInt('id');
    $this->setState('book.id', $pk);

    // Load the global parameters of the component.
    $params = $app->getParams();
    $this->setState('params', $params);

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }


  // Returns a Table object, always creating it.
  public function getTable($type = 'Book', $prefix = 'MybooksTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed    Object on success, false on failure.
   *
   * @since   12.2
   */
  public function getItem($pk = null)
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState('book.id');
    $user = JFactory::getUser();

    if($this->_item === null) {
      $this->_item = array();
    }

    if(!isset($this->_item[$pk])) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select($this->getState('list.select', 'b.id,b.title,b.alias,b.intro_text,b.full_text,b.catid,b.published,'.
				     'b.checked_out,b.checked_out_time,b.created,b.created_by,b.access,b.params,b.metadata,'.
				     'b.metakey,b.metadesc,b.hits,b.publish_up,b.publish_down,b.language,b.modified,b.modified_by'))
	    ->from($db->quoteName('#__mybooks_book').' AS b')
	    ->where('b.id='.$pk);

      // Join on category table.
      $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	    ->join('LEFT', '#__categories AS ca on ca.id = b.catid');

      // Join on user table.
      $query->select('us.name AS author')
	    ->join('LEFT', '#__users AS us on us.id = b.created_by');

      // Join over the categories to get parent category titles
      $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	    ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

      // Filter by language
      if($this->getState('filter.language')) {
	$query->where('b.language in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
      }

      if((!$user->authorise('core.edit.state', 'com_mybooks')) && (!$user->authorise('core.edit', 'com_mybooks'))) {
	// Filter by start and end dates.
	$nullDate = $db->quote($db->getNullDate());
	$nowDate = $db->quote(JFactory::getDate()->toSql());
	$query->where('(b.publish_up = '.$nullDate.' OR b.publish_up <= '.$nowDate.')')
	      ->where('(b.publish_down = '.$nullDate.' OR b.publish_down >= '.$nowDate.')');
      }

      $db->setQuery($query);
      $data = $db->loadObject();

      if(is_null($data)) {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_MYBOOKS_ERROR_BOOK_NOT_FOUND'), 'error');
	return false;
      }

      // Convert parameter fields to objects.
      $registry = new JRegistry;
      $registry->loadString($data->params);

      $data->params = clone $this->getState('params');
      $data->params->merge($registry);

      $user = JFactory::getUser();
      // Technically guest could edit an article, but lets not check that to improve performance a little.
      if(!$user->get('guest')) {
	$userId = $user->get('id');
	$asset = 'com_mybooks.book.'.$data->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $data->params->set('access-edit', true);
	}

	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $data->created_by) {
	    $data->params->set('access-edit', true);
	  }
	}
      }

      $data->categories = $this->getCategories();

      // Get the tags
      $data->tags = new JHelperTags;
      $data->tags->getItemTags('com_mybooks.book', $data->id);

      $this->_item[$pk] = $data;
    }

    return $this->_item[$pk];
  }


  /**
   * Returns the id of the categories bound to a given item.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  array	    The category ids.
   */
  public function getCategories($pk = null)
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState('book.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('id, title, alias, language')
	  ->from('#__mybooks_book_cat_map')
	  ->join('INNER', '#__categories ON id=cat_id')
	  ->where('book_id='.(int)$pk);
    $db->setQuery($query);

    return $db->loadObjectList();
  }


  /**
   * Increment the hit counter for the book.
   *
   * @param   integer  $pk  Optional primary key of the book to increment.
   *
   * @return  boolean  True if successful; false otherwise and internal error set.
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('book.id');

      $table = JTable::getInstance('Book', 'MybooksTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }
}

