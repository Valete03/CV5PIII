<?php
session_start();
if (!empty($_SESSION["utilizador_id"])) {
    $tipo = $_SESSION["tipo"] ?? "";
    if ($tipo === "admin") { header("Location: admin/dashboard.php"); exit; }
    if ($tipo === "medico") { header("Location: medico/dashboard.php"); exit; }
    if ($tipo === "enfermeiro") { header("Location: enfermeiro/dashboard.php"); exit; }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Login Profissional</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"></head>
<body>
<main class="auth-page">
<div class="auth-card">
<div class="auth-logo"><i class="fa-solid fa-user-shield"></i></div>
<span class="eyebrow">ÁREA RESTRITA</span>
<h1>Entrar no sistema</h1><p class="muted">Acesso destinado a administradores, médicos e enfermeiros.</p>
<?php if(isset($_GET["erro"])): ?><div class="error"><i class="fa-solid fa-circle-exclamation"></i> Email, palavra-passe ou estado da conta inválido.</div><?php endif; ?>
<form action="autenticar.php" method="POST">
<label>Email<input type="email" name="email" required></label>
<label style="margin-top:15px">Palavra-passe<input type="password" name="senha" required></label>
<button class="primary-btn" type="submit">Entrar <i class="fa-solid fa-arrow-right"></i></button>
</form>
<a class="back" href="index.php"><i class="fa-solid fa-arrow-left"></i> Voltar para atendimento</a>
</div></main>
</body></html>
