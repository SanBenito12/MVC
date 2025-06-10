<?php
require_once __DIR__ . '/../controladores/CursosController.php';

$cursosCtrl = new CursosController();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_cliente = $_GET['id_cliente'] ?? '';
    $llave_secreta = $_GET['llave_secreta'] ?? '';

    echo json_encode($cursosCtrl->obtenerCursosPorCliente($id_cliente, $llave_secreta));
} else {
    echo json_encode(["error" => "Método no permitido"]);
}
