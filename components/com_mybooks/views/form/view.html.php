<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access'); 


class MybooksViewForm extends JViewLegacy
{
  protected $form = null;
  protected $state = null;
  protected $item = null;
  protected $return_page = null;
  protected $isNew = 0;
  protected $location = null;
  public $unallowed_cats = null;


  /**
   * Execute and display a template script.
   *
   * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  mixed  A string if successful, otherwise an Error object.
   *
   * @see     \JViewLegacy::loadTemplate()
   * @since   3.0
   */
  function display($tpl = null)
  {
    $user = JFactory::getUser();

    // Redirect unregistered users to the login page.
    if($user->guest) {
      $app = JFactory::getApplication();
      $app->redirect('index.php?option=com_users&view=login'); 
      return true;
    }

    // Initialise variables
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    $this->item = $this->get('Item');
    $this->return_page	= $this->get('ReturnPage');
    $this->unallowed_cats = $this->getModel()->getUnallowedCategories($this->form->getValue('catids', null, array()));

    // Check if the user is allowed to create a new document.
    if(empty($this->item->id)) {
      $authorised = $user->authorise('core.create', 'com_mybooks') || (count($user->getAuthorisedCategories('com_mybooks', 'core.create')));
      $this->isNew = 1;
    }
    // Check if the user is allowed to edit this document. 
    else { 
      $authorised = $this->item->params->get('access-edit');
    }

    if($authorised !== true) {
      $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
      return false;
    }

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      $app->enqueueMessage($errors, 'error');
      return false;
    }

    // Create a shortcut to the parameters.
    $params = &$this->state->params;
    // Get the possible extra class name.
    $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

    $this->params = $params;

    // Override global params with document specific params
    $this->params->merge($this->item->params);
    $this->user = $user;

    if($params->get('enable_category') == 1) {
      $this->form->setFieldAttribute('catid', 'default', $params->get('catid', 1));
      $this->form->setFieldAttribute('catid', 'readonly', 'true');
    }

    $this->setDocument();

    parent::display($tpl);
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
