<?php
session_start();
require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php"); exit;
}

$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";

$stmt = $conn->prepare("SELECT id,nome,email,senha,tipo,estado FROM utilizadores WHERE email=? LIMIT 1");
$stmt->bind_param("s",$email);
$stmt->execute();
$resultado = $stmt->get_result();

if($resultado->num_rows !== 1){
    header("Location: login.php?erro=1"); exit;
}
$u = $resultado->fetch_assoc();

if($u["estado"] !== "ativo" || !password_verify($senha,$u["senha"])){
    header("Location: login.php?erro=1"); exit;
}

session_regenerate_id(true);
$_SESSION["utilizador_id"]=$u["id"];
$_SESSION["nome"]=$u["nome"];
$_SESSION["email"]=$u["email"];
$_SESSION["tipo"]=$u["tipo"];

if($u["tipo"]==="admin") header("Location: admin/dashboard.php");
elseif($u["tipo"]==="medico") header("Location: medico/dashboard.php");
else header("Location: enfermeiro/dashboard.php");
exit;
?>
