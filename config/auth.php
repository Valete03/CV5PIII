<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function exigir_login(): void {
    if (empty($_SESSION["utilizador_id"])) {
        header("Location: ../login.php");
        exit;
    }
}

function exigir_tipo(array $tipos): void {
    exigir_login();

    if (!in_array($_SESSION["tipo"] ?? "", $tipos, true)) {
        http_response_code(403);
        die("Acesso não autorizado.");
    }
}

function nome_utilizador(): string {
    return htmlspecialchars($_SESSION["nome"] ?? "Profissional", ENT_QUOTES, "UTF-8");
}

function prioridade_class(?string $prioridade): string {
    $mapa = [
        "Emergente" => "emergente",
        "Muito urgente" => "muito-urgente",
        "Urgente" => "urgente",
        "Pouco urgente" => "pouco-urgente",
        "Não urgente" => "nao-urgente"
    ];
    return $mapa[$prioridade] ?? "sem-prioridade";
}
?>
