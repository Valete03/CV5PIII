<?php
require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php"); exit;
}

$nome=trim($_POST["nome"]??"");
$documento=trim($_POST["documento"]??"");
$contacto=trim($_POST["contacto"]??"");
$data=trim($_POST["data_nascimento"]??"");
$sexo=trim($_POST["sexo"]??"");
$motivo=trim($_POST["motivo"]??"");

if(!$nome || !$documento || !$contacto || !$data || !$sexo || !$motivo){
    die("Todos os campos são obrigatórios. <a href='index.php'>Voltar</a>");
}
if(!in_array($sexo,["Masculino","Feminino"],true)){
    die("Sexo inválido. <a href='index.php'>Voltar</a>");
}

$stmt=$conn->prepare("SELECT id,nome,contacto,data_nascimento,sexo FROM pacientes WHERE documento=? LIMIT 1");
$stmt->bind_param("s",$documento); $stmt->execute();
$res=$stmt->get_result();

if($res->num_rows>0){
    $p=$res->fetch_assoc();
    $paciente_id=$p["id"];
    $stmt=$conn->prepare("UPDATE pacientes SET nome=?,contacto=?,data_nascimento=?,sexo=? WHERE id=?");
    $stmt->bind_param("ssssi",$nome,$contacto,$data,$sexo,$paciente_id);
    $stmt->execute();
}else{
    $stmt=$conn->prepare("INSERT INTO pacientes(nome,documento,contacto,data_nascimento,sexo) VALUES(?,?,?,?,?)");
    $stmt->bind_param("sssss",$nome,$documento,$contacto,$data,$sexo);
    if(!$stmt->execute()) die("Erro ao registar paciente: ".$stmt->error);
    $paciente_id=$conn->insert_id;
}

do{
    $numero="A-".date("Ymd")."-".random_int(100,999);
    $stmt=$conn->prepare("SELECT id FROM atendimentos WHERE numero_atendimento=? LIMIT 1");
    $stmt->bind_param("s",$numero); $stmt->execute();
    $existe=$stmt->get_result()->num_rows>0;
}while($existe);

$stmt=$conn->prepare("INSERT INTO atendimentos(paciente_id,numero_atendimento,motivo) VALUES(?,?,?)");
$stmt->bind_param("iss",$paciente_id,$numero,$motivo);

if(!$stmt->execute()){
    die("Erro ao criar atendimento: ".$stmt->error);
}

header("Location: atendimento_criado.php?numero=".urlencode($numero));
exit;
?>
