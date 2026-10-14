<?php
require 'config.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 4) {
        $erro = 'Preencha tudo corretamente (senha com no mínimo 4 caracteres).';
    } else {
        $s = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $s->execute([$email]);
        if ($s->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            // O primeiro usuário cadastrado vira moderador
            $total = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
            $tipo = ($total == 0) ? 'moderador' : 'normal';

            $s = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)');
            $s->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $tipo]);
            $_SESSION['uid'] = $pdo->lastInsertId();
            header('Location: index.php');
            exit;
        }
    }
}

$titulo = 'Cadastrar';
include 'header.php';
?>
<div class="caixa pequena">
    <h2>Criar conta</h2>
    <?php if ($erro): ?><p class="erro"><?= e($erro) ?></p><?php endif; ?>
    <form method="post">
        <label>Nome</label>
        <input type="text" name="nome" required>
        <label>E-mail</label>
        <input type="email" name="email" required>
        <label>Senha</label>
        <input type="password" name="senha" required>
        <button type="submit">Cadastrar</button>
    </form>
</div>
<?php include 'footer.php'; ?>
