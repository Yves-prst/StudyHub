<?php $usuario = logado(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Fórum Escolar') ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
<header class="topo">
    <a class="logo" href="index.php">StudyHub</a>
    <nav>
        <a href="index.php">Matérias</a>
        <a href="enquetes.php">Enquetes</a>
        <?php if (eh_moderador()): ?>
            <a href="admin.php">Painel</a>
        <?php endif; ?>
        <?php if ($usuario): ?>
            <a href="perfil.php">Meu perfil</a>
            <span class="quem"><?= avatar($usuario['nome'], $usuario['foto'], 28) ?> <?= e($usuario['nome']) ?> <small class="tag <?= e($usuario['tipo']) ?>"><?= e($usuario['tipo']) ?></small></span>
            <a href="logout.php">Sair</a>
        <?php else: ?>
            <span class="quem">Visitante</span>
            <a href="login.php">Entrar</a>
            <a href="registro.php">Cadastrar</a>
        <?php endif; ?>
    </nav>
</header>
<main class="conteudo">
