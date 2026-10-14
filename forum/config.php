<?php
// Conexão com o banco e funções de apoio
session_start();

$pdo = new PDO(
    'mysql:host=localhost;dbname=forum_escolar;charset=utf8mb4',
    'root',   // usuário do MySQL
    '1234',       // senha do MySQL
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Escapa texto para exibir com segurança no HTML
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// Retorna o usuário logado (ou null se for visitante ou banido)
function logado() {
    global $pdo;
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $s = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $s->execute([$_SESSION['uid']]);
    $u = $s->fetch();
    if (!$u || $u['banido']) {
        unset($_SESSION['uid']);
        return null;
    }
    return $u;
}

function eh_moderador() {
    $u = logado();
    return $u && $u['tipo'] === 'moderador';
}

function exigir_login() {
    if (!logado()) {
        header('Location: login.php');
        exit;
    }
}

function exigir_moderador() {
    if (!eh_moderador()) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}
