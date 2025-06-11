<?php
session_start();

if (isset($_SESSION['id_cliente']) && isset($_SESSION['llave_secreta'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
