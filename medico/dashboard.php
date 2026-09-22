<?php

require_once "../config/database.php";
require_once "../config/auth.php";

exigir_tipo(["medico"]);


/*
|--------------------------------------------------------------------------
| MENSAGENS
|--------------------------------------------------------------------------
*/

$mensagem = "";
$erro = "";


/*
|--------------------------------------------------------------------------
| AÇÕES DO MÉDICO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $acao = $_POST["acao"] ?? "";
    $atendimento_id = (int)($_POST["atendimento_id"] ?? 0);
    $medico_id = (int)$_SESSION["utilizador_id"];


    /*
    |--------------------------------------------------------------------------
    | INICIAR ATENDIMENTO
    |--------------------------------------------------------------------------
    */

    if ($acao === "iniciar") {

        if ($atendimento_id <= 0) {

            $erro = "Atendimento inválido.";

        } else {

            $stmt = $conn->prepare("
                UPDATE atendimentos
                SET
                    estado = 'Em atendimento',
                    profissional_medico_id = ?,
                    data_inicio_atendimento = NOW()
                WHERE
                    id = ?
                    AND estado = 'Aguardando médico'
                    AND prioridade IS NOT NULL
            ");

            $stmt->bind_param(
                "ii",
                $medico_id,
                $atendimento_id
            );

            if ($stmt->execute() && $stmt->affected_rows > 0) {

                $mensagem = "Atendimento iniciado com sucesso.";

            } else {

                $erro = "Não foi possível iniciar este atendimento.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CONCLUIR ATENDIMENTO
    |--------------------------------------------------------------------------
    */

    if ($acao === "concluir") {

        $diagnostico = trim(
            $_POST["diagnostico"] ?? ""
        );

        $destino = trim(
            $_POST["destino"] ?? ""
        );

        $destinos_validos = [
            "Alta",
            "Internamento",
            "Transferência",
            "Óbito"
        ];


        if (!$diagnostico) {

            $erro = "O diagnóstico deve ser preenchido.";

        } elseif (!in_array(
            $destino,
            $destinos_validos,
            true
        )) {

            $erro = "Seleccione um destino válido.";

        } else {

            $stmt = $conn->prepare("
                UPDATE atendimentos
                SET
                    estado = 'Concluído',
                    diagnostico = ?,
                    destino = ?,
                    data_conclusao = NOW()
                WHERE
                    id = ?
                    AND profissional_medico_id = ?
                    AND estado = 'Em atendimento'
            ");

            $stmt->bind_param(
                "ssii",
                $diagnostico,
                $destino,
                $atendimento_id,
                $medico_id
            );

            if (
                $stmt->execute()
                && $stmt->affected_rows > 0
            ) {

                $mensagem =
                    "Atendimento concluído com sucesso.";

            } else {

                $erro =
                    "Não foi possível concluir este atendimento.";

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| PACIENTE ACTUAL DO MÉDICO
|--------------------------------------------------------------------------
*/

$actual = null;

$stmt = $conn->prepare("
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

        a.prioridade,
        a.estado,
        a.observacao_triagem,

        a.data_entrada,
        a.data_triagem,
        a.data_inicio_atendimento,

        p.nome,
        p.documento,
        p.contacto,
        p.data_nascimento,
        p.sexo

    FROM atendimentos a

    INNER JOIN pacientes p
        ON p.id = a.paciente_id

    WHERE
        a.profissional_medico_id = ?
        AND a.estado = 'Em atendimento'

    ORDER BY a.data_inicio_atendimento DESC

    LIMIT 1
");

$stmt->bind_param(
    "i",
    $medico_id
);

$stmt->execute();

$res_actual = $stmt->get_result();

if ($res_actual->num_rows > 0) {

    $actual = $res_actual->fetch_assoc();

}


/*
|--------------------------------------------------------------------------
| ATENDIMENTOS TRIADOS
|--------------------------------------------------------------------------
*/

$sql_triagem = "

    SELECT

        a.id,
        a.numero_atendimento,
        a.tipo_situacao,
        a.sintomas,
        a.inicio_sintomas,
        a.tem_dor,
        a.intensidade_dor,
        a.gravidez,
        a.prioridade,
        a.estado,
        a.data_entrada,

        p.nome,
        p.data_nascimento,
        p.sexo

    FROM atendimentos a

    INNER JOIN pacientes p
        ON p.id = a.paciente_id

    WHERE
        a.estado = 'Aguardando médico'
        AND a.prioridade IS NOT NULL

    ORDER BY
        FIELD(
            a.prioridade,
            'Emergente',
            'Muito urgente',
            'Urgente',
            'Pouco urgente',
            'Não urgente'
        ),
        a.data_entrada ASC
";

$resultado_triagem = $conn->query($sql_triagem);


/*
|--------------------------------------------------------------------------
| CONTAGEM POR PRIORIDADE
|--------------------------------------------------------------------------
*/

$contagens = [
    "Emergente" => 0,
    "Muito urgente" => 0,
    "Urgente" => 0,
    "Pouco urgente" => 0,
    "Não urgente" => 0
];

$atendimentos_triagem = [];

if ($resultado_triagem) {

    while ($item = $resultado_triagem->fetch_assoc()) {

        $atendimentos_triagem[] = $item;

        if (isset($contagens[$item["prioridade"]])) {

            $contagens[$item["prioridade"]]++;
        }

    }

}

/*
|--------------------------------------------------------------------------
| ESTATÍSTICAS
|--------------------------------------------------------------------------
*/

$res_concluidos = $conn->query("
    SELECT COUNT(*) AS total
    FROM atendimentos
    WHERE estado = 'Concluído'
");

$total_concluidos = $res_concluidos
    ? (int)$res_concluidos->fetch_assoc()["total"]
    : 0;


$total_triados = array_sum($contagens);


/*
|--------------------------------------------------------------------------
| FUNÇÕES
|--------------------------------------------------------------------------
*/

function calcular_idade_medico($data)
{
    if (!$data) {
        return "-";
    }

    try {

        $nascimento = new DateTime($data);
        $hoje = new DateTime();

        return $hoje->diff($nascimento)->y;

    } catch (Exception $e) {

        return "-";

    }
}


function classe_prioridade_medico($prioridade)
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


function icone_prioridade_medico($prioridade)
{
    $mapa = [

        "Emergente" => "fa-triangle-exclamation",

        "Muito urgente" => "fa-bolt",

        "Urgente" => "fa-circle-exclamation",

        "Pouco urgente" => "fa-circle",

        "Não urgente" => "fa-circle-check"

    ];

    return $mapa[$prioridade] ?? "fa-circle";
}

?>

<!DOCTYPE html>

<html lang="pt">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Atendimento Médico | Hospital de Mavalane
</title>

<link rel="stylesheet"
      href="../style.css">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


<style>

/* =========================================================
   BASE
========================================================= */

body {
    margin: 0;
    background: #f5f7fa;
}


/* =========================================================
   HEADER
========================================================= */

.dashboard-header {

    background: #fff;

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

    background: #edf5fa;

    color: #17658a;
}


.dashboard-brand strong {

    display: block;

    color: #183b56;

    font-size: 15px;
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

    border: 1px solid #dfe5eb;

    border-radius: 8px;

    background: #fff;

    color: #4a5568;

    text-decoration: none;

    font-size: 12px;

    font-weight: 700;
}


/* =========================================================
   CONTAINER
========================================================= */

.dashboard-container {

    width: min(1250px, calc(100% - 40px));

    margin: auto;

    padding: 32px 0 50px;
}


.dashboard-title {

    margin-bottom: 25px;
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


/* =========================================================
   ALERTAS
========================================================= */

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


/* =========================================================
   ESTATÍSTICAS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

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


/* =========================================================
   RESUMO DA TRIAGEM
========================================================= */

.triage-overview {

    display: grid;

    grid-template-columns:
        repeat(5, minmax(0, 1fr));

    gap: 10px;

    margin-bottom: 30px;
}


.triage-overview-card {

    background: #fff;

    border: 1px solid #e5e9ef;

    border-radius: 11px;

    padding: 14px;

}


.triage-overview-card strong {

    display: block;

    color: #183b56;

    font-size: 23px;

    margin-top: 7px;
}


.triage-overview-card span {

    color: #718096;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;
}


/* =========================================================
   PRIORIDADE
========================================================= */

.priority-badge {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 7px 11px;

    border-radius: 8px;

    font-size: 11px;

    font-weight: 800;
}


.priority-dot {

    width: 8px;
    height: 8px;

    border-radius: 50%;

    display: inline-block;
}


.priority-emergente {

    background: #fff0f0;

    color: #b42323;
}


.priority-emergente .priority-dot {

    background: #d62828;
}


.priority-muito-urgente {

    background: #fff4e8;

    color: #a95300;
}


.priority-muito-urgente .priority-dot {

    background: #f77f00;
}


.priority-urgente {

    background: #fff9df;

    color: #826900;
}


.priority-urgente .priority-dot {

    background: #d9b94a;
}


.priority-pouco-urgente {

    background: #edf9f0;

    color: #27753d;
}


.priority-pouco-urgente .priority-dot {

    background: #38a169;
}


.priority-nao-urgente {

    background: #edf5ff;

    color: #28639b;
}


.priority-nao-urgente .priority-dot {

    background: #3182ce;
}


/* =========================================================
   PACIENTE ACTUAL
========================================================= */

.current-card {

    background: #fff;

    border: 1px solid #dfe7ed;

    border-radius: 14px;

    overflow: hidden;

    margin-bottom: 30px;
}


.current-header {

    padding: 22px;

    background: #f9fbfc;

    border-bottom: 1px solid #e6ebef;

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 20px;
}


.current-number {

    color: #17658a;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: .4px;
}


.current-header h2 {

    margin: 5px 0;

    color: #183b56;

    font-size: 22px;
}


.patient-meta {

    color: #718096;

    font-size: 12px;
}


.patient-meta span {

    margin-right: 15px;
}


.current-body {

    padding: 22px;
}


.info-title {

    margin-bottom: 9px;

    color: #718096;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .4px;
}


.info-text {

    color: #2d3e50;

    font-size: 14px;

    line-height: 1.65;
}


.info-grid {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 10px;

    margin: 18px 0;
}


.info-box {

    background: #f7f9fb;

    border: 1px solid #e9edf1;

    border-radius: 9px;

    padding: 12px;
}


.info-box span {

    display: block;

    color: #7b8794;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;
}


.info-box strong {

    display: block;

    margin-top: 5px;

    color: #26394d;

    font-size: 13px;
}


/* =========================================================
   TRIAGEM DO PACIENTE
========================================================= */

.triage-summary {

    margin-top: 22px;

    padding: 18px;

    border: 1px solid #e3e9ee;

    border-radius: 11px;

    background: #fbfcfd;
}


.triage-summary h3 {

    margin: 0 0 15px;

    color: #183b56;

    font-size: 15px;
}


.triage-note {

    margin-top: 15px;

    padding: 13px;

    border-radius: 8px;

    background: #fff;

    border: 1px solid #e8edf1;

    color: #425466;

    font-size: 13px;

    line-height: 1.6;
}


/* =========================================================
   FORM MÉDICO
========================================================= */

.doctor-form {

    margin-top: 22px;

    padding-top: 22px;

    border-top: 1px solid #e7ebef;
}


.doctor-form h3 {

    margin: 0 0 15px;

    color: #183b56;

    font-size: 16px;
}


.doctor-form label {

    display: block;

    margin-bottom: 7px;

    color: #425466;

    font-size: 12px;

    font-weight: 800;
}


.doctor-form textarea,
.doctor-form select {

    width: 100%;

    box-sizing: border-box;

    padding: 11px 12px;

    border: 1px solid #d8e0e7;

    border-radius: 8px;

    background: #fff;

    color: #26394d;

    font: inherit;

    font-size: 13px;

    outline: none;
}


.doctor-form textarea {

    min-height: 110px;

    resize: vertical;

    margin-bottom: 15px;
}


.doctor-form select {

    margin-bottom: 15px;
}


.doctor-form textarea:focus,
.doctor-form select:focus {

    border-color: #17658a;
}


.finish-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    border: none;

    border-radius: 8px;

    padding: 12px 18px;

    background: #17658a;

    color: #fff;

    font-size: 13px;

    font-weight: 800;

    cursor: pointer;
}


.finish-btn:hover {

    opacity: .92;
}


/* =========================================================
   ATENDIMENTOS TRIADOS
========================================================= */

.section-heading {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 16px;
}


.section-heading h2 {

    margin: 0;

    color: #183b56;

    font-size: 20px;
}


.section-heading span {

    color: #718096;

    font-size: 12px;
}


.triage-section {

    margin-bottom: 28px;
}


.triage-section-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 10px;

    padding-bottom: 10px;

    border-bottom: 1px solid #e5e9ef;
}


.triage-section-header h3 {

    margin: 0;

    color: #183b56;

    font-size: 15px;
}


.triage-section-header span {

    color: #718096;

    font-size: 11px;

    font-weight: 700;
}


.triage-patients {

    display: grid;

    gap: 10px;
}


.triage-patient-card {

    background: #fff;

    border: 1px solid #e3e8ee;

    border-radius: 12px;

    padding: 16px 18px;

    display: grid;

    grid-template-columns:
        minmax(0, 1fr) auto;

    align-items: center;

    gap: 18px;
}


.patient-number {

    color: #17658a;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: .3px;
}


.patient-name {

    margin: 4px 0;

    color: #26394d;

    font-size: 15px;

    font-weight: 800;
}


.patient-description {

    color: #718096;

    font-size: 12px;

    line-height: 1.5;

    max-width: 750px;
}


.patient-details {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-top: 8px;
}


.patient-detail {

    padding: 5px 8px;

    border-radius: 6px;

    background: #f5f7fa;

    color: #657383;

    font-size: 10px;

    font-weight: 700;
}


.patient-action {

    text-align: right;

    min-width: 150px;
}


.start-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    border: none;

    border-radius: 8px;

    padding: 10px 13px;

    background: #17658a;

    color: #fff;

    font-size: 11px;

    font-weight: 800;

    cursor: pointer;
}


.start-btn:hover {

    opacity: .92;
}


.no-patients {

    padding: 15px;

    border: 1px dashed #d8e0e7;

    border-radius: 9px;

    background: #fafbfc;

    color: #718096;

    font-size: 12px;
}


/* =========================================================
   RESPONSIVO
========================================================= */

@media (max-width: 1000px) {

    .triage-overview {

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

    }

}


@media (max-width: 850px) {

    .info-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }

}


@media (max-width: 700px) {

    .triage-overview {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }


    .triage-patient-card {

        grid-template-columns: 1fr;

    }


    .patient-action {

        text-align: left;

        min-width: 0;

    }

}


@media (max-width: 650px) {

    .dashboard-container {

        width: calc(100% - 28px);

        padding-top: 24px;

    }


    .dashboard-header {

        padding: 15px 18px;

    }


    .user-info {

        display: none;

    }


    .stats {

        grid-template-columns: 1fr;

    }


    .current-header {

        flex-direction: column;

    }


    .info-grid {

        grid-template-columns: 1fr;

    }


    .triage-overview {

        grid-template-columns: 1fr;

    }

}

</style>

</head>


<body>


<header class="dashboard-header">

    <div class="dashboard-brand">

        <div class="dashboard-brand-icon">

            <i class="fa-solid fa-user-doctor"></i>

        </div>

        <div>

            <strong>
                HOSPITAL DE MAVALANE
            </strong>

            <span>
                Painel Médico
            </span>

        </div>

    </div>


    <div class="user-area">

        <div class="user-info">

            <strong>
                <?= nome_utilizador(); ?>
            </strong>

            <span>
                Médico
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
            ATENDIMENTO MÉDICO
        </span>

        <h1>
            Gestão de atendimentos
        </h1>

        <p>
            Consulte os pacientes após a triagem e realize o atendimento médico.
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


    <!-- =====================================================
         ESTATÍSTICAS
    ====================================================== -->

    <div class="stats">

        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-clipboard-check"></i>

            </div>

            <div>

                <strong>
                    <?= $total_triados; ?>
                </strong>

                <span>
                    Atendimentos triados
                </span>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-user-doctor"></i>

            </div>

            <div>

                <strong>
                    <?= $actual ? "1" : "0"; ?>
                </strong>

                <span>
                    Atendimento em curso
                </span>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <div>

                <strong>
                    <?= $total_concluidos; ?>
                </strong>

                <span>
                    Atendimentos concluídos
                </span>

            </div>

        </div>

    </div>


    <!-- =====================================================
         RESUMO DA TRIAGEM
    ====================================================== -->

    <section>

        <div class="section-heading">

            <h2>
                Resumo da triagem
            </h2>

            <span>
                Atendimentos aguardando avaliação médica
            </span>

        </div>


        <div class="triage-overview">


            <div class="triage-overview-card">

                <span>Emergente</span>

                <strong>
                    <?= $contagens["Emergente"]; ?>
                </strong>

            </div>


            <div class="triage-overview-card">

                <span>Muito urgente</span>

                <strong>
                    <?= $contagens["Muito urgente"]; ?>
                </strong>

            </div>


            <div class="triage-overview-card">

                <span>Urgente</span>

                <strong>
                    <?= $contagens["Urgente"]; ?>
                </strong>

            </div>


            <div class="triage-overview-card">

                <span>Pouco urgente</span>

                <strong>
                    <?= $contagens["Pouco urgente"]; ?>
                </strong>

            </div>


            <div class="triage-overview-card">

                <span>Não urgente</span>

                <strong>
                    <?= $contagens["Não urgente"]; ?>
                </strong>

            </div>

        </div>

    </section>


    <!-- =====================================================
         PACIENTE ACTUAL
    ====================================================== -->

    <?php if ($actual): ?>

        <section class="current-card">


            <div class="current-header">

                <div>

                    <div class="current-number">

                        <?= htmlspecialchars(
                            $actual["numero_atendimento"]
                        ); ?>

                    </div>


                    <h2>

                        <?= htmlspecialchars(
                            $actual["nome"]
                        ); ?>

                    </h2>


                    <div class="patient-meta">

                        <span>

                            <i class="fa-solid fa-id-card"></i>

                            <?= htmlspecialchars(
                                $actual["documento"]
                            ); ?>

                        </span>


                        <span>

                            <i class="fa-solid fa-phone"></i>

                            <?= htmlspecialchars(
                                $actual["contacto"]
                            ); ?>

                        </span>

                    </div>

                </div>


                <div class="priority-badge priority-<?= classe_prioridade_medico(
                    $actual["prioridade"]
                ); ?>">

                    <span class="priority-dot"></span>

                    <?= htmlspecialchars(
                        $actual["prioridade"]
                    ); ?>

                </div>

            </div>


            <div class="current-body">


                <div class="info-title">
                    Dados do paciente
                </div>


                <div class="info-grid">


                    <div class="info-box">

                        <span>
                            Idade
                        </span>

                        <strong>

                            <?= calcular_idade_medico(
                                $actual["data_nascimento"]
                            ); ?>

                            anos

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Sexo
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $actual["sexo"]
                            ); ?>

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Dor
                        </span>

                        <strong>

                            <?php if (
                                $actual["tem_dor"] === "Sim"
                            ): ?>

                                Sim -
                                <?= (int)$actual["intensidade_dor"]; ?>/10

                            <?php else: ?>

                                Não

                            <?php endif; ?>

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Gravidez
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $actual["gravidez"]
                            ); ?>

                        </strong>

                    </div>

                </div>


                <div class="info-title">
                    Situação apresentada
                </div>


                <div class="info-text">

                    <strong>
                        <?= htmlspecialchars(
                            $actual["tipo_situacao"]
                        ); ?>
                    </strong>

                </div>


                <br>


                <div class="info-title">
                    O que o paciente relatou
                </div>


                <div class="info-text">

                    <?= nl2br(
                        htmlspecialchars(
                            $actual["sintomas"]
                        )
                    ); ?>

                </div>


                <div class="info-grid">


                    <div class="info-box">

                        <span>
                            Início
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $actual["inicio_sintomas"]
                            ); ?>

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Data de entrada
                        </span>

                        <strong>

                            <?= date(
                                "d/m/Y H:i",
                                strtotime(
                                    $actual["data_entrada"]
                                )
                            ); ?>

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Data da triagem
                        </span>

                        <strong>

                            <?php if ($actual["data_triagem"]): ?>

                                <?= date(
                                    "d/m/Y H:i",
                                    strtotime(
                                        $actual["data_triagem"]
                                    )
                                ); ?>

                            <?php else: ?>

                                -

                            <?php endif; ?>

                        </strong>

                    </div>


                    <div class="info-box">

                        <span>
                            Estado
                        </span>

                        <strong>
                            Em atendimento
                        </strong>

                    </div>

                </div>


                <!-- =================================================
                     INFORMAÇÃO DA TRIAGEM
                ================================================== -->

                <div class="triage-summary">

                    <h3>

                        <i class="fa-solid fa-clipboard-check"></i>

                        Informação da triagem

                    </h3>


                    <div class="info-title">

                        Prioridade confirmada

                    </div>


                    <div>

                        <div class="priority-badge priority-<?= classe_prioridade_medico(
                            $actual["prioridade"]
                        ); ?>">

                            <span class="priority-dot"></span>

                            <?= htmlspecialchars(
                                $actual["prioridade"]
                            ); ?>

                        </div>

                    </div>


                    <div class="triage-note">

                        <strong>
                            Observação da enfermagem:
                        </strong>

                        <br><br>

                        <?php if (
                            trim($actual["observacao_triagem"] ?? "")
                        ): ?>

                            <?= nl2br(
                                htmlspecialchars(
                                    $actual["observacao_triagem"]
                                )
                            ); ?>

                        <?php else: ?>

                            Nenhuma observação registada.

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =================================================
                     FORMULÁRIO MÉDICO
                ================================================== -->

                <form method="POST"
                      class="doctor-form">


                    <input type="hidden"
                           name="acao"
                           value="concluir">


                    <input type="hidden"
                           name="atendimento_id"
                           value="<?= (int)$actual["id"]; ?>">


                    <h3>
                        Registo do atendimento médico
                    </h3>


                    <label>
                        Diagnóstico
                    </label>


                    <textarea
                        name="diagnostico"
                        required
                        placeholder="Registe o diagnóstico do paciente..."
                    ></textarea>


                    <label>
                        Destino do paciente
                    </label>


                    <select name="destino"
                            required>

                        <option value="">
                            Seleccione
                        </option>

                        <option value="Alta">
                            Alta
                        </option>

                        <option value="Internamento">
                            Internamento
                        </option>

                        <option value="Transferência">
                            Transferência
                        </option>

                        <option value="Óbito">
                            Óbito
                        </option>

                    </select>


                    <button type="submit"
                            class="finish-btn">

                        <i class="fa-solid fa-check"></i>

                        Concluir atendimento

                    </button>

                </form>


            </div>

        </section>

    <?php endif; ?>


    <!-- =====================================================
         ATENDIMENTOS TRIADOS
    ====================================================== -->

    <section>

        <div class="section-heading">

            <h2>
                Atendimentos triados
            </h2>

            <span>
                <?= $total_triados; ?>
                atendimento(s)
            </span>

        </div>


        <?php

        /*
        |--------------------------------------------------------------------------
        | REINICIAR RESULTADO PARA PODER AGRUPAR
        |--------------------------------------------------------------------------
        */

       $grupos = [

    "Emergente" => [],

    "Muito urgente" => [],

    "Urgente" => [],

    "Pouco urgente" => [],

    "Não urgente" => []

];


foreach ($atendimentos_triagem as $paciente) {

    if (
        isset(
            $grupos[$paciente["prioridade"]]
        )
    ) {

        $grupos[
            $paciente["prioridade"]
        ][] = $paciente;

    }

}


        foreach ($grupos as $prioridade => $pacientes):

    ?>

        <section class="triage-section">


            <div class="triage-section-header">

                <h3>

                    <i class="fa-solid <?= icone_prioridade_medico(
                        $prioridade
                    ); ?>"></i>

                    <?= htmlspecialchars($prioridade); ?>

                </h3>


                <span>

                    <?= count($pacientes); ?>

                    atendimento(s)

                </span>

            </div>


            <?php if (count($pacientes) > 0): ?>

                <div class="triage-patients">


                    <?php foreach ($pacientes as $paciente): ?>


                        <div class="triage-patient-card">


                            <div>

                                <div class="patient-number">

                                    <?= htmlspecialchars(
                                        $paciente["numero_atendimento"]
                                    ); ?>

                                </div>


                                <div class="patient-name">

                                    <?= htmlspecialchars(
                                        $paciente["nome"]
                                    ); ?>

                                </div>


                                <div class="patient-description">

                                    <?= htmlspecialchars(
                                        $paciente["tipo_situacao"]
                                    ); ?>


                                    <?php if (
                                        !empty(
                                            $paciente["sintomas"]
                                        )
                                    ): ?>

                                        —
                                        <?= htmlspecialchars(
                                            $paciente["sintomas"]
                                        ); ?>

                                    <?php endif; ?>

                                </div>


                                <div class="patient-details">


                                    <span class="patient-detail">

                                        <i class="fa-solid fa-cake-candles"></i>

                                        <?= calcular_idade_medico(
                                            $paciente["data_nascimento"]
                                        ); ?>

                                        anos

                                    </span>


                                    <span class="patient-detail">

                                        <i class="fa-solid fa-venus-mars"></i>

                                        <?= htmlspecialchars(
                                            $paciente["sexo"]
                                        ); ?>

                                    </span>


                                    <span class="patient-detail">

                                        <i class="fa-solid fa-heart-pulse"></i>

                                        Dor:

                                        <?= $paciente["tem_dor"] === "Sim"
                                            ? "Sim " . (int)$paciente["intensidade_dor"] . "/10"
                                            : "Não";
                                        ?>

                                    </span>


                                    <?php if (
                                        !empty(
                                            $paciente["gravidez"]
                                        )
                                    ): ?>

                                        <span class="patient-detail">

                                            <i class="fa-solid fa-person-pregnant"></i>

                                            Gravidez:

                                            <?= htmlspecialchars(
                                                $paciente["gravidez"]
                                            ); ?>

                                        </span>

                                    <?php endif; ?>


                                </div>

                            </div>


                            <div class="patient-action">


                                <div style="margin-bottom:9px;">

                                    <span class="priority-badge priority-<?= classe_prioridade_medico(
                                        $prioridade
                                    ); ?>">

                                        <span class="priority-dot"></span>

                                        <?= htmlspecialchars(
                                            $prioridade
                                        ); ?>

                                    </span>

                                </div>


                                <form method="POST">

                                    <input type="hidden"
                                           name="acao"
                                           value="iniciar">


                                    <input type="hidden"
                                           name="atendimento_id"
                                           value="<?= (int)$paciente["id"]; ?>">


                                    <button type="submit"
                                            class="start-btn">

                                        <i class="fa-solid fa-user-doctor"></i>

                                        Iniciar atendimento

                                    </button>

                                </form>


                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>

                <div class="no-patients">

                    Nenhum atendimento nesta classificação.

                </div>

            <?php endif; ?>


        </section>


    <?php endforeach; ?>


    </section>


</main>


</body>

</html>