<?php
function read_xlsx_first_sheet(string $file): array {
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) { return []; }
    $shared = [];
    if ($data = $zip->getFromName('xl/sharedStrings.xml')) {
        $xml = simplexml_load_string($data);
        foreach ($xml->si as $si) { $shared[] = (string)$si->t; }
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (!$sheetXml) { return []; }
    $xml = simplexml_load_string($sheetXml);
    $rows = [];
    foreach ($xml->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            $r = (string)$c['r'];
            $col = preg_replace('/[0-9]/','',$r);
            $idx = column_index($col);
            $v = (string)$c->v;
            if ((string)$c['t'] === 's') { $v = $shared[(int)$v] ?? ''; }
            $cells[$idx] = $v;
        }
        ksort($cells);
        $rows[] = array_values($cells);
    }
    $zip->close();
    return $rows;
}
function column_index(string $letters): int {
    $n = 0;
    $len = strlen($letters);
    for ($i=0; $i<$len; $i++) {
        $n = $n*26 + (ord($letters[$i]) - 64);
    }
    return $n - 1;
}
?>
