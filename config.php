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

// Foto de perfil (ou um círculo com a inicial do nome, se não tiver foto)
function avatar($nome, $foto, $tam = 40) {
    $estilo = 'width:' . (int)$tam . 'px;height:' . (int)$tam . 'px;';
    if ($foto) {
        return '<img class="avatar" style="' . $estilo . '" src="uploads/fotos/' . e($foto)
             . '" alt="Foto de ' . e($nome) . '">';
    }
    $inicial = mb_strtoupper(mb_substr($nome, 0, 1));
    return '<span class="avatar" style="' . $estilo . 'font-size:' . round($tam * 0.45) . 'px">'
         . e($inicial) . '</span>';
}

// Desenha as estrelas de uma média (ex.: 3.6 -> ★★★★☆)
function estrelas($media) {
    $n = (int)round($media);
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

// Texto da avaliação: "★★★★☆ 4,0 (3 avaliações)"
function mostrar_nota($media, $qtd) {
    if ($qtd < 1) {
        return '<small>Sem avaliações</small>';
    }
    return '<span class="estrelas">' . estrelas($media) . '</span> '
         . number_format($media, 1, ',', '')
         . ' <small>(' . (int)$qtd . ' avaliaç' . ($qtd == 1 ? 'ão' : 'ões') . ')</small>';
}
