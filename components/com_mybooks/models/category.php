<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2017 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');


/**
 * My Books Component Model
 *
 * @package     Joomla.Site
 * @subpackage  com_mybooks
 */
class MybooksModelCategory extends JModelList
{
  
  /**
   * Category items data
   *
   * @var array
   */
  protected $_item = null;

  protected $_books = null;

  protected $_siblings = null;

  protected $_children = null;

  protected $_parent = null;

  /**
   * Model context string.
   *
   * @var		string
   */
  protected $_context = 'com_mybooks.category';

  /**
   * The category that applies.
   *
   * @access    protected
   * @var        object
   */
  protected $_category = null;

  /**
   * The list of other book categories.
   *
   * @access    protected
   * @var        array
   */
  protected $_categories = null;


  /**
   * Method to get a list of items.
   *
   * @return  mixed  An array of objects on success, false on failure.
   */

  /**
   * Constructor.
   *
   * @param   array  An optional associative array of configuration settings.
   * @see     JController
   * @since   1.6
   */
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'b.id',
	      'title', 'b.title',
	      'author', 'b.author',
	      'created', 'b.created',
	      'catid', 'b.catid', 'category_title',
	      'modified', 'b.modified',
	      'published', 'b.published',
	      'ordering', 'b.ordering',
	      'publish_up', 'b.publish_up',
	      'publish_down', 'b.publish_down'
      );
    }

    parent::__construct($config);
  }


  /**
   * Method to auto-populate the model state.
   *
   * Book. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   */
  protected function populateState($ordering = null, $direction = null)
  {
    $app = JFactory::getApplication('site');

    // Get and set the current category id.
    $pk = $app->input->getInt('id');
    $this->setState('category.id', $pk);

    // getParams function return global parameters overrided by the menu parameters (if any).
    // N.B: Some specific parameters of this menu are not returned.
    $params = $app->getParams();

    $menuParams = new JRegistry;

    // Get the menu with its specific parameters.
    if($menu = $app->getMenu()->getActive()) {
      $menuParams->loadString($menu->params);
    }

    // Merge Global and Menu Item params into a new object.
    $mergedParams = clone $menuParams;
    $mergedParams->merge($params);

    // Load the parameters in the session.
    $this->setState('params', $mergedParams);

    // process show_noauth parameter

    // The user is not allowed to see the registered books unless he has the proper view permissions.
    if(!$params->get('show_noauth')) {
      // Set the access filter to true. This way the SQL query checks against the user
      // view permissions and fetchs only the books this user is allowed to see.
      $this->setState('filter.access', true);
    }
    // The user is allowed to see any of the registred books (ie: intro_text as a teaser). 
    else {
      // The user is allowed to see all the books or some of them.
      // All of the books are returned and it's up to thelayout to 
      // deal with the access (ie: redirect the user to login form when Read more
      // button is clicked).
      $this->setState('filter.access', false);
    }

    // Set limit for query. If list, use parameter. If blog, add blog parameters for limit.
    // Important: The pagination limit box must be hidden to use the limit value based upon the layout.
    if(!$params->get('show_pagination_limit') && (($app->input->get('layout') === 'blog') || $params->get('layout_type') === 'blog')) {
      $limit = $params->get('num_leading_books') + $params->get('num_intro_books') + $params->get('num_links');
    }
    else { // list layout or blog layout with the pagination limit box shown.
      // Get the number of songs to display per page.
      $limit = $params->get('display_num', 10);

      if($params->get('show_pagination_limit')) {
	// Gets the limit value from the pagination limit box.
	$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $limit, 'uint');
      }
    }

    $this->setState('list.limit', $limit);

    // Get the limitstart variable (used for the pagination) from the form variable.
    $limitstart = $app->input->get('limitstart', 0, 'uint');
    $this->setState('list.start', $limitstart);

    // Optional filter text
    $filterSearch = $this->getUserStateFromRequest($this->context.'.list.filter_search', 'filter_search');
    $this->setState('list.filter_search', $filterSearch);
    // Filter.order
    $filterOrdering = $this->getUserStateFromRequest($this->context.'.list.filter_ordering', 'filter_ordering');
    $this->setState('list.filter_ordering', $filterOrdering);

    $user = JFactory::getUser();
    $asset = 'com_mybooks';

    if($pk) {
      $asset .= '.category.'.$pk;
    }

    // Check against the category permissions.
    if((!$user->authorise('core.edit.state', $asset)) && (!$user->authorise('core.edit', $asset))) {
      // limit to published for people who can't edit or edit.state.
      $this->setState('filter.published', 1);

      // Filter by start and end dates.
      $this->setState('filter.publish_date', true);
    }
    else {
      // User can access published, unpublished and archived books.
      $this->setState('filter.published', array(0, 1, 2));
    }

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }


  /**
   * Method to get a list of items.
   *
   * @return  mixed  An array of objects on success, false on failure.
   */
  public function getItems()
  {
    // Invoke the parent getItems method (using the getListQuery method) to get the main list
    $items = parent::getItems();
    $input = JFactory::getApplication()->input;

    // Get some user data.
    $user = JFactory::getUser();
    $userId = $user->get('id');
    $guest = $user->get('guest');
    $groups = $user->getAuthorisedViewLevels();

    // Convert the params field into an object, saving original in _params
    foreach($items as $item) {
      // Get the book parameters only.
      $bookParams = new JRegistry;
      $bookParams->loadString($item->params);
      // Set the params attribute, eg: the merged global and menu parameters set
      // in the populateState function.
      $item->params = clone $this->getState('params');

      // For Blog layout, book params override menu item params only if menu param='use_book'.
      // Otherwise, menu item params control the layout.
      // If menu item is 'use_book' and there is no book param, use global.
      if($input->getString('layout') == 'blog' || $this->getState('params')->get('layout_type') == 'blog') {
	// Create an array of just the params set to 'use_book'
	$menuParamsArray = $this->getState('params')->toArray();
	$bookArray = array();

	foreach($menuParamsArray as $key => $value) {
	  if($value === 'use_book') {
	    // If the book has a value, use it
	    if($bookParams->get($key) != '') {
	      // Get the value from the book
	      $bookArray[$key] = $bookParams->get($key);
	    }
	    else {
	      // Otherwise, use the global value
	      $bookArray[$key] = $globalParams->get($key);
	    }
	  }
	}

	// Merge the selected book params
	if(count($bookArray) > 0) {
	  $bookParams = new JRegistry;
	  $bookParams->loadArray($bookArray);
	  $item->params->merge($bookParams);
	}
      }
      else { // Default layout (list).
	// Merge all of the book params.
	// N.B: Book params (if they are defined) override global/menu params.
	$item->params->merge($bookParams);
      }

      // Compute the asset access permissions.
      // Technically guest could edit a book, but lets not check that to improve performance a little.
      if(!$guest) {
	$asset = 'com_mybooks.book.'.$item->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $item->params->set('access-edit', true);
	}
	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $item->created_by) {
	    $item->params->set('access-edit', true);
	  }
	}
      }

      $access = $this->getState('filter.access');
      // Set the access view parameter.
      if($access) {
	// If the access filter has been set, we already have only the books this user can view.
	$item->params->set('access-view', true);
      }
      else { // If no access filter is set, the layout takes some responsibility for display of limited information.
	if($item->catid == 0 || $item->category_access === null) {
	  // In case the book is not linked to a category, we just check permissions against the book access.
	  $item->params->set('access-view', in_array($item->access, $groups));
	}
	else { // Check the user permissions against the book access as well as the category access.
	  $item->params->set('access-view', in_array($item->access, $groups) && in_array($item->category_access, $groups));
	}
      }

      // Set the type of date to display, (default layout only).
      if($this->getState('params')->get('layout_type') != 'blog'
	  && $this->getState('params')->get('list_show_date')
	  && $this->getState('params')->get('order_date')) {
	switch($this->getState('params')->get('order_date')) {
	  case 'modified':
		  $item->displayDate = $item->modified;
		  break;

	  case 'published':
		  $item->displayDate = ($item->publish_up == 0) ? $item->created : $item->publish_up;
		  break;

	  default: // created
		  $item->displayDate = $item->created;
	}
      }

      // Get the tags
      $item->tags = new JHelperTags;
      $item->tags->getItemTags('com_mybooks.book', $item->id);
    }

    return $items;
  }



  /**
   * Method to build an SQL query to load the list data (book items).
   *
   * @return  string    An SQL query
   * @since   1.6
   */
  protected function getListQuery()
  {
    $user = JFactory::getUser();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    // Create a new query object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select required fields from the categories.
    $query->select($this->getState('list.select', 'b.id,b.title,b.alias,b.intro_text,b.full_text,b.catid,b.published,'.
	                           'b.checked_out,b.checked_out_time,b.created,b.created_by,b.access,b.params,b.metadata,'.
				   'b.metakey,b.metadesc,b.hits,b.publish_up,b.publish_down,b.language,b.modified,b.modified_by'))
	  ->from($db->quoteName('#__mybooks_book').' AS b')
	  // Display books of the current category.
	  ->where('b.catid='.(int)$this->getState('category.id'));

    // Join on category table.
    $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	  ->join('LEFT', '#__categories AS ca on ca.id = b.catid');

    // Join over the categories to get parent category titles
    $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	  ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

    // Join over the users.
    $query->select('us.name AS author')
	  ->join('LEFT', '#__users AS us ON us.id = b.created_by');

    // Join over the asset groups.
    $query->select('al.title AS access_level');
    $query->join('LEFT', '#__viewlevels AS al ON al.id = b.access');

    // Filter by access level.
    if($access = $this->getState('filter.access')) {
      $query->where('b.access IN ('.$groups.')')
	    ->where('ca.access IN ('.$groups.')');
    }

    // Filter by state
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      // User is only allowed to see published books.
      $query->where('b.published='.(int)$published);
    }
    elseif(is_array($published)) {
      // User is allowed to see books with different states.
      JArrayHelper::toInteger($published);
      $published = implode(',', $published);
      $query->where('b.published IN ('.$published.')');
    }

    // Do not show expired books to users who can't edit or edit.state.
    if($this->getState('filter.publish_date')) {
      // Filter by start and end dates.
      $nullDate = $db->quote($db->getNullDate());
      $nowDate = $db->quote(JFactory::getDate()->toSql());

      $query->where('(b.publish_up = '.$nullDate.' OR b.publish_up <= '.$nowDate.')')
	    ->where('(b.publish_down = '.$nullDate.' OR b.publish_down >= '.$nowDate.')');
    }

    // Filter by language
    if($this->getState('filter.language')) {
      $query->where('b.language IN ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
    }

    // Filter by search in title
    $filterSearch = $this->getState('list.filter_search');
    // Get the field to search by.
    $field = $this->getState('params')->get('filter_field');
    if(!empty($filterSearch)) {
      $filterSearch = $db->quote('%'.$db->escape($filterSearch, true).'%');
      $query->where('(b.'.$field.' LIKE '.$filterSearch.')');
    }

    // Get the books ordering by default set in the menu options. (N.B: sec stands for secondary). 
    $bookOrderBy = $this->getState('params')->get('orderby_sec', 'rdate');
    // If books are sorted by date (ie: date, rdate), order_date defines
    // which type of date should be used (ie: created, modified or publish_up).
    $bookOrderDate = $this->getState('params')->get('order_date');
    // Get the field to use in the ORDER BY clause according to the orderby_sec option.
    $orderBy = MybooksHelperQuery::orderbySecondary($bookOrderBy, $bookOrderDate);

    // Filter by order (eg: the select list set by the end user).
    $filterOrdering = $this->getState('list.filter_ordering');
    // If the end user has define an order, we override the ordering by default.
    if(!empty($filterOrdering)) {
      $orderBy = MybooksHelperQuery::orderbySecondary($filterOrdering, $bookOrderDate);
    }

    $query->order($orderBy);

    return $query;
  }


  /**
   * Method to get category data for the current category
   *
   * @param   integer  An optional ID
   *
   * @return  object
   * @since   1.5
   */
  public function getCategory()
  {
    if(!is_object($this->_item)) {
      $app = JFactory::getApplication();
      $menu = $app->getMenu();
      $active = $menu->getActive();
      $params = new JRegistry;

      if($active) {
	$params->loadString($active->params);
      }

      $options = array();
      $options['countItems'] = $params->get('show_cat_num_books_cat', 1) || $params->get('show_empty_categories', 0);
      $categories = JCategories::getInstance('Mybooks', $options);
      $this->_item = $categories->get($this->getState('category.id', 'root'));

      // Compute selected asset permissions.
      if(is_object($this->_item)) {
	$user = JFactory::getUser();
	$asset = 'com_mybooks.category.'.$this->_item->id;

	// Check general create permission.
	if($user->authorise('core.create', $asset)) {
	  $this->_item->getParams()->set('access-create', true);
	}

	$this->_children = $this->_item->getChildren();
	$this->_parent = false;

	if($this->_item->getParent()) {
	  $this->_parent = $this->_item->getParent();
	}

	$this->_rightsibling = $this->_item->getSibling();
	$this->_leftsibling = $this->_item->getSibling(false);
      }
      else {
	$this->_children = false;
	$this->_parent = false;
      }
    }

    // Get the tags
    $this->_item->tags = new JHelperTags;
    $this->_item->tags->getItemTags('com_mybooks.category', $this->_item->id);

    return $this->_item;
  }

  /**
   * Get the parent category
   *
   * @param   integer  An optional category id. If not supplied, the model state 'category.id' will be used.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   */
  public function getParent()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_parent;
  }

  /**
   * Get the sibling (adjacent) categories.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   */
  function &getLeftSibling()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_leftsibling;
  }

  function &getRightSibling()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_rightsibling;
  }

  /**
   * Get the child categories.
   *
   * @param   integer  An optional category id. If not supplied, the model state 'category.id' will be used.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   * @since   1.6
   */
  function &getChildren()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    // Order subcategories
    if(count($this->_children)) {
      $params = $this->getState()->get('params');

      if($params->get('orderby_pri') == 'alpha' || $params->get('orderby_pri') == 'ralpha') {
	jimport('joomla.utilities.arrayhelper');
	JArrayHelper::sortObjects($this->_children, 'title', ($params->get('orderby_pri') == 'alpha') ? 1 : -1);
      }
    }

    return $this->_children;
  }

  /**
   * Increment the hit counter for the category.
   *
   * @param   int  $pk  Optional primary key of the category to increment.
   *
   * @return  boolean True if successful; false otherwise and internal error set.
   *
   * @since   3.2
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');

      $table = JTable::getInstance('Category', 'JTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }


  /**
   * Returns book title suggestions for a given search request.
   *
   * @param   int  $pk  	Optional primary key of the current tag.
   * @param   string $search 	The request search to get the matching title suggestions.
   *
   * @return  mixed		An array of suggestion results.
   *
   */
  public function getAutocompleteSuggestions($pk = null, $search)
  {
    $pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');
    $results = array();

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('title AS value, id AS data')
	  ->from('#__mybooks_book')
	  ->where('catid='.(int)$pk)
	  ->where('published=1')
	  ->where('title LIKE '.$db->Quote($search.'%'))
	  ->order('title DESC');
    $db->setQuery($query);
    // Requested to get the JQuery autocomplete working properly.
    $results['suggestions'] = $db->loadAssocList();

    return $results;
  }
}

