<?php
require_once __DIR__ . '/../includes/db.php';

class CursosController
{
    // Validar cliente por ID y llave_secreta
    private function obtenerClientePorCredenciales($id_cliente, $llave_secreta)
    {
        $endpoint = "clientes?select=id&id_cliente=eq." . urlencode($id_cliente) . "&llave_secreta=eq." . urlencode($llave_secreta);
        $respuesta = supabaseRequest($endpoint, "GET");

        if ($respuesta["status"] === 200 && count($respuesta["body"]) > 0) {
            return $respuesta["body"][0]; // Retorna solo el campo id del cliente
        }

        return null;
    }

    // Obtener cursos del cliente autenticado
    public function obtenerCursosPorCliente($id_cliente, $llave_secreta)
    {
        $cliente = $this->obtenerClientePorCredenciales($id_cliente, $llave_secreta);

        if (!$cliente) {
            return ["error" => "Credenciales inválidas"];
        }

        $endpoint = "cursos?select=*&id_creador=eq." . $cliente["id"];
        $respuesta = supabaseRequest($endpoint, "GET");

        if ($respuesta["status"] === 200) {
            return $respuesta["body"];
        } else {
            return ["error" => "No se pudieron obtener los cursos"];
        }
    }
}
