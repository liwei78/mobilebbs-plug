<?php

require_once ('../../global.php');
require_once ('json.class.php');
require_once ('tool.php');
header("Content-type: application/json; charset=UTF-8 ");
if($_SERVER['REQUEST_METHOD']=="GET") {
	$httpTid = base64_decode($_SERVER['HTTP_TID']);
    $httpPage= base64_decode($_SERVER['HTTP_PAGE']);
    $httpmodel=base64_decode($_SERVER['HTTP_MODEL']);                                                                  $huid = trim(base64_decode($_SERVER['HTTP_UID']));
    $httpsize =base64_decode($_SERVER['HTTP_PAGESIZE']);
    
	$Tid = intval($httpTid)>0?intval($httpTid):0;
    $Page = intval($httpPage)>0?intval($httpPage):0;
    $model = intval($httpmodel)>0?intval($httpmodel):0;
    $size = intval($httpsize)>0?intval($httpsize):10; 

	if ($Tid==0) {
	    $ecode = base64_encode("2");
        echo "{\"error\":\"$ecode\"}";
        return ;
	}
	$frontnumber = ($Page*$size);
    $zjson['list'] = array();
   

    $tabPre = $db->dbpre;
    $tquery = $db->query("select * from ".$tabPre."threads left join pw_tmsgs using(tid) where tid=$Tid ");
    while ($forums = $db->fetch_array($tquery)) {
		$tid=$forums['tid'];//TID����
		$fid=$forums['fid'];//FID���
		$author=$forums['author'];//����
		$authorid=$forums['authorid'];//����ID
		$subject=$forums['subject'];//����
		$content=$forums['content'];//����
		$ifshield=$forums['ifshield'];//�Ƿ����ε�
		$replies=$forums['replies'];//�ظ���
		$hits=$forums['hits'];//�����
		$postdate=date("Y-m-d H:i:s",$forums['postdate']);//����ʱ��
		$lastpost=date("Y-m-d H:i:s",$forums['lastpost']);//����޸�ʱ��
		$lastposter=$forums['lastposter'];//���ظ���
		$uid=!empty($winduid)?$winduid:0;
		$replyCountnum=20;
		$sumpage=$replies/$replyCountnum;
		$sumpage=(int)($sumpage+1);

		if ($authorid>0){
			$mimgurl = UC_API.getInfo($authorid);
		}
		$ary = getstatebyuid($authorid);
        $state = $ary['state'];
        $sdate = $ary['sdate'];
        $edate = $ary['edate'];
		if ($model==0){
		    $content =convert($content,$model);
		    $matches = array();
		    $arrcontent=array();
		    preg_match_all('/\[(attachment|p_w_upload|p_w_picpath)=(\d+)\]/is', $content, $matches);
		    foreach ($matches[0] as $key => $aid) {
				array_push($arrcontent,$aid);
		        }
		    $arrcontent = attachment($content);
		    //�滻���ݸ���ͼƬ
		    for ($ci=0;$ci<= count($arrcontent);$ci++){
			    $aid=$arrcontent[$ci];//�õ�����id
			    if ($aid!=0){
				    $aquery = $db->query("select * from ".$tabPre."attachs where aid=".$aid);
				    if ($aforum = $db->fetch_array($aquery)) {
					    $aimg = $aforum['attachurl'];
				        }
				    $attmsg="[attachment=".$aid."]";
				    $amsg = "";
				    //�滻����
				    $content= str_replace($attmsg,$amsg,$content);
			        }
		    }
		}else{
			$content =convert($content,$model);
		    //����attachment  ����ͼƬ
		    $matches = array();
		    $arrcontent=array();
		    preg_match_all('/\[(attachment|p_w_upload|p_w_picpath)=(\d+)\]/is', $content, $matches);
		    foreach ($matches[0] as $key => $aid) {
				array_push($arrcontent,$aid);
		        }
		    $arrcontent = attachment($content);
		    //�滻���ݸ���ͼƬ
		    for ($ci=0;$ci<= count($arrcontent);$ci++){
			    $aid=$arrcontent[$ci];//�õ�����id
			    if ($aid!=0){
				    $aquery = $db->query("select * from ".$tabPre."attachs where aid=".$aid);
				    if ($aforum = $db->fetch_array($aquery)) {
					    $aimg = $aforum['attachurl'];
					    $filename = $aforum['name'];
                        $type = $aforum['type'];
				        }
				    $attmsg="[attachment=".$aid."]";
				    if ($type=='img'){
                      $amsg = "<img src=\"".UC_API."/attachment/".$aimg."\" align=\"absbottom\" border=\"0\" />";
                    }else{
                      $amsg = "<a href=\"".UC_API."/attachment/".$aimg."\" >$filename</a>";     
                    }
                    //�滻����
				    $content= str_replace($attmsg,$amsg,$content);
			    }
		    }
		}
        $replies = $db->get_value("select count(tid) from ".$tabPre."posts where tid='$tid'");
		if (intval($Page)==0){
			//$authorstate = GetState($authorid);
            //$ifshield = $ifshield==1?1:$authorstate;
            //$content = str_replace("\n", "\",",$content);
		    $platearray = array("pid"       => base64_encode('0'),
                                "author"    => base64_encode(iconv("gb2312","utf-8",$author)),
                                "authorid"  => base64_encode($authorid),
                                "msg"       => '',
                                "message"   => base64_encode(iconv("gb2312","utf-8",$content)),
		                        "dateline"  => base64_encode(iconv("gb2312","utf-8",$postdate)),
                                "mimgurl"   => base64_encode(urldecode($mimgurl)),
                                "status"    => base64_encode($ifshield),
                                "replies"   => base64_encode($replies),
                                "lou"       => base64_encode('0'),
				                "userstate" => $state,
								"sdate"     => $sdate,
								"edate"     => $edate
			                    );
		    array_push($zjson['list'],$platearray);
		}
	}

    //��ȡ�ظ���Ϣ
    $pquery = $db->query("select * from ".$tabPre."posts where tid =$Tid LIMIT $frontnumber, $size");
	$loui=1+$frontnumber;
	while ($forums = $db->fetch_array($pquery)) {
		$pid=$forums['pid'];//TID����
		$fid=$forums['fid'];//FID���
		$tid=$forums['tid'];//TID����
		$author=$forums['author'];//����
		$authorid=$forums['authorid'];//����ID
		$subject=$forums['subject'];//����
		$content=$forums['content'];//����
		$ifshield=$forums['ifshield'];//�Ƿ����ε�
		$postdate=date("Y-m-d H:i:s",$forums['postdate']);//����ʱ��
		$uid=empty($winduid)?0:$winduid;
		
		if ($authorid>0){
			$mimgurl = UC_API.getInfo($authorid);
		}
		$ary = getstatebyuid($authorid);
        $state = $ary['state'];
        $sdate = $ary['sdate'];
        $edate = $ary['edate'];
        //------------------------------------------------------------------------------
		if ($model==0){
		    $content =convert($content,$model);
		    $matches = array();
		    $arrcontent=array();
		    preg_match_all('/\[(attachment|p_w_upload|p_w_picpath)=(\d+)\]/is', $content, $matches);
		    foreach ($matches[0] as $key => $aid) {
				array_push($arrcontent,$aid);
		        }
		    $arrcontent = attachment($content);
            
		    //�滻���ݸ���ͼƬ
		    for ($ci=0;$ci<= count($arrcontent);$ci++){
			    $aid=$arrcontent[$ci];//�õ�����id
			    if ($aid!=0){
				    $aquery = $db->query("select * from ".$tabPre."attachs where aid=".$aid);
				    if ($aforum = $db->fetch_array($aquery)) {
					    $aimg = $aforum['attachurl'];
				        }
				    $attmsg="[attachment=".$aid."]";
				    $amsg = "";
				    //�滻����
				    $content= str_replace($attmsg,$amsg,$content);
			        }
		    }
		}else{
			$content =convert($content,$model);
			//����attachment  ����ͼƬ
		    $matches = array();
		    $arrcontent=array();
		    preg_match_all('/\[(attachment|p_w_upload|p_w_picpath)=(\d+)\]/is', $content, $matches);
		    foreach ($matches[0] as $key => $aid) {
				array_push($arrcontent,$aid);
		        }
		    $arrcontent = attachment($content);
		    //�滻���ݸ���ͼƬ
		    for ($ci=0;$ci<=count($arrcontent);$ci++){
			    $aid=$arrcontent[$ci];//�õ�����id
			    if ($aid!=0){
				    $aquery = $db->query("select * from ".$tabPre."attachs where aid=".$aid);
				    if ($aforum = $db->fetch_array($aquery)) {
					    $aimg = $aforum['attachurl'];
					    $filename = $aforum['name'];
                        $type = $aforum['type'];
				        }
				    $attmsg="[attachment=".$aid."]";
				    if ($type=='img'){
				       $amsg = "<img src=\"".UC_API."/attachment/".$aimg."\" align=\"absbottom\" border=\"0\" />";
                    }else{
                       $amsg = "<a href=\"".UC_API."/attachment/".$aimg."\" >$filename</a>";     
                    }
				    //�滻����
				    $content= str_replace($attmsg,$amsg,$content);
			    }
		    }
		}
		//attachment   //ȥ��attachment��ǩ
        $replies = $db->get_value("select count(tid) from ".$tabPre."posts where tid='$tid'");
        //$authorstate = GetState($authorid);
        //$ifshield = $ifshield==1?1:$authorstate;
        //$content = str_replace("\n", "\",",$content);
		$platearray = array( "pid"      => base64_encode($pid),
                             "author"   => base64_encode(iconv("gb2312","utf-8",$author)),
                             "authorid" => base64_encode($authorid),
                             "msg"      => base64_encode(iconv("gb2312","utf-8",$subject)),
                             "message"  => base64_encode(iconv("gb2312","utf-8",$content)),
		                     "dateline" => base64_encode(iconv("gb2312","utf-8",$postdate)),
                             "mimgurl"  => base64_encode(urldecode($mimgurl)),
                             "status"   => base64_encode($ifshield),
                             "replies"  => base64_encode($replies),
                             "lou"      => base64_encode($loui),
			                 "userstate"=> $state,
                             "sdate"    => $sdate,
                             "edate"    => $edate
			               );
		$loui=$loui+1;
		array_push($zjson['list'],$platearray);
	}
	echo ArrayJSON($zjson);
}else {
	$ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}

?>