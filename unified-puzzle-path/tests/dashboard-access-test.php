<?php
require __DIR__.'/../dashboard-access.php';
function assertDashboard($ok,$message){if(!$ok)throw new Exception($message);}
class DashboardResult {function fetch_assoc(){return ['booking_id'=>152];}}
class DashboardStatement {function get_result(){return new DashboardResult;}function close(){}}
function puzzlepathProgressStatement($db,$sql,$types,$values){
 assertDashboard(strpos($sql,"a.expires_at>UTC_TIMESTAMP()")!==false,'Expiry not enforced');
 assertDashboard(strpos($sql,"p.quest_status='completed'")!==false,'Completion not enforced');
 assertDashboard(strpos($sql,"b.payment_status='paid'")!==false,'Paid booking not enforced');
 assertDashboard($values[0]===hash('sha256',str_repeat('a',64)),'Plain token reached lookup');
 return new DashboardStatement;
}
foreach(['',str_repeat('a',63),str_repeat('x',64),['token'],'152'] as $bad)assertDashboard(ppDashboardTokenBooking(null,$bad)===null,'Malformed token accepted');
assertDashboard(ppDashboardTokenBooking(null,str_repeat('a',64))===152,'Valid token failed booking scope');
echo "PASS: dashboard tokens are hashed, validated, expiring, paid and completion scoped.\n";
