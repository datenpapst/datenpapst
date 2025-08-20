<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="calendar.ics"');

$aptStmt = $pdo->prepare('SELECT apartment_id FROM tenant_apartment WHERE user_id=? AND is_active=1');
$aptStmt->execute([$user['id']]);
$apartmentIds = $aptStmt->fetchAll(PDO::FETCH_COLUMN);

$sql = 'SELECT title,start,end FROM calendar_events WHERE (user_id=? OR (visible_to_tenants=1 AND (';
$params = [$user['id']];
$parts = [];
if ($apartmentIds) {
    $in = implode(',', array_fill(0, count($apartmentIds), '?'));
    $parts[] = "apartment_id IN ($in)";
    $params = array_merge($params, $apartmentIds);
}
$parts[] = '(apartment_id IS NULL AND user_id IS NULL)';
$sql .= implode(' OR ', $parts) . ')))';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//TanMan Plattform//DE\r\n";
foreach ($events as $e) {
    $start = gmdate('Ymd\THis\Z', strtotime($e['start']));
    $end = $e['end'] ? gmdate('Ymd\THis\Z', strtotime($e['end'])) : $start;
    $uid = uniqid();
    echo "BEGIN:VEVENT\r\nUID:$uid\r\nSUMMARY:" . addslashes($e['title']) . "\r\nDTSTART:$start\r\nDTEND:$end\r\nEND:VEVENT\r\n";
}
echo "END:VCALENDAR";
?>
