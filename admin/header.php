<?php declare(strict_types=1);

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * wgEvents module for xoops
 *
 * @copyright    2021 XOOPS Project (https://xoops.org)
 * @license      GPL 2.0 or later
 * @package      wgevents
 * @since        1.0.0
 * @min_xoops    2.5.11 Beta1
 * @author       Goffy - Wedega - Email:webmaster@wedega.com - Website:https://xoops.wedega.com
 */

require \dirname(__DIR__, 3) . '/include/cp_header.php';
require_once \dirname(__DIR__) . '/include/common.php';

$sysPathIcon16   = '../' . $GLOBALS['xoopsModule']->getInfo('sysicons16');
$sysPathIcon32   = '../' . $GLOBALS['xoopsModule']->getInfo('sysicons32');
$pathModuleAdmin = $GLOBALS['xoopsModule']->getInfo('dirmoduleadmin');
$modPathIcon16   = \WGEVENTS_URL . '/' . $GLOBALS['xoopsModule']->getInfo('modicons16') . '/';
$modPathIcon32   = \WGEVENTS_URL . '/' . $GLOBALS['xoopsModule']->getInfo('modicons32') . '/';

// Get instance of module
$helper = \XoopsModules\Wgevents\Helper::getInstance();
$eventsHandler = $helper->getHandler('Events');
$categoriesHandler = $helper->getHandler('Categories');
$registrationsHandler = $helper->getHandler('Registrations');
$questionsHandler = $helper->getHandler('Questions');
$answersHandler = $helper->getHandler('Answers');
$textblocksHandler = $helper->getHandler('Textblocks');
$fieldsHandler = $helper->getHandler('Fields');
$logsHandler = $helper->getHandler('Logs');
$accountsHandler = $helper->getHandler('Accounts');
$myts = MyTextSanitizer::getInstance();
// 
if (!isset($xoopsTpl) || !\is_object($xoopsTpl)) {
    require_once \XOOPS_ROOT_PATH . '/class/template.php';
    $xoopsTpl = new \XoopsTpl();
}

// Load languages
\xoops_loadLanguage('admin');
\xoops_loadLanguage('main');
\xoops_loadLanguage('modinfo');

// Local admin menu class
if (\file_exists($GLOBALS['xoops']->path($pathModuleAdmin.'/moduleadmin.php'))) {
    require_once $GLOBALS['xoops']->path($pathModuleAdmin.'/moduleadmin.php');
} else {
    \redirect_header('../../../admin.php', 5, \_AM_MODULEADMIN_MISSING);
}

xoops_cp_header();

// System icons path
$GLOBALS['xoopsTpl']->assign('sysPathIcon16', $sysPathIcon16);
$GLOBALS['xoopsTpl']->assign('sysPathIcon32', $sysPathIcon32);
$GLOBALS['xoopsTpl']->assign('modPathIcon16', $modPathIcon16);
$GLOBALS['xoopsTpl']->assign('modPathIcon32', $modPathIcon32);

$adminObject = \Xmf\Module\Admin::getInstance();
$style = \WGEVENTS_URL . '/assets/css/admin/style.css';
