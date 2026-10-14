<?php
require 'config.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $s->execute([trim($_POST['email'] ?? '')]);
    $u = $s->fetch();

    if (!$u || !password_verify($_POST['senha'] ?? '', $u['senha'])) {
        $erro = 'E-mail ou senha incorretos.';
    } elseif ($u['banido']) {
        $erro = 'Sua conta foi banida.';
    } else {
        $_SESSION['uid'] = $u['id'];
        header('Location: index.php');
        exit;
    }
}

$titulo = 'Entrar';
include 'header.php';
?>
<div class="caixa pequena">
    <h2>Entrar</h2>
    <?php if ($erro): ?><p class="erro"><?= e($erro) ?></p><?php endif; ?>
    <form method="post">
        <label>E-mail</label>
        <input type="email" name="email" required>
        <label>Senha</label>
        <input type="password" name="senha" required>
        <button type="submit">Entrar</button>
    </form>
    <p>Sem conta? <a href="registro.php">Cadastre-se</a> ou continue como visitante.</p>
</div>
<?php include 'footer.php'; ?>
