<?php
require_once "config/database.php";

$nome="Administrador";
$email="admin@hospital.com";
$senha="123456";
$hash=password_hash($senha,PASSWORD_DEFAULT);

$stmt=$conn->prepare("SELECT id FROM utilizadores WHERE email=? LIMIT 1");
$stmt->bind_param("s",$email); $stmt->execute();
if($stmt->get_result()->num_rows>0){
    die("O administrador já existe. Apague este ficheiro depois de usar.");
}
$stmt=$conn->prepare("INSERT INTO utilizadores(nome,email,senha,tipo,estado) VALUES(?,?,?,'admin','ativo')");
$stmt->bind_param("sss",$nome,$email,$hash);
if($stmt->execute()){
    echo "<h2>Administrador criado com sucesso.</h2><p>Email: <b>$email</b></p><p>Palavra-passe: <b>$senha</b></p><p><b>Apague criar_admin.php agora.</b></p>";
}else echo "Erro: ".$conn->error;
?>
