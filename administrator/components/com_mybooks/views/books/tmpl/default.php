<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access'); 

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', '.multipleCategories', null, array('placeholder_text_multiple' => JText::_('JOPTION_SELECT_CATEGORY')));
JHtml::_('formbehavior.chosen', '.multipleMainCategories', null, array('placeholder_text_multiple' => JText::_('COM_MYBOOKS_SELECT_MAIN_CATEGORY')));
JHtml::_('formbehavior.chosen', '.multipleUsers',null, array('placeholder_text_multiple' => JText::_('COM_MYBOOKS_SELECT_CREATOR')));
JHtml::_('formbehavior.chosen', '.multipleTags', null, array('placeholder_text_multiple' => JText::_('JOPTION_SELECT_TAG')));
JHtml::_('formbehavior.chosen', '.multipleAccessLevels', null, array('placeholder_text_multiple' => JText::_('JOPTION_SELECT_ACCESS')));
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('formbehavior.chosen', 'select');

$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$archived = $this->state->get('filter.published') == 2 ? true : false;
$trashed = $this->state->get('filter.published') == -2 ? true : false;
$canOrder = $user->authorise('core.edit.state', 'com_mybooks.category');
$saveOrder = $listOrder == 'cm.ordering';

$selectedCatId = 0;
// Ensures that one and only one category is selected.
if($this->state->get('filter.category_id') !== null && count($this->state->get('filter.category_id')) == 1) {
  $selectedCatId = $this->state->get('filter.category_id')[0];
}

if($saveOrder && $selectedCatId) {
  $saveOrderingUrl = 'index.php?option=com_mybooks&task=books.saveOrderAjax&tmpl=component';
  JHtml::_('sortablelist.sortable', 'bookList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}
?>

<script type="text/javascript">
Joomla.orderTable = function()
{
  table = document.getElementById("sortTable");
  direction = document.getElementById("directionTable");
  order = table.options[table.selectedIndex].value;

  if(order != '<?php echo $listOrder; ?>') {
    dirn = 'asc';
  }
  else {
    dirn = direction.options[direction.selectedIndex].value;
  }

  Joomla.tableOrdering(order, dirn, '');
}
</script>


<form action="<?php echo JRoute::_('index.php?option=com_mybooks&view=books');?>" method="post" name="adminForm" id="adminForm">

<?php if (!empty( $this->sidebar)) : ?>
  <div id="j-sidebar-container" class="span2">
	  <?php echo $this->sidebar; ?>
  </div>
  <div id="j-main-container" class="span10">
<?php else : ?>
  <div id="j-main-container">
<?php endif;?>

<?php
// Search tools bar 
echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
?>

  <div class="clr"> </div>
  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped" id="bookList">
      <thead>
	<tr>
	<th width="1%" class="nowrap center hidden-phone">
	<?php echo JHtml::_('searchtools.sort', '', 'cm.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
	</th>
	<th width="1%" class="hidden-phone">
	<?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	<?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'b.published', $listDirn, $listOrder); ?>
	</th>
	<th>
	<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_TITLE', 'b.title', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort',  'JGRID_HEADING_ACCESS', 'b.access', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'creator', $listDirn, $listOrder); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'language', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JDATE', 'b.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGLOBAL_HITS', 'b.hits', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'b.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $ordering = ($listOrder == 'cm.ordering');
      $canCreate = $user->authorise('core.create', 'com_mybooks.category.'.$item->catid);
      $canEdit = $user->authorise('core.edit','com_mybooks.book.'.$item->id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_mybooks.book.'.$item->id) && $item->created_by == $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_mybooks.book.'.$item->id) && $canCheckin); 
      ?>

      <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $selectedCatId; ?>">
	<td class="order nowrap center hidden-phone">
	  <?php
	  $iconClass = '';
	  if(!$canChange)
	  {
	    $iconClass = ' inactive';
	  }
	  elseif(!$saveOrder || !$selectedCatId)
	  {
	    $iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::tooltipText('COM_MYBOOKS_ORDERING_DISABLED');
	  }
	  ?>
	  <span class="sortable-handler<?php echo $iconClass ?>">
		  <i class="icon-menu"></i>
	  </span>
	  <?php if($canChange && $saveOrder && $selectedCatId) : ?>
	      <input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering;?>" class="width-20 text-area-order " />
	  <?php endif; ?>
	  </td>
	  <td class="center hidden-phone">
		  <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	  </td>
	  <td class="center">
	    <div class="btn-group">
	      <?php echo JHtml::_('jgrid.published', $item->published, $i, 'books.', $canChange, 'cb'); ?>
	      <?php
	      if($canChange) {
		// Create dropdown items
		$action = $archived ? 'unarchive' : 'archive';
		JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'books');

		$action = $trashed ? 'untrash' : 'trash';
		JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'books');

		// Render dropdown list
		echo JHtml::_('actionsdropdown.render', $this->escape($item->title));
	      }
	      ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'books.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn) : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_mybooks&task=book.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>"><?php echo $this->escape($item->title); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->title); ?>
	      <?php endif; ?>
		<span class="small break-word">
		  <?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
		</span>
		<div class="small">
		  <?php echo JText::_('COM_MYBOOKS_FIELD_MAIN_CATEGORY_LABEL') . ": ".$this->escape($item->category_title); ?>
		</div>
	    </div>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->access_level); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->creator); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php if ($item->language == '*'):?>
	      <?php echo JText::alt('JALL', 'language'); ?>
	    <?php else:?>
	      <?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
	    <?php endif;?>
	  </td>
	  <td class="nowrap small hidden-phone">
	    <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
	  </td>
	  <td class="hidden-phone">
	    <?php echo (int) $item->hits; ?>
	  </td>
	  <td>
	    <?php echo $item->id; ?>
	  </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="10">
	     <p class="counter pull-right small">
	       <?php echo $this->pagination->getResultsCounter(); ?>
	     </p>
	     <?php echo $this->pagination->getListFooter(); ?>
	  </td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_mybooks" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

