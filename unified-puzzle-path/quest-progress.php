<?php
/** Shared booking progress storage. All DATETIME values are UTC. */
function puzzlepathProgressStatement($db, $sql, $types = '', $values = []) {
    $stmt = $db->prepare($sql);
    if (!$stmt) { throw new RuntimeException('Could not prepare quest progress update.'); }
    if ($types !== '') { $stmt->bind_param($types, ...$values); }
    if (!$stmt->execute()) { throw new RuntimeException('Could not save quest progress.'); }
    return $stmt;
}
function puzzlepathEnsureProgressTables($db) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS wp2s_pp_booking_progress (booking_id mediumint(9) NOT NULL, quest_status varchar(20) NOT NULL DEFAULT 'not_started', started_at datetime NULL, completed_at datetime NULL, duration_ms bigint NULL, source varchar(32) NOT NULL DEFAULT 'app', note text NULL, updated_at datetime NOT NULL, PRIMARY KEY (booking_id), KEY quest_status (quest_status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS wp2s_pp_redemptions (id INT AUTO_INCREMENT PRIMARY KEY, booking_code VARCHAR(50) UNIQUE NOT NULL, redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS pp_quest_tracking (id INT AUTO_INCREMENT PRIMARY KEY, hunt_id INT NOT NULL, booking_code VARCHAR(50) NOT NULL, start_time TIMESTAMP NULL, end_time TIMESTAMP NULL, total_time_ms BIGINT NULL, user_agent TEXT, ip_address VARCHAR(45), completion_data JSON, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_booking (booking_code), INDEX idx_hunt_completion (hunt_id, end_time), INDEX idx_start_time (start_time)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach ($queries as $sql) { if (!$db->query($sql)) { throw new RuntimeException('Could not initialise quest progress storage.'); } }
    foreach (['wp2s_pp_bookings','wp2s_pp_booking_progress','wp2s_pp_redemptions','pp_quest_tracking'] as $table) {
        $stmt = puzzlepathProgressStatement($db, 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', 's', [$table]);
        $engine = $stmt->get_result()->fetch_row()[0] ?? ''; $stmt->close();
        if (strtoupper($engine) !== 'INNODB') { throw new RuntimeException('Quest progress storage needs transactional tables. Please contact support.'); }
    }
}
function puzzlepathProgressTimes($data, $finished) {
    $start = strtotime($data['startTime'] ?? '');
    $end = $finished ? strtotime($data['endTime'] ?? '') : time();
    if (!$start || !$end || $start > time() + 300 || $end > time() + 300 || ($finished && $end < $start)) {
        throw new RuntimeException('Invalid quest timing. Your answers have been kept; please try again.');
    }
    $duration = $finished ? (int) ($data['totalTime'] ?? 0) : null;
    $maximum = defined('MAX_QUEST_TIME') ? MAX_QUEST_TIME : 14400;
    if ($finished && ($duration < 1 || $duration > $maximum * 1000 || abs(($end - $start) * 1000 - $duration) > 2000)) {
        throw new RuntimeException('Invalid quest duration. Please contact support; your answers have been kept.');
    }
    return [gmdate('Y-m-d H:i:s', $start), $finished ? gmdate('Y-m-d H:i:s', $end) : null, $duration];
}
function puzzlepathRecordQuestProgress($db, $data, $finished) {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
        ini_set('session.use_strict_mode','1');
        session_start();
    }
    $code = validateInput($data['bookingCode'] ?? '', 'booking_number');
    $hunt = (int) ($data['huntId'] ?? 0);
    if (!$code || !$hunt) { throw new RuntimeException('A valid booking and quest are required.'); }
    [$started, $ended, $duration] = puzzlepathProgressTimes($data, $finished);
    puzzlepathEnsureProgressTables($db);
    if (!$db->begin_transaction()) { throw new RuntimeException('Could not start quest save.'); }
    try {
        $stmt = puzzlepathProgressStatement($db, "SELECT id, event_id, payment_status FROM wp2s_pp_bookings WHERE booking_code = ? LIMIT 1 FOR UPDATE", 's', [$code]);
        $booking = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$booking || (int)$booking['event_id'] !== $hunt || $booking['payment_status'] !== 'paid') {
            throw new RuntimeException('This booking is not valid for this quest.');
        }
        $id = (int)$booking['id'];
        $stmt = puzzlepathProgressStatement($db, 'SELECT * FROM wp2s_pp_booking_progress WHERE booking_id = ?', 'i', [$id]);
        $previous = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (($previous['quest_status'] ?? '') === 'completed') {
            if ($finished && (int)($_SESSION['pp_completion_booking']??0)!==$id) throw new RuntimeException('This quest is already completed. Please use the private dashboard link in your completion email.');
            if ($finished) { puzzlepathProgressStatement($db, 'INSERT IGNORE INTO wp2s_pp_redemptions (booking_code) VALUES (?)', 's', [$code])->close(); }
            if (!$db->commit()) { throw new RuntimeException('Could not confirm saved completion.'); }
            return ['quest_status'=>'completed', 'completed_at'=>$previous['completed_at'], 'duration_ms'=>$previous['duration_ms']];
        }
        if ($finished) {
            // No-input clues need no answer; all other active clues need a correct saved answer.
            $stmt = puzzlepathProgressStatement($db, "SELECT COUNT(*) AS total, SUM(CASE WHEN c.input_type = 'none' OR EXISTS (SELECT 1 FROM wp2s_pp_user_answers a WHERE a.booking_code = ? AND a.clue_id = c.id AND a.is_correct = 1) THEN 1 ELSE 0 END) AS answered FROM wp2s_pp_clues c WHERE c.hunt_id = ? AND c.is_active = 1", 'si', [$code, $hunt]);
            $counts = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!(int)$counts['total'] || (int)$counts['total'] !== (int)$counts['answered']) { throw new RuntimeException('Please complete all required clue answers before finishing. Your progress has been kept.'); }
        }
        $status = $finished ? 'completed' : 'in_progress';
        $now = gmdate('Y-m-d H:i:s');
        puzzlepathProgressStatement($db, "INSERT INTO wp2s_pp_booking_progress (booking_id, quest_status, started_at, completed_at, duration_ms, source, updated_at) VALUES (?, ?, ?, ?, ?, 'app', ?) ON DUPLICATE KEY UPDATE quest_status=VALUES(quest_status), started_at=COALESCE(started_at, VALUES(started_at)), completed_at=VALUES(completed_at), duration_ms=VALUES(duration_ms), source='app', note=NULL, updated_at=VALUES(updated_at)", 'isssis', [$id,$status,$started,$ended,$duration,$now])->close();
        $stmt = puzzlepathProgressStatement($db, 'SELECT id FROM pp_quest_tracking WHERE booking_code = ? AND hunt_id = ? ORDER BY id DESC LIMIT 1', 'si', [$code,$hunt]);
        $tracking = $stmt->get_result()->fetch_assoc(); $stmt->close();
        // FROM_UNIXTIME writes TIMESTAMP correctly regardless of MySQL session timezone.
        $startEpoch = strtotime($started . ' UTC'); $endEpoch = $finished ? strtotime($ended . ' UTC') : null;
        if ($tracking) {
            $trackingId = (int)$tracking['id'];
            if ($finished) { puzzlepathProgressStatement($db, 'UPDATE pp_quest_tracking SET end_time=FROM_UNIXTIME(?), total_time_ms=? WHERE id=?', 'iii', [$endEpoch,$duration,$trackingId])->close(); }
        } else {
            puzzlepathProgressStatement($db, 'INSERT INTO pp_quest_tracking (hunt_id, booking_code, start_time, end_time, total_time_ms) VALUES (?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?)', 'isiii', [$hunt,$code,$startEpoch,$endEpoch,$duration])->close();
            $trackingId = $db->insert_id;
        }
        if ($finished) { puzzlepathProgressStatement($db, 'INSERT IGNORE INTO wp2s_pp_redemptions (booking_code) VALUES (?)', 's', [$code])->close(); }
        if (!$db->commit()) { throw new RuntimeException('Could not confirm quest save.'); }
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if ($finished) { $_SESSION['pp_completion_booking']=$id; unset($_SESSION['quest_tracking_id'], $_SESSION['quest_start_time']); }
        else { $_SESSION['quest_tracking_id']=$trackingId; $_SESSION['quest_start_time']=time(); }
        return ['quest_status'=>$status,'completed_at'=>$ended,'duration_ms'=>$duration];
    } catch (Throwable $e) { $db->rollback(); throw $e; }
}
