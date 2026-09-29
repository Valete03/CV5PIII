```php
<?php
require_once "../config/database.php";
require_once "../config/auth.php";

exigir_tipo(["admin"]);

$mensagem = "";
$erro = "";

/* =========================================================
   GUARDAR ALTERAÇÕES DO ATENDIMENTO
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = (int)($_POST["id"] ?? 0);

    $prioridade = trim($_POST["prioridade"] ?? "");
    $observacao_triagem = trim($_POST["observacao_triagem"] ?? "");
    $diagnostico = trim($_POST["diagnostico"] ?? "");
    $destino = trim($_POST["destino"] ?? "");
    $estado = trim($_POST["estado"] ?? "");
    $tipo_situacao = trim($_POST["tipo_situacao"] ?? "");

    if ($id <= 0) {

        $erro = "Atendimento inválido.";

    } else {

        $id_seguro = (int)$id;

        $actual_resultado = $conn->query("
            SELECT *
            FROM atendimentos
            WHERE id = $id_seguro
            LIMIT 1
        ");

        if (!$actual_resultado || $actual_resultado->num_rows === 0) {

            $erro = "Atendimento não encontrado.";

        } else {

            $actual = $actual_resultado->fetch_assoc();

            /* =================================================
               DATAS
            ================================================= */

            $data_triagem = $actual["data_triagem"];

            if (!empty($prioridade) && empty($data_triagem)) {
                $data_triagem = date("Y-m-d H:i:s");
            }

            $data_inicio = $actual["data_inicio_atendimento"];

            if (
                (!empty($diagnostico) || !empty($destino))
                && empty($data_inicio)
            ) {
                $data_inicio = date("Y-m-d H:i:s");
            }

            $data_conclusao = $actual["data_conclusao"];

            if ($estado === "Concluído") {

                if (empty($data_conclusao)) {
                    $data_conclusao = date("Y-m-d H:i:s");
                }

            } else {

                $data_conclusao = null;
            }

            /* =================================================
               ESCAPAR VALORES
            ================================================= */

            $prioridade_db = $conn->real_escape_string($prioridade);
            $estado_db = $conn->real_escape_string($estado);
            $observacao_db = $conn->real_escape_string($observacao_triagem);
            $diagnostico_db = $conn->real_escape_string($diagnostico);
            $destino_db = $conn->real_escape_string($destino);
            $tipo_situacao_db = $conn->real_escape_string($tipo_situacao);

            if (!empty($data_triagem)) {
                $data_triagem_db = "'" . $conn->real_escape_string($data_triagem) . "'";
            } else {
                $data_triagem_db = "NULL";
            }

            if (!empty($data_inicio)) {
                $data_inicio_db = "'" . $conn->real_escape_string($data_inicio) . "'";
            } else {
                $data_inicio_db = "NULL";
            }

            if (!empty($data_conclusao)) {
                $data_conclusao_db = "'" . $conn->real_escape_string($data_conclusao) . "'";
            } else {
                $data_conclusao_db = "NULL";
            }

            /* =================================================
               ACTUALIZAR
            ================================================= */

            $sql_update = "
                UPDATE atendimentos SET

                    prioridade = '$prioridade_db',
                    estado = '$estado_db',
                    observacao_triagem = '$observacao_db',
                    diagnostico = '$diagnostico_db',
                    destino = '$destino_db',
                    data_triagem = $data_triagem_db,
                    data_inicio_atendimento = $data_inicio_db,
                    data_conclusao = $data_conclusao_db,
                    tipo_situacao = '$tipo_situacao_db'

                WHERE id = $id_seguro
            ";

            if ($conn->query($sql_update)) {

                $mensagem = "Atendimento actualizado com sucesso.";

            } else {

                $erro = "Erro ao actualizar o atendimento: " . $conn->error;
            }
        }
    }
}


/* =========================================================
   VER ATENDIMENTO
========================================================= */

$atendimento_id = (int)($_GET["id"] ?? 0);

$atendimento = null;


/* =========================================================
   BUSCAR ATENDIMENTO ESPECÍFICO
========================================================= */

if ($atendimento_id > 0) {

    $id_busca = (int)$atendimento_id;

    $resultado = $conn->query("
        SELECT
            a.*,
            p.nome
        FROM atendimentos a
        JOIN pacientes p ON p.id = a.paciente_id
        WHERE a.id = $id_busca
        LIMIT 1
    ");

    if ($resultado && $resultado->num_rows > 0) {

        $atendimento = $resultado->fetch_assoc();

    } else {

        $erro = "Atendimento não encontrado.";
    }
}


/* =========================================================
   LISTA DE ATENDIMENTOS
   ATENDIMENTOS CONCLUÍDOS NÃO APARECEM NA LISTA
========================================================= */

if ($atendimento_id <= 0) {

    $lista = $conn->query("
        SELECT
            a.*,
            p.nome
        FROM atendimentos a
        JOIN pacientes p ON p.id = a.paciente_id
        WHERE a.estado <> 'Concluído'
        ORDER BY a.data_entrada DESC
    ");
}

?>

<!DOCTYPE html>

<html lang="pt">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>Atendimentos</title>

<link rel="stylesheet" href="../style.css">

<style>

    .gestao-info {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .gestao-info-item {
        padding: 12px 14px;
        background: #f8f9fa;
        border: 1px solid #e5e7eb;
        border-radius: 7px;
    }

    .gestao-info-item strong {
        display: block;
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 5px;
    }

    .gestao-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .gestao-full {
        grid-column: 1 / -1;
    }

    .gestao-group {
        margin-bottom: 0;
    }

    .gestao-group label {
        display: block;
        margin-bottom: 7px;
        font-weight: 600;
    }

    .gestao-group select,
    .gestao-group textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background: #fff;
    }

    .gestao-group textarea {
        min-height: 100px;
        resize: vertical;
    }

    .gestao-section-title {
        margin: 0 0 18px 0;
        font-size: 18px;
        font-weight: 700;
    }

    .gestao-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .gestao-save {
        border: none;
        padding: 11px 18px;
        border-radius: 6px;
        background: #111827;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
    }

    .gestao-back {
        display: inline-block;
        padding: 11px 18px;
        border-radius: 6px;
        background: #e5e7eb;
        color: #111827;
        text-decoration: none;
        font-weight: 600;
    }

    .gestao-alert-success {
        padding: 12px 15px;
        margin-bottom: 18px;
        border-radius: 6px;
        background: #dcfce7;
        color: #166534;
    }

    .gestao-alert-error {
        padding: 12px 15px;
        margin-bottom: 18px;
        border-radius: 6px;
        background: #fee2e2;
        color: #991b1b;
    }

    .gestao-manage {
        display: inline-block;
        padding: 7px 12px;
        border-radius: 5px;
        background: #111827;
        color: #fff;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }

    @media (max-width: 700px) {

        .gestao-info,
        .gestao-form-grid {
            grid-template-columns: 1fr;
        }

        .gestao-full {
            grid-column: auto;
        }

    }

</style>

</head>

<body class="dashboard">

<header class="topbar">

    <div class="title">
        Hospital de Mavalane
        <small>Atendimentos</small>
    </div>

    <div>
        <?= htmlspecialchars(nome_utilizador()) ?>
        &nbsp;
        <a class="logout" href="../logout.php">Sair</a>
    </div>

</header>


<div class="dash-wrap">


    <aside class="sidebar">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="utilizadores.php">
            Profissionais
        </a>

        <a href="pacientes.php">
            Pacientes
        </a>

        <a class="active" href="atendimentos.php">
            Atendimentos
        </a>

    </aside>


    <main class="content">


        <?php if (!empty($mensagem)): ?>

            <div class="gestao-alert-success">
                <?= htmlspecialchars($mensagem) ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($erro)): ?>

            <div class="gestao-alert-error">
                <?= htmlspecialchars($erro) ?>
            </div>

        <?php endif; ?>


        <?php if ($atendimento_id <= 0): ?>


            <!-- =================================================
                 LISTA
            ================================================== -->

            <div class="page-title">

                <h1>
                    Todos os atendimentos
                </h1>

            </div>


            <div class="panel">

                <div class="table-wrap">

                    <table>

                        <tr>

                            <th>Número</th>
                            <th>Paciente</th>
                            <th>Motivo</th>
                            <th>Prioridade</th>
                            <th>Estado</th>
                            <th>Entrada</th>
                            <th>Acção</th>

                        </tr>


                        <?php if ($lista && $lista->num_rows > 0): ?>

                            <?php while ($a = $lista->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <b>
                                            <?= htmlspecialchars($a["numero_atendimento"]) ?>
                                        </b>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($a["nome"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($a["motivo"]) ?>
                                    </td>

                                    <td>

                                        <?php if (!empty($a["prioridade"])): ?>

                                            <span class="badge priority <?= prioridade_class($a["prioridade"]) ?>">
                                                <?= htmlspecialchars($a["prioridade"]) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="badge status">
                                                Aguardando
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <span class="badge status">
                                            <?= htmlspecialchars($a["estado"]) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <?= date(
                                            "d/m/Y H:i",
                                            strtotime($a["data_entrada"])
                                        ) ?>

                                    </td>

                                    <td>

                                        <a
                                            class="gestao-manage"
                                            href="atendimentos.php?id=<?= (int)$a["id"] ?>"
                                        >
                                            Gerir
                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7">
                                    Não existem atendimentos pendentes.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </table>

                </div>

            </div>


        <?php elseif ($atendimento): ?>


            <!-- =================================================
                 FICHA DO ATENDIMENTO
            ================================================== -->

            <div class="page-title">

                <h1>
                    Gestão do atendimento
                </h1>

            </div>


            <!-- =================================================
                 INFORMAÇÕES DO PACIENTE
            ================================================== -->

            <div class="panel">

                <h2 class="gestao-section-title">
                    Dados do atendimento
                </h2>


                <div class="gestao-info">


                    <div class="gestao-info-item">

                        <strong>Número</strong>

                        <?= htmlspecialchars(
                            $atendimento["numero_atendimento"]
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Paciente</strong>

                        <?= htmlspecialchars(
                            $atendimento["nome"]
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Motivo da visita</strong>

                        <?= htmlspecialchars(
                            $atendimento["motivo"]
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Tipo de situação</strong>

                        <?= htmlspecialchars(
                            $atendimento["tipo_situacao"] ?? "Não definido"
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Sintomas</strong>

                        <?= nl2br(
                            htmlspecialchars(
                                $atendimento["sintomas"] ?? "Não informado"
                            )
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Início dos sintomas</strong>

                        <?= htmlspecialchars(
                            $atendimento["inicio_sintomas"] ?? "Não informado"
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Tem dor?</strong>

                        <?= htmlspecialchars(
                            $atendimento["tem_dor"] ?? "Não informado"
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Intensidade da dor</strong>

                        <?= htmlspecialchars(
                            $atendimento["intensidade_dor"] ?? "Não informado"
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Gravidez</strong>

                        <?= htmlspecialchars(
                            $atendimento["gravidez"] ?? "Não informado"
                        ) ?>

                    </div>


                    <div class="gestao-info-item">

                        <strong>Sector de atendimento</strong>

                        <?= htmlspecialchars(
                            $atendimento["setor_atendimento"] ?? "Não informado"
                        ) ?>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 FORMULÁRIO
            ================================================== -->

            <form method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)$atendimento["id"] ?>"
                >


                <!-- =================================================
                     TRIAGEM
                ================================================== -->

                <div class="panel">

                    <h2 class="gestao-section-title">
                        Triagem
                    </h2>


                    <div class="gestao-form-grid">


                        <div class="gestao-group">

                            <label for="prioridade">
                                Nível de prioridade
                            </label>

                            <select
                                name="prioridade"
                                id="prioridade"
                                required
                            >

                                <option value="">
                                    Seleccione a prioridade
                                </option>

                                <option
                                    value="Emergente"
                                    <?= ($atendimento["prioridade"] ?? "") === "Emergente" ? "selected" : "" ?>
                                >
                                    Emergente
                                </option>

                                <option
                                    value="Muito urgente"
                                    <?= ($atendimento["prioridade"] ?? "") === "Muito urgente" ? "selected" : "" ?>
                                >
                                    Muito urgente
                                </option>

                                <option
                                    value="Urgente"
                                    <?= ($atendimento["prioridade"] ?? "") === "Urgente" ? "selected" : "" ?>
                                >
                                    Urgente
                                </option>

                                <option
                                    value="Pouco urgente"
                                    <?= ($atendimento["prioridade"] ?? "") === "Pouco urgente" ? "selected" : "" ?>
                                >
                                    Pouco urgente
                                </option>

                                <option
                                    value="Não urgente"
                                    <?= ($atendimento["prioridade"] ?? "") === "Não urgente" ? "selected" : "" ?>
                                >
                                    Não urgente
                                </option>

                            </select>

                        </div>


                        <div class="gestao-group">

                            <label for="tipo_situacao">
                                Tipo de situação
                            </label>

                            <select
                                name="tipo_situacao"
                                id="tipo_situacao"
                            >

                                <option value="">
                                    Seleccione
                                </option>

                                <option
                                    value="Urgência"
                                    <?= ($atendimento["tipo_situacao"] ?? "") === "Urgência" ? "selected" : "" ?>
                                >
                                    Urgência
                                </option>

                                <option
                                    value="Emergência"
                                    <?= ($atendimento["tipo_situacao"] ?? "") === "Emergência" ? "selected" : "" ?>
                                >
                                    Emergência
                                </option>

                                <option
                                    value="Consulta"
                                    <?= ($atendimento["tipo_situacao"] ?? "") === "Consulta" ? "selected" : "" ?>
                                >
                                    Consulta
                                </option>

                            </select>

                        </div>


                        <div class="gestao-group">

                            <label for="destino">
                                Sector de encaminhamento
                            </label>

                            <select
                                name="destino"
                                id="destino"
                                required
                            >

                                <option value="">
                                    Seleccione o sector
                                </option>

                                <option
                                    value="Emergência"
                                    <?= ($atendimento["destino"] ?? "") === "Emergência" ? "selected" : "" ?>
                                >
                                    Emergência
                                </option>

                                <option
                                    value="Urgência"
                                    <?= ($atendimento["destino"] ?? "") === "Urgência" ? "selected" : "" ?>
                                >
                                    Urgência
                                </option>

                                <option
                                    value="Maternidade"
                                    <?= ($atendimento["destino"] ?? "") === "Maternidade" ? "selected" : "" ?>
                                >
                                    Maternidade
                                </option>

                                <option
                                    value="Pediatria"
                                    <?= ($atendimento["destino"] ?? "") === "Pediatria" ? "selected" : "" ?>
                                >
                                    Pediatria
                                </option>

                                <option
                                    value="Ortopedia"
                                    <?= ($atendimento["destino"] ?? "") === "Ortopedia" ? "selected" : "" ?>
                                >
                                    Ortopedia
                                </option>

                                <option
                                    value="Cirurgia"
                                    <?= ($atendimento["destino"] ?? "") === "Cirurgia" ? "selected" : "" ?>
                                >
                                    Cirurgia
                                </option>

                                <option
                                    value="Medicina"
                                    <?= ($atendimento["destino"] ?? "") === "Medicina" ? "selected" : "" ?>
                                >
                                    Medicina
                                </option>

                            </select>

                        </div>


                        <div class="gestao-group gestao-full">

                            <label for="observacao_triagem">
                                Observação da triagem
                            </label>

                            <textarea
                                name="observacao_triagem"
                                id="observacao_triagem"
                                placeholder="Registe as observações da triagem..."
                            ><?= htmlspecialchars(
                                $atendimento["observacao_triagem"] ?? ""
                            ) ?></textarea>

                        </div>


                    </div>

                </div>


                <!-- =================================================
                     ATENDIMENTO MÉDICO
                ================================================== -->

                <div class="panel">

                    <h2 class="gestao-section-title">
                        Atendimento médico
                    </h2>


                    <div class="gestao-form-grid">


                        <div class="gestao-group gestao-full">

                            <label for="diagnostico">
                                Diagnóstico
                            </label>

                            <textarea
                                name="diagnostico"
                                id="diagnostico"
                                placeholder="Registe o diagnóstico do paciente..."
                            ><?= htmlspecialchars(
                                $atendimento["diagnostico"] ?? ""
                            ) ?></textarea>

                        </div>


                        <div class="gestao-group">

                            <label for="destino_medico">
                                Destino do paciente
                            </label>

                            <select
                                name="destino"
                                id="destino_medico"
                            >

                                <option value="">
                                    Seleccione o destino
                                </option>

                                <option
                                    value="Alta"
                                    <?= ($atendimento["destino"] ?? "") === "Alta" ? "selected" : "" ?>
                                >
                                    Alta
                                </option>

                                <option
                                    value="Observação"
                                    <?= ($atendimento["destino"] ?? "") === "Observação" ? "selected" : "" ?>
                                >
                                    Observação
                                </option>

                                <option
                                    value="Internamento"
                                    <?= ($atendimento["destino"] ?? "") === "Internamento" ? "selected" : "" ?>
                                >
                                    Internamento
                                </option>

                                <option
                                    value="Transferência"
                                    <?= ($atendimento["destino"] ?? "") === "Transferência" ? "selected" : "" ?>
                                >
                                    Transferência
                                </option>

                                <option
                                    value="Outro"
                                    <?= ($atendimento["destino"] ?? "") === "Outro" ? "selected" : "" ?>
                                >
                                    Outro
                                </option>

                            </select>

                        </div>


                        <div class="gestao-group">

                            <label for="estado">
                                Estado do atendimento
                            </label>

                            <select
                                name="estado"
                                id="estado"
                                required
                            >

                                <option
                                    value="Pendente"
                                    <?= ($atendimento["estado"] ?? "") === "Pendente" ? "selected" : "" ?>
                                >
                                    Pendente
                                </option>

                                <option
                                    value="Em triagem"
                                    <?= ($atendimento["estado"] ?? "") === "Em triagem" ? "selected" : "" ?>
                                >
                                    Em triagem
                                </option>

                                <option
                                    value="Em atendimento"
                                    <?= ($atendimento["estado"] ?? "") === "Em atendimento" ? "selected" : "" ?>
                                >
                                    Em atendimento
                                </option>

                                <option
                                    value="Concluído"
                                    <?= ($atendimento["estado"] ?? "") === "Concluído" ? "selected" : "" ?>
                                >
                                    Concluído
                                </option>

                            </select>

                        </div>


                    </div>


                    <div class="gestao-actions">

                        <button
                            type="submit"
                            class="gestao-save"
                        >
                            Guardar alterações
                        </button>


                        <a
                            href="atendimentos.php"
                            class="gestao-back"
                        >
                            Voltar
                        </a>

                    </div>

                </div>


            </form>


        <?php endif; ?>


    </main>

</div>

</body>

</html>
```
