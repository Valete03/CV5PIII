<?php

require_once "config/database.php";

$numero = trim($_GET["numero"] ?? "");

$erro = "";
$atendimento = null;

if ($numero !== "") {

    $sql = "
        SELECT
            a.id,
            a.numero_atendimento,
            a.motivo,
            a.prioridade,
            a.estado,
            a.data_entrada,
            p.nome
        FROM atendimentos a
        INNER JOIN pacientes p
            ON p.id = a.paciente_id
        WHERE a.numero_atendimento = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro na consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $numero);

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $atendimento = $resultado->fetch_assoc();

    } else {

        $erro = "Não encontramos nenhum atendimento com este número.";

    }
}


/*
|--------------------------------------------------------------------------
| Classe da prioridade
|--------------------------------------------------------------------------
*/

function classePrioridade(?string $prioridade): string
{
    $classes = [
        "Emergente" => "emergente",
        "Muito urgente" => "muito-urgente",
        "Urgente" => "urgente",
        "Pouco urgente" => "pouco-urgente",
        "Não urgente" => "nao-urgente"
    ];

    return $classes[$prioridade] ?? "sem-prioridade";
}


/*
|--------------------------------------------------------------------------
| Ícone do estado
|--------------------------------------------------------------------------
*/

function iconeEstado(string $estado): string
{
    return match ($estado) {

        "Aguardando triagem" => "🩺",

        "Em triagem" => "🩺",

        "Aguardando médico" => "👨‍⚕️",

        "Em atendimento" => "🏥",

        "Concluído" => "✓",

        default => "•"
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
        Consultar Atendimento | Hospital de Mavalane
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>

<div class="consulta-page">

    <div class="consulta-container">


        <a
            href="index.php"
            class="back-link"
        >
            ← Voltar ao início
        </a>


        <div class="consulta-header">

            <div class="hospital-icon">
                🏥
            </div>

            <span>
                HOSPITAL DE MAVALANE
            </span>

            <h1>
                Acompanhe o seu atendimento
            </h1>

            <p>
                Introduza o número de atendimento recebido
                no momento do registo.
            </p>

        </div>


        <?php if (!$atendimento): ?>


            <!-- FORMULÁRIO DE CONSULTA -->

            <div class="consulta-card">

                <h2>
                    Consultar atendimento
                </h2>

                <p class="consulta-description">

                    Digite o seu número para verificar
                    o estado do seu atendimento.

                </p>


                <?php if ($erro): ?>

                    <div class="consulta-error">

                        ⚠️

                        <?= htmlspecialchars($erro) ?>

                    </div>

                <?php endif; ?>


                <form
                    method="GET"
                    action="consultar_atendimento.php"
                >

                    <label for="numero">

                        Número de atendimento

                    </label>


                    <input
                        type="text"
                        id="numero"
                        name="numero"
                        placeholder="Ex.: A-20260915-141"
                        value="<?= htmlspecialchars($numero) ?>"
                        required
                    >


                    <button
                        type="submit"
                        class="btn-primary"
                    >

                        🔍 Consultar atendimento

                    </button>

                </form>


                <div class="consulta-help">

                    <strong>
                        Onde encontro o meu número?
                    </strong>

                    <p>

                        O número foi apresentado após
                        o registo do seu atendimento.
                        Guarde-o para acompanhar o estado
                        da sua triagem.

                    </p>

                </div>

            </div>


        <?php else: ?>


            <!-- RESULTADO -->

            <?php

                $estado = $atendimento["estado"];

                $prioridade = $atendimento["prioridade"];

            ?>


            <div class="resultado-atendimento">


                <!-- NÚMERO -->

                <div class="resultado-top">

                    <span class="resultado-label">

                        NÚMERO DE ATENDIMENTO

                    </span>


                    <strong class="numero-atendimento">

                        <?= htmlspecialchars(
                            $atendimento["numero_atendimento"]
                        ) ?>

                    </strong>

                </div>


                <!-- PACIENTE -->

                <div class="paciente-info">

                    <span>
                        Paciente
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $atendimento["nome"]
                        ) ?>

                    </strong>

                </div>


                <!-- PRIORIDADE -->

                <?php if ($prioridade): ?>


                    <div
                        class="prioridade-box
                        <?= classePrioridade($prioridade) ?>"
                    >

                        <span>
                            PRIORIDADE DA TRIAGEM
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $prioridade
                            ) ?>

                        </strong>

                        <small>

                            Prioridade definida pela
                            equipa de enfermagem.

                        </small>

                    </div>


                <?php else: ?>


                    <div class="prioridade-box sem-prioridade">

                        <span>
                            PRIORIDADE
                        </span>

                        <strong>
                            Aguardando triagem
                        </strong>

                        <small>

                            A equipa de enfermagem
                            ainda irá realizar a sua avaliação.

                        </small>

                    </div>


                <?php endif; ?>


                <!-- ESTADO -->

                <div class="estado-box">

                    <span>
                        ESTADO ACTUAL
                    </span>

                    <strong>

                        <?= iconeEstado($estado) ?>

                        <?= htmlspecialchars($estado) ?>

                    </strong>

                </div>


                <!-- LINHA DO TEMPO -->

                <div class="timeline">


                    <!-- 1 -->

                    <div class="timeline-item concluido">

                        <div class="timeline-icon">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Atendimento registado
                            </strong>

                            <span>
                                Os seus dados foram registados.
                            </span>

                        </div>

                    </div>


                    <!-- 2 -->

                    <div
                        class="timeline-item
                        <?= $prioridade
                            ? 'concluido'
                            : 'actual' ?>"
                    >

                        <div class="timeline-icon">

                            <?= $prioridade
                                ? '✓'
                                : '2' ?>

                        </div>

                        <div>

                            <strong>
                                Triagem
                            </strong>

                            <span>

                                <?= $prioridade

                                    ? "Triagem realizada pela equipa de enfermagem."

                                    : "Aguardando avaliação da enfermagem."
                                ?>

                            </span>

                        </div>

                    </div>


                    <!-- 3 -->

                    <div
                        class="timeline-item
                        <?= in_array(
                            $estado,
                            [
                                "Aguardando médico",
                                "Em atendimento",
                                "Concluído"
                            ]
                        )
                            ? 'concluido'
                            : '' ?>

                        <?= $estado === "Aguardando médico"
                            ? 'actual'
                            : '' ?>"
                    >

                        <div class="timeline-icon">
                            3
                        </div>

                        <div>

                            <strong>
                                Aguardando médico
                            </strong>

                            <span>

                                <?php

                                if ($estado === "Aguardando médico") {

                                    echo "A sua triagem foi concluída. Aguarde a chamada.";

                                } else {

                                    echo "Após a triagem, o atendimento médico será iniciado.";

                                }

                                ?>

                            </span>

                        </div>

                    </div>


                    <!-- 4 -->

                    <div
                        class="timeline-item
                        <?= in_array(
                            $estado,
                            [
                                "Em atendimento",
                                "Concluído"
                            ]
                        )
                            ? 'concluido'
                            : '' ?>

                        <?= $estado === "Em atendimento"
                            ? 'actual'
                            : '' ?>"
                    >

                        <div class="timeline-icon">
                            4
                        </div>

                        <div>

                            <strong>
                                Em atendimento
                            </strong>

                            <span>

                                <?php

                                if ($estado === "Em atendimento") {

                                    echo "O seu atendimento médico está em curso.";

                                } else {

                                    echo "Aguardando início do atendimento médico.";

                                }

                                ?>

                            </span>

                        </div>

                    </div>


                    <!-- 5 -->

                    <div
                        class="timeline-item
                        <?= $estado === "Concluído"
                            ? 'concluido actual'
                            : '' ?>"
                    >

                        <div class="timeline-icon">
                            5
                        </div>

                        <div>

                            <strong>
                                Concluído
                            </strong>

                            <span>

                                <?php

                                if ($estado === "Concluído") {

                                    echo "O atendimento foi concluído.";

                                } else {

                                    echo "O atendimento ainda não foi concluído.";

                                }

                                ?>

                            </span>

                        </div>

                    </div>


                </div>


                <!-- ACTUALIZAÇÃO -->

                <div class="actualizacao">

                    🔄

                    Para consultar o estado mais recente,
                    actualize esta página.

                </div>


                <a
                    href="consultar_atendimento.php?numero=<?= urlencode(
                        $atendimento["numero_atendimento"]
                    ) ?>"
                    class="btn-primary"
                >

                    🔄 Actualizar estado

                </a>


            </div>


        <?php endif; ?>


        <div class="consulta-footer">

            <a href="index.php">
                ← Voltar ao início
            </a>

            <span>
                Hospital de Mavalane • Serviço de Urgência
            </span>

        </div>


    </div>

</div>

</body>

</html>