<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2017 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');


/**
 * My Books categories view.
 *
 */
class MybooksViewCategories extends JViewCategories
{
  protected $item = null;

  /**
   * @var    string  Default title to use for page title
   * @since  3.2
   */
  protected $defaultPageTitle = 'COM_MYBOOKS_DEFAULT_PAGE_TITLE';

  /**
   * @var    string  The name of the extension for the category
   * @since  3.2
   */
  protected $extension = 'com_mybooks';

  /**
   * @var    string  The name of the view to link individual items to
   * @since  3.2
   */
  protected $viewName = 'book';

  /**
   * Execute and display a template script.
   *
   * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  mixed  A string if successful, otherwise a Error object.
   */
  public function display($tpl = null)
  {
    $state = $this->get('State');
    $items = $this->get('Items');
    $parent = $this->get('Parent');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JFactory::getApplication()->enqueueMessage($errors, 'error');
      return false;
    }

    if($items === false) {
      JFactory::getApplication()->enqueueMessage(JText::_('JGLOBAL_CATEGORY_NOT_FOUND'), 'error');
      return false;
    }

    if($parent == false) {
      JFactory::getApplication()->enqueueMessage(JText::_('JGLOBAL_CATEGORY_NOT_FOUND'), 'error');
      return false;
    }

    $params = &$state->params;

    $items = array($parent->id => $items);

    // Escape strings for HTML output
    $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

    $this->maxLevelcat = $params->get('maxLevelcat', -1);
    $this->params = &$params;
    $this->parent = &$parent;
    $this->items  = &$items;

    $this->setDocument();

    return parent::display($tpl);
  }


  /**
   * Includes possible css and Javascript files.
   *
   * @return  void
   */
  protected function setDocument() 
  {
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_mybooks/css/mybooks.css');
  }
}

