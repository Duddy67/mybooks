<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access to this file.
defined('JPATH_BASE') or die('Restricted access');

$categories = $displayData['item']->categories;
?>

<ul class="tags inline">
<?php foreach($categories as $category) : ?> 
  <li class="tag-<?php echo $tag->tag_id; ?> tag-list0" itemprop="keywords">
    <?php // No need link for the current category (used in category view). 
	  if(isset($displayData['current_cat_id']) && $displayData['current_cat_id'] == $category->id) : ?> 
      <span class="label label-default"><?php echo $this->escape($category->title); ?></span>
  <?php else : 
          $labelType = 'success';
	  if($category->id == $displayData['item']->parent_id) {
	    // Sets the parent category to a different color.
	    $labelType = 'warning';
	  }
    ?> 
      <a href="<?php echo JRoute::_(MybooksHelperRoute::getCategoryRoute($category->id.':'.$category->alias, $category->language));?>" class="label label-<?php echo $labelType; ?>"><?php echo $this->escape($category->title); ?></a>
  <?php endif; ?> 
  </li>
<?php endforeach; ?>
</ul>

