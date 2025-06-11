<?php
require_once 'includes/db.php';
session_start();

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];

    // Verificar si el correo ya existe
    $verificar = supabaseRequest("clientes?select=id&email=eq." . urlencode($email), "GET");

    if ($verificar["status"] === 200 && count($verificar["body"]) > 0) {
        $mensaje = "⚠️ Ya existe un usuario registrado con ese correo.";
    } else {
        // Registrar nuevo cliente
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

        $res = supabaseRequest("clientes", "POST", $data);

        if ($res["status"] === 201) {
            // Guardar sesión y redirigir
            $_SESSION['id_cliente'] = $id_cliente;
            $_SESSION['llave_secreta'] = $llave_secreta;
            header("Location: dashboard.php");
            exit;
        } else {
            $mensaje = "❌ Error al registrar usuario: " . $res["raw"];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - MVC SISTEMA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/estilosreg.css" rel="stylesheet">
</head>
<body>

    <div class="register-container">
        <div class="register-header">
            <h1>
                <i class="fas fa-user-plus"></i>
                MvC SISTEMA
            </h1>
            <p>Crea tu cuenta y comienza a aprender</p>
        </div>

        <form method="POST" class="register-form">
            <div class="form-group">
                <label for="nombre">
                    <i class="fas fa-user"></i>
                    Nombre
                </label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    class="form-input" 
                    required 
                    placeholder="Ingresa tu nombre"
                    autocomplete="given-name"
                >
            </div>

            <div class="form-group">
                <label for="apellido">
                    <i class="fas fa-user"></i>
                    Apellido
                </label>
                <input 
                    type="text" 
                    id="apellido" 
                    name="apellido" 
                    class="form-input" 
                    required 
                    placeholder="Ingresa tu apellido"
                    autocomplete="family-name"
                >
            </div>

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

            <button type="submit" class="register-btn">
                <i class="fas fa-user-plus"></i>
                Crear mi cuenta
            </button>
        </form>

        <div class="login-link">
            <p><a href="login.php">¿Ya tienes cuenta? Inicia sesión</a></p>
        </div>

        <?php if ($mensaje): ?>
            <div class="mensaje-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Agregar efecto de carga al enviar el formulario
        document.querySelector('.register-form').addEventListener('submit', function() {
            const container = document.querySelector('.register-container');
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