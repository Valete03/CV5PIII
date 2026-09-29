
<?php
require_once "../config/database.php";
require_once "../config/auth.php";

exigir_tipo(["admin"]);

$paciente_id = (int)($_GET["id"] ?? 0);

$paciente = null;
$historico = null;


/* =========================================================
   VER FICHA DE UM PACIENTE
========================================================= */

if ($paciente_id > 0) {

    $id_seguro = (int)$paciente_id;

    /* Buscar dados do paciente */

    $resultado = $conn->query("
        SELECT *
        FROM pacientes
        WHERE id = $id_seguro
        LIMIT 1
    ");

    if ($resultado && $resultado->num_rows > 0) {

        $paciente = $resultado->fetch_assoc();

        /* Buscar histórico de atendimentos */

        $historico = $conn->query("
            SELECT *
            FROM atendimentos
            WHERE paciente_id = $id_seguro
            ORDER BY data_entrada DESC
        ");

    }
}


/* =========================================================
   LISTA DE PACIENTES
========================================================= */

if ($paciente_id <= 0) {

    $lista = $conn->query("
        SELECT *
        FROM pacientes
        ORDER BY data_registo DESC
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

    <title>Pacientes</title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

    <style>

        /* =====================================================
           ESTILOS DA FICHA DO PACIENTE
        ===================================================== */

        .paciente-info {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;

        }


        .paciente-info-item {

            padding: 12px 14px;

            background: #f8f9fa;

            border: 1px solid #e5e7eb;

            border-radius: 7px;

        }


        .paciente-info-item strong {

            display: block;

            font-size: 12px;

            color: #6b7280;

            margin-bottom: 5px;

        }


        .paciente-section-title {

            margin: 0 0 18px 0;

            font-size: 18px;

            font-weight: 700;

        }


        .paciente-ver {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 5px;

            background: #111827;

            color: #fff;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

        }


        .paciente-voltar {

            display: inline-block;

            padding: 10px 16px;

            border-radius: 6px;

            background: #e5e7eb;

            color: #111827;

            text-decoration: none;

            font-weight: 600;

            margin-bottom: 18px;

        }


        .historico-card {

            border: 1px solid #e5e7eb;

            border-radius: 8px;

            padding: 18px;

            margin-bottom: 15px;

            background: #fff;

        }


        .historico-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;

        }


        .historico-item strong {

            display: block;

            font-size: 12px;

            color: #6b7280;

            margin-bottom: 4px;

        }


        .historico-texto {

            padding: 12px;

            background: #f8f9fa;

            border-radius: 6px;

            margin-top: 12px;

        }


        .historico-texto strong {

            display: block;

            margin-bottom: 6px;

        }


        .sem-historico {

            padding: 15px;

            background: #f8f9fa;

            border-radius: 7px;

            color: #6b7280;

        }


        @media (max-width: 700px) {

            .paciente-info,
            .historico-grid {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body class="dashboard">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="topbar">

        <div class="title">

            Hospital de Mavalane

            <small>
                Pacientes
            </small>

        </div>


        <div>

            <?= htmlspecialchars(nome_utilizador()) ?>

            &nbsp;

            <a
                class="logout"
                href="../logout.php"
            >
                Sair
            </a>

        </div>

    </header>


    <div class="dash-wrap">


        <!-- =================================================
             SIDEBAR
        ================================================== -->

        <aside class="sidebar">

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="utilizadores.php">
                Profissionais
            </a>

            <a
                class="active"
                href="pacientes.php"
            >
                Pacientes
            </a>

            <a href="atendimentos.php">
                Atendimentos
            </a>

        </aside>


        <!-- =================================================
             CONTEÚDO
        ================================================== -->

        <main class="content">


            <?php if ($paciente_id <= 0): ?>


                <!-- =========================================
                     LISTA DE PACIENTES
                ========================================== -->

                <div class="page-title">

                    <h1>
                        Pacientes registados
                    </h1>

                </div>


                <div class="panel">

                    <div class="table-wrap">

                        <table>

                            <tr>

                                <th>
                                    Nome
                                </th>

                                <th>
                                    Documento
                                </th>

                                <th>
                                    Contacto
                                </th>

                                <th>
                                    Nascimento
                                </th>

                                <th>
                                    Sexo
                                </th>

                                <th>
                                    Acção
                                </th>

                            </tr>


                            <?php if ($lista && $lista->num_rows > 0): ?>

                                <?php while ($p = $lista->fetch_assoc()): ?>

                                    <tr>


                                        <td>

                                            <?= htmlspecialchars(
                                                $p["nome"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $p["documento"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $p["contacto"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= date(
                                                "d/m/Y",
                                                strtotime(
                                                    $p["data_nascimento"]
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $p["sexo"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <a
                                                class="paciente-ver"
                                                href="pacientes.php?id=<?= (int)$p["id"] ?>"
                                            >
                                                Ver ficha
                                            </a>

                                        </td>


                                    </tr>

                                <?php endwhile; ?>


                            <?php else: ?>


                                <tr>

                                    <td colspan="6">
                                        Não existem pacientes registados.
                                    </td>

                                </tr>


                            <?php endif; ?>


                        </table>

                    </div>

                </div>


            <?php elseif ($paciente): ?>


                <!-- =========================================
                     FICHA DO PACIENTE
                ========================================== -->

                <div class="page-title">

                    <h1>
                        Ficha do paciente
                    </h1>

                </div>


                <a
                    href="pacientes.php"
                    class="paciente-voltar"
                >
                    ← Voltar aos pacientes
                </a>


                <!-- =========================================
                     DADOS PESSOAIS
                ========================================== -->

                <div class="panel">

                    <h2 class="paciente-section-title">
                        Dados pessoais
                    </h2>


                    <div class="paciente-info">


                        <div class="paciente-info-item">

                            <strong>
                                Nome
                            </strong>

                            <?= htmlspecialchars(
                                $paciente["nome"]
                            ) ?>

                        </div>


                        <div class="paciente-info-item">

                            <strong>
                                Documento
                            </strong>

                            <?= htmlspecialchars(
                                $paciente["documento"]
                            ) ?>

                        </div>


                        <div class="paciente-info-item">

                            <strong>
                                Contacto
                            </strong>

                            <?= htmlspecialchars(
                                $paciente["contacto"]
                            ) ?>

                        </div>


                        <div class="paciente-info-item">

                            <strong>
                                Data de nascimento
                            </strong>

                            <?= date(
                                "d/m/Y",
                                strtotime(
                                    $paciente["data_nascimento"]
                                )
                            ) ?>

                        </div>


                        <div class="paciente-info-item">

                            <strong>
                                Sexo
                            </strong>

                            <?= htmlspecialchars(
                                $paciente["sexo"]
                            ) ?>

                        </div>


                        <div class="paciente-info-item">

                            <strong>
                                Data de registo
                            </strong>

                            <?= date(
                                "d/m/Y H:i",
                                strtotime(
                                    $paciente["data_registo"]
                                )
                            ) ?>

                        </div>


                    </div>

                </div>


                <!-- =========================================
                     HISTÓRICO MÉDICO
                ========================================== -->

                <div class="panel">

                    <h2 class="paciente-section-title">
                        Histórico de atendimentos
                    </h2>


                    <?php if ($historico && $historico->num_rows > 0): ?>


                        <?php while ($a = $historico->fetch_assoc()): ?>


                            <div class="historico-card">


                                <div class="historico-grid">


                                    <div class="historico-item">

                                        <strong>
                                            Número do atendimento
                                        </strong>

                                        <?= htmlspecialchars(
                                            $a["numero_atendimento"]
                                        ) ?>

                                    </div>


                                    <div class="historico-item">

                                        <strong>
                                            Data de entrada
                                        </strong>

                                        <?= date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $a["data_entrada"]
                                            )
                                        ) ?>

                                    </div>


                                    <div class="historico-item">

                                        <strong>
                                           Queixa principal
                                        </strong>

                                        <?= htmlspecialchars(
                                            $a["motivo"]
                                        ) ?>

                                    </div>


                                    <div class="historico-item">

                                        <strong>
                                            Tipo de situação
                                        </strong>

                                        <?= htmlspecialchars(
                                            $a["tipo_situacao"]
                                            ?: "Não definido"
                                        ) ?>

                                    </div>


                                    <div class="historico-item">

                                        <strong>
                                            Prioridade
                                        </strong>

                                        <?= htmlspecialchars(
                                            $a["prioridade"]
                                            ?: "Não definida"
                                        ) ?>

                                    </div>


                                    <div class="historico-item">

                                        <strong>
                                            Estado
                                        </strong>

                                        <?= htmlspecialchars(
                                            $a["estado"]
                                        ) ?>

                                    </div>


                                    <div class="historico-item">

                                        <strong>
                                            Sector de encaminhamento
                                        </strong>

                                        <?= htmlspecialchars(
                                            $a["destino"]
                                            ?: "Não definido"
                                        ) ?>

                                    </div>


                                    <div class="historico-item">

                                        <strong>
                                            Data de conclusão
                                        </strong>

                                        <?= !empty($a["data_conclusao"])
                                            ? date(
                                                "d/m/Y H:i",
                                                strtotime(
                                                    $a["data_conclusao"]
                                                )
                                            )
                                            : "Não concluído"
                                        ?>

                                    </div>


                                </div>


                                <!-- =================================
                                     SINTOMAS
                                ================================== -->

                                <div class="historico-texto">

                                    <strong>
                                        Sintomas
                                    </strong>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $a["sintomas"]
                                            ?: "Não informado"
                                        )
                                    ) ?>

                                </div>


                                <!-- =================================
                                     OBSERVAÇÃO DA TRIAGEM
                                ================================== -->

                                <div class="historico-texto">

                                    <strong>
                                        Observação da triagem
                                    </strong>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $a["observacao_triagem"]
                                            ?: "Não informado"
                                        )
                                    ) ?>

                                </div>


                                <!-- =================================
                                     DIAGNÓSTICO
                                ================================== -->

                                <div class="historico-texto">

                                    <strong>
                                        Diagnóstico
                                    </strong>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $a["diagnostico"]
                                            ?: "Não informado"
                                        )
                                    ) ?>

                                </div>


                                <!-- =================================
                                     DESTINO / RESULTADO
                                ================================== -->

                                <div class="historico-texto">

                                    <strong>
                                        Destino / resultado
                                    </strong>

                                    <?= htmlspecialchars(
                                        $a["destino"]
                                        ?: "Não informado"
                                    ) ?>

                                </div>


                            </div>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <div class="sem-historico">

                            Este paciente ainda não possui
                            atendimentos registados.

                        </div>


                    <?php endif; ?>


                </div>


            <?php else: ?>


                <div class="notice error">

                    Paciente não encontrado.

                </div>


            <?php endif; ?>


        </main>

    </div>


</body>

</html>
