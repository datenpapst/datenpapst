<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/image_utils.php';
require_once __DIR__ . '/language.php';
$pdo = get_db();
$user = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $stmt = $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?');
    $stmt->execute([$phone, $user['id']]);
    if (!empty($_FILES['profile_image']['name'])) {
        [$ok, $res] = save_scaled_image($_FILES['profile_image'], __DIR__ . '/uploads/profile', 400, 400, 1048576);
        if ($ok) {
            $pdo->prepare('UPDATE users SET profile_image=? WHERE id=?')->execute([$res, $user['id']]);
        } else {
            $message = $res;
        }
    }
    if ($message === '') {
        $message = 'Gespeichert';
    }
}

$stmt = $pdo->prepare('SELECT phone, profile_image FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
$stmt = $pdo->prepare('SELECT otp_secret FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$otp = $stmt->fetch();
$profile_phone = $profile['phone'] ?? '';
$has_2fa = !empty($otp['otp_secret']);
$cstmt = $pdo->prepare('SELECT start_date, end_date FROM tenant_apartment WHERE user_id=? AND is_active=1 LIMIT 1');
$cstmt->execute([$user['id']]);
$contract = $cstmt->fetch();
if ($contract) {
    $start = $contract['start_date'];
    $end = $contract['end_date'];
    $notice = date('Y-m-d', strtotime($start . ' +12 months'));
    $moveout = date('Y-m-d', strtotime($start . ' +15 months'));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Profil</title>
</head>
<body>
<p>Eingeloggt als <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="dashboard.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Kontaktinformationen</h2>
<?php if ($message): ?><p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php $img = $profile['profile_image'] ?? ''; ?>
<?php if ($img): ?><p><img src="uploads/profile/<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt=""></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <label>Telefon: <input type="text" name="phone" value="<?php echo htmlspecialchars($profile_phone, ENT_QUOTES, 'UTF-8'); ?>"></label>
    <label>Profilbild: <input type="file" name="profile_image" accept="image/jpeg,image/png"></label>
    <button type="submit">Speichern</button>
</form>
<p>2FA Status: <?php echo $has_2fa ? 'aktiv' : 'inaktiv'; ?> - <a href="enable_2fa.php">bearbeiten</a></p>
<?php if ($contract): ?>
<h2><?php echo t('contract_info'); ?></h2>
<p><?php echo t('contract_start'); ?> <?php echo htmlspecialchars($start); ?></p>
<p><?php echo t('contract_end'); ?> <?php echo htmlspecialchars($end); ?></p>
<p><?php echo t('earliest_notice'); ?> <?php echo htmlspecialchars($notice); ?></p>
<p><?php echo t('earliest_moveout'); ?> <?php echo htmlspecialchars($moveout); ?></p>
<?php endif; ?>
</body>
</html>
