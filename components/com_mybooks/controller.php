<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2017 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access.
defined('_JEXEC') or die('Restricted access'); 


class MybooksController extends JControllerLegacy
{
  /**
   * Constructor.
   *
   * @param   array  $config  An optional associative array of configuration settings.
   * Recognized key values include 'name', 'default_task', 'model_path', and
   * 'view_path' (this list is not meant to be comprehensive).
   *
   * @since   12.2
   */
  public function __construct($config = array())
  {
    $this->input = JFactory::getApplication()->input;

    // Book frontpage Editor book proxying:
    if($this->input->get('view') === 'books' && $this->input->get('layout') === 'modal') {
      JHtml::_('stylesheet', 'system/adminlist.css', array(), true);
      $config['base_path'] = JPATH_COMPONENT_ADMINISTRATOR;
    }

    parent::__construct($config);
  }


  public function display($cachable = false, $urlparams = false) 
  {
    // Set the default view name and format from the Request.
    // N.B: we are using b_id to avoid collisions with the router and the return page.
    // Frontend is a bit messier than the backend.
    $id = $this->input->getInt('b_id');
    // Set the view, (categories by default).
    $vName = $this->input->getCmd('view', 'categories');
    $this->input->set('view', $vName);

    // Check for edit form.
    if($vName == 'form' && !$this->checkEditId('com_mybooks.edit.book', $id)) {
      // Somehow the person just went to the form - we don't allow that.
      JFactory::getApplication()->enqueueMessage(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 'error');
      return false;
    }

    // Make sure the parameters passed in the input by the component are safe.
    $safeurlparams = array('catid' => 'INT', 'id' => 'INT',
			    'cid' => 'ARRAY', 'limit' => 'UINT',
			    'limitstart' => 'UINT', 'return' => 'BASE64',
			    'filter' => 'STRING', 'filter-search' => 'STRING',
			    'filter-ordering' => 'STRING', 'lang' => 'CMD',
			    'Itemid' => 'INT');

    // Display the view.
    parent::display($cachable, $safeurlparams);
  }


  /**
   * Checks whether the token is valid before sending the Ajax request to the corresponding Json view.
   *
   * @return  mixed	The Ajax request result or an error message if the token is
   * 			invalid.  
   */
  public function ajax() 
  {
    if(!JSession::checkToken('get')) {
      echo new JResponseJson(null, JText::_('JINVALID_TOKEN'), true);
    }
    else {
      parent::display();
    }
  }
}


