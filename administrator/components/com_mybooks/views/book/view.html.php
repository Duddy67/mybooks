<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access'); 
 

class MybooksViewBook extends JViewLegacy
{
  protected $item;
  protected $form;
  protected $state;


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
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JFactory::getApplication()->enqueueMessage($errors, 'error');
      return false;
    }

    $this->addToolBar();
    $this->setDocument();

    // Display the template.
    parent::display($tpl);
  }


  /**
   * Add the page title and toolbar.
   *
   * @return  void
   *
   * @since   1.6
   */
  protected function addToolBar() 
  {
    // Make main menu inactive.
    JFactory::getApplication()->input->set('hidemainmenu', true);

    $user = JFactory::getUser();
    $userId = $user->get('id');

    // Get the allowed actions list
    $canDo = MybooksHelper::getActions($this->state->get('filter.category_id'));
    $isNew = $this->item->id == 0;
    $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

    // Display the view title (according to the user action) and the icon.
    JToolBarHelper::title($isNew ? JText::_('COM_MYBOOKS_NEW_BOOK') : JText::_('COM_MYBOOKS_EDIT_BOOK'), 'pencil-2');

    if($isNew) {
      // Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('book.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('book.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('book.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('book.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('book.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_mybooks', 'core.create'))) > 0) {
	    JToolBarHelper::custom('book.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }

      // If checked out, we can still save
      if($canDo->get('core.create')) {
	JToolBarHelper::save2copy('book.save2copy');
      }
    }

    JToolBarHelper::cancel('book.cancel', 'JTOOLBAR_CANCEL');
  }


  /**
   * Includes possible css and Javascript files.
   *
   * @return  void
   */
  protected function setDocument() 
  {
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_mybooks/mybooks.css');
  }
}



