<?php
session_start(); // Iniciar sesión al principio del script
require_once 'includes/db.php';

$mensaje = "";
$cursos = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];

    // Login (como en ClientesController)
    $endpoint = "clientes?select=id,id_cliente,llave_secreta,nombre,apellido&email=eq." . urlencode($email);
    $respuesta = supabaseRequest($endpoint, "GET");

    if ($respuesta["status"] === 200 && count($respuesta["body"]) > 0) {
        $cliente = $respuesta["body"][0];
        $id_cliente = $cliente['id_cliente'];
        $llave_secreta = $cliente['llave_secreta'];

        // Obtener cursos
        $endpointCursos = "clientes?select=id&id_cliente=eq." . urlencode($id_cliente) . "&llave_secreta=eq." . urlencode($llave_secreta);
        $clienteInfo = supabaseRequest($endpointCursos, "GET");

        if ($clienteInfo["status"] === 200 && count($clienteInfo["body"]) > 0) {
            // Guardar datos en sesión
            $_SESSION['id_cliente'] = $cliente['id_cliente'];
            $_SESSION['llave_secreta'] = $cliente['llave_secreta'];
            $_SESSION['email'] = $email;
            $_SESSION['nombre'] = $cliente['nombre'];
            $_SESSION['apellido'] = $cliente['apellido'];
            $_SESSION['id_real'] = $clienteInfo["body"][0]['id'];
            
            // Redirigir al dashboard
            header("Location: dashboard.php");
            exit;

        } else {
            $mensaje = "Credenciales inválidas.";
        }
    } else {
        $mensaje = "Cliente no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login PHP</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        input, button { margin-top: 10px; padding: 8px; width: 300px; }
        .curso { margin-top: 15px; border: 1px solid #ccc; padding: 10px; }
    </style>
</head>
<body>

    <h2>Iniciar sesión</h2>
    <form method="POST">
        <input type="email" name="email" required placeholder="Correo electrónico"><br>
        <button type="submit">Ver mis cursos</button>
    </form>

    <?php if ($mensaje): ?>
        <p style="color:red;"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

</body>
</html>