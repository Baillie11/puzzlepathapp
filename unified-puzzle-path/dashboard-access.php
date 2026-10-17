<?php
/** Booking-scoped dashboard access. Plain access tokens are never stored. */
function ppDashboardInstall($db) {
    if (!$db->query("CREATE TABLE IF NOT EXISTS wp2s_pp_dashboard_access (token_hash char(64) NOT NULL PRIMARY KEY, booking_id mediumint(9) NOT NULL, expires_at datetime NOT NULL, created_at datetime NOT NULL, KEY booking_id (booking_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) throw new RuntimeException('Dashboard access is temporarily unavailable.');
    if (!$db->query("CREATE TABLE IF NOT EXISTS wp2s_pp_completion_emails (booking_id mediumint(9) NOT NULL PRIMARY KEY, status varchar(20) NOT NULL DEFAULT 'pending', attempts int NOT NULL DEFAULT 0, updated_at datetime NOT NULL, sent_at datetime NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) throw new RuntimeException('Completion email queue is temporarily unavailable.');
}
function ppDashboardSession() {
    if (session_status() === PHP_SESSION_ACTIVE && session_name() !== 'PPDashboard') session_write_close();
    if (session_status() === PHP_SESSION_NONE) {
        session_name('PPDashboard');
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
        ini_set('session.use_strict_mode','1');
        session_start();
    }
}
function ppDashboardGrant($bookingId) {
    ppDashboardSession(); session_regenerate_id(true);
    $_SESSION['pp_dashboard_booking']=(int)$bookingId;
    $_SESSION['pp_dashboard_expires']=time()+43200;
}
function ppDashboardTokenBooking($db,$token) {
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D',$token)) return null;
    $stmt=puzzlepathProgressStatement($db,"SELECT a.booking_id FROM wp2s_pp_dashboard_access a JOIN wp2s_pp_booking_progress p ON p.booking_id=a.booking_id JOIN wp2s_pp_bookings b ON b.id=a.booking_id WHERE a.token_hash=? AND a.expires_at>UTC_TIMESTAMP() AND p.quest_status='completed' AND b.payment_status='paid'",'s',[hash('sha256',$token)]);
    $row=$stmt->get_result()->fetch_assoc();$stmt->close();return $row?(int)$row['booking_id']:null;
}
function ppDashboardAfterCompletion($db,$code) {
    ppDashboardInstall($db);
    $stmt=puzzlepathProgressStatement($db,"SELECT b.id FROM wp2s_pp_bookings b JOIN wp2s_pp_booking_progress p ON p.booking_id=b.id WHERE b.booking_code=? AND b.payment_status='paid' AND p.quest_status='completed'",'s',[$code]);
    $row=$stmt->get_result()->fetch_assoc();$stmt->close();
    if (!$row) throw new RuntimeException('A saved completion is required for your dashboard.');
    $id=(int)$row['id'];
    if ((int)($_SESSION['pp_completion_booking']??0)!==$id) throw new RuntimeException('Please use your private dashboard email link.');
    ppDashboardGrant($id);
    puzzlepathProgressStatement($db,"INSERT IGNORE INTO wp2s_pp_completion_emails (booking_id,status,updated_at) VALUES (?,'pending',UTC_TIMESTAMP())",'i',[$id])->close();
    // This short-lived capability lets WordPress verify the completion and email its stored customer address.
    $token=bin2hex(random_bytes(32));
    puzzlepathProgressStatement($db,'INSERT INTO wp2s_pp_dashboard_access (token_hash,booking_id,expires_at,created_at) VALUES (?,?,DATE_ADD(UTC_TIMESTAMP(), INTERVAL 10 MINUTE),UTC_TIMESTAMP())','si',[hash('sha256',$token),$id])->close();
    session_write_close();
    $mailStatus='pending';
    if (function_exists('curl_init')) {
        $curl=curl_init('https://puzzlepath.com.au/wp-json/puzzlepath/v1/completion-email');
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode(['access_token'=>$token]),CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $response=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
        $result=json_decode($response?:'',true);
        if ($status===200 && isset($result['email_status'])) $mailStatus=$result['email_status'];
    }
    return ['dashboard_url'=>'dashboard.php','email_status'=>$mailStatus];
}
