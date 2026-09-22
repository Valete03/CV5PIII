<?php

require_once "config/database.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| DADOS DO PACIENTE
|--------------------------------------------------------------------------
*/

$nome = trim($_POST["nome"] ?? "");

$documento = trim($_POST["documento"] ?? "");

$contacto = trim($_POST["contacto"] ?? "");

$data = trim($_POST["data_nascimento"] ?? "");

$sexo = trim($_POST["sexo"] ?? "");


/*
|--------------------------------------------------------------------------
| DADOS DA SITUAÇÃO
|--------------------------------------------------------------------------
*/

$tipo_situacao = trim($_POST["tipo_situacao"] ?? "");

$sintomas = trim($_POST["sintomas"] ?? "");

$inicio_sintomas = trim($_POST["inicio_sintomas"] ?? "");

$tem_dor = trim($_POST["tem_dor"] ?? "");

$intensidade_dor = $_POST["intensidade_dor"] ?? null;

$gravidez = trim($_POST["gravidez"] ?? "Não aplicável");


/*
|--------------------------------------------------------------------------
| VALIDAÇÕES
|--------------------------------------------------------------------------
*/

if (
    !$nome ||
    !$documento ||
    !$contacto ||
    !$data ||
    !$sexo ||
    !$tipo_situacao ||
    !$sintomas ||
    !$inicio_sintomas ||
    !$tem_dor
) {

    die("
        Todos os campos obrigatórios devem ser preenchidos.
        <br><br>
        <a href='index.php'>Voltar</a>
    ");

}


if (!in_array($sexo, ["Masculino", "Feminino"], true)) {

    die("
        Sexo inválido.
        <br><br>
        <a href='index.php'>Voltar</a>
    ");

}


if (!in_array($tem_dor, ["Sim", "Não"], true)) {

    die("
        Informação sobre dor inválida.
        <br><br>
        <a href='index.php'>Voltar</a>
    ");

}


if ($tem_dor === "Sim") {

    if (
        $intensidade_dor === null ||
        $intensidade_dor === "" ||
        !is_numeric($intensidade_dor) ||
        $intensidade_dor < 0 ||
        $intensidade_dor > 10
    ) {

        die("
            Indique a intensidade da dor entre 0 e 10.
            <br><br>
            <a href='index.php'>Voltar</a>
        ");

    }

    $intensidade_dor = (int)$intensidade_dor;

} else {

    $intensidade_dor = null;

}


/*
|--------------------------------------------------------------------------
| VALIDAR GRAVIDEZ
|--------------------------------------------------------------------------
*/

$opcoes_gravidez = [
    "Sim",
    "Não",
    "Não sei",
    "Não aplicável"
];

if (!in_array($gravidez, $opcoes_gravidez, true)) {

    $gravidez = "Não aplicável";

}


/*
|--------------------------------------------------------------------------
| PROCURAR PACIENTE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id
    FROM pacientes
    WHERE documento = ?
    LIMIT 1
");

$stmt->bind_param("s", $documento);

$stmt->execute();

$res = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| PACIENTE EXISTENTE
|--------------------------------------------------------------------------
*/

if ($res->num_rows > 0) {

    $p = $res->fetch_assoc();

    $paciente_id = $p["id"];


    $stmt = $conn->prepare("
        UPDATE pacientes

        SET
            nome = ?,
            contacto = ?,
            data_nascimento = ?,
            sexo = ?

        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssi",
        $nome,
        $contacto,
        $data,
        $sexo,
        $paciente_id
    );

    $stmt->execute();


/*
|--------------------------------------------------------------------------
| NOVO PACIENTE
|--------------------------------------------------------------------------
*/

} else {

    $stmt = $conn->prepare("
        INSERT INTO pacientes
        (
            nome,
            documento,
            contacto,
            data_nascimento,
            sexo
        )

        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssss",
        $nome,
        $documento,
        $contacto,
        $data,
        $sexo
    );


    if (!$stmt->execute()) {

        die("
            Erro ao registar paciente:
            " . htmlspecialchars($stmt->error) . "
        ");

    }


    $paciente_id = $conn->insert_id;

}


/*
|--------------------------------------------------------------------------
| GERAR NÚMERO DE ATENDIMENTO
|--------------------------------------------------------------------------
*/

do {

    $numero =
        "A-" .
        date("Ymd") .
        "-" .
        random_int(100, 999);


    $stmt = $conn->prepare("
        SELECT id
        FROM atendimentos
        WHERE numero_atendimento = ?
        LIMIT 1
    ");


    $stmt->bind_param("s", $numero);

    $stmt->execute();

    $existe = $stmt->get_result()->num_rows > 0;

} while ($existe);


/*
|--------------------------------------------------------------------------
| CRIAR ATENDIMENTO
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO atendimentos
    (
        paciente_id,
        numero_atendimento,
        motivo,
        tipo_situacao,
        sintomas,
        inicio_sintomas,
        tem_dor,
        intensidade_dor,
        gravidez,
        estado
    )

    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aguardando triagem')
");


/*
|--------------------------------------------------------------------------
| O campo motivo continua preenchido
| para manter compatibilidade com o sistema antigo.
|--------------------------------------------------------------------------
*/

$motivo = $sintomas;


$stmt->bind_param(
    "issssssss",
    $paciente_id,
    $numero,
    $motivo,
    $tipo_situacao,
    $sintomas,
    $inicio_sintomas,
    $tem_dor,
    $intensidade_dor,
    $gravidez
);

if (!$stmt->execute()) {

    die("
        Erro ao criar atendimento:
        " . htmlspecialchars($stmt->error) . "
        <br><br>
        <a href='index.php'>Voltar</a>
    ");

}


/*
|--------------------------------------------------------------------------
| ENVIAR PARA PÁGINA DE CONFIRMAÇÃO
|--------------------------------------------------------------------------
*/

header(
    "Location: atendimento_criado.php?numero=" .
    urlencode($numero)
);

exit;

?>