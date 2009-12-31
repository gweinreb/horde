<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
new Horde_Application(array('admin' => true));

if (!Horde_Util::extensionExists('domxml') &&
    !Horde_Util::extensionExists('dom')) {
    throw new Horde_Exception('You need the domxml or dom PHP extension to use the configuration tool.');
}

$app = Horde_Util::getFormData('app');
$appname = $registry->get('name', $app);
$title = sprintf(_("%s Setup"), $appname);

if (empty($app) || !in_array($app, $registry->listApps(array('inactive', 'hidden', 'notoolbar', 'active', 'admin')))) {
    $notification->push(_("Invalid application."), 'horde.error');
    $url = Horde::applicationUrl('admin/setup/index.php', true);
    header('Location: ' . $url);
    exit;
}

$vars = Horde_Variables::getDefaultVariables();

require_once 'Horde/Config.php';
$form = new ConfigForm($vars, $app);
$form->setButtons(sprintf(_("Generate %s Configuration"), $appname));
if (file_exists($registry->get('fileroot', $app) . '/config/conf.bak.php')) {
    $form->appendButtons(_("Revert Configuration"));
}

$php = '';
if (Horde_Util::getFormData('submitbutton') == _("Revert Configuration")) {
    $path = $registry->get('fileroot', $app) . '/config';
    if (@copy($path . '/conf.bak.php', $path . '/conf.php')) {
        $notification->push(_("Successfully reverted configuration. Reload to see changes."), 'horde.success');
        @unlink($path . '/conf.bak.php');
    } else {
        $notification->push(_("Could not revert configuration."), 'horde.error');
    }
} elseif ($form->validate($vars)) {
    $config = new Horde_Config($app);
    $php = $config->generatePHPConfig($vars);
    $path = $registry->get('fileroot', $app) . '/config';
    if (file_exists($path . '/conf.php')) {
        if (@copy($path . '/conf.php', $path . '/conf.bak.php')) {
            $notification->push(sprintf(_("Successfully saved the backup configuration file %s."), Horde_Util::realPath($path . '/conf.bak.php')), 'horde.success');
        } else {
            $notification->push(sprintf(_("Could not save the backup configuration file %s."), Horde_Util::realPath($path . '/conf.bak.php')), 'horde.warning');
        }
    }
    if ($fp = @fopen($path . '/conf.php', 'w')) {
        /* Can write, so output to file. */
        fwrite($fp, Horde_String::convertCharset($php, Horde_Nls::getCharset(), 'iso-8859-1'));
        fclose($fp);
        $notification->push(sprintf(_("Successfully wrote %s"), Horde_Util::realPath($path . '/conf.php')), 'horde.success');
        $registry->clearCache();
        header('Location: ' . Horde::applicationUrl('admin/setup/index.php', true));
        exit;
    } else {
        /* Cannot write. */
        $notification->push(sprintf(_("Could not save the configuration file %s. You can either use one of the options to save the code back on %s or copy manually the code below to %s."), Horde_Util::realPath($path . '/conf.php'), Horde::link(Horde::url('index.php') . '#update', _("Setup")) . _("Setup") . '</a>', Horde_Util::realPath($path . '/conf.php')), 'horde.warning', array('content.raw'));
        /* Save to session. */
        $_SESSION['_config'][$app] = $php;
    }
} elseif ($form->isSubmitted()) {
    $notification->push(_("There was an error in the configuration form. Perhaps you left out a required field."), 'horde.error');
}

/* Render the configuration form. */
$renderer = $form->getRenderer();
$renderer->setAttrColumnWidth('50%');
$form = Horde_Util::bufferOutput(array($form, 'renderActive'), $renderer, $vars, 'config.php', 'post');


/* Set up the template. */
$template = new Horde_Template();
$template->set('php', htmlspecialchars($php), true);
/* Create the link for the diff popup only if stored in session. */
$diff_link = '';
if (!empty($_SESSION['_config'][$app])) {
    $url = Horde::applicationUrl('admin/setup/diff.php', true);
    $url = Horde_Util::addParameter($url, 'app', $app, false);
    $diff_link = Horde::link('#', '', '', '', Horde::popupJs($url, array('height' => 480, 'width' => 640, 'urlencode' => true)) . 'return false;') . _("show differences") . '</a>';
}
$template->set('diff_popup', $diff_link, true);
$template->set('form', $form);
$template->setOption('gettext', true);

require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $template->fetch(HORDE_TEMPLATES . '/admin/setup/config.html');
require HORDE_TEMPLATES . '/common-footer.inc';
