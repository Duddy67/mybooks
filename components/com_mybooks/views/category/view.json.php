<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');


/**
 * JSON Category View class. Mainly used for Ajax request. 
 */
class MybooksViewCategory extends JViewCategory
{
  public function display($tpl = null)
  {
    $jinput = JFactory::getApplication()->input;
    $catId = $jinput->get('cat_id', 0, 'uint');
    $search = $jinput->get('search', '', 'str');

    // Get some data from the models
    $model = $this->getModel();
    $results = $model->getAutocompleteSuggestions($catId, $search);

    echo new JResponseJson($results);
  }
}

