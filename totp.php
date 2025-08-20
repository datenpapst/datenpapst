<?php
function generate_secret(int $length = 16): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[random_int(0, 31)];
    }
    return $secret;
}

function base32_decode(string $b32): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $buffer = 0;
    $bitsLeft = 0;
    $result = '';
    for ($i = 0, $len = strlen($b32); $i < $len; $i++) {
        $val = strpos($alphabet, $b32[$i]);
        if ($val === false) {
            continue;
        }
        $buffer = ($buffer << 5) | $val;
        $bitsLeft += 5;
        if ($bitsLeft >= 8) {
            $bitsLeft -= 8;
            $result .= chr(($buffer >> $bitsLeft) & 0xFF);
        }
    }
    return $result;
}

function get_totp(string $secret, ?int $time = null): string {
    $time = $time ?? time();
    $counter = pack('N*', 0) . pack('N*', intdiv($time, 30));
    $key = base32_decode($secret);
    $hash = hash_hmac('sha1', $counter, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
    $code = $truncated % 1000000;
    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}

function verify_totp(string $secret, string $code, int $window = 1): bool {
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(get_totp($secret, time() + $i * 30), $code)) {
            return true;
        }
    }
    return false;
}
?>
