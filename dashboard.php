<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');
$site_title = get_setting('site_title', 'TanMan Plattform');

$stmt = $pdo->prepare('SELECT widget FROM user_widgets WHERE user_id = ? ORDER BY position');
$stmt->execute([$user['id']]);
$widgets = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!$widgets) {
    $widgets = $pdo->query('SELECT widget FROM dashboard_widgets ORDER BY position')->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?> - <?php echo t('dashboard_menu'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<?php $img = $pdo->prepare('SELECT profile_image FROM users WHERE id=?'); $img->execute([$user['id']]); $avatar=$img->fetchColumn(); ?>
<p><?php if ($avatar): ?><img src="uploads/profile/<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt=""> <?php endif; ?><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<?php foreach ($widgets as $w): ?>
<section class="widget" data-widget="<?php echo htmlspecialchars($w, ENT_QUOTES, 'UTF-8'); ?>">Loading...</section>
<?php endforeach; ?>
<nav>
<ul>
    <li><a href="dashboard_documents.php"><?php echo t('documents'); ?></a></li>
    <li><a href="dashboard_inbox.php"><?php echo t('messages'); ?></a></li>
    <li><a href="dashboard_profile.php"><?php echo t('profile'); ?></a></li>
    <li><a href="dashboard_requests.php"><?php echo t('self_service'); ?></a></li>
    <li><a href="dashboard_privacy.php">Datenschutz</a></li>
    <li><a href="dashboard_faq.php"><?php echo t('faq'); ?></a></li>
    <li><a href="dashboard_downloads.php"><?php echo t('downloads'); ?></a></li>
    <li><a href="dashboard_rent.php"><?php echo t('rent_costs'); ?></a></li>
    <li><a href="dashboard_inventory.php"><?php echo t('inventory'); ?></a></li>
    <li><a href="dashboard_damage.php"><?php echo t('damage_reports'); ?></a></li>
    <li><a href="dashboard_repairs.php"><?php echo t('small_repairs'); ?></a></li>
    <li><a href="dashboard_keys.php"><?php echo t('keys'); ?></a></li>
    <li><a href="dashboard_supply.php"><?php echo t('supply_contracts'); ?></a></li>
    <li><a href="dashboard_therme.php"><?php echo t('therme_service'); ?></a></li>
    <li><a href="dashboard_usage.php"><?php echo t('usage_change'); ?></a></li>
    <li><a href="dashboard_replacements.php"><?php echo t('replacement_candidates'); ?></a></li>
    <li><a href="dashboard_calendar.php"><?php echo t('calendar'); ?></a></li>
    <li><a href="dashboard_moveout.php"><?php echo t('moveout'); ?></a></li>
    <li><a href="dashboard_layout.php"><?php echo t('dashboard_layout'); ?></a></li>
</ul>
</nav>
</body>
</html>
