<?php
// ⚙️ Configuración de Supabase
define('SUPABASE_URL', 'https://TU-PROYECTO-AQUI.supabase.co');
define('SUPABASE_KEY', 'TU-ANON-KEY-AQUI');
define('SUPABASE_SERVICE_ROLE_KEY', 'TU-SERVICE-ROLE-KEY-AQUI');

// 🔧 Función para hacer peticiones REST a Supabase
function supabaseRequest($endpoint, $method = "GET", $data = null) {
    $curl = curl_init();

    $headers = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/json"
    ];

    $url = SUPABASE_URL . "/rest/v1/" . $endpoint;

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers
    ];

    if ($data !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    // 🔍 Depuración temporal (comenta esto en producción)
    if ($error) {
        echo "❌ cURL error: " . $error . "<br>";
    }

    return [
        "status" => $httpcode,
        "body" => json_decode($response, true),
        "raw" => $response // Devuelve también el texto bruto por si hay errores de JSON
    ];
}
