<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access'); 

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');


// Create some shortcuts.
$params = $this->item->params;
$item = $this->item;

// Sets the canonical url of the item.

// Gets the current protocol and domain name without path (if any).
$domain = preg_replace('#'.JUri::root(true).'/$#', '', JUri::root());
// Uses the main category to build the canonical url. 
$link = $domain.JRoute::_(MybooksHelperRoute::getBookRoute($this->item->slug, $this->item->catid, $this->item->language));
$canUrl = '<link href="'.$link.'" rel="canonical" />';
// Inserts the canonical link in HTML head.
$document = JFactory::getDocument();
$document->addCustomTag($canUrl);
?>

<div class="item-page<?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Book">
  <?php if($item->params->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1><?php echo $this->escape($params->get('page_heading')); ?></h1>
    </div>
  <?php endif; ?>

  <?php echo JLayoutHelper::render('book.title', array('item' => $item, 'now_date' => $this->nowDate)); ?>

  <?php echo JLayoutHelper::render('book.icons', array('item' => $this->item, 'user' => $this->user, 'uri' => $this->uri)); ?>

  <?php $useDefList = ($params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
		       || $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category')
		       || $params->get('show_author') ); ?>

  <?php if ($useDefList) : ?>
    <?php echo JLayoutHelper::render('book.info_block', array('item' => $item, 'params' => $params)); ?>
  <?php endif; ?>

  <?php echo JLayoutHelper::render('book.categories', array('item' => $this->item)); ?>

  <?php if($item->params->get('show_intro')) : ?>
    <?php echo $item->intro_text; ?>
  <?php endif; ?>

  <?php if(!empty($item->full_text)) : ?>
    <?php echo $item->full_text; ?>
  <?php endif; ?>

  <?php if($params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
	  <?php $this->item->tagLayout = new JLayoutFile('joomla.content.tags'); 
		echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
  <?php endif; ?>
</div>
