<?php
// show messages file
// $Id: message.php,v 1.24 2011/03/14 14:25:45 nobu Exp $

include "../../mainfile.php";
include "functions.php";

$myts =& MyTextSanitizer::getInstance();
$xoopsOption['template_main'] = "ccenter_message.html";
$uid = is_object($xoopsUser)?$xoopsUser->getVar('uid'):0;

$msgid = intval($_GET['id']);

$data = cc_get_message($msgid);

// change to accept status when change user access
if ($uid && $uid == $data['touid'] && $data['status']==_STATUS_NONE) {
    change_message_status($msgid, $uid, _STATUS_ACCEPT);
    $data['status'] = _STATUS_ACCEPT;
}

// recording contactee access time
$now = time();
if ($uid == $data['uid'] && $now>$data['atime']) {
    $xoopsDB->queryF("UPDATE ".CCMES." SET atime=$now WHERE msgid=$msgid");
}

include XOOPS_ROOT_PATH."/header.php";

$breadcrumbs = new XoopsBreadcrumbs(_MD_CCENTER_RECEPTION, 'reception.php');

$add = $pass?"p=".urlencode($pass):"";
$to_uname = XoopsUser::getUnameFromId($data['touid']);
$items = get_form_attribute($data['defs']);
$values = cc_display_values(unserialize_text($data['body']), $items, $data['msgid'], $add);
$data['comment'] = $myts->displayTarea($data['comment']);
$isadmin = $uid && $xoopsUser->isAdmin($xoopsModule->getVar('mid'));
$title = $data['title'];
list($lab) = array_keys($values);
if ($isadmin) {
    $breadcrumbs->set($title, "reception.php?form=".$data['fidref']);
} else {
    $breadcrumbs->set($title, "index.php?form=".$data['fidref']);
}
$breadcrumbs->set($lab.': '.$values[$lab], '');
$breadcrumbs->assign();
$has_mail = false;
foreach ($items as $item) {
    if ($item['type'] == 'mail') {
	$has_mail = true;
	break;
    }
}
$xoopsTpl->assign(
    array('subject'=>$title,
	  'sender'=>xoops_getLinkedUnameFromId($data['uid']),
	  'sendto'=>$data['touid']?xoops_getLinkedUnameFromId($data['touid']):_MD_CONTACT_NOTYET,
	  'cdate'=>formatTimestamp($data['ctime']),
	  'mdate'=>myTimestamp($data['mtime'], 'l', _MD_TIME_UNIT),
	  'adate'=>myTimestamp($data['atime'], 'l', _MD_TIME_UNIT),
	  'readit'=>($data['atime']>=$data['mtime']),
	  'data'=> $data,
	  'items'=>$values,
	  'status'=>$msg_status[$data['status']],
	  'is_eval'=>is_cc_evaluate($msgid, $uid, $pass),
	  'is_mine'=>$data['touid']==$uid,
	  'is_getmine'=>$data['touid']==0 && $uid && in_array($data['cgroup'], $xoopsUser->getGroups()),
	  'own_status'=>array_slice($msg_status, 1, $isadmin?4:3),
	  'xoops_pagetitle'=> htmlspecialchars($xoopsModule->getVar('name')." | ".$data['title']),
	  'has_mail'=>$has_mail,
	));


include XOOPS_ROOT_PATH.'/include/comment_view.php';

include XOOPS_ROOT_PATH."/footer.php";
?>