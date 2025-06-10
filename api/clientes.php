<?php
require_once __DIR__ . '/../controladores/ClientesController.php';

$clienteCtrl = new ClientesController();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = json_decode(file_get_contents("php://input"), true);
    $accion = $_GET['accion'] ?? '';

    if ($accion === 'registro') {
        echo json_encode($clienteCtrl->registrar($json['nombre'], $json['apellido'], $json['email']));
    } elseif ($accion === 'login') {
        echo json_encode($clienteCtrl->login($json['email']));
    } else {
        echo json_encode(["error" => "Acción no válida"]);
    }
} else {
    echo json_encode(["error" => "Método no permitido"]);
}
