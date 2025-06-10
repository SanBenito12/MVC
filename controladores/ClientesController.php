<?php
require_once __DIR__ . '/../includes/db.php';

class ClientesController
{
    // Registrar un nuevo cliente
    public function registrar($nombre, $apellido, $email)
    {
        $id_cliente = bin2hex(random_bytes(32));
        $llave_secreta = bin2hex(random_bytes(32));

        $data = [
            "nombre" => $nombre,
            "apellido" => $apellido,
            "email" => $email,
            "id_cliente" => $id_cliente,
            "llave_secreta" => $llave_secreta,
            "created_at" => date("c"),
            "updated_at" => date("c")
        ];

        $respuesta = supabaseRequest("clientes", "POST", $data);

        if ($respuesta["status"] === 201) {
            return [
                "mensaje" => "Cliente creado con éxito",
                "id_cliente" => $id_cliente,
                "llave_secreta" => $llave_secreta
            ];
        } else {
            return ["error" => "Error al registrar cliente", "detalle" => $respuesta["raw"]];
        }
    }

    // Login por email (y devuelve claves si existe)
    public function login($email)
    {
        $endpoint = "clientes?select=*&email=eq." . urlencode($email);
        $respuesta = supabaseRequest($endpoint, "GET");

        if ($respuesta["status"] === 200 && count($respuesta["body"]) > 0) {
            $cliente = $respuesta["body"][0];
            return [
                "mensaje" => "Cliente autenticado",
                "id_cliente" => $cliente["id_cliente"],
                "llave_secreta" => $cliente["llave_secreta"]
            ];
        } else {
            return ["error" => "Cliente no encontrado"];
        }
    }
}
