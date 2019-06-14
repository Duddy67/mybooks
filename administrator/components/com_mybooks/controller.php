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
   * Typical view method for MVC based architecture
   *
   * This function is provide as a default implementation, in most cases
   * you will need to override it in your own controllers.
   *
   * @param   boolean  $cachable   If true, the view output will be cached
   * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
   *
   * @return  \JControllerLegacy  A \JControllerLegacy object to support chaining.
   *
   * @since   3.0
   */
  public function display($cachable = false, $urlparams = false) 
  {
    // Displays the submenu.
    MybooksHelper::addSubmenu($this->input->get('view', 'books'));

    // Sets the default view.
    $this->input->set('view', $this->input->get('view', 'books'));

    // Displays the view.
    parent::display();
  }
}


