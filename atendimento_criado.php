<?php
require_once "config/database.php";
$numero=trim($_GET["numero"]??"");
if(!$numero){header("Location: index.php");exit;}

$stmt=$conn->prepare("SELECT a.numero_atendimento,p.nome,a.estado,a.prioridade FROM atendimentos a JOIN pacientes p ON p.id=a.paciente_id WHERE a.numero_atendimento=? LIMIT 1");
$stmt->bind_param("s",$numero);$stmt->execute();$res=$stmt->get_result();
if($res->num_rows!==1){header("Location: index.php");exit;}
$d=$res->fetch_assoc();
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Atendimento Registado</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"></head>
<body><main class="success-page"><div class="success-card">
<div class="success-icon"><i class="fa-solid fa-check"></i></div>
<span class="eyebrow">HOSPITAL DE MAVALANE</span><h1>Atendimento registado!</h1>
<p class="muted">Olá, <b><?=htmlspecialchars($d["nome"])?></b>. O seu atendimento foi registado com sucesso.</p>
<div class="attendance-number-box"><span>NÚMERO DO ATENDIMENTO</span><strong><?=htmlspecialchars($d["numero_atendimento"])?></strong></div>
<div class="waiting-message"><i class="fa-solid fa-user-nurse"></i><div><b>Aguarde pela triagem</b><p>A prioridade será definida pela equipa de enfermagem. Guarde o seu número de atendimento.</p></div></div>
<div class="important-message"><i class="fa-solid fa-circle-info"></i><p>Se o seu estado se agravar, informe imediatamente um profissional de saúde.</p></div>
<a href="index.php" class="back-home"><i class="fa-solid fa-house"></i> Voltar ao início</a>
</div></main></body></html>
