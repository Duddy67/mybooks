<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access'); 

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

// Prevent params layout (layouts/joomla/edit/params.php) to display twice some fieldsets.
$this->ignore_fieldsets = array('details', 'permissions', 'jmetadata');
$canDo = MybooksHelper::getActions($this->state->get('filter.category_id'));
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'book.cancel' || document.formvalidator.isValid(document.getElementById('book-form'))) {
    Joomla.submitform(task, document.getElementById('book-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_mybooks&view=book&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="book-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_MYBOOKS_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span9">
	    <div class="form-vertical">
	      <?php echo $this->form->getControlGroup('booktext'); ?>
	    </div>
	</div>
	<div class="span3 form-vertical">
	  <?php echo JLayoutHelper::render('edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
	</div>
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

      <?php if($canDo->get('core.admin')) : ?>
	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_MYBOOKS_TAB_PERMISSIONS', true)); ?>
		<?php echo $this->form->getInput('rules'); ?>
		<?php echo $this->form->getInput('asset_id'); ?>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?>
  </div>

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

