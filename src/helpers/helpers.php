<?php
function generarCodigoMesa($len = 6) {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i=0; $i<$len; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
    return $code;
}

function euros_a_centimos($strEuros) {
    $norm = str_replace(',', '.', trim($strEuros));
    return (int) round(floatval($norm) * 100);
}

function centimos_a_euros($cents) {
    return number_format($cents / 100, 2, ',', '.') . ' €';
}

function dividir_centimos_equitable($precio_centimos, $participante_ids) {
    $n = count($participante_ids);
    if ($n <= 0) return [];
    sort($participante_ids, SORT_NUMERIC);
    $base = intdiv($precio_centimos, $n);
    $resto = $precio_centimos % $n;
    $res = [];
    foreach ($participante_ids as $idx => $pid) {
        $res[$pid] = $base + ($idx < $resto ? 1 : 0);
    }
    return $res;
}

function generar_pin_pago($len = 4) {
    $pin = '';
    for ($i=0; $i<$len; $i++) $pin .= strval(random_int(0,9));
    if ($pin[0] === '0') { $pin[0] = strval(random_int(1,9)); }
    return $pin;
}
