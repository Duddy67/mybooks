<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tabstate');
JHtml::_('behavior.calendar');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');


// Create shortcut to parameters.
$params = $this->state->get('params');
$uri = JUri::getInstance();
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'book.cancel' || document.formvalidator.isValid(document.id('book-form'))) {
    Joomla.submitform(task, document.getElementById('book-form'));
  }
  else {
    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}
</script>

<div class="edit-book <?php echo $this->pageclass_sfx; ?>">
  <?php if($params->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1>
	<?php echo $this->escape($params->get('page_heading')); ?>
      </h1>
    </div>
  <?php endif; ?>

  <form action="<?php echo JRoute::_('index.php?option=com_mybooks&b_id='.(int)$this->item->id); ?>" 
   method="post" name="adminForm" id="book-form" enctype="multipart/form-data" class="form-validate form-vertical">

      <div class="btn-toolbar">
	<div class="btn-group">
	  <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('book.save')">
		  <span class="icon-ok"></span>&#160;<?php echo JText::_('JSAVE') ?>
	  </button>
	</div>
	<div class="btn-group">
	  <button type="button" class="btn" onclick="Joomla.submitbutton('book.cancel')">
		  <span class="icon-cancel"></span>&#160;<?php echo JText::_('JCANCEL') ?>
	  </button>
	</div>
	<?php if ($params->get('save_history', 0)) : ?>
	<div class="btn-group">
		<?php echo $this->form->getInput('contenthistory'); ?>
	</div>
	<?php endif; ?>
      </div>

      <fieldset>

	<ul class="nav nav-tabs">
		<li class="active"><a href="#details" data-toggle="tab"><?php echo JText::_('COM_MYBOOKS_TAB_DETAILS') ?></a></li>
		<li><a href="#publishing" data-toggle="tab"><?php echo JText::_('COM_MYBOOKS_TAB_PUBLISHING') ?></a></li>
		<li><a href="#language" data-toggle="tab"><?php echo JText::_('JFIELD_LANGUAGE_LABEL') ?></a></li>
		<li><a href="#metadata" data-toggle="tab"><?php echo JText::_('COM_MYBOOKS_TAB_METADATA') ?></a></li>
	</ul>

	<div class="tab-content">
	    <div class="tab-pane active" id="details">
	      <?php echo $this->form->renderField('title'); 
		    echo $this->form->renderField('alias'); 
		    echo $this->form->renderField('booktext');
	      ?>
	      </div>

	      <div class="tab-pane" id="publishing">
		<?php echo $this->form->renderField('catids'); 

		      // Existing item.
		      if($this->form->getValue('id') != 0 && empty($this->item->unallowed_cats)) {
		        echo $this->form->renderField('catid'); 
		      }
			
		      // Users with unallowed categories cannot change the main category.
		      if(!empty($this->item->unallowed_cats)) {
			$this->form->setFieldAttribute('catid', 'type', 'hidden'); 
			echo $this->form->getInput('catid');
		      }

		      echo $this->form->renderField('tags'); 
		      echo $this->form->renderField('access');
		  ?>

		<?php if($this->item->params->get('access-change')) : ?>
		  <?php echo $this->form->renderField('published'); ?>
		  <?php echo $this->form->renderField('publish_up'); ?>
		  <?php echo $this->form->renderField('publish_down'); ?>
		<?php endif; ?>
	      </div>

	      <div class="tab-pane" id="language">
		<?php echo $this->form->renderField('language'); ?>
	      </div>

	      <div class="tab-pane" id="metadata">
		<?php echo $this->form->renderField('metadesc'); ?>
		<?php echo $this->form->renderField('metakey'); ?>
	      </div>
	    </div>

    <input type="hidden" name="unallowed_cats" value="<?php echo json_encode($this->item->unallowed_cats); ?>" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="return" value="<?php echo $this->return_page; ?>" />

    <?php if($this->params->get('enable_category', 0) == 1) :?>
      <input type="hidden" name="jform[catid]" value="<?php echo $this->params->get('catid', 1); ?>" />
    <?php endif; ?>

    <?php echo JHtml::_('form.token'); ?>
    </fieldset>
  </form>
</div>

