<?php
/**
 * Basic file scanning helper that uses the free ClamAV engine when present.
 * Returns true if the file appears clean or no scanner is available.
 */
function scan_file(string $path): bool {
    $clamscan = trim(shell_exec('command -v clamscan'));
    if ($clamscan === '') {
        return true; // ClamAV not installed; assume clean
    }
    exec($clamscan . ' --no-summary ' . escapeshellarg($path), $output, $status);
    return $status === 0;
}

