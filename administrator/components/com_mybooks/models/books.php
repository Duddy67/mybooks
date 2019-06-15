<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access'); 

use Joomla\Utilities\ArrayHelper;


class MybooksModelBooks extends JModelList
{
  /**
   * Constructor.
   *
   * @param   array  $config  An optional associative array of configuration settings.
   *
   * @see     \JModelLegacy
   * @since   1.6
   */
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array('id', 'b.id',
				       'title', 'b.title', 
				       'alias', 'b.alias',
				       'created', 'b.created', 
				       'created_by', 'b.created_by',
				       'published', 'b.published', 
			               'access', 'b.access', 'access_level',
				       'user', 'user_id',
				       'ordering', 'cm.ordering',
				       'language', 'b.language',
				       'hits', 'b.hits',
				       'catid', 'b.catid', 'category_id',
				       'tag'
				      );
    }

    parent::__construct($config);
  }


  /**
   * Method to auto-populate the model state.
   *
   * This method should only be called once per instantiation and is designed
   * to be called on the first call to the getState() method unless the model
   * configuration flag to ignore the request is set.
   *
   * Book. Calling getState in this method will result in recursion.
   *
   * @param   string  $ordering   An optional ordering field.
   * @param   string  $direction  An optional direction (asc|desc).
   *
   * @return  void
   *
   * @since   1.6
   */
  protected function populateState($ordering = null, $direction = null)
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $session = JFactory::getSession();

    // Adjust the context to support modal layouts.
    if($layout = JFactory::getApplication()->input->get('layout')) {
      $this->context .= '.'.$layout;
    }

    // Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language');
    $this->setState('filter.language', $language);

    // Used with the multiple list selections.
    $formSubmited = $app->input->post->get('form_submited');

    $categoryId = $this->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id');
    $userId = $this->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag');
    $access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access');

    if($formSubmited) {
      // Gets the current value of the fields.

      $categoryId = $app->input->post->get('category_id');
      $this->setState('filter.category_id', $categoryId);

      $userId = $app->input->post->get('user_id');
      $this->setState('filter.user_id', $userId);

      $tag = $app->input->post->get('tag');
      $this->setState('filter.tag', $tag);

      $access = $app->input->post->get('access');
      $this->setState('filter.access', $access);
    }

    // List state information.
    parent::populateState('b.title', 'asc');

    // Force a language
    $forcedLanguage = $app->input->get('forcedLanguage');

    if(!empty($forcedLanguage)) {
      $this->setState('filter.language', $forcedLanguage);
      $this->setState('filter.forcedLanguage', $forcedLanguage);
    }
  }


  /**
   * Method to get a store id based on the model configuration state.
   *
   * This is necessary because the model is used by the component and
   * different modules that might need different sets of data or different
   * ordering requirements.
   *
   * @param   string  $id  An identifier string to generate the store id.
   *
   * @return  string  A store id.
   *
   * @since   1.6
   */
  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.serialize($this->getState('filter.access'));
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.serialize($this->getState('filter.user_id'));
    $id .= ':'.serialize($this->getState('filter.category_id'));
    $id .= ':'.serialize($this->getState('filter.tag'));
    $id .= ':'.$this->getState('filter.language');

    return parent::getStoreId($id);
  }


  /**
   * Method to get a \JDatabaseQuery object for retrieving the data set from a database.
   *
   * @return  \JDatabaseQuery  A \JDatabaseQuery object to retrieve the data set.
   *
   * @since   1.6
   */
  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $user = JFactory::getUser();

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'b.id,b.title,b.alias,b.created,b.published,b.catid,b.hits,b.access,'.
				   'cm.ordering,b.created_by,b.checked_out,b.checked_out_time,b.language'))
	  ->from('#__mybooks_book AS b');

    // Get the user name.
    $query->select('us.name AS user')
	  ->join('LEFT', '#__users AS us ON us.id = b.created_by');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor')
	  ->join('LEFT', '#__users AS uc ON uc.id=b.checked_out');

    // Join over the categories.
    $query->select('ca.title AS category_title')
	  ->join('LEFT', '#__categories AS ca ON ca.id = b.catid');

    // Join over the language
    $query->select('lg.title AS language_title')
	  ->join('LEFT', $db->quoteName('#__languages').' AS lg ON lg.lang_code = b.language');

    // Join over the asset groups.
    $query->select('al.title AS access_level')
	  ->join('LEFT', '#__viewlevels AS al ON al.id = b.access');

    // Filter by category.
    $categoryId = $this->getState('filter.category_id');
    if(is_numeric($categoryId)) {
      // Gets the books from the category mapping table.
      $query->join('INNER', '#__mybooks_book_cat_map AS cm ON cm.book_id=b.id AND cm.cat_id='.(int)$categoryId);
    }
    elseif(is_array($categoryId)) {
      $categoryId = ArrayHelper::toInteger($categoryId);
      $categoryId = implode(',', $categoryId);
      // Gets the books from the category mapping table.
      $query->join('INNER', '#__mybooks_book_cat_map AS cm ON cm.book_id=b.id AND cm.cat_id IN('.$categoryId.')');
    }
    else {
      // Gets the ordering value from the category mapping table.
      $query->join('LEFT', '#__mybooks_book_cat_map AS cm ON cm.book_id=b.id AND cm.cat_id=b.catid');
    }

    // Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('b.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(b.title LIKE '.$search.')');
      }
    }

    // Filter by access level.
    $access = $this->getState('filter.access');

    if(is_numeric($access)) {
      $query->where('b.access='.(int) $access);
    }
    elseif (is_array($access)) {
      $access = ArrayHelper::toInteger($access);
      $access = implode(',', $access);
      $query->where('b.access IN ('.$access.')');
    }

    // Filter by access level on categories.
    if(!$user->authorise('core.admin')) {
      $groups = implode(',', $user->getAuthorisedViewLevels());
      $query->where('b.access IN ('.$groups.')');
      $query->where('ca.access IN ('.$groups.')');
    }

    // Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('b.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(b.published IN (0, 1))');
    }

    // Filter by user.
    $userId = $this->getState('filter.user_id');

    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('b.created_by'.$type.(int) $userId);
    }
    elseif(is_array($userId)) {
      $userId = ArrayHelper::toInteger($userId);
      $userId = implode(',', $userId);
      $query->where('b.created_by IN ('.$userId.')');
    }

    // Filter by language.
    if($language = $this->getState('filter.language')) {
      $query->where('b.language = '.$db->quote($language));
    }

    // Filter by a single or group of tags.
    $hasTag = false;
    $tagId = $this->getState('filter.tag');

    if(is_numeric($tagId)) {
      $hasTag = true;
      $query->where($db->quoteName('tagmap.tag_id').' = '.(int)$tagId);
    }
    elseif(is_array($tagId)) {
      $tagId = ArrayHelper::toInteger($tagId);
      $tagId = implode(',', $tagId);

      if(!empty($tagId)) {
	$hasTag = true;
	$query->where($db->quoteName('tagmap.tag_id').' IN ('.$tagId.')');
      }
    }

    if($hasTag) {
      $query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap').
		   ' ON '.$db->quoteName('tagmap.content_item_id').' = '.$db->quoteName('b.id').
		   ' AND '.$db->quoteName('tagmap.type_alias').' = '.$db->quote('com_notebook.note'));
    }

    // Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'b.title');
    $orderDirn = $this->state->get('list.direction'); // asc or desc

    // To prevent duplicates when filtering with multiple selection lists.
    $query->group('b.id');

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}

