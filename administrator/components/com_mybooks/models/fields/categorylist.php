<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');


/**
 * Form Field class for the Joomla Platform.
 * Supports an HTML select list of categories
 *
 * @since  1.6
 */
class JFormFieldCategorylist extends JFormFieldList
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  1.6
   */
  public $type = 'CategoryList';

  /**
   * Method to get the field options for category
   * Use the extension attribute in a form to specify the.specific extension for
   * which categories should be displayed.
   * Use the show_root attribute to specify whether to show the global category root in the list.
   *
   * @return  array    The field option objects.
   *
   * @since   1.6
   */
  protected function getOptions()
  {
    $options = array();
    $extension = $this->element['extension'] ? (string) $this->element['extension'] : (string) $this->element['scope'];
    $published = (string) $this->element['published'];
    $language  = (string) $this->element['language'];

    // Gets all the main category ids.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('DISTINCT catid')
	  ->from('#__mybooks_book');
    $db->setQuery($query);
    $mainCats = $db->loadColumn();

    // Load the category options for a given extension.
    if (!empty($extension))
    {
      // Filter over published state or not depending upon if it is present.
      $filters = array();
      if ($published)
      {
	$filters['filter.published'] = explode(',', $published);
      }

      // Filter over language depending upon if it is present.
      if ($language)
      {
	$filters['filter.language'] = explode(',', $language);
      }

      if ($filters === array())
      {
	$options = JHtml::_('category.options', $extension);
      }
      else
      {
	$options = JHtml::_('category.options', $extension, $filters);
      }

      // Verify permissions.  If the action attribute is set, then we scan the options.
      if ((string) $this->element['action'])
      {
	// Get the current user object.
	$user = JFactory::getUser();

	foreach ($options as $i => $option)
	{
	  /*
	   * To take save or create in a category you need to have create rights for that category
	   * unless the item is already in that category.
	   * Unset the option if the user isn't authorised for it. In this field assets are always categories.
	   */
	  if ($user->authorise('core.create', $extension . '.category.' . $option->value) === false)
	  {
	    unset($options[$i]);
	  }
	}
      }

      // Override.
      foreach ($options as $i => $option)
      {
	if(in_array($option->value, $mainCats)) {
	  // Marks it as a main category. 
	  $option->text = $option->text.' (#)';
	}
      }

      if (isset($this->element['show_root']))
      {
	array_unshift($options, JHtml::_('select.option', '0', JText::_('JGLOBAL_ROOT')));
      }
    }
    else
    {
      JLog::add(JText::_('JLIB_FORM_ERROR_FIELDS_CATEGORY_ERROR_EXTENSION_EMPTY'), JLog::WARNING, 'jerror');
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}
