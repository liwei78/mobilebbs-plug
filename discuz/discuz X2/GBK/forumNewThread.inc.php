<?php
defined('IN_DISCUZ') or exit;
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="POST") {
    $httpUid = trim(base64_decode($_SERVER['HTTP_UID']));
    $httpFid = trim(base64_decode($_SERVER['HTTP_FID']));
	               
    $httpSubject = addslashes(iconv("utf-8","gb2312",urldecode(base64_decode($_SERVER['HTTP_SUBJECT']))));
	$httpMessage = addslashes(iconv("utf-8","gb2312",urldecode(base64_decode($_SERVER['HTTP_MESSAGE']))));
    //$httpMessage = mysql_real_escape_string(base64_decode($_SERVER['HTTP_MESSAGE']));
    $httpAid = trim(base64_decode($_SERVER['HTTP_AID']));
    
    $uid = !empty($httpUid)?$httpUid:0;
    $fid = !empty($httpFid)?$httpFid:0;
    if ($uid>0){
        $username = DB::result_first("SELECT username FROM ".DB::table('ucenter_members')." where uid='$uid'");
    }
    $author=$username;
    $authorId=$uid;
    $subject = !empty($httpSubject)?$httpSubject:0;
    $message = !empty($httpMessage)?$httpMessage:0;
    $readperm = 0;
    $price = 0;
    $typeid = 0;
    $sortid =0;
    $gtimestamp =$_G['timestamp'];
    $displayorder = 0;
    $digest = 0;
    $replycredit=0;
    $closed=0;
    $special=0;
    $moderated = 0;
    $status=32;
    $isgroup =0;
    $subject=urldecode($subject);
    $message=urldecode($message);
    if($uid==0){
        $ecode = base64_encode("5");
        echo "{\"error\":\"$ecode\"}";
        return;
    }
    elseif ($fid==0){
        $ecode = base64_encode("2");
        echo "{\"error\":\"$ecode\"}";
        return ;
    }elseif (empty($subject)) {
        $ecode = base64_encode("3");
        echo "{\"error\":\"$ecode\"}";
        return ;
    }elseif (empty($message)) {
        $ecode = base64_encode("4");
        echo "{\"error\":\"$ecode\"}";
        return ;
    }elseif (empty($httpAid)&&strpos($message,'[attach]')==True){
       $ecode = base64_encode("5");
       echo "{\"error\":\"$ecode\"}";
       return ; 
    }
    $attach = empty($httpAid)?"0":"2";
    $strsql = "INSERT INTO ".DB::table('forum_thread')." (fid, posttableid, readperm, price, typeid, sortid, author, authorid, subject, dateline, lastpost, lastposter, displayorder, digest, special, attachment, moderated, status, isgroup, replycredit, closed)
        VALUES ('$fid', '0', '$readperm', '$price', '$typeid', '$sortid', '$author', '$authorId', '$subject', '$gtimestamp', '$gtimestamp', '$author', '$displayorder', '$digest', '$special', '$attach', '$moderated', '$status', '$isgroup', '$replycredit', '$closed')";
    DB::query($strsql);
    $tid = DB::insert_id();
    $useip =$_G['clientip'];
    $pinvisible = 0;
    $isanonymous = 0;
    $usesig = 1;
    $htmlon = 0;
    $bbcodeoff = 0; //�����༭������
    $smileyoff = 0; //�����������
    $parseurloff = 0;
    $tagstr ="";
    $status=0;
    $message = preg_replace('/\[attachimg\](\d+)\[\/attachimg\]/is', '[attach]\1[/attach]', $message);
    $data= array(
        'fid' => $fid,
        'tid' => $tid,
        'first' => '1',
        'author' => $author,
        'authorid' => $authorId,
        'subject' => mysql_real_escape_string($subject),
        'dateline' => $gtimestamp,
        'message' => mysql_real_escape_string($message),
        'useip' => $useip,
        'invisible' => $pinvisible,
        'anonymous' => $isanonymous,
        'usesig' => $usesig,
        'htmlon' => $htmlon,
        'bbcodeoff' => $bbcodeoff,
        'smileyoff' => $smileyoff,
        'parseurloff' => $parseurloff,
        'attachment' => $attach,
        'tags' => $tagstr,
        'replycredit' => '0',
        'status' => $status
    );
    if(isset($tid)) {
        $tableid = DB::result_first("SELECT posttableid FROM ".DB::table('forum_thread')." WHERE tid='$tid'");
    } else {
        $tableid = $tid = 0;
    }
    $pid = DB::insert('forum_post_tableid', array('pid' => null), true);
    if(!$tableid) {
        $tablename = 'forum_post';
    } else {
        $tablename = "forum_post_$tableid";
    }
    DB::insert($tablename, $data);
    save_syscache('max_post_id', $pid);
    $operator = '+';
    $uidarray = $authorId;
    $action = 'post';
    $val = $operator == '+' ? 1 : -1;
    $extsql = array();
    if($action == 'reply') {
        $extsql = array('posts' => $val);
    } elseif($action == 'post') {
        $extsql = array('threads' => $val, 'posts' => $val);
    }
    $uidarray = (array)$uidarray;
    foreach($uidarray as $uid) {
            updatecreditbyaction($action, $uid, $extsql, '', $val, 1, $fid);
    }
    if($operator == '+' && ($action == 'reply' || $action == 'post')) {
        $uids = implode(',', $uidarray);
        DB::query("UPDATE ".DB::table('common_member_status')." SET lastpost='".TIMESTAMP."' WHERE uid IN ('$uids')", 'UNBUFFERED');
    }
    $subject = str_replace("\t", ' ', $subject);
    $lastpost = "$tid\t$subject\t$gtimestamp\t$author";
    DB::query("UPDATE ".DB::table('forum_forum')." SET lastpost='$lastpost', threads=threads+1, posts=posts+1, todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
    if (!empty($httpAid))
    {
       $rtid = getattachtableid($tid);
       DB::update('forum_attachment',array('tid' => $tid,'pid'=>$pid,'uid'=>$uid,'tableid'=>$rtid),"aid IN (".$httpAid.")");
       $query = DB::query("select aid,uid,dateline,filename,filesize,attachment,remote,isimage,width,thumb from ".DB::table('forum_attachment_unused')." where aid in (".$httpAid.")");
       while($attach = DB::fetch($query)) {
           $taid = $attach['aid'];
           $arrayData = array(
             'aid'=>$taid,
             'tid'=>$tid,
             'pid'=>$pid,
             'uid'=>$uid,
             'dateline'=>$attach['dateline'],
             'filename'=>$attach['filename'],
             'filesize'=>$attach['filesize'],
             'attachment'=>$attach['attachment'],
             'remote'=>$attach['remote'],
             'isimage'=>$attach['isimage'],
             'width'=>$attach['width'],
             'thumb'=>$attach['thumb']
           );
        DB::insert("forum_attachment_".$rtid,$arrayData);
        DB::delete("forum_attachment_unused","aid=".$taid);   
       }
    }
    $ecode = base64_encode("0");
    echo "{\"error\":\"$ecode\"}";
}else {
    $ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}

?>
