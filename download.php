<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();
$pdo = get_db();
if (isset($_GET['template_id'])) {
    $id = (int)$_GET['template_id'];
    $stmt = $pdo->prepare('SELECT filename FROM templates WHERE id = ?');
    $stmt->execute([$id]);
    $tpl = $stmt->fetch();
    if (!$tpl) {
        http_response_code(404);
        exit('Vorlage nicht gefunden');
    }
    $path = __DIR__ . '/templates/' . $tpl['filename'];
    $name = $tpl['filename'];
} elseif (isset($_GET['candidate_file'])) {
    $id = (int)$_GET['candidate_file'];
    $stmt = $pdo->prepare('SELECT rc.tenant_id, f.filename, f.candidate_id FROM replacement_files f JOIN replacement_candidates rc ON f.candidate_id=rc.id WHERE f.id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || ($user['role'] !== 'admin' && $row['tenant_id'] != $user['id'])) { http_response_code(404); exit('Datei nicht gefunden'); }
    $path = __DIR__ . '/uploads/replacements/' . $row['candidate_id'] . '/' . $row['filename'];
    $name = $row['filename'];
} else {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT filename FROM documents WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    $doc = $stmt->fetch();
    if (!$doc) {
        http_response_code(404);
        exit('Dokument nicht gefunden');
    }
    $path = __DIR__ . '/uploads/' . $user['id'] . '/' . $doc['filename'];
    $name = $doc['filename'];
}
if (!is_file($path)) {
    http_response_code(404);
    exit('Datei fehlt');
}
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($name) . '"');
readfile($path);
?>
