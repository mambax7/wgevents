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

use Xmf\Request;
use XoopsModules\Wgevents;
use XoopsModules\Wgevents\{
    Constants,
    Common,
    MailHandler,
    Utility
};

require __DIR__ . '/header.php';
$GLOBALS['xoopsOption']['template_main'] = 'wgevents_registration.tpl';
require_once \XOOPS_ROOT_PATH . '/header.php';

$op      = Request::getCmd('op', 'list');
$regId   = Request::getInt('id');
$regEvid = Request::getInt('evid');
$start   = Request::getInt('start');
$limit   = Request::getInt('limit', $helper->getConfig('userpager'));
$redir   = Request::getString('redir', 'list');

$GLOBALS['xoopsTpl']->assign('start', $start);
$GLOBALS['xoopsTpl']->assign('limit', $limit);

if (Request::hasVar('cancel')) {
    $op = 'listmyevent';
}

// Define Stylesheet
$GLOBALS['xoTheme']->addStylesheet($style, null);
// Paths
$GLOBALS['xoopsTpl']->assign('xoops_icons32_url', \XOOPS_ICONS32_URL);
$GLOBALS['xoopsTpl']->assign('wgevents_url', \WGEVENTS_URL);
$GLOBALS['xoopsTpl']->assign('wgevents_icons_url_16', \WGEVENTS_ICONS_URL_16);
// Keywords
$keywords = [];
// Breadcrumbs
$xoBreadcrumbs[] = ['title' => \_MA_WGEVENTS_INDEX, 'link' => 'index.php'];
// Permission
$permView = $permissionsHandler->getPermRegistrationView();
$GLOBALS['xoopsTpl']->assign('permView', $permView);

$uidCurrent = \is_object($GLOBALS['xoopsUser']) ? $GLOBALS['xoopsUser']->uid() : 0;

switch ($op) {
    case 'show':
    case 'list':
    default:
        break;
    case 'listmy':
        // Check permissions
        if (!$permissionsHandler->getPermRegistrationsSubmit()) {
            \redirect_header('registration.php?op=list', 3, \_NOPERM);
        }
        // Breadcrumbs
        $xoBreadcrumbs[] = ['title' => \_MA_WGEVENTS_REGISTRATIONS_MYLIST];
        $events = [];
        $registrations = [];
        $regIp = $_SERVER['REMOTE_ADDR'];
        // get all events with my registrations
        $sql = 'SELECT evid, name ';
        $sql .= 'FROM ' . $GLOBALS['xoopsDB']->prefix('wgevents_registration') . ' ';
        $sql .= 'INNER JOIN ' . $GLOBALS['xoopsDB']->prefix('wgevents_event') . ' ON ' . $GLOBALS['xoopsDB']->prefix('wgevents_registration') . '.evid = ' . $GLOBALS['xoopsDB']->prefix('wgevents_event') . '.id ';
        $sql .= 'WHERE (((' . $GLOBALS['xoopsDB']->prefix('wgevents_registration') . '.ip)="' . $regIp . '")) ';
        $sql .= 'OR (((' . $GLOBALS['xoopsDB']->prefix('wgevents_registration') . '.submitter)=' . $uidCurrent . ')) ';
        $sql .= 'GROUP BY ' . $GLOBALS['xoopsDB']->prefix('wgevents_registration') . '.evid, ' . $GLOBALS['xoopsDB']->prefix('wgevents_event') . '.name ';
        $sql .= 'ORDER BY ' . $GLOBALS['xoopsDB']->prefix('wgevents_event') . '.datefrom DESC;';
        $result = $GLOBALS['xoopsDB']->query($sql);
        while (list($evId, $evName) = $GLOBALS['xoopsDB']->fetchRow($result)) {
            $events[$evId] = [
                'id' => $evId,
                'name' => $evName
            ];
        }
        foreach ($events as $evId => $event) {
            // get all questions for this event
            $GLOBALS['xoopsTpl']->assign('redir', 'listmy');
            // get all questions for this event
            $questionsArr = $questionHandler->getQuestionsByEvent($evId);
            $registrations[$evId]['questions'] = $questionsArr;
            $registrations[$evId]['footerCols'] = \count($questionsArr) + 9;
            //get list of existing registrations for current user/current IP
            $registrations[$evId]['event_name'] = $event['name'];
            $permEdit = $permissionsHandler->getPermEventsEdit($event['submitter'], $event['status']) || $uidCurrent == $event['submitter'];
            $registrations[$evId]['permEditEvent'] = $permEdit;
            $registrations[$evId]['details'] = $registrationHandler->getRegistrationDetailsByEvent($evId, $questionsArr);
        }
        if (\count($registrations) > 0) {
            $GLOBALS['xoopsTpl']->assign('registrations', $registrations);
            unset($registrations);
        } else {
            $GLOBALS['xoopsTpl']->assign('warning', \_MA_WGEVENTS_REGISTRATIONS_THEREARENT);
        }
        break;
    case 'listmyevent':
    case 'listeventall':
        // Check params
        if (0 == $regEvid) {
            \redirect_header('index.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        // Check permissions
        if (!$permissionsHandler->getPermRegistrationsSubmit()) {
            \redirect_header('registration.php?op=list', 3, \_NOPERM);
        }

        $captionList = \_MA_WGEVENTS_REGISTRATIONS_MYLIST;
        $currentUserOnly = true;
        if ('listeventall' == $op) {
            $captionList = \_MA_WGEVENTS_REGISTRATIONS_LIST;
            $currentUserOnly = false;
            $GLOBALS['xoopsTpl']->assign('showSubmitter', true);
        }
        $GLOBALS['xoopsTpl']->assign('captionList', $captionList);
        $GLOBALS['xoopsTpl']->assign('redir', $redir);
        // Breadcrumbs
        $xoBreadcrumbs[] = ['title' => \_MA_WGEVENTS_REGISTRATION_ADD];
        // get all questions for this event
        $questionsArr = $questionHandler->getQuestionsByEvent($regEvid);

        //get list of existing registrations for current user/current IP
        $eventObj = $eventHandler->get($regEvid);
        $event_name = $eventObj->getVar('name');
        $registrations[$regEvid]['event_id'] = $regEvid;
        $registrations[$regEvid]['event_name'] = $event_name;
        $permEdit = $permissionsHandler->getPermEventsEdit($eventObj->getVar('submitter'), $eventObj->getVar('status')) || $uidCurrent == $eventObj->getVar('submitter');
        $registrations[$regEvid]['permEditEvent'] = $permEdit;
        $registrations[$regEvid]['event_fee'] = $eventObj->getVar('fee');
        $registrations[$regEvid]['event_register_max'] = $eventObj->getVar('register_max');
        $registrations[$regEvid]['questions'] = $questionsArr;
        $registrations[$regEvid]['footerCols'] = \count($questionsArr) + 9;
        $registrations[$regEvid]['details'] = $registrationHandler->getRegistrationDetailsByEvent($regEvid, $questionsArr, $currentUserOnly);
        if ($registrations) {
            $GLOBALS['xoopsTpl']->assign('registrations', $registrations);
            unset($registrations);
        }
        if ('listeventall' == $op) {
            $GLOBALS['xoopsTpl']->assign('showHandleList', true);
        } else {
            $permEdit = $permissionsHandler->getPermEventsEdit($eventObj->getVar('submitter'), $eventObj->getVar('status'));
            if ($permEdit ||
                (\time() >= $eventObj->getVar('register_from') && \time() <= $eventObj->getVar('register_to'))
                ) {
                // Form Create
                $registrationObj = $registrationHandler->create();
                $registrationObj->setVar('evid', $regEvid);
                $registrationObj->setRedir($redir);
                $form = $registrationObj->getForm();
                $GLOBALS['xoopsTpl']->assign('form', $form->render());
            }
            if (!$permEdit && \time() < $eventObj->getVar('register_from')) {
                $GLOBALS['xoopsTpl']->assign('warning', sprintf(\_MA_WGEVENTS_REGISTRATION_TOEARLY, \formatTimestamp($eventObj->getVar('register_from'), 'm')));
            }
            if (!$permEdit && \time() > $eventObj->getVar('register_to')) {
                $GLOBALS['xoopsTpl']->assign('warning', sprintf(\_MA_WGEVENTS_REGISTRATION_TOLATE, \formatTimestamp($eventObj->getVar('register_to'), 'm')));
            }
        }
        break;
    case 'save':
        // Security Check
        if (!$GLOBALS['xoopsSecurity']->check()) {
            \redirect_header('registration.php', 3, \implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
        }
        // Check params
        if (0 == $regEvid) {
            \redirect_header('index.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        $eventObj           = $eventHandler->get($regEvid);
        $evSubmitter        = $eventObj->getVar('submitter');
        $evStatus           = $eventObj->getVar('status');
        $registerForceVerif = (bool)$eventObj->getVar('register_forceverif');

        if ($regId > 0) {
            // Check permissions
            $registrationObj = $registrationHandler->get($regId);
            if (!$permissionsHandler->getPermRegistrationsEdit(
                    $registrationObj->getVar('ip'),
                    $registrationObj->getVar('submitter'),
                    $evSubmitter,
                    $evStatus,
                )) {
                    \redirect_header('registration.php?op=list', 3, \_NOPERM);
            }
            $registrationObj = $registrationHandler->get($regId);
            $registrationObjOld = $registrationHandler->get($regId);
        } else {
            // Check permissions
            if (!$permissionsHandler->getPermRegistrationsSubmit()) {
                \redirect_header('registration.php?op=list', 3, \_NOPERM);
            }
            $registrationObj = $registrationHandler->create();
        }
        // create item in table registrations
        $answersValueArr = [];
        $answersTypeArr = [];
        $answersValueArr = Request::getArray('ans_id');
        $answersTypeArr = Request::getArray('type');
        $registrationObj->setVar('evid', $regEvid);
        $registrationObj->setVar('salutation', Request::getInt('salutation'));
        $registrationObj->setVar('firstname', Request::getString('firstname'));
        $registrationObj->setVar('lastname', Request::getString('lastname'));
        $regEmail = Request::getString('email');
        $registrationObj->setVar('email', $regEmail);
        $registrationObj->setVar('email_send', Request::getInt('email_send'));
        $registrationObj->setVar('gdpr', Request::getInt('gdpr'));
        $registrationObj->setVar('ip', Request::getString('ip'));
        $regVerifkey = ('' === Request::getString('verifkey')) ? xoops_makepass() . xoops_makepass(): Request::getString('verifkey');
        $registrationObj->setVar('verifkey', $regVerifkey);
        $regStatus = Request::getInt('status');
        $registrationObj->setVar('status', $regStatus);
        $registrationObj->setVar('financial', Request::getInt('financial'));
        $regListwait = 0;
        if ($regId > 0 || $permissionsHandler->getPermRegistrationsApprove($evSubmitter, $evStatus)) {
            //existing registration or user has perm to approve => take value of form
            $registrationObj->setVar('listwait', Request::getInt('listwait'));
        } else {
            //check number of registrations
            $eventRegisterMax = (int)$eventObj->getVar('register_max');
            if ($eventRegisterMax > 0) {
                $crRegCheck = new \CriteriaCompo();
                $crRegCheck->add(new \Criteria('evid', $regEvid));
                $numberRegCurr = $registrationHandler->getCount($crRegCheck);
                if ($eventRegisterMax <= $numberRegCurr) {
                    $regListwait = 1;
                }
            }
            $registrationObj->setVar('listwait', $regListwait);
        }
        if (Request::hasVar('datecreated_int')) {
            $registrationObj->setVar('datecreated', Request::getInt('datecreated_int'));
        } else {
            $registrationDatecreatedObj = \DateTime::createFromFormat(\_SHORTDATESTRING, Request::getString('datecreated'));
            $registrationObj->setVar('datecreated', $registrationDatecreatedObj->getTimestamp());
        }
        $regSubmitter = Request::getInt('submitter');
        $registrationObj->setVar('submitter', $regSubmitter);
        // Insert Data
        if ($registrationHandler->insert($registrationObj)) {
            $newRegId = $regId > 0 ? $regId : $registrationObj->getNewInsertedId();
            if ($regId > 0) {
                // create copy before deleting
                // get all questions for this event
                $questionsArr = $questionHandler->getQuestionsByEvent($regEvid);
                // get old answers for this questions
                $answersOld = $answerHandler->getAnswersDetailsByRegistration($newRegId, $questionsArr);
                // delete all existing answers
                $answerHandler->cleanupAnswers($regEvid, $regId);
            }
            // create items in table answers
            foreach ($answersValueArr as $key => $value) {
                $answer = '';
                switch ($answersTypeArr[$key]) {
                    case Constants::FIELD_CHECKBOX:
                    case Constants::FIELD_COMBOBOX:
                    case Constants::FIELD_SELECTBOX:
                        $answer = serialize($value);
                        break;
                    default:
                        $answer = $value;
                        break;
                }
                if ('' != $answer) {
                    $answerObj = $answerHandler->create();
                    $answerObj->setVar('regid', $newRegId);
                    $answerObj->setVar('queid', $key);
                    $answerObj->setVar('evid', $regEvid);
                    $answerObj->setVar('text', $answer);
                    $answerObj->setVar('datecreated', \time());
                    $answerObj->setVar('submitter', $regSubmitter);
                    // Insert Data
                    $answerHandler->insert($answerObj);
                }
            }
            // Handle notification
            /*
            $regEvid = $registrationObj->getVar('evid');
            $tags = [];
            $tags['ITEM_NAME'] = $regEvid;
            $tags['ITEM_URL']  = \XOOPS_URL . '/modules/wgevents/registration.php?op=show&id=' . $regId;
            $notificationHandler = \xoops_getHandler('notification');
            if ($regId > 0) {
                // Event modify notification
                $notificationHandler->triggerEvent('global', 0, 'global_modify', $tags);
                $notificationHandler->triggerEvent('registrations', $newRegId, 'registration_modify', $tags);
            } else {
                // Event new notification
                $notificationHandler->triggerEvent('global', 0, 'global_new', $tags);
            }
            */
            // send notifications/confirmation emails
            $infotext        = '';
            $previousMail    = '';
            $newRegistration = false;
            if ($regId > 0) {
                // find changes in table registrations
                $infotext = $registrationHandler->getRegistrationsCompare($registrationObjOld, $registrationObj);
                if ('' != $infotext) {
                    // create history
                    if ($registrationObjOld->getVar('email') != $registrationObj->getVar('email')) {
                        $previousMail = $registrationObjOld->getVar('email');
                    }
                    $registrationhistHandler->createHistory($registrationObjOld, 'update');
                }
                // find changes in table answers
                if (\is_array($answersOld)) {
                    // get new answers for this questions
                    $answersNew = $answerHandler->getAnswersDetailsByRegistration($newRegId, $questionsArr);
                    $result = $answerHandler->getAnswersCompare($answersOld, $answersNew);
                    if ('' != $result) {
                        // create history
                        $answerhistHandler->createHistory($regEvid, $regId, 'update');
                    }
                    $infotext .= $result;
                }
                // other params
                $typeNotify  = Constants::MAIL_REG_NOTIFY_MODIFY;
                $typeConfirm = Constants::MAIL_REG_CONFIRM_MODIFY;
            } else {
                $newRegistration = true;
                if (1 == $regListwait) {
                    // registration was put on a waiting list
                    $infotext .= \_MA_WGEVENTS_MAIL_REG_IN_LISTWAIT . PHP_EOL;
                }

                if (Constants::STATUS_SUBMITTED == $regStatus) {
                    // user has no perm for autoverify
                    $verif = [
                        $newRegId,
                        WGEVENTS_URL,
                        $regEvid,
                        $regEmail,
                        $regVerifkey
                    ];
                    $verifCode = base64_encode(implode('||', $verif));
                    $verifLink = WGEVENTS_URL . '/verification.php?verifkey=' . $verifCode;
                    $infotext .= \sprintf(\_MA_WGEVENTS_MAIL_REG_IN_VERIF, $verifLink) . PHP_EOL;
                }
                if (1 == $regListwait || Constants::STATUS_SUBMITTED == $regStatus) {
                    // registration was put on a waiting list
                    $infotext .= \_MA_WGEVENTS_MAIL_REG_IN_FINAL . PHP_EOL;
                }
                $typeNotify  = Constants::MAIL_REG_NOTIFY_IN;
                $typeConfirm = Constants::MAIL_REG_CONFIRM_IN;
            }
            if ($newRegistration || '' != $infotext) {
                $registerNotify = (string)$eventObj->getVar('register_notify', 'e');
                if ('' != $registerNotify) {
                    // send notifications to emails of register_notify
                    $notifyEmails = $eventHandler->getRecipientsNotify($registerNotify);
                    if (\count($notifyEmails) > 0) {
                        $mailsHandler = new MailHandler();
                        $mailParams   = $mailsHandler->getMailParam($regEvid, $newRegId);
                        $mailParams['infotext'] = $infotext;
                        $mailParams['recipients'] = $notifyEmails;
                        $mailsHandler->setParams($mailParams);
                        $mailsHandler->setType($typeNotify);
                        $mailsHandler->executeReg();
                        unset($mailsHandler);
                    }
                }
                if (('' != $regEmail && Request::getInt('email_send') > 0) || ('' != $previousMail)) {
                    // send confirmation, if radio is checked
                    // or inform old email in any case if email changed
                    $recipients = [];
                    $recipients[] = $regEmail;
                    if ('' != $previousMail) {
                        // add old email address if it changed in order to inform old mail address
                        $recipients[] = $previousMail;
                    }
                    $mailsHandler = new MailHandler();
                    $mailParams = $mailsHandler->getMailParam($regEvid, $newRegId);
                    $mailParams['infotext'] = $infotext;
                    $mailParams['recipients'] = $recipients;
                    $mailsHandler->setParams($mailParams);
                    $mailsHandler->setType($typeConfirm);
                    $mailsHandler->executeReg();
                    unset($mailsHandler);
                }
            }
            // redirect after insert
            \redirect_header('registration.php?op=' . $redir . '&amp;redir=' . $redir . '&amp;evid=' . $regEvid, 2, \_MA_WGEVENTS_FORM_OK);
        }
        // Get Form Error
        $GLOBALS['xoopsTpl']->assign('error', $registrationObj->getHtmlErrors());
        $form = $registrationObj->getForm();
        $GLOBALS['xoopsTpl']->assign('form', $form->render());
        break;
    case 'edit':
        // Breadcrumbs
        $xoBreadcrumbs[] = ['title' => \_MA_WGEVENTS_REGISTRATION_EDIT];
        // Check params
        if (0 == $regId) {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        // Check permissions
        $registrationObj = $registrationHandler->get($regId);
        $eventObj = $eventHandler->get($registrationObj->getVar('evid'));
        if (!$permissionsHandler->getPermRegistrationsEdit(
                $registrationObj->getVar('ip'),
                $registrationObj->getVar('submitter'),
                $eventObj->getVar('submitter'),
                $eventObj->getVar('status'),
            )) {
                \redirect_header('registration.php?op=list', 3, \_NOPERM);
        }
        // Get Form
        $registrationObj = $registrationHandler->get($regId);
        $registrationObj->setRedir($redir);
        $registrationObj->setStart = $start;
        $registrationObj->setLimit = $limit;
        $form = $registrationObj->getForm();
        $GLOBALS['xoopsTpl']->assign('form', $form->render());
        break;

    case 'clone':
        echo 'noch nicht programmiert';die;
        // Breadcrumbs
        $xoBreadcrumbs[] = ['title' => \_MA_WGEVENTS_REGISTRATION_CLONE];
        // Check permissions
        if (!$permissionsHandler->getPermGlobalSubmit()) {
            \redirect_header('registration.php?op=list', 3, \_NOPERM);
        }
        // Request source
        $regIdSource = Request::getInt('id_source');
        // Check params
        if (0 == $regIdSource) {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        // Get Form
        $registrationObjSource = $registrationHandler->get($regIdSource);
        $registrationObj = $registrationObjSource->xoopsClone();
        $form = $registrationObj->getForm();
        $GLOBALS['xoopsTpl']->assign('form', $form->render());
        break;
    case 'delete':
        // Breadcrumbs
        $xoBreadcrumbs[] = ['title' => \_MA_WGEVENTS_REGISTRATION_DELETE];
        // Check params
        if (0 == $regId) {
            \redirect_header('index.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        // Check permissions
        $registrationObj = $registrationHandler->get($regId);
        $eventObj = $eventHandler->get($registrationObj->getVar('evid'));

        $mailsHandler = new MailHandler();
        $mailParams = $mailsHandler->getMailParam($regEvid, $regId);
        unset($mailsHandler);

        $mailParams['email'] = $registrationObj->getVar('email');
        if (isset($_REQUEST['ok']) && 1 == $_REQUEST['ok']) {
            if (!$GLOBALS['xoopsSecurity']->check()) {
                \redirect_header('registration.php', 3, \implode(', ', $GLOBALS['xoopsSecurity']->getErrors()));
            }
            // create history
            $registrationhistHandler->createHistory($registrationObj, 'delete');
            if ($registrationHandler->delete($registrationObj)) {
                // create history
                $answerhistHandler->createHistory($mailParams['evId'], $regId, 'delete');
                //delete existing answers
                $answerHandler->cleanupAnswers($mailParams['evId'], $regId);
                // Event delete notification
                /*
                $tags = [];
                $tags['ITEM_NAME'] = $regEvid;
                $notificationHandler = \xoops_getHandler('notification');
                $notificationHandler->triggerEvent('global', 0, 'global_delete', $tags);
                $notificationHandler->triggerEvent('registrations', $regId, 'registration_delete', $tags);
                */
                // send notifications/confirmation emails
                $typeNotify  = Constants::MAIL_REG_NOTIFY_OUT;
                $typeConfirm = Constants::MAIL_REG_CONFIRM_OUT;
                $registerNotify = (string)$eventObj->getVar('register_notify', 'e');
                if ('' != $registerNotify) {
                    // send notifications to emails of register_notify
                    $notifyEmails = $eventHandler->getRecipientsNotify($registerNotify);
                    if (\count($notifyEmails) > 0) {
                        $mailsHandler = new MailHandler();
                        $mailParams['recipients'] = $notifyEmails;
                        $mailsHandler->setParams($mailParams);
                        $mailsHandler->setType($typeNotify);
                        $mailsHandler->executeReg();
                        unset($mailsHandler);
                    }
                }
                // send email in any case if email is available
                if ('' != $mailParams['regEmail']) {
                    // send confirmation
                    $mailsHandler = new MailHandler();
                    $mailParams['recipients'] = $mailParams['regEmail'];
                    $mailsHandler->setParams($mailParams);
                    $mailsHandler->setType($typeConfirm);
                    $mailsHandler->executeReg();
                    unset($mailsHandler);
                }

                \redirect_header('registration.php?op=' . $redir . '&amp;redir=' . $redir . '&amp;id=' . $regId . '&amp;evid=' . $regEvid, 3, \_MA_WGEVENTS_FORM_DELETE_OK);
            } else {
                $GLOBALS['xoopsTpl']->assign('error', $registrationObj->getHtmlErrors());
            }
        } else {
            $customConfirm = new Common\Confirm(
                ['ok' => 1, 'id' => $regId, 'evid' => $regEvid, 'op' => 'delete', 'redir' => $redir],
                $_SERVER['REQUEST_URI'],
                \sprintf(\_MA_WGEVENTS_CONFIRMDELETE_REGISTRATION, $mailParams['regFirstname'] . ' ' . $mailParams['regLastname']),
                \_MA_WGEVENTS_CONFIRMDELETE_TITLE,
                \_MA_WGEVENTS_CONFIRMDELETE_LABEL
            );
            $form = $customConfirm->getFormConfirm();
            $GLOBALS['xoopsTpl']->assign('form', $form->render());
        }
        break;
    case 'change_financial':
        if (0 == $regEvid) {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        $eventObj = $eventHandler->get($regEvid);
        if (!$permissionsHandler->getPermRegistrationsApprove($eventObj->getVar('submitter'), $eventObj->getVar('status'))) {
            \redirect_header('registration.php?op=list', 3, \_NOPERM);
        }
        if ($regId > 0) {
            // Check permissions
            $registrationObj = $registrationHandler->get($regId);
            $registrationObjOld = $registrationHandler->get($regId);
        } else {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        $registrationObj->setVar('financial', Request::getInt('changeto'));
        // Insert Data
        if ($registrationHandler->insert($registrationObj)) {
            // create history
            $registrationhistHandler->createHistory($registrationObjOld, 'update');
            // Handle notification
            /*
            $regEvid = $registrationObj->getVar('evid');
            $tags = [];
            $tags['ITEM_NAME'] = $regEvid;
            $tags['ITEM_URL']  = \XOOPS_URL . '/modules/wgevents/registration.php?op=show&id=' . $regId;
            $notificationHandler = \xoops_getHandler('notification');
            if ($regId > 0) {
                // Event modify notification
                $notificationHandler->triggerEvent('global', 0, 'global_modify', $tags);
                $notificationHandler->triggerEvent('registrations', $newRegId, 'registration_modify', $tags);
            } else {
                // Event new notification
                $notificationHandler->triggerEvent('global', 0, 'global_new', $tags);
            }
            */
            // send notifications/confirmation emails
            $regEvid = $registrationObj->getVar('evid');
            $eventObj = $eventHandler->get($regEvid);
            // find changes in table registrations
            $infotext = $registrationHandler->getRegistrationsCompare($registrationObjOld, $registrationObj);
            $typeNotify  = Constants::MAIL_REG_NOTIFY_MODIFY;
            $typeConfirm = Constants::MAIL_REG_CONFIRM_MODIFY;
            $registerNotify = (string)$eventObj->getVar('register_notify', 'e');
            if ('' != $registerNotify) {
                // send notifications to emails of register_notify
                $notifyEmails = $eventHandler->getRecipientsNotify($registerNotify);
                if (\count($notifyEmails) > 0) {
                    $mailsHandler = new MailHandler();
                    $mailParams   = $mailsHandler->getMailParam($regEvid, $regId);
                    $mailParams['infotext'] = $infotext;
                    $mailParams['recipients'] = $notifyEmails;
                    $mailsHandler->setParams($mailParams);
                    $mailsHandler->setType($typeNotify);
                    $mailsHandler->executeReg();
                    unset($mailsHandler);
                }
            }
            $regEmail = $registrationObj->getVar('email');
            if ('' != $regEmail) {
                // send confirmation, if radio is checked
                $mailsHandler = new MailHandler();
                $mailParams = $mailsHandler->getMailParam($regEvid, $regId);
                $mailParams['infotext'] = $infotext;
                $mailParams['recipients'] = $mailParams['regEmail'];
                $mailsHandler->setParams($mailParams);
                $mailsHandler->setType($typeConfirm);
                $mailsHandler->executeReg();
                unset($mailsHandler);
            }
            // redirect after insert
            \redirect_header('registration.php?op=' . $redir . '&amp;redir=' . $redir . '&amp;evid=' . $regEvid, 2, \_MA_WGEVENTS_FORM_OK);
        }
        // Get Form Error
        $GLOBALS['xoopsTpl']->assign('error', $registrationObj->getHtmlErrors());
        $form = $registrationObj->getForm();
        $GLOBALS['xoopsTpl']->assign('form', $form->render());
        break;
    case 'listwait_takeover':
        if (0 == $regEvid) {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        $eventObj = $eventHandler->get($regEvid);
        if (!$permissionsHandler->getPermRegistrationsApprove($eventObj->getVar('submitter'), $eventObj->getVar('status'))) {
            \redirect_header('registration.php?op=list', 3, \_NOPERM);
        }
        if ($regId > 0) {
            // Check permissions
            $registrationObj = $registrationHandler->get($regId);
            $registrationObjOld = $registrationHandler->get($regId);
            // create history
            $registrationhistHandler->createHistory($registrationObj, 'update');
        } else {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        $registrationObj->setVar('listwait', 0);
        // Insert Data
        if ($registrationHandler->insert($registrationObj)) {
            // Handle notification
            /*
            $regEvid = $registrationObj->getVar('evid');
            $tags = [];
            $tags['ITEM_NAME'] = $regEvid;
            $tags['ITEM_URL']  = \XOOPS_URL . '/modules/wgevents/registration.php?op=show&id=' . $regId;
            $notificationHandler = \xoops_getHandler('notification');
            if ($regId > 0) {
                // Event modify notification
                $notificationHandler->triggerEvent('global', 0, 'global_modify', $tags);
                $notificationHandler->triggerEvent('registrations', $newRegId, 'registration_modify', $tags);
            } else {
                // Event new notification
                $notificationHandler->triggerEvent('global', 0, 'global_new', $tags);
            }
            */
            // send notifications/confirmation emails
            $regEvid = $registrationObj->getVar('evid');
            $eventObj = $eventHandler->get($regEvid);
            // find changes in table registrations
            $infotext = $registrationHandler->getRegistrationsCompare($registrationObjOld, $registrationObj);

            $typeNotify  = Constants::MAIL_REG_NOTIFY_MODIFY;
            $typeConfirm = Constants::MAIL_REG_CONFIRM_MODIFY;
            $registerNotify = (string)$eventObj->getVar('register_notify', 'e');
            if ('' != $registerNotify) {
                // send notifications to emails of register_notify
                $notifyEmails = $eventHandler->getRecipientsNotify($registerNotify);
                if (\count($notifyEmails) > 0) {
                    $mailsHandler = new MailHandler();
                    $mailParams   = $mailsHandler->getMailParam($regEvid, $regId);
                    $mailParams['infotext'] = $infotext;
                    $mailParams['recipients'] = $notifyEmails;
                    $mailsHandler->setParams($mailParams);
                    $mailsHandler->setType($typeNotify);
                    $mailsHandler->executeReg();
                    unset($mailsHandler);
                }
            }
            $regEmail = $registrationObj->getVar('email');
            if ('' != $regEmail) {
                // send confirmation, if radio is checked
                $mailsHandler = new MailHandler();
                $mailParams = $mailsHandler->getMailParam($regEvid, $regId);
                $mailParams['infotext'] = $infotext;
                $mailParams['recipients'] = $mailParams['regEmail'];
                $mailsHandler->setParams($mailParams);
                $mailsHandler->setType($typeConfirm);
                $mailsHandler->executeReg();
                unset($mailsHandler);
            }
            // redirect after insert
            \redirect_header('registration.php?op=' . $redir . '&amp;redir=' . $redir . '&amp;evid=' . $regEvid, 2, \_MA_WGEVENTS_FORM_OK);
        }
        // Get Form Error
        $GLOBALS['xoopsTpl']->assign('error', $registrationObj->getHtmlErrors());
        $form = $registrationObj->getForm();
        $GLOBALS['xoopsTpl']->assign('form', $form->render());
        break;
    case 'approve_status':
        if (0 == $regEvid) {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        $eventObj = $eventHandler->get($regEvid);
        if (!$permissionsHandler->getPermRegistrationsApprove($eventObj->getVar('submitter'), $eventObj->getVar('status'))) {
            \redirect_header('registration.php?op=list', 3, \_NOPERM);
        }
        if ($regId > 0) {
            // Check permissions
            $registrationObj = $registrationHandler->get($regId);
            $registrationObjOld = $registrationHandler->get($regId);
            // create history
            $registrationhistHandler->createHistory($registrationObj, 'update');
        } else {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        $registrationObj->setVar('listwait', 0);
        $registrationObj->setVar('status', Constants::STATUS_APPROVED);
        // Insert Data
        if ($registrationHandler->insert($registrationObj)) {
            // Handle notification
            /*
            $regEvid = $registrationObj->getVar('evid');
            $tags = [];
            $tags['ITEM_NAME'] = $regEvid;
            $tags['ITEM_URL']  = \XOOPS_URL . '/modules/wgevents/registration.php?op=show&id=' . $regId;
            $notificationHandler = \xoops_getHandler('notification');
            if ($regId > 0) {
                // Event modify notification
                $notificationHandler->triggerEvent('global', 0, 'global_modify', $tags);
                $notificationHandler->triggerEvent('registrations', $newRegId, 'registration_modify', $tags);
            } else {
                // Event new notification
                $notificationHandler->triggerEvent('global', 0, 'global_new', $tags);
            }
            */
            // send notifications/confirmation emails
            $regEvid = $registrationObj->getVar('evid');
            $eventObj = $eventHandler->get($regEvid);
            // find changes in table registrations
            $infotext = $registrationHandler->getRegistrationsCompare($registrationObjOld, $registrationObj);

            $typeNotify  = Constants::MAIL_REG_NOTIFY_MODIFY;
            $typeConfirm = Constants::MAIL_REG_CONFIRM_MODIFY;
            $registerNotify = (string)$eventObj->getVar('register_notify', 'e');
            if ('' != $registerNotify) {
                // send notifications to emails of register_notify
                $notifyEmails = $eventHandler->getRecipientsNotify($registerNotify);
                if (\count($notifyEmails) > 0) {
                    $mailsHandler = new MailHandler();
                    $mailParams   = $mailsHandler->getMailParam($regEvid, $regId);
                    $mailParams['infotext'] = $infotext;
                    $mailParams['recipients'] = $notifyEmails;
                    $mailsHandler->setParams($mailParams);
                    $mailsHandler->setType($typeNotify);
                    $mailsHandler->executeReg();
                    unset($mailsHandler);
                }
            }
            $regEmail = $registrationObj->getVar('email');
            if ('' != $regEmail) {
                // send confirmation, if radio is checked
                $mailsHandler = new MailHandler();
                $mailParams = $mailsHandler->getMailParam($regEvid, $regId);
                $mailParams['infotext'] = $infotext;
                $mailParams['recipients'] = $mailParams['regEmail'];
                $mailsHandler->setParams($mailParams);
                $mailsHandler->setType($typeConfirm);
                $mailsHandler->executeReg();
                unset($mailsHandler);
            }
            // redirect after insert
            \redirect_header('registration.php?op=' . $redir . '&amp;redir=' . $redir . '&amp;evid=' . $regEvid, 2, \_MA_WGEVENTS_FORM_OK);
        }
        // Get Form Error
        $GLOBALS['xoopsTpl']->assign('error', $registrationObj->getHtmlErrors());
        $form = $registrationObj->getForm();
        $GLOBALS['xoopsTpl']->assign('form', $form->render());
        break;
    case 'contactall':
        // Breadcrumbs
        $xoBreadcrumbs[] = ['title' => \_MA_WGEVENTS_CONTACT_ALL];
        // Check params
        if (0 == $regEvid) {
            \redirect_header('registration.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }
        // Get Form
        $eventObj = $eventHandler->get($regEvid);
        $form = $eventObj->getFormContactAll();
        $GLOBALS['xoopsTpl']->assign('form', $form->render());
        break;
    case 'exec_contactall':
        // Security Check
        if (!$GLOBALS['xoopsSecurity']->check()) {
            \redirect_header('registration.php', 3, \implode(',', $GLOBALS['xoopsSecurity']->getErrors()));
        }
        // Check params
        if (0 == $regEvid) {
            \redirect_header('index.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }

        $eventObj = $eventHandler->get($regEvid);
        // Check permissions
        if (!$permissionsHandler->getPermEventsEdit($eventObj->getVar('submitter'), $eventObj->getVar('status'))) {
            \redirect_header('index.php?op=list', 3, \_NOPERM);
        }
        $crRegistration = new \CriteriaCompo();
        $crRegistration->add(new \Criteria('evid', $regEvid));
        $numberRegCurr = $registrationHandler->getCount($crRegistration);
        $mailToArr = [];
        if ($numberRegCurr > 0) {
            $registrationsAll = $registrationHandler->getAll($crRegistration);
            foreach (\array_keys($registrationsAll) as $i) {
                $mailToArr[$registrationsAll[$i]->getVar('email')] = $registrationsAll[$i]->getVar('email');
            }
        }
        $mailFrom = Request::getString('mail_from');
        if (1 == Request::getInt('mail_copy')) {
            $mailToArr[$mailFrom] = $mailFrom;
        }
        $mailParams = [];
        $mailParams['template']    ='mail_event_notify_all.tpl';
        $mailParams['evId']        = $regEvid;
        $mailParams['evName']      = $eventObj->getVar('name');
        $mailParams['evDatefrom']  = $eventObj->getVar('datefrom');
        $mailParams['evLocation']  = $eventObj->getVar('location');
        $mailParams['mailFrom']    = $mailFrom;
        $mailParams['mailBody']    = Request::getString('mail_body');
        $mailParams['mailSubject'] = Request::getString('mail_subject');
        $mailParams['recipients']  = $mailToArr;

        $mailsHandler = new MailHandler();
        $mailsHandler->setParams($mailParams);
        $mailsHandler->setHtml(true);
        $result = $mailsHandler->executeContactAll();
        unset($mailsHandler);
        if ($result) {
            // redirect after insert
            \redirect_header('registration.php?op=listeventall&amp;evid=' . $regEvid, 2, \_MA_WGEVENTS_FORM_OK);
        } else {
            \redirect_header('index.php?op=list', 3, \_MA_WGEVENTS_INVALID_PARAM);
        }

        break;
}

// Keywords
wgeventsMetaKeywords($helper->getConfig('keywords') . ', ' . \implode(',', $keywords));
unset($keywords);

// Description
wgeventsMetaDescription(\_MA_WGEVENTS_REGISTRATIONS_DESC);
$GLOBALS['xoopsTpl']->assign('xoops_mpageurl', \WGEVENTS_URL.'/registration.php');
$GLOBALS['xoopsTpl']->assign('wgevents_upload_url', \WGEVENTS_UPLOAD_URL);

require __DIR__ . '/footer.php';
