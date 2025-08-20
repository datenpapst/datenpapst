<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Zugriff verweigert');
}
$pdo = get_db();
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/image_utils.php';
require_once __DIR__ . '/upload_utils.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_apartment'])) {
        $address = trim($_POST['address'] ?? '');
        $type = $_POST['property_type'] ?? 'apartment';
        $size = (int)($_POST['size'] ?? 0);
        $floor = (int)($_POST['floor'] ?? 0);
        $lift = isset($_POST['has_lift']) ? 1 : 0;
        $rooms = (int)($_POST['rooms'] ?? 0);
        $outdoor = trim($_POST['outdoor_space'] ?? '');
        $energy = trim($_POST['energy_info'] ?? '');
        $cellar = isset($_POST['cellar']) ? 1 : 0;
        $cellar_size = (int)($_POST['cellar_size'] ?? 0);
        $cellar_unit = trim($_POST['cellar_unit'] ?? '');
        $unit_number = trim($_POST['unit_number'] ?? '');
        $manager = trim($_POST['manager_contact'] ?? '');
        $rent = (float)($_POST['rent_base'] ?? 0);
        $cpi = (float)($_POST["cpi_base"] ?? 100);
        $cpi_th = (float)($_POST["cpi_threshold"] ?? 5);
        $cpi_index = $_POST["cpi_index"] ?? "VPI2020";
        $bank_name = trim($_POST['bank_name'] ?? '');
        $bank_iban = trim($_POST['bank_iban'] ?? '');
        $bank_bic = trim($_POST['bank_bic'] ?? '');
        $heating = isset($_POST['heating_included']) ? 1 : 0;
        $floors_total = (int)($_POST['floors_total'] ?? 0);
        $garden = isset($_POST['has_garden']) ? 1 : 0;
        $garage = isset($_POST['has_garage']) ? 1 : 0;
        $parking = trim($_POST['parking'] ?? '');
        $laundry = isset($_POST['laundry_room']) ? 1 : 0;
        $bike = isset($_POST['bike_room']) ? 1 : 0;
        $stroller = isset($_POST['stroller_room']) ? 1 : 0;
        $stmt = $pdo->prepare('INSERT INTO apartments (address, property_type, size, floor, has_lift, rooms, outdoor_space, energy_info, cellar, cellar_size, cellar_unit, unit_number, manager_contact, rent_base, cpi_base, cpi_threshold, cpi_index, bank_name, bank_iban, bank_bic, heating_included, floors_total, has_garden, has_garage, parking, laundry_room, bike_room, stroller_room) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$address, $type, $size, $floor, $lift, $rooms, $outdoor, $energy, $cellar, $cellar_size, $cellar_unit, $unit_number, $manager, $rent, $cpi, $cpi_th, $cpi_index, $bank_name, $bank_iban, $bank_bic, $heating, $floors_total, $garden, $garage, $parking, $laundry, $bike, $stroller]);
    } elseif (isset($_POST['assign'])) {
        $email = trim($_POST['email'] ?? '');
        $apartment_id = (int)($_POST['apartment_id'] ?? 0);
        $start = $_POST['start_date'] ?: date('Y-m-d');
        $end = date('Y-m-d', strtotime($start . ' +3 years'));
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u) {
            $stmt = $pdo->prepare('INSERT INTO tenant_apartment (user_id, apartment_id, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$u['id'], $apartment_id, $start, $end]);
        } else {
            $message = 'Benutzer nicht gefunden';
        }
    } elseif (isset($_POST['extend_contract'])) {
        $ta = (int)($_POST['ta_id'] ?? 0);
        $new_end = $_POST['new_end_date'] ?? '';
        if ($ta && $new_end) {
            $stmt = $pdo->prepare('UPDATE tenant_apartment SET end_date=? WHERE id=?');
            $stmt->execute([$new_end, $ta]);
        }
    } elseif (isset($_POST['upload_doc'])) {
        $apartment_id = (int)($_POST['apartment_id'] ?? 0);
        if (!empty($_FILES['doc']['name'])) {
            $dir = __DIR__ . '/uploads/apartment_docs/' . $apartment_id;
            if (!is_dir($dir)) { mkdir($dir,0770,true); }
            $name = basename($_FILES['doc']['name']);
            if (scan_file($_FILES['doc']['tmp_name'])) {
                move_uploaded_file($_FILES['doc']['tmp_name'], $dir . '/' . $name);
                $pdo->prepare('INSERT INTO documents (user_id, apartment_id, filename, visibility) VALUES (0,?,?,"admin")')->execute([$apartment_id,$name]);
            }
        }
    } elseif (isset($_POST['upload_title'])) {
        $apartment_id = (int)($_POST['apartment_id'] ?? 0);
        if (!empty($_FILES['title']['name']) && is_uploaded_file($_FILES['title']['tmp_name'])) {
            $dir = __DIR__ . '/uploads/apartment_titles/' . $apartment_id;
            list($ok,$name) = save_scaled_image($_FILES['title'], $dir);
            if ($ok) {
                $pdo->prepare('UPDATE apartments SET title_image=? WHERE id=?')->execute([$name,$apartment_id]);
            } else {
                $message = $name;
            }
        }
    } elseif (isset($_POST['update_apartment'])) {
        $apartment_id = (int)($_POST['apartment_id'] ?? 0);
        $bank_name = trim($_POST['bank_name'] ?? '');
        $bank_iban = trim($_POST['bank_iban'] ?? '');
        $bank_bic = trim($_POST['bank_bic'] ?? '');
        $rent = (float)($_POST['rent_base'] ?? 0);
        $cpi = (float)($_POST['cpi_base'] ?? 100);
        $cpi_th = (float)($_POST['cpi_threshold'] ?? 5);
        $cpi_index = $_POST['cpi_index'] ?? 'VPI2020';
        $notes = $_POST['notes'] ?? '';
        $heating = isset($_POST['heating_included']) ? 1 : 0;
        $floor = (int)($_POST['floor'] ?? 0);
        $lift = isset($_POST['has_lift']) ? 1 : 0;
        $rooms = (int)($_POST['rooms'] ?? 0);
        $outdoor = trim($_POST['outdoor_space'] ?? '');
        $energy = trim($_POST['energy_info'] ?? '');
        $cellar = isset($_POST['cellar']) ? 1 : 0;
        $cellar_size = (int)($_POST['cellar_size'] ?? 0);
        $cellar_unit = trim($_POST['cellar_unit'] ?? '');
        $unit_number = trim($_POST['unit_number'] ?? '');
        $manager = trim($_POST['manager_contact'] ?? '');
        $type = $_POST['property_type'] ?? 'apartment';
        $floors_total = (int)($_POST['floors_total'] ?? 0);
        $garden = isset($_POST['has_garden']) ? 1 : 0;
        $garage = isset($_POST['has_garage']) ? 1 : 0;
        $parking = trim($_POST['parking'] ?? '');
        $laundry = isset($_POST['laundry_room']) ? 1 : 0;
        $bike = isset($_POST['bike_room']) ? 1 : 0;
        $stroller = isset($_POST['stroller_room']) ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE apartments SET bank_name=?, bank_iban=?, bank_bic=?, rent_base=?, cpi_base=?, cpi_threshold=?, cpi_index=?, notes=?, heating_included=?, floor=?, has_lift=?, rooms=?, outdoor_space=?, energy_info=?, cellar=?, cellar_size=?, cellar_unit=?, unit_number=?, manager_contact=?, property_type=?, floors_total=?, has_garden=?, has_garage=?, parking=?, laundry_room=?, bike_room=?, stroller_room=? WHERE id=?');
        $stmt->execute([$bank_name,$bank_iban,$bank_bic,$rent,$cpi,$cpi_th,$cpi_index,$notes,$heating,$floor,$lift,$rooms,$outdoor,$energy,$cellar,$cellar_size,$cellar_unit,$unit_number,$manager,$type,$floors_total,$garden,$garage,$parking,$laundry,$bike,$stroller,$apartment_id]);
    }
}

$apartments = $pdo->query('SELECT id, address, property_type, size, floor, has_lift, rooms, outdoor_space, energy_info, cellar, cellar_size, cellar_unit, unit_number, manager_contact, bank_name, bank_iban, bank_bic, rent_base, cpi_base, cpi_threshold, cpi_index, notes, title_image, heating_included, floors_total, has_garden, has_garage, parking, laundry_room, bike_room, stroller_room FROM apartments')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Wohnungen verwalten</title>
</head>
<body>
<p>Admin <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?> | <a href="admin.php">Menü</a> | <a href="logout.php">Logout</a></p>
<h2>Wohnungen</h2>
<ul>
<?php foreach ($apartments as $a): ?>
    <li>#<?php echo (int)$a['id']; ?>: <?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo t($a['property_type']); ?>, <?php echo (int)$a['size']; ?> m², <?php echo (int)$a['rooms']; ?> Zi., Stock <?php echo (int)$a['floor']; ?><?php if($a['has_lift']) echo ', Lift'; ?>)
        <?php if ($a['property_type']==='house'): ?>
            <?php if ($a['floors_total']): ?><br><?php echo t('floors_total'); ?>: <?php echo (int)$a['floors_total']; ?><?php endif; ?>
            <?php if ($a['has_garden']): ?><br><?php echo t('garden'); ?><?php endif; ?>
            <?php if ($a['has_garage']): ?><br><?php echo t('garage'); ?><?php endif; ?>
        <?php else: ?>
            <?php if ($a['parking']): ?><br><?php echo t('parking'); ?>: <?php echo htmlspecialchars($a['parking'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            <?php $extras=[]; if($a['laundry_room']) $extras[]=t('laundry_room'); if($a['bike_room']) $extras[]=t('bike_room'); if($a['stroller_room']) $extras[]=t('stroller_room'); if($extras): ?><br><?php echo t('extra_rooms'); ?>: <?php echo implode(', ',$extras); ?><?php endif; ?>
        <?php endif; ?>
        <?php if ($a['outdoor_space']): ?><br><?php echo htmlspecialchars($a['outdoor_space']); ?><?php endif; ?>
        <?php if ($a['manager_contact']): ?><br>HV: <?php echo htmlspecialchars($a['manager_contact'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
        <?php if (!empty($a['title_image'])): ?>
            <br><img src="<?php echo 'uploads/apartment_titles/' . (int)$a['id'] . '/' . htmlspecialchars($a['title_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Titelbild" width="120">
        <?php endif; ?>
        <br><iframe width="200" height="150" style="border:0" loading="lazy" allowfullscreen src="https://maps.google.com/maps?q=<?php echo urlencode($a['address']); ?>&amp;output=embed"></iframe>
    </li>
<?php endforeach; ?>
</ul>
<h3>Neue Wohnung anlegen</h3>
<form method="post">
    <input type="hidden" name="create_apartment" value="1">
    <label>Adresse: <input type="text" name="address" required></label>
    <label><?php echo t('property_type'); ?>:
        <select name="property_type">
            <option value="apartment"><?php echo t('apartment'); ?></option>
            <option value="house"><?php echo t('house'); ?></option>
        </select>
    </label>
    <label>Größe (m²): <input type="number" name="size" min="0"></label>
    <label>Stockwerk: <input type="number" name="floor" min="0"></label>
    <label><input type="checkbox" name="has_lift" value="1"> Lift</label>
    <label>Zimmer: <input type="number" name="rooms" min="0"></label>
    <label><?php echo t('floors_total'); ?>: <input type="number" name="floors_total" min="0"></label>
    <label><input type="checkbox" name="has_garden" value="1"> <?php echo t('garden'); ?></label>
    <label><input type="checkbox" name="has_garage" value="1"> <?php echo t('garage'); ?></label>
    <label><?php echo t('parking'); ?>: <input type="text" name="parking"></label>
    <label><input type="checkbox" name="laundry_room" value="1"> <?php echo t('laundry_room'); ?></label>
    <label><input type="checkbox" name="bike_room" value="1"> <?php echo t('bike_room'); ?></label>
    <label><input type="checkbox" name="stroller_room" value="1"> <?php echo t('stroller_room'); ?></label>
    <label>Außenfläche:
        <select name="outdoor_space">
            <option value="">Keine</option>
            <option value="balcony">Balkon</option>
            <option value="terrace">Terrasse</option>
        </select>
    </label>
    <label>Energieausweis: <textarea name="energy_info"></textarea></label>
    <label><input type="checkbox" name="cellar" value="1"> Keller vorhanden</label>
    <label>Kellergröße (m²): <input type="number" name="cellar_size" min="0"></label>
    <label>Kellerabteil: <input type="text" name="cellar_unit"></label>
    <label>Top/Einheit: <input type="text" name="unit_number"></label>
    <label>Hausverwaltung Kontakt: <input type="text" name="manager_contact"></label>
    <label>Grundmiete: <input type="number" step="0.01" name="rent_base"></label>
    <label>Basis-VPI: <input type="number" step="0.01" name="cpi_base" value="100"></label>
    <label><?php echo t('cpi_threshold'); ?> (%): <input type="number" step="0.1" name="cpi_threshold" value="<?php echo htmlspecialchars(get_setting('cpi_threshold_default','5'), ENT_QUOTES, 'UTF-8'); ?>"></label>
    <label><?php echo t('cpi_index'); ?>:
        <select name="cpi_index">
            <option value="VPI2020">VPI 2020</option>
            <option value="VPI2015">VPI 2015</option>
        </select>
    </label>
    <label>Bank: <input type="text" name="bank_name"></label>
    <label>IBAN: <input type="text" name="bank_iban"></label>
    <label>BIC: <input type="text" name="bank_bic"></label>
    <label>Heizkosten über BK? <input type="checkbox" name="heating_included" value="1"></label>
    <button type="submit">Speichern</button>
</form>
<h3>Mieter zuordnen</h3>
<?php if ($message): ?><p style="color:red;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post">
    <input type="hidden" name="assign" value="1">
    <label>Mieter Email: <input type="email" name="email" required></label>
    <label>Wohnung:
        <select name="apartment_id">
        <?php foreach ($apartments as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
        </select>
    </label>
    <label>Vertragsbeginn: <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>"></label>
    <button type="submit">Zuordnen</button>
</form>

<?php
$contracts = $pdo->query('SELECT ta.id, u.email, a.address, ta.start_date, ta.end_date FROM tenant_apartment ta JOIN users u ON ta.user_id=u.id JOIN apartments a ON ta.apartment_id=a.id WHERE ta.is_active=1')->fetchAll();
if ($contracts): ?>
<h3>Aktive Mietverträge</h3>
<table border="1"><tr><th>Mieter</th><th>Wohnung</th><th>Beginn</th><th>Ende</th><th>Verlängern</th></tr>
<?php foreach ($contracts as $c): ?>
<tr>
    <td><?php echo htmlspecialchars($c['email']); ?></td>
    <td><?php echo htmlspecialchars($c['address']); ?></td>
    <td><?php echo htmlspecialchars($c['start_date']); ?></td>
    <td><?php echo htmlspecialchars($c['end_date']); ?></td>
    <td>
        <form method="post" style="display:inline">
            <input type="hidden" name="extend_contract" value="1">
            <input type="hidden" name="ta_id" value="<?php echo $c['id']; ?>">
            <input type="date" name="new_end_date" value="<?php echo htmlspecialchars($c['end_date']); ?>">
            <button>Speichern</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<h3>Dokument für Wohnung hochladen</h3>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="upload_doc" value="1">
    <label>Wohnung:
        <select name="apartment_id">
        <?php foreach ($apartments as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
        </select>
    </label>
    <input type="file" name="doc">
    <button>Upload</button>
</form>
<h3>Titelbild hochladen</h3>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="upload_title" value="1">
    <label>Wohnung:
        <select name="apartment_id">
        <?php foreach ($apartments as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
        </select>
    </label>
    <input type="file" name="title" accept="image/jpeg,image/png">
    <button>Upload</button>
</form>
<h3>Wohnungsdetails aktualisieren</h3>
<form method="post">
    <input type="hidden" name="update_apartment" value="1">
    <label>Wohnung:
        <select name="apartment_id">
        <?php foreach ($apartments as $a): ?>
            <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['address'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
        </select>
    </label>
    <label>Grundmiete: <input type="number" step="0.01" name="rent_base"></label>
    <label>Basis-VPI: <input type="number" step="0.01" name="cpi_base"></label>
    <label><?php echo t('cpi_threshold'); ?> (%): <input type="number" step="0.1" name="cpi_threshold"></label>
    <label><?php echo t('cpi_index'); ?>:
        <select name="cpi_index">
            <option value="VPI2020">VPI 2020</option>
            <option value="VPI2015">VPI 2015</option>
        </select>
    </label>
    <label>Bank: <input type="text" name="bank_name"></label>
    <label>IBAN: <input type="text" name="bank_iban"></label>
    <label>BIC: <input type="text" name="bank_bic"></label>
    <label>Heizkosten über BK? <input type="checkbox" name="heating_included" value="1"></label>
    <label><?php echo t('property_type'); ?>:
        <select name="property_type">
            <option value="apartment"><?php echo t('apartment'); ?></option>
            <option value="house"><?php echo t('house'); ?></option>
        </select>
    </label>
    <label>Stockwerk: <input type="number" name="floor" min="0"></label>
    <label><input type="checkbox" name="has_lift" value="1"> Lift</label>
    <label>Zimmer: <input type="number" name="rooms" min="0"></label>
    <label><?php echo t('floors_total'); ?>: <input type="number" name="floors_total" min="0"></label>
    <label><input type="checkbox" name="has_garden" value="1"> <?php echo t('garden'); ?></label>
    <label><input type="checkbox" name="has_garage" value="1"> <?php echo t('garage'); ?></label>
    <label><?php echo t('parking'); ?>: <input type="text" name="parking"></label>
    <label><input type="checkbox" name="laundry_room" value="1"> <?php echo t('laundry_room'); ?></label>
    <label><input type="checkbox" name="bike_room" value="1"> <?php echo t('bike_room'); ?></label>
    <label><input type="checkbox" name="stroller_room" value="1"> <?php echo t('stroller_room'); ?></label>
    <label>Außenfläche:
        <select name="outdoor_space">
            <option value="">Keine</option>
            <option value="balcony">Balkon</option>
            <option value="terrace">Terrasse</option>
        </select>
    </label>
    <label>Energieausweis: <textarea name="energy_info"></textarea></label>
    <label><input type="checkbox" name="cellar" value="1"> Keller vorhanden</label>
    <label>Kellergröße (m²): <input type="number" name="cellar_size" min="0"></label>
    <label>Kellerabteil: <input type="text" name="cellar_unit"></label>
    <label>Top/Einheit: <input type="text" name="unit_number"></label>
    <label>Hausverwaltung Kontakt: <input type="text" name="manager_contact"></label>
    <label>Notizen: <textarea name="notes"></textarea></label>
    <button type="submit">Aktualisieren</button>
</form>
</body>
</html>
