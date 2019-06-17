<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('_JEXEC') or die('Restricted access'); 


class plgSystemMybooks extends JPlugin
{
  /**
   * Application object.
   *
   * @var    JApplicationCms
   * @since  3.3
   */
  protected $app;


  /**
   * Constructor.
   *
   * @param   object  &$subject  The object to observe.
   * @param   array   $config	An optional associative array of configuration settings.
   *
   * @since   1.0
   */
  public function __construct(&$subject, $config)
  {
    // Loads the component language.
    $lang = JFactory::getLanguage();
    $langTag = $lang->getTag();
    $lang->load('com_mybooks', JPATH_ROOT.'/administrator/components/com_mybooks', $langTag);

    $this->app = JFactory::getApplication();
    // Calling the parent Constructor
    parent::__construct($subject, $config);
  }


  /**
   * Listener for the `onAfterRoute` event
   *
   * @return  void
   *
   * @since   1.0
   */
  public function onAfterRoute()
  {
    $jinput = $this->app->input;
    $component = $jinput->get('option', '', 'string');

    if($component == 'com_categories' && $jinput->get('extension', '', 'string') == 'com_mybooks' && $this->app->isAdmin()) {
      // Loads the overrided category controllers.
      require_once(dirname(__FILE__).'/code/com_categories/controllers/categories.php');
      require_once(dirname(__FILE__).'/code/com_categories/controllers/category.php');
    }
  }
}

