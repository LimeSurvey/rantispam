<?php
/**
 * @version     3.0.0
 * @package     com_rantispam
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ratmil <ratmil_torres@yahoo.com> - http://www.ratmilwebsolutions.com
 */
// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_rantispam/assets/css/rantispam.css');
?>
<script type="text/javascript">
    
    
    Joomla.submitbutton = function(task)
    {
        if(task == 'spam.cancel'){
            Joomla.submitform(task, document.getElementById('spam-form'));
        }
        else{
            
            if (task != 'spam.cancel' && document.formvalidator.isValid(document.id('spam-form'))) {
                Joomla.submitform(task, document.getElementById('spam-form'));
            }
            else {
                alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
            }
        }
    }
</script>

<form action="<?php echo JRoute::_('index.php?option=com_rantispam&layout=edit&spam_id=' . (int) $this->item->spam_id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="spam-form" class="form-validate">
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('spam_id'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('spam_id'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('user_id'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('user_id'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('user_ip'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('user_ip'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('spam_text'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('spam_text'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('spam_score'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('spam_score'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('detect_time'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('detect_time'); ?></div>
			</div>


            </fieldset>
        </div>

        

        <input type="hidden" name="task" value="" />
        <?php echo JHtml::_('form.token'); ?>

    </div>
</form>