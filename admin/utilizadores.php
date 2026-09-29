
<?php
require_once "../config/database.php";
require_once "../config/auth.php";

exigir_tipo(["admin"]);

$msg = "";
$erro = "";


/* =========================================================
   MENSAGEM APÓS CRIAÇÃO
========================================================= */

if (isset($_GET["criado"]) && $_GET["criado"] === "1") {
    $msg = "Profissional criado com sucesso.";
}


/* =========================================================
   CRIAR PROFISSIONAL
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $tipo = $_POST["tipo"] ?? "";


    if (
        !$nome ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($senha) < 6 ||
        !in_array($tipo, ["medico", "enfermeiro"], true)
    ) {

        $erro = "Preencha correctamente. A palavra-passe deve ter pelo menos 6 caracteres.";

    } else {

        $hash = password_hash(
            $senha,
            PASSWORD_DEFAULT
        );


        $stmt = $conn->prepare("
            INSERT INTO utilizadores(
                nome,
                email,
                senha,
                tipo
            )
            VALUES(?,?,?,?)
        ");


        $stmt->bind_param(
            "ssss",
            $nome,
            $email,
            $hash,
            $tipo
        );


        if ($stmt->execute()) {

            /*
             * Redirecciona depois de criar.
             * Assim os inputs ficam vazios.
             */
            header("Location: utilizadores.php?criado=1");
            exit;

        } else {

            $erro = "Não foi possível criar. O email pode já existir.";
        }
    }
}


/* =========================================================
   ACTIVAR / DESACTIVAR PROFISSIONAL
========================================================= */

if (isset($_GET["toggle"])) {

    $id = (int)$_GET["toggle"];


    $stmt = $conn->prepare("
        UPDATE utilizadores
        SET estado = IF(
            estado='ativo',
            'inativo',
            'ativo'
        )
        WHERE id=?
        AND tipo<>'admin'
    ");


    $stmt->bind_param(
        "i",
        $id
    );


    $stmt->execute();


    header("Location: utilizadores.php");
    exit;
}


/* =========================================================
   LISTAR PROFISSIONAIS
========================================================= */

$lista = $conn->query("
    SELECT
        id,
        nome,
        email,
        tipo,
        estado,
        data_criacao
    FROM utilizadores
    ORDER BY data_criacao DESC
");

?>

<!DOCTYPE html>
<html lang="pt">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>Profissionais</title>

    <link
        rel="stylesheet"
        href="../style.css"
    >

</head>


<body class="dashboard">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="topbar">

        <div class="title">

            Hospital de Mavalane

            <small>
                Gestão de profissionais
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

            <a
                class="active"
                href="utilizadores.php"
            >
                Profissionais
            </a>

            <a href="pacientes.php">
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


            <div class="page-title">

                <h1>
                    Profissionais
                </h1>

            </div>


            <!-- =================================================
                 MENSAGEM DE SUCESSO
            ================================================== -->

            <?php if ($msg): ?>

                <div class="notice">
                    <?= htmlspecialchars($msg) ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 MENSAGEM DE ERRO
            ================================================== -->

            <?php if ($erro): ?>

                <div class="notice error">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 CRIAR PROFISSIONAL
            ================================================== -->

            <div class="panel">

                <h3>
                    Criar médico ou enfermeiro
                </h3>


                <form
                    method="POST"
                    class="form-inline"
                >


                    <label>

                        Nome

                        <input
                            name="nome"
                            required
                        >

                    </label>


                    <label>

                        Email

                        <input
                            type="email"
                            name="email"
                            required
                        >

                    </label>


                    <label>

                        Palavra-passe

                        <input
                            type="password"
                            name="senha"
                            minlength="6"
                            required
                        >

                    </label>


                    <label>

                        Tipo

                        <select
                            name="tipo"
                            required
                        >

                            <option value="">
                                Seleccionar
                            </option>

                            <option value="medico">
                                Médico
                            </option>

                            <option value="enfermeiro">
                                Enfermeiro
                            </option>

                        </select>

                    </label>


                    <button
                        class="action"
                        type="submit"
                    >
                        Criar conta
                    </button>


                </form>

            </div>


            <!-- =================================================
                 CONTAS EXISTENTES
            ================================================== -->

            <div class="panel">

                <h3>
                    Contas existentes
                </h3>


                <div class="table-wrap">

                    <table>

                        <tr>

                            <th>
                                Nome
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Tipo
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acção
                            </th>

                        </tr>


                        <?php while ($u = $lista->fetch_assoc()): ?>

                            <tr>


                                <td>
                                    <?= htmlspecialchars($u["nome"]) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($u["email"]) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        ucfirst($u["tipo"])
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $u["estado"]
                                    ) ?>
                                </td>


                                <td>


                                    <?php if ($u["tipo"] !== "admin"): ?>


                                        <a
                                            class="action secondary"
                                            href="?toggle=<?= (int)$u["id"] ?>"
                                            onclick="return confirm('Tem a certeza que pretende activar/desactivar este profissional?');"
                                        >
                                            Activar/Desactivar
                                        </a>


                                    <?php else: ?>


                                        Administrador


                                    <?php endif; ?>


                                </td>


                            </tr>

                        <?php endwhile; ?>


                    </table>

                </div>

            </div>


        </main>

    </div>


</body>

</html>

