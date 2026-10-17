<?php
if(!defined('PUZZLE_PATH_ACCESS') || empty($booking)){http_response_code(403);exit;}

// This file is included only after the private dashboard booking has been authorised.
$boards=[];$latestQuest=(int)$booking['event_id'];
$stmt=puzzlepathProgressStatement($db,"SELECT b.event_id FROM wp2s_pp_bookings b JOIN wp2s_pp_booking_progress p ON p.booking_id=b.id WHERE LOWER(TRIM(b.customer_email))=LOWER(TRIM(?)) AND b.payment_status='paid' AND p.quest_status='completed' ORDER BY p.completed_at DESC,b.id DESC LIMIT 1",'s',[$booking['customer_email']]);
$latest=$stmt->get_result()->fetch_assoc();$stmt->close();if($latest)$latestQuest=(int)$latest['event_id'];
$stmt=puzzlepathProgressStatement($db,'SELECT id,title,price,quest_description,location,medal_image_url,seats,display_on_site FROM wp2s_pp_events WHERE display_on_site=1 OR id=? ORDER BY (id=?) DESC,title','ii',[$latestQuest,$latestQuest]);
$result=$stmt->get_result();while($quest=$result->fetch_assoc())$boards[]=['quest'=>$quest];$stmt->close();
foreach($boards as &$board){
 $qid=(int)$board['quest']['id'];$board['featured']=$qid===$latestQuest;
 $base=" FROM wp2s_pp_bookings b JOIN wp2s_pp_booking_progress p ON p.booking_id=b.id WHERE b.event_id=? AND b.payment_status='paid' AND COALESCE(b.is_tester,0)=0 AND p.quest_status='completed' AND p.source='app' AND p.duration_ms>0";
 $stmt=puzzlepathProgressStatement($db,'SELECT b.id,p.duration_ms,p.completed_at'.$base.' ORDER BY p.duration_ms,p.completed_at,b.id LIMIT 10','i',[$qid]);
 $leadersForQuest=[];$result=$stmt->get_result();while($row=$result->fetch_assoc())$leadersForQuest[]=$row;$stmt->close();
 $stmt=puzzlepathProgressStatement($db,"SELECT b.id,b.customer_name,b.is_tester,p.duration_ms,p.source FROM wp2s_pp_bookings b JOIN wp2s_pp_booking_progress p ON p.booking_id=b.id WHERE b.event_id=? AND LOWER(TRIM(b.customer_email))=LOWER(TRIM(?)) AND b.payment_status='paid' AND p.quest_status='completed' ORDER BY p.completed_at DESC,b.id DESC LIMIT 1",'is',[$qid,$booking['customer_email']]);
 $own=$stmt->get_result()->fetch_assoc();$stmt->close();$ownRank=null;$board['completed']=(bool)$own;
 if($own && !(int)$own['is_tester'] && $own['source']==='app' && (int)$own['duration_ms']>0){
  $stmt=puzzlepathProgressStatement($db,'SELECT COUNT(*) AS faster'.$base.' AND p.duration_ms<?','ii',[$qid,(int)$own['duration_ms']]);$ownRank=1+(int)$stmt->get_result()->fetch_assoc()['faster'];$stmt->close();
 }
 if($own)$board['rows']=pdleaderboardRows($leadersForQuest,$own,$ownRank);
 else{
  $board['rows']=[];$last=null;$place=0;
  foreach($leadersForQuest as $i=>$row){if($last!==$row['duration_ms'])$place=$i+1;$last=$row['duration_ms'];$board['rows'][]=['rank'=>$place,'name'=>'Explorer '.$row['id'],'duration_ms'=>$row['duration_ms'],'own'=>false,'note'=>''];}
 }
}
unset($board);
