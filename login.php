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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MVC SISTEMA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/estiloslog.css" rel="stylesheet">
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h1>
                <i class="fas fa-graduation-cap"></i>
                MvC SISTEMA
            </h1>
            <p>Accede a tus cursos</p>
        </div>

        <div class="register-link">
            <p><a href="registro.php">¿No tienes cuenta? Regístrate aquí</a></p>
        </div>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    Correo Electrónico
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    required 
                    placeholder="Ingresa tu correo electrónico"
                    autocomplete="email"
                >
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Acceder a mis cursos
            </button>
        </form>

        <?php if ($mensaje): ?>
            <div class="mensaje-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Agregar efecto de carga al enviar el formulario
        document.querySelector('.login-form').addEventListener('submit', function() {
            const container = document.querySelector('.login-container');
            container.classList.add('loading');
        });

        // Efecto de focus mejorado
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>

</body>
</html>