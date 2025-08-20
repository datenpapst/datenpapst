<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/upload_utils.php';
$pdo = get_db();
$user = current_user();
$error='';
$apstmt = $pdo->prepare('SELECT apartment_id FROM tenant_apartment WHERE user_id=? AND is_active=1');
$apstmt->execute([$user['id']]);
$apartment_id = $apstmt->fetchColumn();
if (!$apartment_id) {
    echo 'Keine aktive Wohnung';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_candidate'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name) {
            $stmt = $pdo->prepare('INSERT INTO replacement_candidates (apartment_id, tenant_id, name, email, phone) VALUES (?,?,?,?,?)');
            $stmt->execute([$apartment_id, $user['id'], $name, $email, $phone]);
        } else {
            $error = t('fill_all_fields');
        }
    } elseif (isset($_POST['upload']) && isset($_FILES['doc'])) {
        $cid = (int)$_POST['candidate_id'];
        $c = $pdo->prepare('SELECT id FROM replacement_candidates WHERE id=? AND tenant_id=?');
        $c->execute([$cid, $user['id']]);
        if ($c->fetch()) {
            if ($_FILES['doc']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['doc']['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf','jpg','jpeg','png'];
                if (in_array($ext, $allowed, true) && scan_file($_FILES['doc']['tmp_name'])) {
                    $dir = __DIR__ . '/uploads/replacements/' . $cid;
                    if (!is_dir($dir)) { mkdir($dir,0700,true); }
                    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                    move_uploaded_file($_FILES['doc']['tmp_name'], $dir.'/'.$filename);
                    $title = trim($_POST['title'] ?? 'Dokument');
                    $pdo->prepare('INSERT INTO replacement_files (candidate_id, filename, title) VALUES (?,?,?)')->execute([$cid, $filename, $title]);
                } else {
                    $error = 'Upload fehlgeschlagen';
                }
            }
        }
    }
}
$cands = $pdo->prepare('SELECT * FROM replacement_candidates WHERE tenant_id=? ORDER BY created_at DESC');
$cands->execute([$user['id']]);
$candidates = $cands->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title><?php echo t('replacement_candidates'); ?></title></head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2><?php echo t('replacement_candidates'); ?></h2>
<?php if ($error): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<h3><?php echo t('add_candidate'); ?></h3>
<form method="post">
<input type="hidden" name="add_candidate" value="1">
<label><?php echo t('name'); ?>:<input type="text" name="name" required></label><br>
<label><?php echo t('email'); ?>:<input type="email" name="email"></label><br>
<label><?php echo t('phone'); ?>:<input type="text" name="phone"></label><br>
<button type="submit"><?php echo t('save'); ?></button>
</form>
<?php foreach ($candidates as $cand): ?>
<h4><?php echo htmlspecialchars($cand['name']); ?> (<?php echo t($cand['status']); ?>)</h4>
<p><?php echo htmlspecialchars($cand['email'] . ' ' . $cand['phone']); ?></p>
<?php $files = $pdo->prepare('SELECT id, title FROM replacement_files WHERE candidate_id=?'); $files->execute([$cand['id']]); $docs=$files->fetchAll(); ?>
<ul>
<?php foreach ($docs as $d): ?>
<li><a href="download.php?candidate_file=<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['title']); ?></a></li>
<?php endforeach; ?>
</ul>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="candidate_id" value="<?php echo $cand['id']; ?>">
<input type="hidden" name="upload" value="1">
<label><?php echo t('title'); ?>:<input type="text" name="title"></label>
<input type="file" name="doc" required>
<button type="submit"><?php echo t('upload'); ?></button>
</form>
<?php endforeach; ?>
</body></html>
