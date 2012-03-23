<?php
require_once ('../../global.php');
require_once ('json.class.php');
require_once ('tool.php');
header("Content-type: application/json; charset=UTF-8 "); 

if($_SERVER['REQUEST_METHOD']=="GET") {
    $httpSize = trim(base64_decode($_SERVER['HTTP_PAGESIZE']));
	$huid = trim(base64_decode($_SERVER['HTTP_UID']));
    $Size = intval($httpSize)>0?intval($httpSize):10;
    $tabPre = $db->dbpre;
    $tquery = $db->query("select * from ".$tabPre."threads left join ".$tabPre."tmsgs using(tid) where ".
              "fid!=0  order by lastpost desc LIMIT 0, $Size");
	$zjson['list']=array();
	while ($forums = $db->fetch_array($tquery)) {
		$tid = $forums['tid'];//TID����
        $fid = $forums['fid'];//FID���
        $author = $forums['author'];//����
        $authorid = $forums['authorid'];//����ID
        $subject = $forums['subject'];//������������ݡ����ʾ�������Ƿ����������ǻظ�
        $content = $forums['content'];//����
        $ifsheid = $forums['ifshield'];//�Ƿ�����
        $locked = $forums['locked'];//1����2�ر�
        $closed = $locked>=1?$locked:$ifsheid; //1����2�ر�2����
        $replies = $forums['replies'];//�ظ���
        $hits = $forums['hits'];//�����
        $postdate = date("Y-m-d H:i:s",$forums['postdate']);//����ʱ��
        $lastpost = date("Y-m-d H:i:s",$forums['lastpost']);//����޸�ʱ��
        $lastposter = $forums['lastposter'];//���ظ���
        $uid = empty($winduid)?$winduid:0;
        $replyCountnum = 20;
        $sumpage = $replies/$replyCountnum;
        $sumpage = (int)($sumpage+1);

		if ($authorid>0){
			$mimgurl = UC_API.getInfo($authorid);
		}
		$_summary = strip_tags(stripWindCode($content));
	
		$_summary = str_replace(array('"', "\n", "\r", '&nbsp;', '&amp;', '&lt;', '', '&#160;'), '', $_summary);
		$_summary = substrs($_summary, 255);
		if ($ifConvert) {
			$wordsfb = L::loadClass('FilterUtil', 'filter');
			$_summary = $wordsfb->convert($_summary);
		}
		$content = $_summary;
		$ary = getstatebyuid($huid);
        $state = $ary['state'];
        $sdate = $ary['sdate'];
        $edate = $ary['edate'];
		$platearray = array("tid"           => base64_encode($tid),
                            "fid"           => base64_encode($fid),
                            "author"        => base64_encode(iconv("gb2312","utf-8",$author)),
                            "authorId"      => base64_encode($authorid),
                            "subject"       => base64_encode(iconv("gb2312","utf-8",$subject)),
                            "views"         => base64_encode($hits),
                            "replies"       => base64_encode($replies),
		                    "dateline"      => base64_encode($postdate),
                            "lastpost"      => base64_encode($lastpost),
                            "lastposter"    => base64_encode(iconv("gb2312","utf-8",$lastposter)),
                            "mimgurl"       => base64_encode($mimgurl),
                            "closed"        => base64_encode($closed),
                            "sumpage"       => base64_encode($sumpage),
			                "userstate"     => $state,
                            "sdate"         => $sdate,
                            "edate"         => $edate
			               );
		array_push($zjson['list'],$platearray);
	}
	echo  ArrayJSON($zjson);
	
}else {
	$ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}

?>