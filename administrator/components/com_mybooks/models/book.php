<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2017 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access'); 


class MybooksModelBook extends JModelAdmin
{
  // Prefix used with the controller messages.
  protected $text_prefix = 'COM_MYBOOKS';


  /**
   * Returns a Table object, always creating it.
   *
   * @param   string  $type    The table type to instantiate
   * @param   string  $prefix  A prefix for the table class name. Optional.
   * @param   array   $config  Configuration array for model. Optional.
   *
   * @return  JTable    A database object
   */
  public function getTable($type = 'Book', $prefix = 'MybooksTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  /**
   * Method to get the record form.
   *
   * @param   array    $data      Data for the form.
   * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
   *
   * @return  JForm|boolean  A JForm object on success, false on failure
   *
   * @since   1.6
   */
  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_mybooks.book', 'book', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  /**
   * Method to get the data that should be injected in the form.
   *
   * @return  mixed  The data for the form.
   *
   * @since   1.6
   */
  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_mybooks.edit.book.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed  Object on success, false on failure.
   */
  public function getItem($pk = null)
  {
    if($item = parent::getItem($pk)) {
      // Gets both intro_text and full_text together as booktext
      $item->booktext = trim($item->full_text) != '' ? $item->intro_text."<hr id=\"system-readmore\" />".$item->full_text : $item->intro_text;

      // Gets tags for this item.
      if(!empty($item->id)) {
	$item->tags = new JHelperTags;
	$item->tags->getTagIds($item->id, 'com_mybooks.book');

	$item->catids = $this->getCategories();
      }
    }

    return $item;
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
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('cat_id')
	  ->from('#__mybooks_book_cat_map')
	  ->where('book_id='.(int)$pk);
    $db->setQuery($query);

    return $db->loadColumn();
  }


  /**
   * Prepare and sanitise the table data prior to saving.
   *
   * @param   JTable  $table  A JTable object.
   *
   * @return  void
   *
   * @since   1.6
   */
  protected function prepareTable($table)
  {
    // Set the publish date to now
    if($table->published == 1 && (int)$table->publish_up == 0) {
      $table->publish_up = JFactory::getDate()->toSql();
    }

    if($table->published == 1 && intval($table->publish_down) == 0) {
      $table->publish_down = $this->getDbo()->getNullDate();
    }
  }


  /**
   * Method to save the form data.
   *
   * @param   array  $data  The form data.
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   1.6
   */
  public function save($data)
  {
    // Ensures first that one or more categories are selected.
    if(!isset($data['catids'])) {
      $this->setError(JText::_('COM_MYBOOKS_DATABASE_ERROR_NO_CATEGORY_SELECTED'));
      return false;
    }

    return parent::save($data);
  }


  /**
   * Saves the manually set order of records.
   * N.B: The function is reshaped to fit the multicategory feature. The item mapping table
   *      is used instead of the item table. 
   *
   * @param   array    $pks    An array of primary key ids.
   * @param   integer  $order  +1 or -1
   *
   * @return  mixed
   *
   * @since   12.2
   */
  public function saveorder($pks = null, $order = null)
  {
    if(empty($pks)) {
      JFactory::getApplication()->enqueueMessage(JText::_($this->text_prefix.'_ERROR_NO_ITEMS_SELECTED'), 'warning');
      return false;
    }

    // Initializes some variables.
    $post = JFactory::getApplication()->input->post->getArray();
    $sortByMainCat = $sortBySingleCat = false;
    $catId = null;

    // No category is selected.
    if(!isset($post['filter']['category_id'])) {
      $sortByMainCat = true;
    }
    // Only one category is selected.
    elseif(count($post['filter']['category_id']) == 1) {
      $sortBySingleCat = true;
      $catId = $post['filter']['category_id'][0];
    }

    // N.B: Items are not sorted if several categories are selected.
    if($sortBySingleCat || $sortByMainCat) {

      $db = $this->getDbo();
      $query = $db->getQuery(true);

      // Collects the items from the mapping table in order to get the ordering value.
      $query->select('book_id, cat_id, ordering')
	    ->from('#__mybooks_book_cat_map');

      if($sortByMainCat) {
	// Gets the main category id (ie: catid) from the item table. 
	$query->join('INNER', '#__mybooks_book ON id=book_id')
	      ->where('cat_id=catid');
      }
      // sortBySingleCat
      else {
	$query->where('cat_id='.(int)$catId);
      }

      $query->where('book_id IN('.implode(',', $pks).')');
      $db->setQuery($query);
      $items = $db->loadAssocList('book_id');

      // Initialize re-usable member properties
      $this->initBatch();

      $conditions = array();

      // Update ordering values
      foreach($pks as $i => $pk) {
	// Gets the item object.
	$this->table->load((int)$pk);

	// Access checks.
	if(!$this->canEditState($this->table)) {
	  // Prune items that you can't change.
	  unset($pks[$i]);
	  JLog::add(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), JLog::WARNING, 'jerror');
	}
	elseif($items[$pk]['ordering'] != $order[$i]) {
	  if($this->type) {
	    $this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
	  }

	  $query->clear();
	  $query->update('#__mybooks_book_cat_map')
		->set('ordering='.(int)$order[$i])
		->where('book_id='.(int)$pk)
		// As it's multicategory the category id must match as well.
		->where('cat_id='.(int)$items[$pk]['cat_id']);
	  $db->setQuery($query);
	  $db->execute();

	  // Sets the condition array, (always the same in our case).
	  $conditions[] = array($pk, 'cat_id='.(int)$items[$pk]['cat_id']);
	}
      }

      // Execute reorder for each category.
      foreach($conditions as $cond) {
	$this->table->load($cond[0]);
	$this->table->reorder($cond[1]);
      }

      // Clear the component's cache
      $this->cleanCache();

      return true;
    }

    // No item has been sorted.
    return true;
  }
}

