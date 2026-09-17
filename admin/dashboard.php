<?php
require_once "../config/database.php"; require_once "../config/auth.php"; exigir_tipo(["admin"]);

$pacientes=$conn->query("SELECT COUNT(*) total FROM pacientes")->fetch_assoc()["total"];
$atendimentos=$conn->query("SELECT COUNT(*) total FROM atendimentos")->fetch_assoc()["total"];
$aguardando=$conn->query("SELECT COUNT(*) total FROM atendimentos WHERE estado='Aguardando triagem'")->fetch_assoc()["total"];
$concluidos=$conn->query("SELECT COUNT(*) total FROM atendimentos WHERE estado='Concluído'")->fetch_assoc()["total"];
$recentes=$conn->query("SELECT a.*,p.nome FROM atendimentos a JOIN pacientes p ON p.id=a.paciente_id ORDER BY a.data_entrada DESC LIMIT 10");
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin | Hospital de Mavalane</title><link rel="stylesheet" href="../style.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"></head>
<body class="dashboard"><header class="topbar"><div><div class="title">Hospital de Mavalane</div><small>Painel administrativo</small></div><div><?=nome_utilizador()?> &nbsp; <a class="logout" href="../logout.php">Sair</a></div></header>
<div class="dash-wrap"><aside class="sidebar"><a class="active" href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a><a href="utilizadores.php"><i class="fa-solid fa-users"></i> Profissionais</a><a href="pacientes.php"><i class="fa-solid fa-hospital-user"></i> Pacientes</a><a href="atendimentos.php"><i class="fa-solid fa-list"></i> Atendimentos</a></aside>
<main class="content"><div class="page-title"><h1>Visão geral</h1></div>
<div class="stats"><div class="stat"><span>Pacientes</span><strong><?=$pacientes?></strong></div><div class="stat"><span>Atendimentos</span><strong><?=$atendimentos?></strong></div><div class="stat"><span>Aguardando triagem</span><strong><?=$aguardando?></strong></div><div class="stat"><span>Concluídos</span><strong><?=$concluidos?></strong></div></div>
<div class="panel"><h3>Atendimentos recentes</h3><div class="table-wrap"><table><tr><th>Número</th><th>Paciente</th><th>Prioridade</th><th>Estado</th><th>Entrada</th></tr>
<?php while($a=$recentes->fetch_assoc()): ?><tr><td><b><?=htmlspecialchars($a["numero_atendimento"])?></b></td><td><?=htmlspecialchars($a["nome"])?></td><td><span class="badge priority <?=prioridade_class($a["prioridade"])?>"><?=htmlspecialchars($a["prioridade"]??"Aguardando triagem")?></span></td><td><span class="badge status"><?=htmlspecialchars($a["estado"])?></span></td><td><?=date("d/m/Y H:i",strtotime($a["data_entrada"]))?></td></tr><?php endwhile; ?>
</table></div></div></main></div></body></html>
