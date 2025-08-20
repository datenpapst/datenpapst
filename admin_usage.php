<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
if ($u['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
require_once __DIR__ . '/language.php';
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_manager'])) {
        $id = (int)$_POST['id'];
        require_once __DIR__ . '/mail_utils.php';
        $stmt = $pdo->prepare('SELECT ur.category, ur.person_name, ur.details, u.email, ta.apartment_id FROM usage_requests ur JOIN users u ON ur.user_id=u.id LEFT JOIN tenant_apartment ta ON ta.user_id=u.id AND ta.is_active=1 WHERE ur.id=?');
        $stmt->execute([$id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $body = $row['category']." - ".$row['person_name']."\n".$row['details']."\nMieter: ".$row['email'];
            notify_manager((int)$row['apartment_id'], 'Anliegen', $body);
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $stmt = $pdo->prepare('UPDATE usage_requests SET status=? WHERE id=?');
        $stmt->execute([$status, $id]);
    }
}
$reqs = $pdo->query('SELECT ur.id, u.email, ur.category, ur.person_name, ur.details, ur.status, ur.created_at, ta.apartment_id FROM usage_requests ur JOIN users u ON ur.user_id=u.id LEFT JOIN tenant_apartment ta ON ta.user_id=u.id AND ta.is_active=1 ORDER BY ur.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title><?php echo t('usage_requests'); ?></title><link rel="stylesheet" href="style.css"></head>
<body>
<div id="burger">☰</div>
<h1><?php echo t('usage_requests'); ?></h1>
<table border="1"><tr><th><?php echo t('email'); ?></th><th><?php echo t('category'); ?></th><th><?php echo t('person_name'); ?></th><th><?php echo t('details'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('actions'); ?></th></tr>
<?php foreach ($reqs as $r): ?>
<tr><td><?php echo htmlspecialchars($r['email']); ?></td><td><?php echo t($r['category']); ?></td><td><?php echo htmlspecialchars($r['person_name']); ?></td><td><?php echo htmlspecialchars($r['details']); ?></td><td><?php echo t($r['status']); ?></td><td>
<form method="post" style="display:inline"><input type="hidden" name="id" value="<?php echo $r['id']; ?>">
<select name="status">
<option value="pending" <?php if($r['status']==='pending') echo 'selected'; ?>><?php echo t('pending'); ?></option>
<option value="approved" <?php if($r['status']==='approved') echo 'selected'; ?>><?php echo t('approved'); ?></option>
<option value="rejected" <?php if($r['status']==='rejected') echo 'selected'; ?>><?php echo t('rejected'); ?></option>
</select>
<button type="submit"><?php echo t('save'); ?></button></form>
<form method="post" style="display:inline"><input type="hidden" name="id" value="<?php echo $r['id']; ?>"><button name="send_manager"<?php if(!$r['apartment_id']) echo ' disabled'; ?>><?php echo t('send_to_manager'); ?></button></form>
</td></tr>
<?php endforeach; ?>
</table>
<p><a href="admin.php"><?php echo t('back'); ?></a></p>
</body></html>
