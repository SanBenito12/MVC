<?php
define("SUPABASE_URL", "https://gvpkeksbujfdszckpuki.supabase.co"); 
define("SUPABASE_KEY", "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imd2cGtla3NidWpmZHN6Y2twdWtpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDk1MTA0NTQsImV4cCI6MjA2NTA4NjQ1NH0.hBX14NQ1aoPCX2dZWINUTGjFrgNK_6eti0LYS4CJL5c"); 
define('SUPABASE_SERVICE_ROLE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imd2cGtla3NidWpmZHN6Y2twdWtpIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc0OTUxMDQ1NCwiZXhwIjoyMDY1MDg2NDU0fQ.rNXqhDiKveKgUdFnStIVer7QkpGNsSPwM_f9FheQKhQ'); 

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

    // // 🔍 Depuración temporal (comenta esto en producción)
    // if ($error) {
    //     echo "❌ cURL error: " . $error . "<br>";
    // }

    return [
        "status" => $httpcode,
        "body" => json_decode($response, true),
        "raw" => $response // Devuelve también el texto bruto por si hay errores de JSON
    ];
}
