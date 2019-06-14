<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2017 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
?>


<ol class="nav nav-tabs nav-stacked">
<?php foreach ($this->link_items as &$item) : ?>
	<li>
	  <a href="<?php echo JRoute::_(MybooksHelperRoute::getBookRoute($item->slug, $item->catid, $item->language)); ?>">
		      <?php echo $item->title; ?></a>
	</li>
<?php endforeach; ?>
</ol>

