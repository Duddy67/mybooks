<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die('Restricted access'); // No direct access to this file.

JFormHelper::loadFieldClass('list');


class JFormFieldComponentuser extends JFormFieldList
{
  /**
   * The form field type.
   *
   * @var		string
   * @since   1.6
   */
  protected $type = 'Componentuser';
  // N.B: Some SQL table names might be different from their object names (eg: pricerule / #__componentname_price_rule)
  //      Enter these exceptions in the array below (eg: 'pricerule' => 'price_rule',...). 
  protected $exceptions = array();


  /**
   * Method to get the field options.
   *
   * @return  array  The field option objects.
   *
   * @since   1.6
   */
  public function getOptions()
  {
    // Get the item name from the form filter name. 
    preg_match('#^com_mybooks\.([a-zA-Z0-9_-]+)(?:\.modal)?\.filter$#', $this->form->getName(), $matches);
    $itemName = $matches[1];
    // We need the item name in the singular in order to build the SQL table name.
    if(preg_match('#ies$#', $itemName)) { 
      // countries, currencies etc...
      $itemName = preg_replace('#ies$#', 'y', $itemName);
    }
    elseif(preg_match('#xes$#', $itemName)) { 
      // taxes, boxes etc...
      $itemName = preg_replace('#es$#', '', $itemName);
    }
    else { // Regular plurials.
      $itemName = preg_replace('#s$#', '', $itemName);
    }

    if(array_key_exists($itemName, $this->exceptions)) {
      $itemName = $this->exceptions[$itemName];
    }

    $options = MybooksHelper::getUsers($itemName);

    return  array_merge(parent::getOptions(), $options);
  }
}
