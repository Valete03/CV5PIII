<?php
// Tela pública do paciente.
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Urgência | Hospital de Mavalane</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<header class="public-header">
    <div class="brand">
        <div class="brand-icon"><i class="fa-solid fa-hospital"></i></div>
        <div>
            <strong>HOSPITAL DE MAVALANE</strong>
            <span>Serviço de Urgência</span>
        </div>
    </div>
    <a href="login.php" class="professional-link">
        <i class="fa-solid fa-user-shield"></i> Área dos Profissionais
    </a>
</header>

<main class="public-main">
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow">ATENDIMENTO DE URGÊNCIA</span>
            <h1>Precisa de atendimento?<br><span>Comece aqui.</span></h1>
            <p>Registe a sua chegada de forma simples. Não é necessário criar conta ou iniciar sessão.</p>
         <div class="hero-actions">

    <button class="primary-btn" onclick="abrirModal()">
        <i class="fa-solid fa-plus"></i>
        Iniciar Atendimento
    </button>

    <a href="consultar_atendimento.php" class="secondary-btn">
        <i class="fa-solid fa-magnifying-glass"></i>
        Consultar Triagem
    </a>

</div>
        </div>
        <div class="hero-panel">
            <div class="shield"><i class="fa-solid fa-heart-pulse"></i></div>
            <h3>Serviço de Urgência</h3>
            <p>Disponível para receção e triagem dos pacientes.</p>
            <div class="open-status"><span></span> Atendimento ativo</div>
        </div>
    </section>

    <section class="how">
        <div class="section-heading">
            <span class="eyebrow">PROCESSO</span>
            <h2>Como funciona?</h2>
        </div>
        <div class="steps">
            <div class="step"><b>01</b><i class="fa-solid fa-clipboard-list"></i><h3>Registe os dados</h3><p>Preencha os seus dados e explique o motivo da visita.</p></div>
            <div class="step"><b>02</b><i class="fa-solid fa-user-nurse"></i><h3>Faça a triagem</h3><p>A equipa de enfermagem avalia a prioridade clínica.</p></div>
            <div class="step"><b>03</b><i class="fa-solid fa-user-doctor"></i><h3>Receba atendimento</h3><p>O paciente é encaminhado conforme a prioridade.</p></div>
        </div>
    </section>
</main>

<footer class="public-footer">
    <span>© Hospital de Mavalane</span>
    <span>Serviço de Urgência</span>
</footer>

<div id="modal" class="modal">
    <div class="modal-card">
        <button class="close-modal" onclick="fecharModal()">&times;</button>
        <span class="eyebrow">NOVA CHEGADA</span>
        <h2>Iniciar Atendimento</h2>
        <p class="muted">Preencha os dados abaixo. A prioridade será definida pela enfermagem.</p>

        <form action="processar_atendimento.php" method="POST">
            <div class="form-grid">
                <label>Nome completo
                    <input type="text" name="nome" required maxlength="150">
                </label>
                <label>Nº de identificação
                    <input type="text" name="documento" required maxlength="50">
                </label>
                <label>Contacto
                    <input type="text" name="contacto" required maxlength="30">
                </label>
                <label>Data de nascimento
                    <input type="date" name="data_nascimento" required>
                </label>
                <label>Sexo
                    <select name="sexo" required>
                        <option value="">Selecione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Feminino">Feminino</option>
                    </select>
                </label>
                <label class="full">Motivo da visita
                    <textarea name="motivo" rows="4" required maxlength="1000" placeholder="Descreva brevemente o que aconteceu..."></textarea>
                </label>
            </div>
            <label class="check"><input type="checkbox" name="terms" required> Confirmo que os dados fornecidos são verdadeiros.</label>
            <button class="primary-btn full-btn" type="submit"><i class="fa-solid fa-arrow-right"></i> Continuar Atendimento</button>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById("modal");
function abrirModal(){ modal.classList.add("show"); document.body.style.overflow="hidden"; }
function fecharModal(){ modal.classList.remove("show"); document.body.style.overflow=""; }
window.addEventListener("click", e => { if(e.target === modal) fecharModal(); });
window.addEventListener("keydown", e => { if(e.key === "Escape") fecharModal(); });
</script>
</body>
</html>
