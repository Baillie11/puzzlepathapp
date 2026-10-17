<?php
require __DIR__.'/../quest-progress.php';
function validateInput($v,$type){ return is_string($v)&&strlen($v)>3?$v:false; }
function check($ok,$message){ if(!$ok)throw new RuntimeException($message); }
class Rows {
    private $row; function __construct($row){$this->row=$row;}
    function fetch_assoc(){return $this->row;} function fetch_row(){return $this->row;}
}
class Statement {
    private $db; private $sql; private $values=[];
    function __construct($db,$sql){$this->db=$db;$this->sql=$sql;}
    function bind_param($types,&...$values){$this->values=$values;}
    function execute(){
        $s=$this->sql;$d=$this->db;
        if(strpos($s,'INSERT INTO wp2s_pp_booking_progress')===0){
            [$id,$status,$start,$end,$duration]=$this->values;
            $d->progress=['booking_id'=>$id,'quest_status'=>$status,'started_at'=>$start,'completed_at'=>$end,'duration_ms'=>$duration];
        }
        if(strpos($s,'INSERT IGNORE INTO wp2s_pp_redemptions')===0){if($d->failRedemption)throw new RuntimeException('Simulated database failure');$d->redemptions=1;}
        if(strpos($s,'INSERT INTO pp_quest_tracking')===0){$d->tracking=['id'=>44];$d->insert_id=44;}
        return true;
    }
    function get_result(){
        $s=$this->sql;$d=$this->db;
        if(strpos($s,'SELECT ENGINE')===0)return new Rows(['InnoDB']);
        if(strpos($s,'SELECT id, event_id')===0)return new Rows($d->booking);
        if(strpos($s,'SELECT * FROM wp2s_pp_booking_progress')===0)return new Rows($d->progress);
        if(strpos($s,'SELECT COUNT(*) AS total')===0)return new Rows(['total'=>3,'answered'=>$d->answered]);
        if(strpos($s,'SELECT id FROM pp_quest_tracking')===0)return new Rows($d->tracking);
        throw new RuntimeException('Unexpected SQL: '.$s);
    }
    function close(){}
}
class Database {
    public $booking=['id'=>7,'event_id'=>9,'payment_status'=>'paid']; public $progress=null;public $tracking=null;public $answered=3;public $redemptions=0;public $insert_id=0;public $failRedemption=false;public $snapshot;public $commits=0;
    function query($sql){return true;}function prepare($sql){return new Statement($this,$sql);}
    function begin_transaction(){$this->snapshot=[$this->progress,$this->redemptions,$this->tracking];return true;}
    function commit(){$this->commits++;return true;}
    function rollback(){[$this->progress,$this->redemptions,$this->tracking]=$this->snapshot;return true;}
}
$data=['bookingCode'=>'FIXTURE-BOOKING','huntId'=>9,'startTime'=>gmdate('c',time()-3600),'endTime'=>gmdate('c'),'totalTime'=>3600000];
$db=new Database;
$r=puzzlepathRecordQuestProgress($db,$data,false);check($r['quest_status']==='in_progress'&&$db->redemptions===0,'Start failed');
$r=puzzlepathRecordQuestProgress($db,$data,true);check($r['quest_status']==='completed'&&$db->redemptions===1,'Completion not atomic');
$first=$db->progress;$r=puzzlepathRecordQuestProgress($db,$data,true);check($db->progress===$first,'Retry overwrote completion');
unset($_SESSION['pp_completion_booking']);
try{puzzlepathRecordQuestProgress($db,$data,true);throw new Exception('Used booking code alone granted dashboard retry');}catch(RuntimeException $e){}
foreach(['unpaid','wrong_quest','missing_answers','db_failure'] as $case){
 $db=new Database;
 if($case==='unpaid')$db->booking['payment_status']='unpaid';
 if($case==='wrong_quest')$db->booking['event_id']=10;
 if($case==='missing_answers')$db->answered=2;
 if($case==='db_failure')$db->failRedemption=true;
 try{puzzlepathRecordQuestProgress($db,$data,true);throw new Exception('Accepted '.$case);}catch(RuntimeException $e){}
 check($db->progress===null&&$db->redemptions===0&&$db->commits===0,'Partial completion persisted: '.$case);
}
$bad=$data;$bad['totalTime']=1;
try{puzzlepathProgressTimes($bad,true);throw new Exception('Bad duration accepted');}catch(RuntimeException $e){}
$ahead=$data;$ahead['startTime']=gmdate('c',time()+5);
check(puzzlepathProgressTimes($ahead,false)[0]===gmdate('Y-m-d H:i:s',strtotime($ahead['startTime'])),'Small client clock skew rejected at start');
try{puzzlepathProgressTimes($ahead,true);throw new Exception('Finish before start accepted');}catch(RuntimeException $e){}
echo "PASS: quest start, verified completion, duplicate retry, booking eligibility, clue evidence, rollback and timing.\n";
