<?php
/**
 * @package My Books
 * @copyright Copyright (c) 2019 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access'); 

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.framework', true);
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', '.multipleCategories', null, array('placeholder_text_multiple' => JText::_('JOPTION_SELECT_CATEGORY')));
JHtml::_('formbehavior.chosen', '.multipleUsers',null, array('placeholder_text_multiple' => JText::_('COM_MYBOOKS_SELECT_CREATOR')));
JHtml::_('formbehavior.chosen', '.multipleTags', null, array('placeholder_text_multiple' => JText::_('JOPTION_SELECT_TAG')));
JHtml::_('formbehavior.chosen', '.multipleAccessLevels', null, array('placeholder_text_multiple' => JText::_('JOPTION_SELECT_ACCESS')));
JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
require_once JPATH_ROOT.'/components/com_mybooks/helpers/route.php';

$app = JFactory::getApplication();

if($app->isSite()) {
  JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
}

$jinput = JFactory::getApplication()->input;
$function = $jinput->get('function', 'selectBook');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo JRoute::_('index.php?option=com_mybooks&view=books&layout=modal&tmpl=component&function='.$function.'&'.JSession::getFormToken().'=1');?>" method="post" name="adminForm" id="adminForm" class="form-inline">

  <?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
	  <?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
      <table class="table table-striped table-condensed">
	<thead>
	  <tr>
	    <th class="title">
		    <?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'b.title', $listDirn, $listOrder); ?>
	    </th>
	    <th width="15%" class="nowrap hidden-phone">
		    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
	    </th>
	    <th width="15%" class="nowrap hidden-phone">
		    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_LANGUAGE', 'language', $listDirn, $listOrder); ?>
	    </th>
	    <th width="15%" class="nowrap hidden-phone">
		    <?php echo JHtml::_('grid.sort', 'JDATE', 'b.created', $listDirn, $listOrder); ?>
	    </th>
	    <th width="1%" class="nowrap hidden-phone">
		    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'b.id', $listDirn, $listOrder); ?>
	    </th>
	  </tr>
	</thead>
	<tfoot>
	  <tr>
	    <td colspan="5">
	      <?php echo $this->pagination->getListFooter(); ?>
	    </td>
	  </tr>
	</tfoot>
	<tbody>
	<?php foreach($this->items as $i => $item) : ?>
		<?php if($item->language && JLanguageMultilang::isEnabled()) {
			$tag = strlen($item->language);
			if($tag == 5) {
			  $lang = substr($item->language, 0, 2);
			}
			elseif($tag == 6) {
			  $lang = substr($item->language, 0, 3);
			}
			else {
			  $lang = "";
			}
		}
		elseif(!JLanguageMultilang::isEnabled()) {
		  $lang = "";
		}
		?>
	  <tr class="row<?php echo $i % 2; ?>">
		  <td class="has-context">
		    <div class="pull-left">
		      <a href="javascript:void(0)" onclick="if (window.parent)
		      window.parent.<?php echo $this->escape($function);?>('<?php echo $item->id; ?>','<?php echo $this->escape(addslashes($item->title)); ?>','<?php echo $item->catid; ?>');"><?php echo $this->escape($item->title); ?></a>
		      <span class="small break-word">
			<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
		      </span>
		      <div class="small">
			<?php echo JText::_('COM_MYBOOKS_FIELD_MAIN_CATEGORY_LABEL') . ": ".$this->escape($item->category_title); ?>
		      </div>
		    </div>
		  </td>
		  <td  class="small hidden-phone">
		    <?php echo $this->escape($item->access_level); ?>
		  </td>
		  <td  class="small hidden-phone">
		    <?php if ($item->language == '*'):?>
			    <?php echo JText::alt('JALL', 'language'); ?>
		    <?php else:?>
			    <?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
		    <?php endif;?>
		  </td>
		  <td  class="small hidden-phone">
		    <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
		  </td>
		  <td class="center">
		    <?php echo (int) $item->id; ?>
		  </td>
	  </tr>
	<?php endforeach; ?>
	  </tbody>
	</table>

      <div>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo JHtml::_('form.token'); ?>
      </div>
  <?php endif; ?>
</form>
