<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
?>
<script type="text/javascript">
var mybooks = {
  clearSearch: function() {
    document.getElementById('filter_search').value = '';
    mybooks.submitForm();
  },

  submitForm: function() {
    var action = document.getElementById('siteForm').action;
    // Set an anchor on the form.
    document.getElementById('siteForm').action = action+'#siteForm';
    document.getElementById('siteForm').submit();
  }
};
</script>

<div class="list<?php echo $this->pageclass_sfx;?>">
  <?php if ($this->params->get('show_page_heading')) : ?>
	  <h1>
		  <?php echo $this->escape($this->params->get('page_heading')); ?>
	  </h1>
  <?php endif; ?>
  <?php if($this->params->get('show_category_title')) : ?>
	  <h2 class="category-title">
	      <?php echo JHtml::_('content.prepare', $this->category->title, '', $this->category->extension.'.category.title'); ?>
	  </h2>
  <?php endif; ?>
  <?php if ($this->params->get('show_tags', 1)) : ?>
	  <?php echo JLayoutHelper::render('joomla.content.tags', $this->category->tags->itemTags); ?>
  <?php endif; ?>
  <?php if ($this->params->get('show_description') || $this->params->def('show_description_image')) : ?>
	  <div class="category-desc">
		  <?php if ($this->params->get('show_description_image') && $this->category->getParams()->get('image')) : ?>
			  <img src="<?php echo $this->category->getParams()->get('image'); ?>"/>
		  <?php endif; ?>
		  <?php if ($this->params->get('show_description') && $this->category->description) : ?>
			  <?php echo JHtml::_('content.prepare', $this->category->description, '', $this->category->extension.'.category'); ?>
		  <?php endif; ?>
		  <div class="clr"></div>
	  </div>
  <?php endif; ?>

  <form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="siteForm" id="siteForm">

    <?php if($this->params->get('filter_field') != 'hide' || $this->params->get('show_pagination_limit') || $this->params->get('filter_ordering')) : ?>
    <div class="mybooks-toolbar clearfix">
    <?php
            // Gets the filter fields.
	    $fieldset = $this->filterForm->getFieldset('filter');

	    // Loops through the fields.
	    foreach($fieldset as $field) {
	      $filterName = $field->getAttribute('name');

	      if($filterName == 'filter_search' && $this->params->get('filter_field') != 'hide') { ?>
		<div class="btn-group input-append span6">
	      <?php
		    $hint = JText::_('COM_MYBOOKS_'.$this->params->get('filter_field').'_FILTER_LABEL');
		    $this->filterForm->setFieldAttribute($filterName, 'hint', $hint); 
		    // Displays only the input tag (without the div around).
		    echo $this->filterForm->getInput($filterName, null, $this->state->get('list.'.$filterName));
		    // Adds the search and clear buttons.  ?>
		<button type="submit" onclick="mybooks.submitForm();" class="btn hasTooltip"
			title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
		    <i class="icon-search"></i></button>

		<button type="button" onclick="mybooks.clearSearch()" class="btn hasTooltip js-stools-btn-clear"
			title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
		    <?php echo JText::_('JSEARCH_FILTER_CLEAR');?></button>
		</div>
      <?php	}
	      elseif(($filterName == 'filter_ordering' && $this->params->get('filter_ordering')) ||
		     ($filterName == 'limit' && $this->params->get('show_pagination_limit'))) {
		// Sets the field value to the currently selected value.
		$field->setValue($this->state->get('list.'.$filterName));
		echo $field->renderField(array('hiddenLabel' => true, 'class' => 'span3 mybooks-filters'));
	      }
	    }
     ?>
     </div>
    <?php endif; ?>

    <?php if(empty($this->items)) : ?>
	    <?php if($this->params->get('show_no_books', 1)) : ?>
	    <p><?php echo JText::_('COM_MYBOOKS_NO_BOOKS'); ?></p>
	    <?php endif; ?>
    <?php else : ?>
      <?php echo $this->loadTemplate('books'); ?>
    <?php endif; ?>

    <?php if(($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="pagination">
	    <?php echo $this->pagination->getListFooter(); ?>

	    <?php if ($this->params->def('show_pagination_results', 1) || $this->params->def('show_pagination_pages', 1)) : ?>
	      <div class="mybooks-results">
		  <?php if ($this->params->def('show_pagination_results', 1)) : ?>
		      <p class="counter pull-left small">
			<?php echo $this->pagination->getResultsCounter(); ?>
		      </p>
		  <?php endif; ?>
		  <?php if ($this->params->def('show_pagination_pages', 1)) : ?>
		      <p class="counter pull-right small">
			<?php echo $this->pagination->getPagesCounter(); ?>
		      </p>
		  <?php endif; ?>
	      </div>
	    <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if($this->get('children') && $this->maxLevel != 0) : ?>
	    <div class="cat-children">
	      <h3><?php echo JTEXT::_('JGLOBAL_SUBCATEGORIES'); ?></h3>
	      <?php echo $this->loadTemplate('children'); ?>
	    </div>
    <?php endif; ?>

    <input type="hidden" name="limitstart" value="" />
    <input type="hidden" id="token" name="<?php echo JSession::getFormToken(); ?>" value="1" />
    <input type="hidden" id="cat-id" name="cat_id" value="<?php echo $this->category->id; ?>" />
    <input type="hidden" name="task" value="" />
  </form>
</div><!-- list -->

<?php

if($this->params->get('filter_field') == 'title') {
  // Loads the JQuery autocomplete file.
  JHtml::_('script', 'media/jui/js/jquery.autocomplete.min.js');
  // Loads our js script.
  $doc = JFactory::getDocument();
  $doc->addScript(JURI::base().'components/com_mybooks/js/autocomplete.js');
}

