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

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_rantispam/assets/css/rantispam.css');
?>
<?php if(!empty($this->sidebar)): ?>
<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
<?php else : ?>
<div id="j-main-container">
<?php endif;?>

<div style="float:right;">
<img src="<?php echo JURI::root();?>administrator/components/com_rantispam/assets/images/logo128.png" />
</div>
<p><font size="5"><strong>R Antispam 3.3.9</strong></font> </p>
<p><font size="5"><strong>Copyright</strong></font></p>
<p><font size="4">&nbsp;&nbsp;&nbsp;©&nbsp;2010 - <?php echo date("Y"); ?> Ratmil&nbsp;&nbsp;&nbsp;<a href="http://www.ratmilwebsolutions.com">www.ratmilwebsolutions.com</a></font></p>
<p><font size="5"><strong>License.</strong></font></p>
<font size="4"><a href="http://www.gnu.org/licenses/lgpl-3.0.html">Gnu Public License</a></font>
<p><font size="5"><a href="http://extensions.joomla.org/extensions/access-a-security/site-access/16331"><?php echo JText::_("COM_RANTISPAM_VOTE");?></a></font></p>
</div>