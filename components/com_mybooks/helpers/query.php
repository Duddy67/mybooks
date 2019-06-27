<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');


/**
 * My Books Component Query Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_mybooks
 * @since       1.5
 */
class MybooksHelperQuery
{
  /**
   * Translate an order code to a field for primary category ordering.
   *
   * @param   string	$orderby	The ordering code.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.5
   */
  public static function orderbyPrimary($orderby)
  {
    switch ($orderby)
    {
      case 'alpha' :
	      $orderby = 'ca.path, ';
	      break;

      case 'ralpha' :
	      $orderby = 'ca.path DESC, ';
	      break;

      case 'order' :
	      $orderby = 'ca.lft, ';
	      break;

      default :
	      $orderby = '';
	      break;
    }

    return $orderby;
  }

  /**
   * Translate an order code to a field for secondary category ordering.
   *
   * @param   string	$orderby	The ordering code.
   * @param   string	$orderDate	The ordering code for the date.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.5
   */
  public static function orderbySecondary($orderby, $orderDate = 'created')
  {
    $queryDate = self::getQueryDate($orderDate);

    switch ($orderby)
    {
      case 'date' :
	      $orderby = $queryDate;
	      break;

      case 'rdate' :
	      $orderby = $queryDate.' DESC ';
	      break;

      case 'alpha' :
	      $orderby = 'b.title';
	      break;

      case 'ralpha' :
	      $orderby = 'b.title DESC';
	      break;

      case 'order' :
	      $orderby = 'b.ordering';
	      break;

      case 'rorder' :
	      $orderby = 'b.ordering DESC';
	      break;

      case 'creator' :
	      $orderby = 'creator';
	      break;

      case 'rcreator' :
	      $orderby = 'creator DESC';
	      break;

      default :
	      $orderby = 'b.ordering';
	      break;
    }

    return $orderby;
  }

  /**
   * Translate an order code to a field for primary category ordering.
   *
   * @param   string	$orderDate	The ordering code.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.6
   */
  public static function getQueryDate($orderDate)
  {
    $db = JFactory::getDbo();

    switch($orderDate) {
      case 'modified' :
	      $queryDate = ' CASE WHEN b.modified = '.$db->quote($db->getNullDate()).' THEN b.created ELSE b.modified END';
	      break;

      // use created if publish_up is not set
      case 'published' :
	      $queryDate = ' CASE WHEN b.publish_up = '.$db->quote($db->getNullDate()).' THEN b.created ELSE b.publish_up END ';
	      break;

      case 'created' :
      default :
	      $queryDate = ' b.created ';
	      break;
    }

    return $queryDate;
  }

  /**
   * Method to order the intro books array for ordering
   * down the columns instead of across.
   * The layout always lays the introtext books out across columns.
   * Array is reordered so that, when books are displayed in index order
   * across columns in the layout, the result is that the
   * desired book ordering is achieved down the columns.
   *
   * @param   array    &$books   Array of intro text books
   * @param   integer  $numColumns  Number of columns in the layout
   *
   * @return  array  Reordered array to achieve desired ordering down columns
   *
   * @since   1.6
   */
  public static function orderDownColumns(&$books, $numColumns = 1)
  {
    $count = count($books);

    // Just return the same array if there is nothing to change
    if($numColumns == 1 || !is_array($books) || $count <= $numColumns) {
      $return = $books;
    }
    // We need to re-order the intro books array
    else {
      // We need to preserve the original array keys
      $keys = array_keys($books);

      $maxRows = ceil($count / $numColumns);
      $numCells = $maxRows * $numColumns;
      $numEmpty = $numCells - $count;
      $index = array();

      // Calculate number of empty cells in the array

      // Fill in all cells of the array
      // Put -1 in empty cells so we can skip later
      for($row = 1, $i = 1; $row <= $maxRows; $row++) {
	for($col = 1; $col <= $numColumns; $col++) {
	  if($numEmpty > ($numCells - $i)) {
	    // Put -1 in empty cells
	    $index[$row][$col] = -1;
	  }
	  else {
	    // Put in zero as placeholder
	    $index[$row][$col] = 0;
	  }

	  $i++;
	}
      }

      // Layout the books in column order, skipping empty cells
      $i = 0;

      for($col = 1; ($col <= $numColumns) && ($i < $count); $col++) {
	for($row = 1; ($row <= $maxRows) && ($i < $count); $row++) {
	  if($index[$row][$col] != - 1) {
	    $index[$row][$col] = $keys[$i];
	    $i++;
	  }
	}
      }

      // Now read the $index back row by row to get books in right row/col
      // so that they will actually be ordered down the columns (when read by row in the layout)
      $return = array();
      $i = 0;

      for($row = 1; ($row <= $maxRows) && ($i < $count); $row++) {
	for($col = 1; ($col <= $numColumns) && ($i < $count); $col++) {
	  $return[$keys[$i]] = $books[$index[$row][$col]];
	  $i++;
	}
      }
    }

    return $return;
  }
}

