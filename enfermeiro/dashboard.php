<?php

require_once "../config/database.php";
require_once "../config/auth.php";

exigir_tipo(["enfermeiro"]);


/*
|--------------------------------------------------------------------------
| PROCESSAR TRIAGEM
|--------------------------------------------------------------------------
*/

$mensagem = "";
$erro = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $atendimento_id = (int)($_POST["atendimento_id"] ?? 0);

    $prioridade = trim($_POST["prioridade"] ?? "");

    $observacao = trim($_POST["observacao_triagem"] ?? "");


    $prioridades_validas = [
        "Emergente",
        "Muito urgente",
        "Urgente",
        "Pouco urgente",
        "Não urgente"
    ];


    if ($atendimento_id <= 0) {

        $erro = "Atendimento inválido.";

    } elseif (!in_array($prioridade, $prioridades_validas, true)) {

        $erro = "Seleccione uma prioridade válida.";

    } else {

        /*
        |--------------------------------------------------------------
        | Confirmar que o atendimento ainda aguarda triagem
        |--------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            UPDATE atendimentos

            SET
                prioridade = ?,
                estado = 'Aguardando médico',
                observacao_triagem = ?,
                profissional_triagem_id = ?,
                data_triagem = NOW()

            WHERE
                id = ?
                AND estado = 'Aguardando triagem'
        ");


        $profissional_id = (int)$_SESSION["utilizador_id"];


        $stmt->bind_param(
            "ssii",
            $prioridade,
            $observacao,
            $profissional_id,
            $atendimento_id
        );


        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {

                $mensagem = "Triagem registada com sucesso.";

            } else {

                $erro = "Este atendimento já foi triado ou não está disponível.";

            }

        } else {

            $erro = "Erro ao registar a triagem.";

        }

    }

}


/*
|--------------------------------------------------------------------------
| BUSCAR ATENDIMENTOS AGUARDANDO TRIAGEM
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        a.id,
        a.numero_atendimento,
        a.motivo,
        a.tipo_situacao,
        a.sintomas,
        a.inicio_sintomas,
        a.tem_dor,
        a.intensidade_dor,
        a.gravidez,
        a.estado,
        a.data_entrada,

        p.nome,
        p.documento,
        p.contacto,
        p.data_nascimento,
        p.sexo

    FROM atendimentos a

    INNER JOIN pacientes p
        ON p.id = a.paciente_id

    WHERE a.estado = 'Aguardando triagem'

    ORDER BY a.data_entrada ASC
";


$resultado = $conn->query($sql);


/*
|--------------------------------------------------------------------------
| ESTATÍSTICAS
|--------------------------------------------------------------------------
*/

$res_aguardar = $conn->query("
    SELECT COUNT(*) AS total
    FROM atendimentos
    WHERE estado = 'Aguardando triagem'
");

$aguardando = $res_aguardar
    ? (int)$res_aguardar->fetch_assoc()["total"]
    : 0;


$res_triagem = $conn->query("
    SELECT COUNT(*) AS total
    FROM atendimentos
    WHERE estado = 'Aguardando médico'
");

$triados = $res_triagem
    ? (int)$res_triagem->fetch_assoc()["total"]
    : 0;


/*
|--------------------------------------------------------------------------
| CALCULAR IDADE
|--------------------------------------------------------------------------
*/

function calcular_idade($data_nascimento)
{
    if (!$data_nascimento) {
        return "-";
    }

    try {

        $nascimento = new DateTime($data_nascimento);

        $hoje = new DateTime();

        return $hoje->diff($nascimento)->y . " anos";

    } catch (Exception $e) {

        return "-";

    }
}


/*
|--------------------------------------------------------------------------
| CLASSE DA PRIORIDADE
|--------------------------------------------------------------------------
*/

function classe_prioridade($prioridade)
{
    $mapa = [
        "Emergente" => "emergente",
        "Muito urgente" => "muito-urgente",
        "Urgente" => "urgente",
        "Pouco urgente" => "pouco-urgente",
        "Não urgente" => "nao-urgente"
    ];

    return $mapa[$prioridade] ?? "";
}

?>

<!DOCTYPE html>

<html lang="pt">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Triagem | Hospital de Mavalane</title>


<link rel="stylesheet"
      href="../style.css">


<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


<style>

/* =========================================================
   DASHBOARD DO ENFERMEIRO
========================================================= */

.dashboard-body {
    background: #f5f7fa;
    min-height: 100vh;
}


/* HEADER */

.dashboard-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e9ef;
    padding: 18px 30px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;
}


.dashboard-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}


.dashboard-brand-icon {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #eaf4fb;
    color: #17658a;
}


.dashboard-brand strong {
    display: block;
    font-size: 15px;
    color: #183b56;
}


.dashboard-brand span {
    display: block;
    margin-top: 2px;

    color: #718096;
    font-size: 12px;
}


.user-area {
    display: flex;
    align-items: center;
    gap: 15px;
}


.user-info {
    text-align: right;
}


.user-info strong {
    display: block;
    color: #183b56;
    font-size: 13px;
}


.user-info span {
    color: #718096;
    font-size: 12px;
}


.logout-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;

    padding: 9px 13px;

    border-radius: 8px;

    background: #fff;
    border: 1px solid #dfe5eb;

    color: #4a5568;

    text-decoration: none;

    font-size: 12px;
    font-weight: 700;
}


/* CONTAINER */

.dashboard-container {
    width: min(1250px, calc(100% - 40px));
    margin: 0 auto;
    padding: 32px 0 50px;
}


.dashboard-title {
    margin-bottom: 25px;
}


.dashboard-title .eyebrow {
    color: #17658a;
}


.dashboard-title h1 {
    margin: 7px 0 5px;

    color: #183b56;

    font-size: 28px;
}


.dashboard-title p {
    margin: 0;

    color: #718096;

    font-size: 14px;
}


/* ALERTAS */

.alert {
    padding: 13px 16px;

    border-radius: 9px;

    margin-bottom: 20px;

    font-size: 13px;
    font-weight: 600;
}


.alert-success {
    background: #eaf8ef;
    color: #237a43;
    border: 1px solid #ccebd6;
}


.alert-error {
    background: #fff0f0;
    color: #a12b2b;
    border: 1px solid #f0cccc;
}


/* ESTATÍSTICAS */

.stats {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 15px;

    margin-bottom: 28px;
}


.stat-card {
    background: #fff;

    border: 1px solid #e6ebf0;

    border-radius: 12px;

    padding: 18px 20px;

    display: flex;
    align-items: center;
    gap: 15px;
}


.stat-icon {
    width: 44px;
    height: 44px;

    border-radius: 10px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #edf5fa;
    color: #17658a;
}


.stat-card strong {
    display: block;

    font-size: 24px;

    color: #183b56;
}


.stat-card span {
    color: #718096;

    font-size: 12px;
}


/* LISTA */

.section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 15px;
}


.section-title h2 {
    margin: 0;

    color: #183b56;

    font-size: 19px;
}


.section-title span {
    color: #718096;

    font-size: 12px;
}


/* CARD DO PACIENTE */

.patient-card {
    background: #fff;

    border: 1px solid #e3e8ee;

    border-radius: 14px;

    padding: 22px;

    margin-bottom: 18px;
}


.patient-top {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20px;

    padding-bottom: 18px;

    border-bottom: 1px solid #edf0f3;
}


.patient-number {
    color: #17658a;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: .5px;

    margin-bottom: 5px;
}


.patient-name {
    margin: 0;

    color: #183b56;

    font-size: 19px;
}


.patient-basic {
    margin-top: 7px;

    color: #718096;

    font-size: 12px;
}


.patient-basic span {
    margin-right: 14px;
}


/* SITUAÇÃO */

.triage-grid {
    display: grid;

    grid-template-columns:
        minmax(0, 1.7fr)
        minmax(280px, 1fr);

    gap: 22px;

    margin-top: 20px;
}


.info-block {
    margin-bottom: 17px;
}


.info-label {
    display: block;

    margin-bottom: 6px;

    color: #718096;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .4px;
}


.info-value {
    color: #29384a;

    font-size: 14px;

    line-height: 1.6;
}


.situation-badge {
    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 7px 10px;

    border-radius: 7px;

    background: #eef6fb;

    color: #17658a;

    font-size: 12px;

    font-weight: 800;
}


/* DADOS */

.data-row {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 10px;
}


.data-item {
    background: #f7f9fb;

    border: 1px solid #edf0f3;

    border-radius: 9px;

    padding: 11px 12px;
}


.data-item span {
    display: block;

    color: #7a8795;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;
}


.data-item strong {
    display: block;

    margin-top: 4px;

    color: #26394d;

    font-size: 13px;
}


/* FORM TRIAGEM */

.triage-form {
    background: #f8fafc;

    border: 1px solid #e4eaf0;

    border-radius: 12px;

    padding: 18px;
}


.triage-form h3 {
    margin: 0 0 5px;

    color: #183b56;

    font-size: 16px;
}


.triage-form > p {
    margin: 0 0 17px;

    color: #718096;

    font-size: 12px;

    line-height: 1.5;
}


.form-label {
    display: block;

    margin-bottom: 7px;

    color: #37485a;

    font-size: 12px;

    font-weight: 800;
}


.triage-form select,
.triage-form textarea {
    width: 100%;

    box-sizing: border-box;

    padding: 11px 12px;

    border: 1px solid #d9e0e7;

    border-radius: 8px;

    background: #fff;

    color: #26394d;

    font: inherit;

    font-size: 13px;

    outline: none;
}


.triage-form textarea {
    resize: vertical;

    min-height: 95px;

    margin-bottom: 15px;
}


.triage-form select:focus,
.triage-form textarea:focus {
    border-color: #17658a;
}


.priority-preview {
    margin-top: 13px;

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 7px;
}


.priority-option {
    border: 1px solid #dce3e9;

    border-radius: 7px;

    padding: 8px;

    background: #fff;

    font-size: 11px;

    color: #4a5568;
}


.priority-option strong {
    display: block;

    color: #26394d;

    font-size: 11px;
}


/* CORES */

.priority-dot {
    display: inline-block;

    width: 8px;
    height: 8px;

    border-radius: 50%;

    margin-right: 5px;
}


.dot-emergente {
    background: #d62828;
}

.dot-muito-urgente {
    background: #f77f00;
}

.dot-urgente {
    background: #e9c46a;
}

.dot-pouco-urgente {
    background: #38a169;
}

.dot-nao-urgente {
    background: #3182ce;
}


/* BOTÃO */

.confirm-btn {
    width: 100%;

    border: none;

    border-radius: 8px;

    padding: 12px 15px;

    margin-top: 4px;

    background: #17658a;

    color: #fff;

    font-size: 13px;

    font-weight: 800;

    cursor: pointer;

    transition: .2s ease;
}


.confirm-btn:hover {
    transform: translateY(-1px);
    opacity: .94;
}


/* SEM ATENDIMENTOS */

.empty-state {
    background: #fff;

    border: 1px dashed #d8e0e7;

    border-radius: 13px;

    padding: 45px 20px;

    text-align: center;

    color: #718096;
}


.empty-state i {
    font-size: 32px;

    margin-bottom: 12px;

    color: #8ba0b1;
}


.empty-state h3 {
    margin: 0 0 5px;

    color: #425466;

    font-size: 16px;
}


.empty-state p {
    margin: 0;

    font-size: 13px;
}


/* RESPONSIVO */

@media (max-width: 800px) {

    .dashboard-header {
        padding: 15px 18px;
    }

    .dashboard-container {
        width: min(100% - 28px, 700px);
        padding-top: 25px;
    }

    .triage-grid {
        grid-template-columns: 1fr;
    }

}


@media (max-width: 600px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .patient-top {
        flex-direction: column;
    }

    .data-row {
        grid-template-columns: 1fr;
    }

    .dashboard-title h1 {
        font-size: 23px;
    }

    .user-info {
        display: none;
    }

}

</style>

</head>


<body class="dashboard-body">


<header class="dashboard-header">

    <div class="dashboard-brand">

        <div class="dashboard-brand-icon">
            <i class="fa-solid fa-user-nurse"></i>
        </div>

        <div>

            <strong>
                HOSPITAL DE MAVALANE
            </strong>

            <span>
                Painel de Triagem
            </span>

        </div>

    </div>


    <div class="user-area">

        <div class="user-info">

            <strong>
                <?= nome_utilizador(); ?>
            </strong>

            <span>
                Enfermeiro
            </span>

        </div>


        <a href="../logout.php"
           class="logout-btn">

            <i class="fa-solid fa-right-from-bracket"></i>

            Sair

        </a>

    </div>

</header>


<main class="dashboard-container">


    <div class="dashboard-title">

        <span class="eyebrow">
            TRIAGEM DE URGÊNCIA
        </span>

        <h1>
            Avaliação dos pacientes
        </h1>

        <p>
            Analise as informações apresentadas pelo paciente,
            realize a avaliação de triagem e confirme a prioridade.
        </p>

    </div>


    <?php if ($mensagem): ?>

        <div class="alert alert-success">

            <i class="fa-solid fa-circle-check"></i>

            <?= htmlspecialchars($mensagem); ?>

        </div>

    <?php endif; ?>


    <?php if ($erro): ?>

        <div class="alert alert-error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?= htmlspecialchars($erro); ?>

        </div>

    <?php endif; ?>


    <!-- ESTATÍSTICAS -->

    <div class="stats">

        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-hourglass-half"></i>

            </div>

            <div>

                <strong>
                    <?= $aguardando; ?>
                </strong>

                <span>
                    Aguardando triagem
                </span>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-clipboard-check"></i>

            </div>

            <div>

                <strong>
                    <?= $triados; ?>
                </strong>

                <span>
                    Aguardando médico
                </span>

            </div>

        </div>

    </div>


    <!-- LISTA -->

    <div class="section-title">

        <h2>
            Pacientes aguardando triagem
        </h2>

        <span>
            <?= $aguardando; ?> atendimento(s)
        </span>

    </div>


    <?php if ($resultado && $resultado->num_rows > 0): ?>


        <?php while ($atendimento = $resultado->fetch_assoc()): ?>


            <article class="patient-card">


                <!-- CABEÇALHO -->

                <div class="patient-top">

                    <div>

                        <div class="patient-number">

                            <?= htmlspecialchars(
                                $atendimento["numero_atendimento"]
                            ); ?>

                        </div>


                        <h3 class="patient-name">

                            <?= htmlspecialchars(
                                $atendimento["nome"]
                            ); ?>

                        </h3>


                        <div class="patient-basic">

                            <span>
                                <i class="fa-solid fa-id-card"></i>

                                <?= htmlspecialchars(
                                    $atendimento["documento"]
                                ); ?>
                            </span>


                            <span>
                                <i class="fa-solid fa-phone"></i>

                                <?= htmlspecialchars(
                                    $atendimento["contacto"]
                                ); ?>
                            </span>

                        </div>

                    </div>


                    <div class="situation-badge">

                        <i class="fa-solid fa-clock"></i>

                        Aguardando triagem

                    </div>

                </div>


                <div class="triage-grid">


                    <!-- INFORMAÇÕES -->

                    <div>


                        <div class="info-block">

                            <span class="info-label">
                                Dados básicos
                            </span>


                            <div class="data-row">


                                <div class="data-item">

                                    <span>
                                        Idade
                                    </span>

                                    <strong>

                                        <?= calcular_idade(
                                            $atendimento["data_nascimento"]
                                        ); ?>

                                    </strong>

                                </div>


                                <div class="data-item">

                                    <span>
                                        Sexo
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $atendimento["sexo"]
                                        ); ?>

                                    </strong>

                                </div>

                            </div>

                        </div>


                        <div class="info-block">

                            <span class="info-label">
                                Situação apresentada
                            </span>

                            <div class="info-value">

                                <strong>
                                    <?= htmlspecialchars(
                                        $atendimento["tipo_situacao"]
                                    ); ?>
                                </strong>

                            </div>

                        </div>


                        <div class="info-block">

                            <span class="info-label">
                                O que está a sentir / aconteceu
                            </span>

                            <div class="info-value">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $atendimento["sintomas"]
                                    )
                                ); ?>

                            </div>

                        </div>


                        <div class="info-block">

                            <span class="info-label">
                                Informação adicional
                            </span>


                            <div class="data-row">


                                <div class="data-item">

                                    <span>
                                        Início
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $atendimento["inicio_sintomas"]
                                        ); ?>

                                    </strong>

                                </div>


                                <div class="data-item">

                                    <span>
                                        Dor
                                    </span>

                                    <strong>

                                        <?php if (
                                            $atendimento["tem_dor"] === "Sim"
                                        ): ?>

                                            Sim -
                                            <?= (int)$atendimento["intensidade_dor"]; ?>/10

                                        <?php else: ?>

                                            Não

                                        <?php endif; ?>

                                    </strong>

                                </div>


                                <div class="data-item">

                                    <span>
                                        Gravidez
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $atendimento["gravidez"]
                                        ); ?>

                                    </strong>

                                </div>


                                <div class="data-item">

                                    <span>
                                        Entrada
                                    </span>

                                    <strong>

                                        <?= date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $atendimento["data_entrada"]
                                            )
                                        ); ?>

                                    </strong>

                                </div>

                            </div>

                        </div>


                    </div>


                    <!-- TRIAGEM -->

                    <div>

                        <form method="POST"
                              class="triage-form">


                            <h3>
                                Avaliação de triagem
                            </h3>


                            <p>
                                A prioridade deve ser confirmada
                                pela equipa de enfermagem após a
                                avaliação do paciente.
                            </p>


                            <input type="hidden"
                                   name="atendimento_id"
                                   value="<?= (int)$atendimento["id"]; ?>">


                            <label class="form-label">

                                Observação da enfermagem

                            </label>


                            <textarea
                                name="observacao_triagem"
                                placeholder="Registe os principais achados da avaliação..."></textarea>


                            <label class="form-label">

                                Prioridade do atendimento

                            </label>


                            <select name="prioridade"
                                    required>

                                <option value="">
                                    Seleccione a prioridade
                                </option>

                                <option value="Emergente">
                                    🔴 Emergente
                                </option>

                                <option value="Muito urgente">
                                    🟠 Muito urgente
                                </option>

                                <option value="Urgente">
                                    🟡 Urgente
                                </option>

                                <option value="Pouco urgente">
                                    🟢 Pouco urgente
                                </option>

                                <option value="Não urgente">
                                    🔵 Não urgente
                                </option>

                            </select>


                            <div class="priority-preview">


                                <div class="priority-option">

                                    <strong>
                                        <span class="priority-dot dot-emergente"></span>
                                        Emergente
                                    </strong>

                                    Intervenção imediata.

                                </div>


                                <div class="priority-option">

                                    <strong>
                                        <span class="priority-dot dot-muito-urgente"></span>
                                        Muito urgente
                                    </strong>

                                    Elevada urgência.

                                </div>


                                <div class="priority-option">

                                    <strong>
                                        <span class="priority-dot dot-urgente"></span>
                                        Urgente
                                    </strong>

                                    Necessita avaliação.

                                </div>


                                <div class="priority-option">

                                    <strong>
                                        <span class="priority-dot dot-pouco-urgente"></span>
                                        Pouco urgente
                                    </strong>

                                    Pode aguardar.

                                </div>


                                <div class="priority-option">

                                    <strong>
                                        <span class="priority-dot dot-nao-urgente"></span>
                                        Não urgente
                                    </strong>

                                    Baixa urgência.

                                </div>

                            </div>


                            <button type="submit"
                                    class="confirm-btn">

                                <i class="fa-solid fa-clipboard-check"></i>

                                Confirmar Triagem

                            </button>


                        </form>

                    </div>


                </div>


            </article>


        <?php endwhile; ?>


    <?php else: ?>


        <div class="empty-state">

            <i class="fa-solid fa-circle-check"></i>

            <h3>
                Não existem pacientes aguardando triagem
            </h3>

            <p>
                Todos os atendimentos recebidos foram processados.
            </p>

        </div>


    <?php endif; ?>


</main>


</body>

</html>