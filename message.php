<?php
// show messages file
// $Id: message.php,v 1.6 2007/08/02 16:27:37 nobu Exp $

include "../../mainfile.php";
include "functions.php";

$myts =& MyTextSanitizer::getInstance();
$xoopsOption['template_main'] = "ccenter_message.html";
$uid = is_object($xoopsUser)?$xoopsUser->getVar('uid'):0;
$isadmin = $uid && $xoopsUser->isAdmin($xoopsModule->getVar('mid'));

$msgid = intval($_GET['id']);

if (isset($_GET['p'])) {
    $_SESSION['onepass'] = $myts->stripSlashesGPC($_GET['p']);
}
$pass = empty($_SESSION['onepass'])?"":$_SESSION['onepass'];

$cond = $isadmin?"":" AND status<>'x'";
$res = $xoopsDB->query("SELECT m.*, title FROM ".MESSAGE." m,".FORMS." WHERE msgid=$msgid $cond AND fidref=formid");
if (!$res || $xoopsDB->getRowsNum($res)==0) {
    redirect_header("index.php", 3, _NOPERM);
    exit;
}
$data = $xoopsDB->fetchArray($res);
if (!check_perm($data)) {
    redirect_header(XOOPS_URL.'/user.php', 3, _NOPERM);
    exit;
}
// referer
if ($uid && $uid == $data['touid'] && $data['status']=='-') {
    $now = time();
    $xoopsDB->queryF("UPDATE ".MESSAGE." SET status='a',mtime=$now WHERE msgid=".$msgid);
    cc_log_status($data, 'a');
    $data['status'] = 'a';
}

include XOOPS_ROOT_PATH."/header.php";

$vals = unserialize_text($data['body']);
$add = $pass?"p=".urlencode($pass):"";
$to_uname = XoopsUser::getUnameFromId($data['touid']);
$items=array();
foreach ($vals as $key=>$val) {
    if (preg_match('/^file=(.+)$/', $val, $d)) {
	$val = attach_image($data['msgid'], $d[1], false, $add);
    } else {
	$val = $myts->displayTarea($val);
    }
    $items[$key] = $val;
}
$data['comment'] = $myts->displayTarea($data['comment']);
$isadmin = $uid && $xoopsUser->isAdmin($xoopsModule->getVar('mid'));
$xoopsTpl->assign(
    array('subject'=>$data['title'],
	  'sender'=>xoops_getLinkedUnameFromId($data['uid']),
	  'sendto'=>$data['touid']?xoops_getLinkedUnameFromId($data['touid']):_MD_CONTACT_NOTYET,
	  'cdate'=>formatTimestamp($data['ctime']),
	  'mdate'=>myTimestamp($data['mtime'], 'l', _MD_TIME_UNIT),
	  'data'=> $data,
	  'items'=>$items,
	  'status'=>$msg_status[$data['status']],
	  'is_eval'=>is_evaluate($msgid, $uid, $pass),
	  'is_mine'=>$data['touid']==$uid,
	  'own_status'=>array_slice($msg_status, 1, $isadmin?4:3),
	  'xoops_pagetitle'=> htmlspecialchars($xoopsModule->getVar('name')." | ".$data['title']),
	));


include XOOPS_ROOT_PATH.'/include/comment_view.php';

include XOOPS_ROOT_PATH."/footer.php";
?>