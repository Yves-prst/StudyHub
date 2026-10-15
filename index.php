<?php
require 'config.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Criar matéria (SOMENTE moderador)
    if (isset($_POST['criar'])) {
        exigir_moderador();
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $erro = 'Digite o nome da matéria.';
        } else {
            $s = $pdo->prepare('SELECT id FROM categorias WHERE nome = ?');
            $s->execute([$nome]);
            if ($s->fetch()) {
                $erro = 'Essa matéria já existe.';
            } else {
                $s = $pdo->prepare('INSERT INTO categorias (nome, criado_por) VALUES (?, ?)');
                $s->execute([$nome, logado()['id']]);
            }
        }
    }
    // Apagar matéria (moderador)
    if (isset($_POST['apagar'])) {
        exigir_moderador();
        $s = $pdo->prepare('DELETE FROM categorias WHERE id = ?');
        $s->execute([(int)$_POST['id']]);
    }
    if ($erro === '') {
        header('Location: index.php');
        exit;
    }
}

$categorias = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM perguntas p WHERE p.categoria_id = c.id) AS total
     FROM categorias c ORDER BY c.nome'
)->fetchAll();

$titulo = 'Matérias';
include 'header.php';
?>
<h1>Matérias</h1>

<?php if (eh_moderador()): ?>
<div class="caixa">
    <h3>Criar nova matéria</h3>
    <?php if ($erro): ?><p class="erro"><?= e($erro) ?></p><?php endif; ?>
    <form method="post" class="linha">
        <input type="text" name="nome" maxlength="60" placeholder="Ex: Geografia" required>
        <button type="submit" name="criar">Criar</button>
    </form>
    <small>Dica: para deixar os usuários decidirem, crie uma <a href="enquetes.php">enquete</a>. Se for aprovada, a matéria é criada sozinha.</small>
</div>
<?php elseif (logado()): ?>
<p class="aviso">Só moderadores criam matérias. Para sugerir uma nova, fale com um moderador e participe das <a href="enquetes.php">enquetes</a>.</p>
<?php else: ?>
<p class="aviso">Você está como visitante: pode ver tudo, mas precisa <a href="login.php">entrar</a> para perguntar ou responder.</p>
<?php endif; ?>

<div class="grade">
    <?php foreach ($categorias as $c): ?>
    <div class="cartao">
        <a href="categoria.php?id=<?= $c['id'] ?>"><strong><?= e($c['nome']) ?></strong></a>
        <small><?= $c['total'] ?> pergunta(s)</small>
        <?php if (eh_moderador()): ?>
        <form method="post" data-confirmar="Apagar esta matéria e tudo dentro dela?">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button type="submit" name="apagar" class="perigo">Apagar</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php include 'footer.php'; ?>
