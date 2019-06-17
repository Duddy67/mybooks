<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access'); 


class MybooksHelper
{
  /**
   * Creates the tabs bar ($viewName = name of the active view).
   *
   * @param   string  $viewName  The name of the view to display.
   *
   * @return  void
   *
   */
  public static function addSubmenu($viewName)
  {
    JHtmlSidebar::addEntry(JText::_('COM_MYBOOKS_SUBMENU_BOOKS'),
				      'index.php?option=com_mybooks&view=books', $viewName == 'books');

    JHtmlSidebar::addEntry(JText::_('COM_MYBOOKS_SUBMENU_CATEGORIES'),
				      'index.php?option=com_categories&extension=com_mybooks', $viewName == 'categories');

    if($viewName == 'categories') {
      $document = JFactory::getDocument();
      $document->setTitle(JText::_('COM_MYBOOKS_ADMINISTRATION_CATEGORIES'));
    }
  }


  /**
   * Gets the list of the allowed actions for the user.
   *
   * @param   array    $catIds    The category ids to check against.
   *
   * @return  JObject             The allowed actions for the current user.
   *
   */
  public static function getActions($catIds = array())
  {
    $user = JFactory::getUser();
    $result = new JObject;

    $actions = array('core.admin', 'core.manage', 'core.create', 'core.edit',
		     'core.edit.own', 'core.edit.state', 'core.delete');

    // Gets from the core the user's permission for each action.
    foreach($actions as $action) {
      // Checks permissions against the component. 
      if(empty($catIds)) { 
	$result->set($action, $user->authorise($action, 'com_mybooks'));
      }
      else {
	// Checks permissions against the component categories.
	foreach($catIds as $catId) {
	  if($user->authorise($action, 'com_mybooks.category.'.$catId)) {
	    $result->set($action, $user->authorise($action, 'com_mybooks.category.'.$catId));
	    break;
	  }

	  $result->set($action, $user->authorise($action, 'com_mybooks.category.'.$catId));
	}
      }
    }

    return $result;
  }


  /**
   * Builds the user list for the filter.
   *
   * @param   string   $itemName    The name of the item to check the users against.
   *
   * @return  object                The list of the users.
   *
   */
  public static function getUsers($itemName)
  {
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('u.id AS value, u.name AS text');
    $query->from('#__users AS u');
    // Gets only the names of users who have created items, this avoids to
    // display all of the users in the drop down list.
    $query->join('INNER', '#__mybooks_'.$itemName.' AS i ON i.created_by = u.id');
    $query->group('u.id');
    $query->order('u.name');

    // Setup the query
    $db->setQuery($query);

    // Returns the result
    return $db->loadObjectList();
  }


  /**
   * Checks that the given category is not used as main category by one or more books.
   *
   * @param   integer    $pk	The category id.
   *
   * @return  boolean		True if the category is not used as main category, false otherwise.
   */
  public static function checkMainCategory($pk)
  {
    return self::checkMainCategories(array($pk));
  }


  /**
   * Checks that the given categories are not used as main category by one or more books.
   *
   * @param   array    $pks		An array of category IDs.
   *
   * @return  boolean			True if the categories are not used as main category, false otherwise.
   */
  public static function checkMainCategories($pks)
  {
    $ids = array();
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    foreach($pks as $pk) {
      // Finds node and all children keys
      $query->clear();
      $query->select('c.id')
	    ->from('#__categories AS node')
	    ->leftJoin('#__categories AS c ON node.lft <= c.lft AND c.rgt <= node.rgt')
	    ->where('node.id = '.(int)$pk);
      $db->setQuery($query);
      $results = $db->loadColumn();

      $ids = array_unique(array_merge($ids,$results), SORT_REGULAR);
    }

    // Checks that no book item is using one of the categories as main category.
    $query->clear();
    $query->select('COUNT(*)')
	  ->from('#__mybooks_book')
	  ->where('catid IN('.implode(',', $ids).')');
    $db->setQuery($query);

    if($db->loadResult()) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_MYBOOKS_WARNING_CATEGORY_USED_AS_MAIN_CATEGORY'), 'warning');
      return false;
    }

    return true;
  }


  /**
   * Function that converts categories paths into paths of names
   * N.B: Adapted from the function used with tags. libraries/src/Helper/TagsHelper.php
   *
   * @param   array  $categories  Array of categories
   *
   * @return  array
   *
   * @since   3.1
   */
  public static function convertPathsToNames($categories)
  {
    // We will replace path aliases with tag names
    if ($categories)
    {
      // Create an array with all the aliases of the results
      $aliases = array();

      foreach ($categories as $category)
      {
	if (!empty($category->path))
	{
	  if ($pathParts = explode('/', $category->path))
	  {
	    $aliases = array_merge($aliases, $pathParts);
	  }
	}
      }

      // Get the aliases titles in one single query and map the results
      if ($aliases)
      {
	// Remove duplicates
	$aliases = array_unique($aliases);

	$db = JFactory::getDbo();

	$query = $db->getQuery(true)
		->select('alias, title')
		->from('#__categories')
		->where('extension="com_mybooks"')
		->where('alias IN (' . implode(',', array_map(array($db, 'quote'), $aliases)) . ')');
	$db->setQuery($query);

	try
	{
	  $aliasesMapper = $db->loadAssocList('alias');
	}
	catch (RuntimeException $e)
	{
	  return false;
	}

	// Rebuild the items path
	if ($aliasesMapper)
	{
	  foreach ($categories as $category)
	  {
	    $namesPath = array();

	    if (!empty($category->path))
	    {
	      if ($pathParts = explode('/', $category->path))
	      {
		foreach ($pathParts as $i => $alias)
		{
		  if (isset($aliasesMapper[$alias]))
		  {
		    $namesPath[] = $aliasesMapper[$alias]['title'];
		  }
		  else
		  {
		    $namesPath[] = $alias;
		  }

		  // Unpublished categories are put into square bracket.
		  if($category->published == 0 && ($i + 1) == $category->level) {
		    $namesPath[$i] = '['.$namesPath[$i].']';
		  }
		}

		$category->text = implode('/', $namesPath);
	      }
	    }
	  }
	}
      }
    }

    return $categories;
  }
}

