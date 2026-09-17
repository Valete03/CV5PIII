<?php

require_once "../config/database.php";
require_once "../config/auth.php";

exigir_tipo(["enfermeiro"]);

$mensagem = "";
$erro = "";

/*
|--------------------------------------------------------------------------
| REGISTAR TRIAGEM
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $atendimento_id = (int)($_POST["atendimento_id"] ?? 0);
    $prioridade = trim($_POST["prioridade"] ?? "");

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

        $stmt = $conn->prepare("
            UPDATE atendimentos
            SET prioridade = ?,
                estado = 'Aguardando médico'
            WHERE id = ?
            AND estado = 'Aguardando triagem'
        ");

        if (!$stmt) {

            $erro = "Erro ao preparar a triagem: " . $conn->error;

        } else {

            $stmt->bind_param(
                "si",
                $prioridade,
                $atendimento_id
            );

            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {

                    $mensagem = "Triagem registada com sucesso.";

                } else {

                    $erro = "Este atendimento já foi triado ou não existe.";

                }

            } else {

                $erro = "Erro ao guardar a triagem: " . $stmt->error;

            }

            $stmt->close();
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
    WHERE a.estado = 'Aguardando triagem'
    ORDER BY a.data_entrada ASC
";

$resultado = $conn->query($sql);


/*
|--------------------------------------------------------------------------
| CONTADORES
|--------------------------------------------------------------------------
*/

$total_aguardar = 0;
$total_triadas = 0;

$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM atendimentos
    WHERE estado = 'Aguardando triagem'
");

if ($res) {
    $total_aguardar = (int)$res->fetch_assoc()["total"];
}

$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM atendimentos
    WHERE prioridade IS NOT NULL
");

if ($res) {
    $total_triadas = (int)$res->fetch_assoc()["total"];
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
        Triagem | Hospital de Mavalane
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
                🩺
                <span>Triagem</span>
            </a>

            <a href="../index.php">
                🏠
                <span>Página inicial</span>
            </a>

        </nav>


        <div class="sidebar-user">

            <div class="user-avatar">
                👩‍⚕️
            </div>

            <div>

                <strong>
                    <?= nome_utilizador() ?>
                </strong>

                <span>
                    Enfermeiro
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
         CONTEÚDO
    ====================================================== -->

    <main class="dashboard-main">


        <header class="dashboard-header">

            <div>

                <span class="dashboard-eyebrow">
                    PAINEL DE ENFERMAGEM
                </span>

                <h1>
                    Triagem de Urgência
                </h1>

                <p>
                    Avalie os pacientes e defina a prioridade
                    de atendimento.
                </p>

            </div>

        </header>


        <!-- =================================================
             MENSAGENS
        ================================================== -->

        <?php if ($mensagem): ?>

            <div class="alert-success">

                ✓

                <?= htmlspecialchars($mensagem) ?>

            </div>

        <?php endif; ?>


        <?php if ($erro): ?>

            <div class="alert-error">

                ⚠️

                <?= htmlspecialchars($erro) ?>

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
                        Aguardando triagem
                    </span>

                    <strong>
                        <?= $total_aguardar ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✓
                </div>

                <div>

                    <span>
                        Atendimentos triados
                    </span>

                    <strong>
                        <?= $total_triadas ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- =================================================
             FILA
        ================================================== -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <h2>
                        Pacientes aguardando triagem
                    </h2>

                    <p>
                        Os pacientes são apresentados pela ordem
                        de chegada.
                    </p>

                </div>

                <span class="queue-count">
                    <?= $total_aguardar ?>
                    aguardando
                </span>

            </div>


            <?php if ($resultado && $resultado->num_rows > 0): ?>


                <div class="triagem-list">


                    <?php while ($paciente = $resultado->fetch_assoc()): ?>


                        <article class="triagem-card">


                            <!-- CABEÇALHO -->

                            <div class="triagem-card-header">

                                <div>

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


                                <span class="status-badge waiting">
                                    Aguardando triagem
                                </span>

                            </div>


                            <!-- INFORMAÇÕES -->

                            <div class="patient-details">


                                <div>

                                    <span>
                                        Documento
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $paciente["documento"]
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
                                        Sexo
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $paciente["sexo"]
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Motivo da visita
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $paciente["motivo"]
                                        ) ?>
                                    </strong>

                                </div>


                            </div>


                            <!-- FORMULÁRIO DE TRIAGEM -->

                            <div class="triagem-action">

                                <div>

                                    <h4>
                                        Definir prioridade
                                    </h4>

                                    <p>
                                        A prioridade deve ser atribuída
                                        após a avaliação clínica.
                                    </p>

                                </div>


                                <form
                                    method="POST"
                                    class="triagem-form"
                                >

                                    <input
                                        type="hidden"
                                        name="atendimento_id"
                                        value="<?= (int)$paciente["id"] ?>"
                                    >


                                    <select
                                        name="prioridade"
                                        required
                                    >

                                        <option value="">
                                            Seleccionar prioridade
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


                                    <button
                                        type="submit"
                                        class="btn-triagem"
                                    >
                                        ✓ Registar triagem
                                    </button>

                                </form>

                            </div>


                        </article>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        ✓
                    </div>

                    <h3>
                        Nenhum paciente aguardando triagem
                    </h3>

                    <p>
                        Todos os atendimentos foram avaliados.
                    </p>

                </div>


            <?php endif; ?>


        </section>


    </main>

</div>


</body>

</html>