<?php

require_once "../config/database.php";
require_once "../config/auth.php";

exigir_tipo(["medico"]);

$mensagem = "";
$erro = "";

$medico_id = (int)$_SESSION["utilizador_id"];

/*
|--------------------------------------------------------------------------
| INICIAR ATENDIMENTO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $acao = $_POST["acao"] ?? "";
    $atendimento_id = (int)($_POST["atendimento_id"] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | INICIAR
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
                WHERE id = ?
                AND estado = 'Aguardando médico'
                AND prioridade IS NOT NULL
            ");

            if (!$stmt) {

                $erro = "Erro ao preparar o atendimento: " . $conn->error;

            } else {

                $stmt->bind_param(
                    "ii",
                    $medico_id,
                    $atendimento_id
                );

                $stmt->execute();

                if ($stmt->affected_rows > 0) {

                    $mensagem = "Atendimento iniciado com sucesso.";

                } else {

                    $erro = "Este atendimento já foi iniciado por outro profissional ou não está disponível.";

                }

                $stmt->close();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CONCLUIR
    |--------------------------------------------------------------------------
    */

    if ($acao === "concluir") {

        $diagnostico = trim($_POST["diagnostico"] ?? "");
        $destino = trim($_POST["destino"] ?? "");

        $destinos_validos = [
            "Alta",
            "Internamento",
            "Transferência",
            "Óbito"
        ];

        if ($atendimento_id <= 0) {

            $erro = "Atendimento inválido.";

        } elseif ($diagnostico === "") {

            $erro = "Introduza o diagnóstico.";

        } elseif (!in_array($destino, $destinos_validos, true)) {

            $erro = "Seleccione um destino válido.";

        } else {

            $stmt = $conn->prepare("
                UPDATE atendimentos
                SET
                    estado = 'Concluído',
                    diagnostico = ?,
                    destino = ?,
                    data_conclusao = NOW()
                WHERE id = ?
                AND profissional_medico_id = ?
                AND estado = 'Em atendimento'
            ");

            if (!$stmt) {

                $erro = "Erro ao preparar a conclusão: " . $conn->error;

            } else {

                $stmt->bind_param(
                    "ssii",
                    $diagnostico,
                    $destino,
                    $atendimento_id,
                    $medico_id
                );

                if ($stmt->execute()) {

                    if ($stmt->affected_rows > 0) {

                        $mensagem = "Atendimento concluído com sucesso.";

                    } else {

                        $erro = "Não foi possível concluir este atendimento.";

                    }

                } else {

                    $erro = "Erro ao concluir atendimento: " . $stmt->error;

                }

                $stmt->close();
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| PACIENTE ACTUAL DO MÉDICO
|--------------------------------------------------------------------------
*/

$paciente_actual = null;

$stmt = $conn->prepare("
    SELECT
        a.id,
        a.numero_atendimento,
        a.motivo,
        a.prioridade,
        a.estado,
        a.data_entrada,
        a.data_inicio_atendimento,
        p.nome,
        p.documento,
        p.contacto,
        p.data_nascimento,
        p.sexo
    FROM atendimentos a
    INNER JOIN pacientes p
        ON p.id = a.paciente_id
    WHERE a.estado = 'Em atendimento'
    AND a.profissional_medico_id = ?
    ORDER BY a.data_inicio_atendimento DESC
    LIMIT 1
");

$stmt->bind_param("i", $medico_id);
$stmt->execute();

$res = $stmt->get_result();

if ($res->num_rows === 1) {
    $paciente_actual = $res->fetch_assoc();
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| FILA MÉDICA
|--------------------------------------------------------------------------
|
| Ordem:
| Emergente
| Muito urgente
| Urgente
| Pouco urgente
| Não urgente
|
| Dentro da mesma prioridade:
| quem chegou primeiro
|--------------------------------------------------------------------------
*/

$fila = [];

$sql = "
    SELECT
        a.id,
        a.numero_atendimento,
        a.motivo,
        a.prioridade,
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
    WHERE a.estado = 'Aguardando médico'
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

$res = $conn->query($sql);

if ($res) {

    while ($row = $res->fetch_assoc()) {
        $fila[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| CONTADORES
|--------------------------------------------------------------------------
*/

$aguardando = count($fila);

$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM atendimentos
    WHERE estado = 'Em atendimento'
");

$em_atendimento = $res
    ? (int)$res->fetch_assoc()["total"]
    : 0;


$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM atendimentos
    WHERE estado = 'Concluído'
");

$concluidos = $res
    ? (int)$res->fetch_assoc()["total"]
    : 0;


/*
|--------------------------------------------------------------------------
| CLASSE DA PRIORIDADE
|--------------------------------------------------------------------------
*/

function classePrioridade(?string $prioridade): string
{
    return match ($prioridade) {

        "Emergente" => "emergente",

        "Muito urgente" => "muito-urgente",

        "Urgente" => "urgente",

        "Pouco urgente" => "pouco-urgente",

        "Não urgente" => "nao-urgente",

        default => "sem-prioridade"
    };
}

?>

<!DOCTYPE html>

<html lang="pt">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Painel Médico | Hospital de Mavalane
    </title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

</head>


<body class="dashboard-body">


<div class="dashboard-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="dashboard-sidebar">

        <div class="sidebar-brand">

            <div class="sidebar-logo">
                🏥
            </div>

            <div>

                <strong>
                    Hospital de Mavalane
                </strong>

                <span>
                    Serviço de Urgência
                </span>

            </div>

        </div>


        <nav class="sidebar-nav">

            <a
                href="dashboard.php"
                class="active"
            >
                👨‍⚕️
                <span>Atendimento</span>
            </a>

            <a href="../index.php">
                🏠
                <span>Página inicial</span>
            </a>

        </nav>


        <div class="sidebar-user">

            <div class="user-avatar">
                👨‍⚕️
            </div>

            <div>

                <strong>
                    <?= nome_utilizador() ?>
                </strong>

                <span>
                    Médico
                </span>

            </div>

        </div>


        <a
            href="../logout.php"
            class="sidebar-logout"
        >
            🚪 Sair
        </a>

    </aside>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="dashboard-main">


        <header class="dashboard-header">

            <div>

                <span class="dashboard-eyebrow">
                    PAINEL MÉDICO
                </span>

                <h1>
                    Atendimento de Urgência
                </h1>

                <p>
                    A fila é organizada automaticamente de acordo
                    com a prioridade definida na triagem.
                </p>

            </div>


            <a
                href="dashboard.php"
                class="refresh-button"
            >
                🔄 Actualizar
            </a>

        </header>


        <!-- =================================================
             MENSAGENS
        ================================================== -->

        <?php if ($mensagem): ?>

            <div class="alert-success">
                ✓ <?= htmlspecialchars($mensagem) ?>
            </div>

        <?php endif; ?>


        <?php if ($erro): ?>

            <div class="alert-error">
                ⚠️ <?= htmlspecialchars($erro) ?>
            </div>

        <?php endif; ?>


        <!-- =================================================
             ESTATÍSTICAS
        ================================================== -->

        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon">
                    🕐
                </div>

                <div>

                    <span>
                        Aguardando atendimento
                    </span>

                    <strong>
                        <?= $aguardando ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    👨‍⚕️
                </div>

                <div>

                    <span>
                        Em atendimento
                    </span>

                    <strong>
                        <?= $em_atendimento ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✓
                </div>

                <div>

                    <span>
                        Concluídos
                    </span>

                    <strong>
                        <?= $concluidos ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- =================================================
             PACIENTE ACTUAL
        ================================================== -->

        <?php if ($paciente_actual): ?>

            <section class="current-patient">

                <div class="current-patient-header">

                    <div>

                        <span class="dashboard-eyebrow">
                            ATENDIMENTO EM CURSO
                        </span>

                        <h2>
                            Paciente em atendimento
                        </h2>

                    </div>

                    <span
                        class="priority-badge
                        <?= classePrioridade(
                            $paciente_actual["prioridade"]
                        ) ?>"
                    >

                        <?= htmlspecialchars(
                            $paciente_actual["prioridade"]
                        ) ?>

                    </span>

                </div>


                <div class="current-patient-grid">


                    <div>

                        <span>
                            Número de atendimento
                        </span>

                        <strong class="patient-number">

                            <?= htmlspecialchars(
                                $paciente_actual["numero_atendimento"]
                            ) ?>

                        </strong>

                    </div>


                    <div>

                        <span>
                            Paciente
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $paciente_actual["nome"]
                            ) ?>

                        </strong>

                    </div>


                    <div>

                        <span>
                            Documento
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $paciente_actual["documento"]
                            ) ?>

                        </strong>

                    </div>


                    <div>

                        <span>
                            Contacto
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $paciente_actual["contacto"]
                            ) ?>

                        </strong>

                    </div>


                </div>


                <div class="motivo-medico">

                    <span>
                        MOTIVO DA VISITA
                    </span>

                    <p>

                        <?= htmlspecialchars(
                            $paciente_actual["motivo"]
                        ) ?>

                    </p>

                </div>


                <form
                    method="POST"
                    class="concluir-form"
                >

                    <input
                        type="hidden"
                        name="acao"
                        value="concluir"
                    >

                    <input
                        type="hidden"
                        name="atendimento_id"
                        value="<?= (int)$paciente_actual["id"] ?>"
                    >


                    <div>

                        <label for="diagnostico">
                            Diagnóstico
                        </label>

                        <textarea
                            id="diagnostico"
                            name="diagnostico"
                            rows="4"
                            placeholder="Introduza o diagnóstico do paciente..."
                            required
                        ></textarea>

                    </div>


                    <div>

                        <label for="destino">
                            Destino do paciente
                        </label>

                        <select
                            id="destino"
                            name="destino"
                            required
                        >

                            <option value="">
                                Seleccionar destino
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

                    </div>


                    <button
                        type="submit"
                        class="btn-concluir"
                    >

                        ✓ Concluir atendimento

                    </button>

                </form>

            </section>

        <?php endif; ?>


        <!-- =================================================
             FILA
        ================================================== -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <h2>
                        Próximos pacientes
                    </h2>

                    <p>
                        A ordem abaixo é determinada pela prioridade
                        da triagem e, em caso de empate, pela hora de chegada.
                    </p>

                </div>


                <span class="queue-count">

                    <?= $aguardando ?>

                    aguardando

                </span>

            </div>


            <?php if (count($fila) > 0): ?>


                <div class="doctor-queue">


                    <?php foreach ($fila as $index => $paciente): ?>

                        <?php

                        $posicao = $index + 1;

                        ?>


                        <article class="doctor-card">


                            <div class="doctor-card-top">


                                <div class="queue-position">

                                    <span>
                                        POSIÇÃO
                                    </span>

                                    <strong>
                                        #<?= $posicao ?>
                                    </strong>

                                </div>


                                <div class="doctor-patient-main">

                                    <span class="triagem-number">

                                        <?= htmlspecialchars(
                                            $paciente["numero_atendimento"]
                                        ) ?>

                                    </span>

                                    <h3>

                                        <?= htmlspecialchars(
                                            $paciente["nome"]
                                        ) ?>

                                    </h3>

                                </div>


                                <span
                                    class="priority-badge
                                    <?= classePrioridade(
                                        $paciente["prioridade"]
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $paciente["prioridade"]
                                    ) ?>

                                </span>


                            </div>


                            <div class="doctor-card-info">


                                <div>

                                    <span>
                                        Motivo
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $paciente["motivo"]
                                        ) ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Contacto
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $paciente["contacto"]
                                        ) ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Entrada
                                    </span>

                                    <strong>

                                        <?= date(
                                            "H:i",
                                            strtotime(
                                                $paciente["data_entrada"]
                                            )
                                        ) ?>

                                    </strong>

                                </div>


                            </div>


                            <div class="doctor-card-action">


                                <?php if ($posicao === 1): ?>

                                    <div class="next-patient">

                                        ⭐

                                        <strong>
                                            Próximo paciente
                                        </strong>

                                        <span>
                                            Este é o paciente prioritário
                                            da fila neste momento.
                                        </span>

                                    </div>

                                <?php endif; ?>


                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="acao"
                                        value="iniciar"
                                    >

                                    <input
                                        type="hidden"
                                        name="atendimento_id"
                                        value="<?= (int)$paciente["id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-iniciar"
                                    >

                                        👨‍⚕️ Iniciar atendimento

                                    </button>

                                </form>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        ✓
                    </div>

                    <h3>
                        Não existem pacientes na fila
                    </h3>

                    <p>
                        Os pacientes aparecerão aqui depois de
                        concluírem a triagem.
                    </p>

                </div>


            <?php endif; ?>


        </section>


    </main>

</div>

</body>

</html>