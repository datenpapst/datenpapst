<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') { http_response_code(403); exit('Zugriff verweigert'); }
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/upload_utils.php';
require_once __DIR__ . '/xlsx.php';
require_once __DIR__ . '/mail_utils.php';
$pdo = get_db();
$message = '';
$primary = get_setting('primary_color', '#0d6efd');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['xls']['name'])) {
    if (scan_file($_FILES['xls']['tmp_name'])) {
        $rows = read_xlsx_first_sheet($_FILES['xls']['tmp_name']);
        if ($rows) {
            $header = $rows[0];
            $col20 = array_search('VPI 2020', $header);
            $col15 = array_search('VPI 2015', $header);
            $val20 = $val15 = null;
            for ($i = count($rows)-1; $i>0; $i--) {
                if ($col20 !== false && $val20 === null && !empty($rows[$i][$col20])) {
                    $val20 = (float)str_replace(',', '.', $rows[$i][$col20]);
                }
                if ($col15 !== false && $val15 === null && !empty($rows[$i][$col15])) {
                    $val15 = (float)str_replace(',', '.', $rows[$i][$col15]);
                }
            }
            if ($val20 !== null) { set_setting('cpi_current_VPI2020', $val20); $pdo->prepare('INSERT INTO cpi_history (index_name,value) VALUES (?,?)')->execute(['VPI2020',$val20]); }
            if ($val15 !== null) { set_setting('cpi_current_VPI2015', $val15); $pdo->prepare('INSERT INTO cpi_history (index_name,value) VALUES (?,?)')->execute(['VPI2015',$val15]); }
            $stmt = $pdo->query('SELECT a.id,a.address,a.rent_base,a.cpi_base,a.cpi_threshold,a.cpi_index,ta.user_id FROM apartments a LEFT JOIN tenant_apartment ta ON ta.apartment_id=a.id AND ta.is_active=1');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $current = ($row['cpi_index']==='VPI2015') ? $val15 : $val20;
                if (!$current) continue;
                $increase = ($current - $row['cpi_base']) / $row['cpi_base'] * 100;
                if ($increase >= $row['cpi_threshold']) {
                    $new_rent = round($row['rent_base'] * ($current/$row['cpi_base']),2);
                    $diff = $new_rent - $row['rent_base'];
                    $pdo->prepare('UPDATE apartments SET rent_base=?, cpi_base=? WHERE id=?')->execute([$new_rent,$current,$row['id']]);
                    $pdo->prepare('INSERT INTO rent_increases (apartment_id, old_rent, new_rent, increase_amount, old_cpi, new_cpi) VALUES (?,?,?,?,?,?)')->execute([$row['id'],$row['rent_base'],$new_rent,$diff,$row['cpi_base'],$current]);
                    if ($row['user_id']) {
                        $u=$pdo->prepare('SELECT email FROM users WHERE id=?');$u->execute([$row['user_id']]);$email=$u->fetchColumn();
                        queue_template_mail($email,'rent_increase',[
                            'address'=>$row['address'],
                            'old_rent'=>number_format($row['rent_base'],2,',','.'),
                            'new_rent'=>number_format($new_rent,2,',','.'),
                            'increase'=>number_format($diff,2,',','.')
                        ]);
                    }
                }
            }
            $message = 'VPI verarbeitet';
        } else { $message = 'Datei konnte nicht gelesen werden'; }
    } else { $message = 'Datei blockiert'; }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo t('cpi_upload'); ?></title>
<link rel="stylesheet" href="style.css">
<style>:root{--primary-color:<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>;}</style>
<script src="script.js" defer></script>
</head>
<body>
<div id="burger">☰</div>
<p><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="logout.php"><?php echo t('logout'); ?></a></p>
<label class="switch">
    <input type="checkbox" id="lang-toggle" <?php echo $lang === 'en' ? 'checked' : ''; ?>>
    <span class="slider lang-slider"></span>
</label>
<label class="switch">
    <input type="checkbox" id="dark-toggle">
    <span class="slider"></span>
</label>
<h1><?php echo t('cpi_upload'); ?></h1>
<?php if ($message): ?><p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
    <label><?php echo t('upload'); ?>: <input type="file" name="xls" accept=".xlsx"></label>
    <button type="submit"><?php echo t('save'); ?></button>
</form>
<nav><ul><li><a href="admin.php">Menü</a></li></ul></nav>
</body>
</html>
