<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "hospital_mavalane";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro na conexão com a base de dados: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
