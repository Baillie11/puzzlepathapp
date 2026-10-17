<?php
$source=file_get_contents(__DIR__.'/../dashboard.php');
$start=strpos($source,'function pdleaderboardRows(');$end=strpos($source,'function pddate(',$start);
eval(substr($source,$start,$end-$start));
function checkRows($ok,$message){if(!$ok)throw new Exception($message);}
$booking=['id'=>7,'customer_name'=>'Andrew Baillie','duration_ms'=>124000,'is_tester'=>1];
$rows=pdleaderboardRows([],$booking,null);checkRows(count($rows)===1&&$rows[0]['name']==='Andrew Baillie (you)'&&$rows[0]['rank']===null&&$rows[0]['note']==='Test run','Test user missing from empty leaderboard');
$leaders=[['id'=>2,'duration_ms'=>100000],['id'=>3,'duration_ms'=>100000],['id'=>4,'duration_ms'=>110000]];
$booking['is_tester']=0;$rows=pdleaderboardRows($leaders,$booking,11);checkRows(count($rows)===4&&$rows[3]['rank']===11&&$rows[3]['own'],'Own result outside top ten missing');checkRows($rows[0]['rank']===1&&$rows[1]['rank']===1&&$rows[2]['rank']===3,'Tie positions changed');
$leaders[]=['id'=>7,'duration_ms'=>124000];$rows=pdleaderboardRows($leaders,$booking,4);checkRows(count($rows)===4&&$rows[3]['name']==='Andrew Baillie (you)','Own ranked row duplicated or unnamed');checkRows($rows[0]['name']==='Explorer 2','Another explorer name exposed');
echo "PASS: own test result, empty board, outside top ten, ties, own name and no duplicate row.\n";
