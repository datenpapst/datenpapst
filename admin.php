<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Zugriff verweigert');
}
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');
$site_title = get_setting('site_title', 'TanMan Plattform');
$pdo = get_db();
$widgets = $pdo->query('SELECT widget FROM admin_widgets ORDER BY position')->fetchAll(PDO::FETCH_COLUMN);
$last_cpi = $pdo->query('SELECT MAX(recorded_at) FROM cpi_history')->fetchColumn();
$cpi_warn = !$last_cpi || strtotime($last_cpi) < time() - 90*24*3600;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?> - <?php echo t('admin_menu'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<?php $img = $pdo->prepare('SELECT profile_image FROM users WHERE id=?'); $img->execute([$user['id']]); $avatar=$img->fetchColumn(); ?>
<p><?php if ($avatar): ?><img src="uploads/profile/<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt=""> <?php endif; ?><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<?php if ($cpi_warn): ?><p style="color:red;"><?php echo t('cpi_reminder'); ?></p><?php endif; ?>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<?php foreach ($widgets as $w): ?>
    <?php if ($w === 'stats'): ?>
    <section class="widget">
        <h2><?php echo t('widget_stats'); ?></h2>
        <p><?php echo t('apartments_manage'); ?>: <?php echo $pdo->query('SELECT COUNT(*) FROM apartments')->fetchColumn(); ?></p>
        <p><?php echo t('users'); ?>: <?php echo $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(); ?></p>
    </section>
    <?php elseif ($w === 'requests'): ?>
    <section class="widget">
        <h2><?php echo t('widget_requests'); ?></h2>
        <p><?php echo $pdo->query('SELECT COUNT(*) FROM requests')->fetchColumn(); ?> <?php echo t('requests'); ?></p>
        <p><a href="admin_requests.php"><?php echo t('manage'); ?></a></p>
    </section>
    <?php endif; ?>
<?php endforeach; ?>
<nav>
<ul>
    <li><a href="admin_apartments.php"><?php echo t('apartments_manage'); ?></a></li>
    <li><a href="admin_users.php">User verwalten</a></li>
    <li><a href="admin_messages.php"><?php echo t('messages_send'); ?></a></li>
    <li><a href="admin_announcements.php"><?php echo t('announcements_manage'); ?></a></li>
    <li><a href="admin_faq.php"><?php echo t('faq_manage'); ?></a></li>
    <li><a href="admin_downloads.php"><?php echo t('downloads_manage'); ?></a></li>
    <li><a href="admin_finances.php"><?php echo t('finances'); ?></a></li>
    <li><a href="admin_tax.php"><?php echo t('tax_summary'); ?></a></li>
    <li><a href="admin_cpi.php"><?php echo t('cpi_upload'); ?></a></li>
    <li><a href="admin_backups.php">Backups</a></li>
    <li><a href="admin_pages.php">Impressum/DSGVO</a></li>
    <li><a href="admin_requests.php">Anfragen</a></li>
    <li><a href="admin_usage.php"><?php echo t('usage_requests'); ?></a></li>
    <li><a href="admin_replacements.php"><?php echo t('replacement_candidates'); ?></a></li>
    <li><a href="admin_inventory.php">Inventar</a></li>
    <li><a href="admin_damage.php"><?php echo t('damage_reports'); ?></a></li>
    <li><a href="admin_repairs.php"><?php echo t('small_repairs'); ?></a></li>
    <li><a href="admin_keys.php"><?php echo t('keys'); ?></a></li>
    <li><a href="admin_supply.php"><?php echo t('supply_contracts'); ?></a></li>
    <li><a href="admin_therme.php"><?php echo t('therme_service'); ?></a></li>
    <li><a href="admin_moveout.php"><?php echo t('moveout_manage'); ?></a></li>
    <li><a href="admin_calendar.php"><?php echo t('calendar_manage'); ?></a></li>
    <li><a href="admin_settings.php"><?php echo t('settings'); ?></a></li>
    <li><a href="admin_mail.php"><?php echo t('mail_templates'); ?></a></li>
    <li><a href="admin_layout.php?type=tenant"><?php echo t('dashboard_layout'); ?></a></li>
    <li><a href="admin_layout.php?type=admin"><?php echo t('admin_layout'); ?></a></li>
    <li><a href="admin_translations.php"><?php echo t('translations_manage'); ?></a></li>
    <li><a href="admin_update.php"><?php echo t('updates'); ?></a></li>
</ul>
</nav>
</body>
</html>
