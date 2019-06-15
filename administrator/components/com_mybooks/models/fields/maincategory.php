<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access');


/*
 * Script which build the select list containing the available categories.
 *
 */
class JFormFieldMaincategory extends JFormFieldList
{
  /**
   * The form field type.
   *
   * @var		string
   * @since   1.6
   */
  protected $type = 'maincategory';


  /**
   * Method to get the field options.
   *
   * @return  array  The field option objects.
   *
   * @since   1.6
   */
  protected function getOptions()
  {
    $options = array();
      
    // Gets the categories linked to the item.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('cm.cat_id, c.path, c.language, c.published, c.level')
	  ->from('#__mybooks_book_cat_map AS cm')
	  ->join('LEFT', '#__categories AS c ON c.id=cm.cat_id')
	  ->where('cm.book_id='.(int)$this->form->getValue('id'))
	  // Doesn't retrieve the archived or trashed categories.
	  ->where('c.published NOT IN(2, -2)')
	  ->order('cm.cat_id');
    $db->setQuery($query);
    $categories = $db->loadObjectList();

    $categories = MybooksHelper::convertPathsToNames($categories);

    // Builds the select options.
    foreach($categories as $category) {
      $langTag = '';

      if($category->language !== '*') {
        $langTag = ' ('.$category->language.')';
      }

      $options[] = JHtml::_('select.option', $category->cat_id, $category->text.$langTag);
    }

    // Merges any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}

