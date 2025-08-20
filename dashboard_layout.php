<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
$primary = get_setting('primary_color', '#0d6efd');
$site_title = get_setting('site_title', 'TanMan Plattform');
$pdo = get_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order = explode(',', $_POST['order'] ?? '');
    $pdo->prepare('DELETE FROM user_widgets WHERE user_id=?')->execute([$user['id']]);
    $stmt = $pdo->prepare('INSERT INTO user_widgets (user_id, widget, position) VALUES (?,?,?)');
    $pos = 1;
    foreach ($order as $w) {
        if ($w !== '') {
            $stmt->execute([$user['id'], $w, $pos++]);
        }
    }
    header('Location: dashboard_layout.php');
    exit();
}
$widgets = $pdo->query('SELECT widget FROM dashboard_widgets ORDER BY position')->fetchAll(PDO::FETCH_COLUMN);
$stmt = $pdo->prepare('SELECT widget FROM user_widgets WHERE user_id=? ORDER BY position');
$stmt->execute([$user['id']]);
$personal = $stmt->fetchAll(PDO::FETCH_COLUMN);
if ($personal) { $widgets = $personal; }
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8'); ?> - <?php echo t('dashboard_layout'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;} #widgets li{cursor:move;margin:5px;padding:5px;border:1px solid #ccc;list-style:none;}</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded',function(){
    const list=document.getElementById('widgets');
    new Sortable(list,{animation:150});
    document.getElementById('layout-form').addEventListener('submit',function(){
        const order=[];list.querySelectorAll('li').forEach(li=>order.push(li.dataset.widget));
        document.getElementById('order').value=order.join(',');
    });
});
</script>
</head>
<body>
<h1><?php echo t('dashboard_layout'); ?></h1>
<form method="post" id="layout-form">
<ul id="widgets">
<?php foreach ($widgets as $w): ?>
<li data-widget="<?php echo htmlspecialchars($w, ENT_QUOTES, 'UTF-8'); ?>"><?php echo t('widget_'.$w); ?></li>
<?php endforeach; ?>
</ul>
<input type="hidden" name="order" id="order">
<button type="submit"><?php echo t('save'); ?></button>
</form>
<p><a href="dashboard.php"><?php echo t('dashboard_menu'); ?></a></p>
</body>
</html>
