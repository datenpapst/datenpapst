<?php
require_once __DIR__ . '/auth.php';
require_login();
$u = current_user();
if ($u['role'] !== 'admin') { http_response_code(403); exit('forbidden'); }
require_once __DIR__ . '/language.php';
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    $stmt = $pdo->prepare('UPDATE replacement_candidates SET status=? WHERE id=?');
    $stmt->execute([$status, $id]);
}
$cands = $pdo->query('SELECT rc.id, rc.name, rc.email, rc.phone, rc.status, u.email AS tenant_email, a.address FROM replacement_candidates rc JOIN users u ON rc.tenant_id=u.id JOIN apartments a ON rc.apartment_id=a.id ORDER BY rc.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title><?php echo t('replacement_candidates'); ?></title><link rel="stylesheet" href="style.css"></head>
<body>
<div id="burger">☰</div>
<h1><?php echo t('replacement_candidates'); ?></h1>
<table border="1"><tr><th><?php echo t('apartment'); ?></th><th><?php echo t('tenant'); ?></th><th><?php echo t('candidate'); ?></th><th><?php echo t('contact'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('documents'); ?></th><th><?php echo t('actions'); ?></th></tr>
<?php foreach ($cands as $c): ?>
<?php $f = $pdo->prepare('SELECT id, title FROM replacement_files WHERE candidate_id=?'); $f->execute([$c['id']]); $files=$f->fetchAll(); ?>
<tr><td><?php echo htmlspecialchars($c['address']); ?></td><td><?php echo htmlspecialchars($c['tenant_email']); ?></td><td><?php echo htmlspecialchars($c['name']); ?></td><td><?php echo htmlspecialchars($c['email'] . ' ' . $c['phone']); ?></td><td><?php echo t($c['status']); ?></td><td>
<?php foreach ($files as $file): ?>
<a href="download.php?candidate_file=<?php echo $file['id']; ?>"><?php echo htmlspecialchars($file['title']); ?></a><br>
<?php endforeach; ?>
</td><td>
<form method="post"><input type="hidden" name="id" value="<?php echo $c['id']; ?>">
<select name="status">
<option value="pending" <?php if($c['status']==='pending') echo 'selected'; ?>><?php echo t('pending'); ?></option>
<option value="shortlist" <?php if($c['status']==='shortlist') echo 'selected'; ?>><?php echo t('shortlist'); ?></option>
<option value="viewing" <?php if($c['status']==='viewing') echo 'selected'; ?>><?php echo t('viewing'); ?></option>
<option value="rejected" <?php if($c['status']==='rejected') echo 'selected'; ?>><?php echo t('rejected'); ?></option>
<option value="accepted" <?php if($c['status']==='accepted') echo 'selected'; ?>><?php echo t('accepted'); ?></option>
</select>
<button type="submit"><?php echo t('save'); ?></button></form>
</td></tr>
<?php endforeach; ?>
</table>
<p><a href="admin.php"><?php echo t('back'); ?></a></p>
</body></html>
