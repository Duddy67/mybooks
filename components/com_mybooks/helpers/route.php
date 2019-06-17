<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');


/**
 * My Books Component Route Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_mybooks
 * @since       1.5
 */
abstract class MybooksHelperRoute
{
  /**
   * Get the book route.
   *
   * @param   integer  $id        The route of the book item.
   * @param   integer  $catid     The category ID.
   * @param   integer  $language  The language code.
   *
   * @return  string  The article route.
   *
   * @since   1.5
   */
  public static function getBookRoute($id, $catid = 0, $language = 0)
  {
    // Create the link
    $link = 'index.php?option=com_mybooks&view=book&id='.$id;

    if((int) $catid > 1) {
      $link .= '&catid='.$catid;
    }

    if($language && $language !== '*' && JLanguageMultilang::isEnabled()) {
      $link .= '&lang='.$language;
    }

    return $link;
  }


  /**
   * Get the category route.
   *
   * @param   integer  $catid     The category ID.
   * @param   integer  $language  The language code.
   *
   * @return  string  The book route.
   *
   * @since   1.5
   */
  public static function getCategoryRoute($catid, $language = 0)
  {
    if($catid instanceof JCategoryNode) {
      $id = $catid->id;
    }
    else {
      $id = (int) $catid;
    }

    if($id < 1) {
      $link = '';
    }
    else {
      $link = 'index.php?option=com_mybooks&view=category&id='.$id;

      if($language && $language !== '*' && JLanguageMultilang::isEnabled()) {
	$link .= '&lang='.$language;
      }
    }

    return $link;
  }


  /**
   * Get the form route.
   *
   * @param   integer  $id  The form ID.
   *
   * @return  string  The book route.
   *
   * @since   1.5
   */
  public static function getFormRoute($id)
  {
    return 'index.php?option=com_mybooks&task=book.edit&n_id='.(int)$id;
  }
}
