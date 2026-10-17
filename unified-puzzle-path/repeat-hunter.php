<?php
/** One fixed 30-day welcome-back window per booking email. */
function ppRepeatHunterOffer($db,$booking){
    $email=strtolower(trim($booking['customer_email']??''));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))return null;
    if(!$db->query("CREATE TABLE IF NOT EXISTS wp2s_pp_repeat_hunter_offers (email_hash char(64) NOT NULL PRIMARY KEY, source_booking_id mediumint(9) NOT NULL, first_login_at datetime NOT NULL, expires_at datetime NOT NULL, coupon_code varchar(50) NOT NULL, UNIQUE KEY coupon_code (coupon_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"))throw new RuntimeException('Repeat Hunter offer unavailable.');
    $hash=hash('sha256',$email);$code='RH-'.bin2hex(random_bytes(16));
    // Existing access records preserve the initial login date for earlier dashboard users.
    puzzlepathProgressStatement($db,"INSERT IGNORE INTO wp2s_pp_repeat_hunter_offers (email_hash,source_booking_id,first_login_at,expires_at,coupon_code) SELECT ?,?,COALESCE(MIN(created_at),UTC_TIMESTAMP()),DATE_ADD(COALESCE(MIN(created_at),UTC_TIMESTAMP()),INTERVAL 30 DAY),? FROM wp2s_pp_dashboard_access WHERE booking_id=?",'sisi',[$hash,(int)$booking['id'],$code,(int)$booking['id']])->close();
    $stmt=puzzlepathProgressStatement($db,'SELECT * FROM wp2s_pp_repeat_hunter_offers WHERE email_hash=?','s',[$hash]);$offer=$stmt->get_result()->fetch_assoc();$stmt->close();
    if(!$offer)return null;
    $offer['active']=strtotime($offer['expires_at'].' UTC')>time();
    if($offer['active']){
        // Expiry and email ownership are enforced by the WordPress Repeat Hunter guards.
        puzzlepathProgressStatement($db,"INSERT IGNORE INTO wp2s_pp_coupons (code,discount_percent,max_uses,times_used,expires_at) VALUES (?,25,0,0,NULL)",'s',[$offer['coupon_code']])->close();
    }
    return $offer;
}
