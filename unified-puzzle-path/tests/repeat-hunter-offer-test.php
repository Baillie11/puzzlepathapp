<?php
require __DIR__.'/../repeat-hunter.php';
class OfferFixture {public $rows=[];public $couponWrites=0;function query($sql){return true;}}
class OfferRows {public $row;function __construct($row){$this->row=$row;}function fetch_assoc(){return $this->row;}}
class OfferStatement {public $row;function __construct($row=null){$this->row=$row;}function get_result(){return new OfferRows($this->row);}function close(){}}
function puzzlepathProgressStatement($db,$sql,$types,$values){
 if(strpos($sql,'INSERT IGNORE INTO wp2s_pp_repeat_hunter_offers')===0){[$hash,$id,$code]=$values;if(!isset($db->rows[$hash]))$db->rows[$hash]=['first_login_at'=>'2026-10-01 00:00:00','expires_at'=>gmdate('Y-m-d H:i:s',time()+3600),'coupon_code'=>$code];return new OfferStatement;}
 if(strpos($sql,'SELECT * FROM wp2s_pp_repeat_hunter_offers')===0)return new OfferStatement($db->rows[$values[0]]??null);
 if(strpos($sql,'INSERT IGNORE INTO wp2s_pp_coupons')===0){$db->couponWrites++;return new OfferStatement;}
 throw new Exception('Unexpected SQL');
}
function offerCheck($ok,$message){if(!$ok)throw new Exception($message);}
$db=new OfferFixture;$booking=['id'=>7,'customer_email'=>'fixture@example.test'];$first=ppRepeatHunterOffer($db,$booking);$booking['id']=8;$booking['customer_email']=' FIXTURE@example.test ';$again=ppRepeatHunterOffer($db,$booking);offerCheck($again===$first&&count($db->rows)===1,'Login or another booking restarted window');
$hash=hash('sha256','fixture@example.test');$db->rows[$hash]['expires_at']=gmdate('Y-m-d H:i:s',time()-1);$before=$db->couponWrites;$expired=ppRepeatHunterOffer($db,$booking);offerCheck(!$expired['active']&&$expired['coupon_code']===$first['coupon_code']&&$db->couponWrites===$before,'Expired offer renewed or coupon written');
offerCheck(ppRepeatHunterOffer($db,['id'=>7,'customer_email'=>'invalid'])===null,'Invalid customer email created offer');
echo "PASS: one fixed window per email across logins/bookings, expired offer stays expired, private coupon reuse.\n";
