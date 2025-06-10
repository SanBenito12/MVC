<?php
require_once 'includes/db.php';

session_start();

$mensaje = "";
$cursos = [];
$editando = false;
$curso_editar = null;

// Si no hay sesión, redirige al login
if (!isset($_SESSION['id_cliente']) || !isset($_SESSION['llave_secreta'])) {
    header("Location: login.php");
    exit;
}

// Validar credenciales y obtener ID real del cliente
$cred = supabaseRequest("clientes?select=id&id_cliente=eq." . urlencode($_SESSION['id_cliente']) . "&llave_secreta=eq." . urlencode($_SESSION['llave_secreta']), "GET");

if ($cred["status"] !== 200 || count($cred["body"]) === 0) {
    $mensaje = "Credenciales inválidas.";
} else {
    $id_creador = $cred["body"][0]['id'];

    // Eliminar curso
    if (isset($_POST['eliminar_id'])) {
        $curso_id = $_POST['eliminar_id'];
        $res = supabaseRequest("cursos?id=eq." . $curso_id, "DELETE");
        if ($res["status"] === 204) {
            $mensaje = "Curso eliminado correctamente 🗑️";
        } else {
            $mensaje = "Error al eliminar curso";
        }
    }
    // Actualizar curso existente
    elseif (isset($_POST['editar_id'])) {
        $id = $_POST['editar_id'];
        $data = [
            "titulo" => $_POST['titulo'],
            "descripcion" => $_POST['descripcion'],
            "imagen" => $_POST['imagen'],
            "precio" => floatval($_POST['precio']),
            "instructor" => $_POST['instructor'],
            "updated_at" => date("c")
        ];
        $res = supabaseRequest("cursos?id=eq." . $id, "PATCH", $data);
        if ($res["status"] === 204) {
            $mensaje = "Curso actualizado correctamente ✏️";
        } else {
            $mensaje = "Error al actualizar curso";
        }
    }
    // Crear nuevo curso
    elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        $instructor = $_POST['instructor'];
        $precio = floatval($_POST['precio']);
        $imagenURL = "";

        // Verificar si se subió un archivo de imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            
            // Validaciones adicionales
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
            $tipoArchivo = $_FILES['imagen']['type'];
            $tamanoArchivo = $_FILES['imagen']['size'];
            
            if (!in_array($tipoArchivo, $tiposPermitidos)) {
                $mensaje = "Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, GIF, WebP).";
            } elseif ($tamanoArchivo > 5000000) { // 5MB
                $mensaje = "El archivo es demasiado grande. Máximo 5MB.";
            } else {
                // Proceder con la subida
                $nombreArchivo = uniqid() . "_" . basename($_FILES['imagen']['name']);
                $rutaTemporal = $_FILES['imagen']['tmp_name'];
                $bucket = "cursos";

                $imagenBinaria = file_get_contents($rutaTemporal);
                
                // Función mejorada para subir imagen
                function subirImagenSupabase($bucket, $nombreArchivo, $imagenBinaria, $tipoArchivo) {
                    $upload = curl_init();

                    curl_setopt_array($upload, [
                        CURLOPT_URL => SUPABASE_URL . "/storage/v1/object/$bucket/$nombreArchivo",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => $imagenBinaria,
                        CURLOPT_HTTPHEADER => [
                            "Authorization: Bearer " . SUPABASE_SERVICE_ROLE_KEY, // Usar Service Role Key
                            "Content-Type: $tipoArchivo",
                            "x-upsert: true"
                        ],
                        CURLOPT_VERBOSE => true
                    ]);

                    $uploadResult = curl_exec($upload);
                    $uploadStatus = curl_getinfo($upload, CURLINFO_HTTP_CODE);
                    curl_close($upload);
                    
                    return [
                        'status' => $uploadStatus,
                        'result' => $uploadResult
                    ];
                }

                $uploadResponse = subirImagenSupabase($bucket, $nombreArchivo, $imagenBinaria, $tipoArchivo);
                
                if ($uploadResponse['status'] === 200 || $uploadResponse['status'] === 201) {
                    // URL pública de la imagen
                    $imagenURL = SUPABASE_URL . "/storage/v1/object/public/$bucket/$nombreArchivo";
                } else {
                    $mensaje = "Error al subir imagen a Supabase: " . $uploadResponse['status'] . " - " . $uploadResponse['result'];
                }
            }
        } else {
            // Determinar el tipo de error específico
            if (isset($_FILES['imagen'])) {
                switch ($_FILES['imagen']['error']) {
                    case UPLOAD_ERR_NO_FILE:
                        $mensaje = "Error: No se seleccionó ninguna imagen.";
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $mensaje = "Error: El archivo es demasiado grande.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $mensaje = "Error: El archivo se subió parcialmente.";
                        break;
                    default:
                        $mensaje = "Error desconocido al subir el archivo.";
                }
            } else {
                $mensaje = "Error: No se detectó ningún archivo.";
            }
        }

        // Solo crear el curso si la imagen se subió correctamente
        if (!empty($imagenURL)) {
            $curso = [
                "titulo" => $titulo,
                "descripcion" => $descripcion,
                "imagen" => $imagenURL,
                "precio" => $precio,
                "instructor" => $instructor,
                "id_creador" => $id_creador,
                "created_at" => date("c"),
                "updated_at" => date("c")
            ];

            $res = supabaseRequest("cursos", "POST", $curso);

            if ($res["status"] === 201) {
                $mensaje = "Curso creado con imagen subida ✅";
            } else {
                $mensaje = "Error al crear curso: " . $res["raw"];
            }
        }
    }

    // Preparar formulario de edición
    if (isset($_GET['editar'])) {
        $editando = true;
        $curso_id = $_GET['editar'];
        $resCurso = supabaseRequest("cursos?select=*&id=eq." . $curso_id . "&id_creador=eq." . $id_creador, "GET");
        if ($resCurso["status"] === 200 && count($resCurso["body"]) > 0) {
            $curso_editar = $resCurso["body"][0];
        } else {
            $mensaje = "No se pudo cargar el curso para editar";
            $editando = false;
        }
    }

    // Obtener cursos del cliente
    $resCursos = supabaseRequest("cursos?select=*&id_creador=eq." . $id_creador, "GET");
    if ($resCursos["status"] === 200) {
        $cursos = $resCursos["body"];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Cursos - EduPlatform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-graduation-cap"></i>
                <?= $editando ? 'Editar Curso' : 'Dashboard de Cursos' ?>
            </h1>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Cerrar Sesión
            </a>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <?php if ($editando): ?>
                <h2><i class="fas fa-edit"></i> Editar Curso</h2>
                <form method="POST">
                    <input type="hidden" name="editar_id" value="<?= $curso_editar['id'] ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-book"></i> Título del curso</label>
                            <input type="text" name="titulo" required value="<?= htmlspecialchars($curso_editar['titulo']) ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Instructor</label>
                            <input type="text" name="instructor" required value="<?= htmlspecialchars($curso_editar['instructor']) ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-image"></i> URL de imagen</label>
                            <input type="url" name="imagen" required value="<?= htmlspecialchars($curso_editar['imagen']) ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-dollar-sign"></i> Precio</label>
                            <input type="number" step="0.01" name="precio" required value="<?= $curso_editar['precio'] ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Descripción</label>
                        <textarea name="descripcion" required><?= htmlspecialchars($curso_editar['descripcion']) ?></textarea>
                    </div>
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Actualizar Curso
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <h2><i class="fas fa-plus-circle"></i> Crear Nuevo Curso</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-book"></i> Título del curso</label>
                            <input type="text" name="titulo" required placeholder="Ej: Programación en PHP">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Instructor</label>
                            <input type="text" name="instructor" required placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-image"></i> URL de imagen</label>
                            <input type="file" name="imagen" accept="image/*" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-dollar-sign"></i> Precio</label>
                            <input type="number" step="0.01" name="precio" required placeholder="99.99">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Descripción</label>
                        <textarea name="descripcion" required placeholder="Describe tu curso aquí..."></textarea>
                    </div>
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear Curso
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if ($mensaje): ?>
            <div class="mensaje">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <!-- Courses Section -->
        <div class="courses-header">
            <h2>
                <i class="fas fa-list-alt"></i>
                Mis Cursos (<?= count($cursos) ?>)
            </h2>
        </div>

        <?php if (empty($cursos)): ?>
            <div class="empty-state">
                <i class="fas fa-graduation-cap"></i>
                <h3>¡Aún no tienes cursos!</h3>
                <p>Crea tu primer curso usando el formulario de arriba</p>
            </div>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($cursos as $curso): ?>
                    <div class="curso">
                        <form method="POST" class="curso-form">
                            <input type="hidden" name="editar_id" value="<?= $curso['id'] ?>">
                            
                            <div class="form-group">
                                <label><i class="fas fa-book"></i> Título</label>
                                <input type="text" name="titulo" value="<?= htmlspecialchars($curso['titulo']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-align-left"></i> Descripción</label>
                                <textarea name="descripcion" required><?= htmlspecialchars($curso['descripcion']) ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-user-tie"></i> Instructor</label>
                                <input type="text" name="instructor" value="<?= htmlspecialchars($curso['instructor']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-image"></i> URL de imagen</label>
                                <input type="url" name="imagen" value="<?= htmlspecialchars($curso['imagen']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign"></i> Precio</label>
                                <input type="number" step="0.01" name="precio" value="<?= $curso['precio'] ?>" required>
                            </div>

                            <div class="curso-actions">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Actualizar
                                </button>
                            </div>
                        </form>

                        <form method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este curso?')" style="margin-top: 10px;">
                            <input type="hidden" name="eliminar_id" value="<?= $curso['id'] ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>