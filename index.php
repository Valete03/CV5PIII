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

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<header class="public-header">

    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-hospital"></i>
        </div>

        <div>
            <strong>HOSPITAL DE MAVALANE</strong>
            <span>Serviço de Urgência</span>
        </div>

    </div>

    <a href="login.php" class="professional-link">
        <i class="fa-solid fa-user-shield"></i>
        Área dos Profissionais
    </a>

</header>


<main class="public-main">

    <section class="hero">

        <div class="hero-copy">

            <span class="eyebrow">
                ATENDIMENTO DE URGÊNCIA
            </span>

            <h1>
                Precisa de atendimento?
                <br>
                <span>Comece aqui.</span>
            </h1>

            <p>
                Registe a sua chegada de forma simples.
                Não é necessário criar conta ou iniciar sessão.
            </p>

            <div class="hero-actions">

                <button class="primary-btn" onclick="abrirModal()">
                    <i class="fa-solid fa-plus"></i>
                    Cadastrar
                </button>

                <a href="consultar_atendimento.php"
                   class="secondary-btn">

                    <i class="fa-solid fa-magnifying-glass"></i>
                    Consultar Triagem

                </a>

            </div>

        </div>


        <div class="hero-panel">

            <div class="shield">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>

            <h3>Serviço de Urgência</h3>

            <p>
                Registe os seus dados e informe o que está a sentir
                ou o que aconteceu.
            </p>

            <div class="open-status">
                <span></span>
                Atendimento activo
            </div>

        </div>

    </section>


    <section class="how">

        <div class="section-heading">

            <span class="eyebrow">
                PROCESSO
            </span>

            <h2>Como funciona?</h2>

        </div>


        <div class="steps">

            <div class="step">

                <b>01</b>

                <i class="fa-solid fa-clipboard-list"></i>

                <h3>Registe os dados</h3>

                <p>
                    Informe os seus dados e descreva o que está
                    a sentir ou o que aconteceu.
                </p>

            </div>


            <div class="step">

                <b>02</b>

                <i class="fa-solid fa-user-nurse"></i>

                <h3>Faça a triagem</h3>

                <p>
                    A equipa de enfermagem avalia a situação
                    e determina a prioridade do atendimento.
                </p>

            </div>


            <div class="step">

                <b>03</b>

                <i class="fa-solid fa-user-doctor"></i>

                <h3>Receba atendimento</h3>

                <p>
                    O paciente é encaminhado para atendimento
                    de acordo com a avaliação realizada.
                </p>

            </div>

        </div>

    </section>

</main>


<footer class="public-footer">

    <span>© Hospital de Mavalane</span>

    <span>Serviço de Urgência</span>

</footer>


<!-- MODAL -->

<div id="modal" class="modal">

    <div class="modal-card">

        <button class="close-modal"
                onclick="fecharModal()">
            &times;
        </button>


        <span class="eyebrow">
            NOVO ATENDIMENTO
        </span>

        <h2>
            Iniciar Atendimento
        </h2>

        <p class="muted">
            Preencha os seus dados e informe o que está a
            sentir ou o que aconteceu.
        </p>


        <form action="processar_atendimento.php"
              method="POST"
              id="formAtendimento">


            <!-- DADOS DO PACIENTE -->

            <div class="form-section-title">
                <i class="fa-solid fa-user"></i>
                Dados do paciente
            </div>


            <div class="form-grid">

                <label>
                    Nome completo

                    <input type="text"
                           name="nome"
                           required
                           maxlength="150"
                           placeholder="Digite o seu nome completo">
                </label>


                <label>
                    Nº de identificação

                    <input type="text"
                           name="documento"
                           required
                           maxlength="50"
                           placeholder="BI ou outro documento">
                </label>


                <label>
                    Contacto

                    <input type="text"
                           name="contacto"
                           required
                           maxlength="30"
                           placeholder="+258 8X XXX XXXX">
                </label>


                <label>
                    Data de nascimento

                    <input type="date"
                           name="data_nascimento"
                           required>
                </label>


                <label>
                    Sexo

                    <select name="sexo"
                            id="sexo"
                            required>

                        <option value="">
                            Selecione
                        </option>

                        <option value="Masculino">
                            Masculino
                        </option>

                        <option value="Feminino">
                            Feminino
                        </option>

                    </select>

                </label>

            </div>


            <!-- SITUAÇÃO -->

            <div class="form-section-title">
                <i class="fa-solid fa-notes-medical"></i>
                Situação actual
            </div>


            <div class="form-grid">


                <label>
                    O que está a sentir ou o que aconteceu?

                    <select name="tipo_situacao"
                            required>

                        <option value="">
                            Selecione uma opção
                        </option>

                        <option value="Dor">
                            Dor
                        </option>

                        <option value="Febre">
                            Febre
                        </option>

                        <option value="Dificuldade em respirar">
                            Dificuldade em respirar
                        </option>

                        <option value="Ferimento ou acidente">
                            Ferimento ou acidente
                        </option>

                        <option value="Dor abdominal">
                            Dor abdominal
                        </option>

                        <option value="Náuseas ou vómitos">
                            Náuseas ou vómitos
                        </option>

                        <option value="Desmaio">
                            Desmaio
                        </option>

                        <option value="Outro">
                            Outro
                        </option>

                    </select>

                </label>


                <label>
                    Quando começou?

                    <select name="inicio_sintomas"
                            required>

                        <option value="">
                            Selecione
                        </option>

                        <option value="Hoje">
                            Hoje
                        </option>

                        <option value="Ontem">
                            Ontem
                        </option>

                        <option value="Há alguns dias">
                            Há alguns dias
                        </option>

                        <option value="Há mais de uma semana">
                            Há mais de uma semana
                        </option>

                    </select>

                </label>


                <label class="full">

                    Descreva o que está a sentir ou o que aconteceu

                    <textarea name="sintomas"
                              rows="4"
                              required
                              maxlength="1500"
                              placeholder="Explique brevemente os sintomas ou o que aconteceu..."></textarea>

                </label>


                <label>

                    Está a sentir dor?

                    <select name="tem_dor"
                            id="tem_dor"
                            required>

                        <option value="">
                            Selecione
                        </option>

                        <option value="Sim">
                            Sim
                        </option>

                        <option value="Não">
                            Não
                        </option>

                    </select>

                </label>


                <label id="campoDor"
                       style="display:none;">

                    Intensidade da dor

                    <select name="intensidade_dor">

                        <option value="">
                            Selecione de 0 a 10
                        </option>

                        <option value="0">0 — Sem dor</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10 — Dor máxima</option>

                    </select>

                </label>


                <label id="campoGravidez">

                    Está grávida?

                    <select name="gravidez">

                        <option value="Não aplicável">
                            Não aplicável
                        </option>

                        <option value="Sim">
                            Sim
                        </option>

                        <option value="Não">
                            Não
                        </option>

                        <option value="Não sei">
                            Não sei
                        </option>

                    </select>

                </label>

            </div>


            <div class="info-box">

                <i class="fa-solid fa-circle-info"></i>

                <span>
                    A prioridade do atendimento não é escolhida pelo
                    paciente. A avaliação é realizada pela equipa de
                    enfermagem durante a triagem.
                </span>

            </div>


            <label class="check">

                <input type="checkbox"
                       name="terms"
                       required>

                Confirmo que os dados fornecidos são verdadeiros.

            </label>


            <button class="primary-btn full-btn"
                    type="submit">

                <i class="fa-solid fa-arrow-right"></i>

                Continuar Atendimento

            </button>


        </form>

    </div>

</div>


<script>

const modal = document.getElementById("modal");

const temDor = document.getElementById("tem_dor");

const campoDor = document.getElementById("campoDor");

const sexo = document.getElementById("sexo");

const campoGravidez = document.getElementById("campoGravidez");


function abrirModal() {

    modal.classList.add("show");

    document.body.style.overflow = "hidden";

}


function fecharModal() {

    modal.classList.remove("show");

    document.body.style.overflow = "";

}


temDor.addEventListener("change", function () {

    if (this.value === "Sim") {

        campoDor.style.display = "block";

    } else {

        campoDor.style.display = "none";

        campoDor.querySelector("select").value = "";

    }

});


function atualizarGravidez() {

    if (sexo.value === "Feminino") {

        campoGravidez.style.display = "block";

    } else {

        campoGravidez.style.display = "block";

        campoGravidez.querySelector("select").value = "Não aplicável";

    }

}


sexo.addEventListener("change", atualizarGravidez);


window.addEventListener("click", function(e) {

    if (e.target === modal) {

        fecharModal();

    }

});


window.addEventListener("keydown", function(e) {

    if (e.key === "Escape") {

        fecharModal();

    }

});

</script>

</body>
</html>