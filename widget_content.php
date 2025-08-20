<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';

$widget = $_GET['widget'] ?? '';

switch ($widget) {
case 'apartment':
    $stmt = $pdo->prepare('SELECT a.id, a.address, a.notes, a.cpi_base, a.title_image, a.floor, a.has_lift, a.rooms, a.outdoor_space, a.energy_info, a.cellar, a.cellar_size, a.cellar_unit, a.unit_number, a.manager_contact, a.property_type, a.floors_total, a.has_garden, a.has_garage, a.parking, a.laundry_room, a.bike_room, a.stroller_room FROM apartments a JOIN tenant_apartment ta ON ta.apartment_id = a.id WHERE ta.user_id = ? AND ta.is_active = 1 LIMIT 1');
    $stmt->execute([$user['id']]);
    $apartment = $stmt->fetch();
    if ($apartment) {
        $rent_notice = false;
        $op_notice = false;
        $threshold = (float)get_setting('cpi_threshold','5');
        $current_cpi = (float)get_setting('cpi_current','100');
        $increase = ($current_cpi - $apartment['cpi_base']) / $apartment['cpi_base'] * 100;
        if ($increase > $threshold) { $rent_notice = true; }
        $stmt = $pdo->prepare('SELECT amount FROM operating_costs WHERE apartment_id = ? ORDER BY effective_date DESC LIMIT 2');
        $stmt->execute([$apartment['id']]);
        $costs = $stmt->fetchAll();
        if (count($costs) >=2 && $costs[0]['amount'] > $costs[1]['amount']) { $op_notice = true; }
        echo '<h2>' . t('apartment') . '</h2>';
        echo '<p>' . htmlspecialchars($apartment['address'], ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p>' . t('floor') . ': ' . (int)$apartment['floor'];
        if ($apartment['has_lift']) { echo ' ' . t('with_lift'); }
        echo '<br>' . t('rooms') . ': ' . (int)$apartment['rooms'] . '<br>';
        if ($apartment['property_type'] === 'house') {
            if ($apartment['floors_total']) { echo t('floors_total') . ': ' . (int)$apartment['floors_total'] . '<br>'; }
            if ($apartment['has_garden']) { echo t('garden') . '<br>'; }
            if ($apartment['has_garage']) { echo t('garage') . '<br>'; }
        } else {
            if ($apartment['parking']) { echo t('parking') . ': ' . htmlspecialchars($apartment['parking'], ENT_QUOTES, 'UTF-8') . '<br>'; }
            $extras = [];
            if ($apartment['laundry_room']) $extras[] = t('laundry_room');
            if ($apartment['bike_room']) $extras[] = t('bike_room');
            if ($apartment['stroller_room']) $extras[] = t('stroller_room');
            if ($extras) { echo t('extra_rooms') . ': ' . implode(', ', $extras) . '<br>'; }
        }
        if ($apartment['outdoor_space']) { $out = t($apartment['outdoor_space']); echo t('outdoor_space') . ': ' . htmlspecialchars($out, ENT_QUOTES, 'UTF-8') . '<br>'; }
        if ($apartment['cellar']) { echo t('cellar') . ': ' . (int)$apartment['cellar_size'] . ' m² (' . htmlspecialchars($apartment['cellar_unit'], ENT_QUOTES, 'UTF-8') . ')<br>'; }
        echo t('unit_number') . ': ' . htmlspecialchars($apartment['unit_number'], ENT_QUOTES, 'UTF-8') . '<br>';
        if ($apartment['manager_contact']) { echo t('manager_contact') . ': ' . htmlspecialchars($apartment['manager_contact'], ENT_QUOTES, 'UTF-8') . '<br>'; }
        if ($apartment['energy_info']) { echo t('energy_info') . ': ' . nl2br(htmlspecialchars($apartment['energy_info'], ENT_QUOTES, 'UTF-8')) . '<br>'; }
        echo '</p>';
        if (!empty($apartment['title_image'])) {
            echo '<p><img loading="lazy" src="uploads/apartment_titles/' . (int)$apartment['id'] . '/' . htmlspecialchars($apartment['title_image'], ENT_QUOTES, 'UTF-8') . '" alt="Titelbild" width="200"></p>';
        }
        echo '<iframe width="200" height="150" style="border:0" loading="lazy" allowfullscreen src="https://maps.google.com/maps?q=' . urlencode($apartment['address']) . '&amp;output=embed"></iframe>';
        if (!empty($apartment['notes'])) {
            echo '<p>' . t('contacts') . ': ' . nl2br(htmlspecialchars($apartment['notes'], ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        if ($rent_notice) { echo '<p style="color:red;">' . t('rent_increase') . '</p>'; }
        if ($op_notice) { echo '<p style="color:red;">' . t('op_cost_increase') . '</p>'; }
    }
    break;
case 'messages':
    $stmt = $pdo->prepare('SELECT content, created_at FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
    $stmt->execute([$user['id']]);
    $messages = $stmt->fetchAll();
    echo '<h2>' . t('latest_messages') . '</h2><ul>';
    foreach ($messages as $m) {
        echo '<li>' . htmlspecialchars($m['content'], ENT_QUOTES, 'UTF-8') . ' (' . $m['created_at'] . ')</li>';
    }
    echo '</ul><p><a href="dashboard_inbox.php">' . t('view_all_messages') . '</a></p>';
    break;
case 'announcements':
    $stmt = $pdo->query("SELECT title, content FROM announcements WHERE visible_until IS NULL OR visible_until >= CURDATE() ORDER BY created_at DESC LIMIT 3");
    $ann = $stmt->fetchAll();
    echo '<h2>' . t('announcements') . '</h2><ul>';
    foreach ($ann as $a) {
        echo '<li><strong>' . htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') . '</strong>: ' . htmlspecialchars($a['content'], ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo '</ul>';
    break;
case 'moveout':
    $stmt = $pdo->prepare('SELECT id, move_out_date FROM moveouts WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$user['id']]);
    $m = $stmt->fetch();
    if ($m) {
        $days = (strtotime($m['move_out_date']) - time())/86400;
        if ($days < 0) { $days = 0; }
        echo '<h2>' . t('moveout') . '</h2>';
        echo '<p>' . t('days_until_moveout') . ' ' . (int)$days . '</p>';
        echo '<p><a href="dashboard_moveout.php">' . t('details') . '</a></p>';
    }
    break;
case 'service':
    $ap = $pdo->prepare('SELECT a.id FROM apartments a JOIN tenant_apartment ta ON ta.apartment_id=a.id WHERE ta.user_id=? AND ta.is_active=1 LIMIT 1');
    $ap->execute([$user['id']]);
    $apartment_id = $ap->fetchColumn();
    if ($apartment_id) {
        $stmt = $pdo->prepare('SELECT service_date FROM therme_services WHERE user_id=? AND apartment_id=? ORDER BY service_date DESC LIMIT 1');
        $stmt->execute([$user['id'], $apartment_id]);
        $last = $stmt->fetchColumn();
        $due = !$last || (strtotime($last) < strtotime('-1 year'));
        echo '<h2>' . t('widget_service') . '</h2>';
        if ($due) {
            echo '<p style="color:red;">' . t('service_due') . '</p>';
        } else {
            echo '<p>' . t('last_service') . ' ' . htmlspecialchars($last, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        echo '<p><a href="dashboard_therme.php">' . t('details') . '</a></p>';
    }
    break;
case 'events':
    $aptStmt = $pdo->prepare('SELECT apartment_id FROM tenant_apartment WHERE user_id=? AND is_active=1');
    $aptStmt->execute([$user['id']]);
    $aptIds = $aptStmt->fetchAll(PDO::FETCH_COLUMN);
    $params = [];
    $conditions = [];
    if ($aptIds) {
        $in = implode(',', array_fill(0, count($aptIds), '?'));
        $conditions[] = "(c.apartment_id IN ($in))";
        $params = array_merge($params, $aptIds);
    }
    $conditions[] = '(c.apartment_id IS NULL AND c.user_id IS NULL)';
    $sql = 'SELECT c.title,c.start FROM calendar_events c WHERE c.visible_to_tenants=1 AND c.start>=NOW() AND (' . implode(' OR ', $conditions) . ') ORDER BY c.start LIMIT 3';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    echo '<h2>' . t('upcoming_events') . '</h2>';
    if ($events) {
        echo '<ul>';
        foreach ($events as $e) {
            echo '<li>' . htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($e['start'], ENT_QUOTES, 'UTF-8') . ')</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>' . t('no_events') . '</p>';
    }
    echo '<p><a href="dashboard_calendar.php">' . t('view_calendar') . '</a></p>';
    break;
}
