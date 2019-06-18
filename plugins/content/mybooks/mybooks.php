<?php
/**
 * @package My Books
 * @copyright Copyright (c)2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access'); 

JLoader::register('MybooksHelper', JPATH_ADMINISTRATOR.'/components/com_mybooks/helpers/mybooks.php');


class plgContentMybooks extends JPlugin
{
  protected $post;


  /**
   * Constructor.
   *
   * @param   object  &$subject  The object to observe
   * @param   array   $config    An optional associative array of configuration settings.
   *
   * @since   3.7.0
   */
  public function __construct(&$subject, $config)
  {
    // Loads the component language.
    $lang = JFactory::getLanguage();
    $langTag = $lang->getTag();
    $lang->load('com_mybooks', JPATH_ROOT.'/administrator/components/com_mybooks', $langTag);
    // Gets the POST data.
    $this->post = JFactory::getApplication()->input->post->getArray();

    parent::__construct($subject, $config);
  }


  /**
   * Method called before the content is saved.
   *
   * @param   string  $context  The context of the content passed to the plugin (added in 1.6).
   * @param   object  $data     A JTableContent object.
   * @param   bool    $isNew    If the content is just about to be created.
   *
   * @return  boolean
   *
   * @since   2.5
   */
  public function onContentBeforeSave($context, $data, $isNew)
  {
    return true;
  }


  /**
   * Method called before the content is deleted.
   *
   * @param   string  $context  The context for the content passed to the plugin.
   * @param   object  $data     The data relating to the content that was deleted.
   *
   * @return  boolean
   *
   * @since   1.6
   */
  public function onContentBeforeDelete($context, $data)
  {
    if($context == 'com_categories.category') {
      // Ensures that the deleted category is not used as main category by one or more books.
      if(!MybooksHelper::checkMainCategory($data->id)) {
	return false;
      }
    }

    return true;
  }


  /**
   * Content is passed by reference, but after the save, so no changes will be saved.
   * Method is called right after the content is saved
   *
   * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
   * @param   object   $data     A JTableContent object
   * @param   boolean  $isNew    If the content is just about to be created
   *
   * @return  void
   *
   * @since   1.6
   */
  public function onContentAfterSave($context, $data, $isNew)
  {
    // Filter the sent event.
    if($context == 'com_mybooks.book' || $context == 'com_mybooks.form') { 
      // Check for book order.
      $this->setOrderByCategory($context, $data, $isNew);
    }
  }


  /**
   * Content is passed by reference, but after the deletion.
   *
   * @param   string  $context  The context of the content passed to the plugin (added in 1.6).
   * @param   object  $data     A JTableContent object.
   *
   * @return  void
   *
   * @since   2.5
   */
  public function onContentAfterDelete($context, $data)
  {
    // Filter the sent event.
    if($context == 'com_mybooks.book') {
      // Create a new query object.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      // Delete all the rows linked to the item id. 
      $query->delete('#__mybooks_book_cat_map')
	    ->where('book_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();
    }
    elseif($context == 'com_categories.category') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      // Delete all the rows linked to the item id. 
      $query->delete('#__mybooks_book_cat_map')
	    ->where('cat_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();
    }
  }


  /**
   * This is an event that is called after content has its state change (e.g. Published to Unpublished).
   *
   * @param   string   $context  The context for the content passed to the plugin.
   * @param   array    $pks      A list of primary key ids of the content that has changed state.
   * @param   integer  $value    The value of the state that the content has been changed to.
   *
   * @return  boolean
   *
   * @since   3.1
   */
  public function onContentChangeState($context, $pks, $value)
  {
    return true;
  }


  /**
   * Creates (or updates) a row whenever a book is categorised.
   * The book/category mapping allows to order the books against a given category. 
   *
   * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
   * @param   object   $data     A JTableContent object
   * @param   boolean  $isNew    If the content is just about to be created
   *
   * @return  void
   *
   */
  private function setOrderByCategory($context, $data, $isNew)
  {
    // Retrieves the category array, (N.B: It is not part of the table/data attributes).
    $catIds = $this->post['jform']['catids'];
    $unallowedCats = json_decode($this->post['unallowed_cats']);
file_put_contents('debog_file.txt', print_r($unallowedCats, true));
return;

    // Creates a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    // Retrieves all the rows matching the item id.
    $query->select('m.book_id, m.cat_id, m.ordering')
	  ->from('#__mybooks_book_cat_map AS m')
	  // Inner join in case meanwhile a category has been deleted.
	  ->join('INNER', '#__categories AS c ON c.id=m.cat_id')
	  ->where('m.book_id='.(int)$data->id);
    $db->setQuery($query);
    $categories = $db->loadObjectList();

    $values = array();

    foreach($catIds as $catId) {
      $newCat = true; 

      // In order to preserve the ordering of the old categories checks if 
      // they match those newly selected.
      foreach($categories as $category) {
	if($category->cat_id == $catId || in_array($category->cat_id, $unallowedCats)) {
	  $values[] = $category->book_id.','.$category->cat_id.','.$category->ordering;
	  $newCat = false; 
	  break;
	}
      }

      if($newCat) {
	$values[] = $data->id.','.$catId.',0';
      }
    }

    // Deletes all the rows matching the item id.
    $query->clear();
    $query->delete('#__mybooks_book_cat_map')
	  ->where('book_id='.(int)$data->id);
    $db->setQuery($query);
    $db->execute();

    $columns = array('book_id', 'cat_id', 'ordering');

    // Inserts a new row for each category linked to the item.
    $query->clear();
    $query->insert('#__mybooks_book_cat_map')
	  ->columns($columns)
	  ->values($values);
    $db->setQuery($query);
    $db->execute();
  }
}

