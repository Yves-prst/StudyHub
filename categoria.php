<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);

$s = $pdo->prepare('SELECT * FROM categorias WHERE id = ?');
$s->execute([$id]);
$cat = $s->fetch();
if (!$cat) {
    http_response_code(404);
    exit('Matéria não encontrada.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fazer pergunta (usuário logado)
    if (isset($_POST['perguntar'])) {
        exigir_login();
        $t = trim($_POST['titulo'] ?? '');
        $x = trim($_POST['texto'] ?? '');
        if ($t !== '' && $x !== '') {
            $s = $pdo->prepare('INSERT INTO perguntas (categoria_id, usuario_id, titulo, texto) VALUES (?, ?, ?, ?)');
            $s->execute([$id, logado()['id'], $t, $x]);
        }
    }
    // Apagar pergunta (moderador)
    if (isset($_POST['apagar'])) {
        exigir_moderador();
        $s = $pdo->prepare('DELETE FROM perguntas WHERE id = ?');
        $s->execute([(int)$_POST['pergunta_id']]);
    }
    header('Location: categoria.php?id=' . $id);
    exit;
}

$s = $pdo->prepare(
    'SELECT p.*, u.nome, u.tipo, u.foto,
            (SELECT COUNT(*) FROM respostas r WHERE r.pergunta_id = p.id) AS total
     FROM perguntas p JOIN usuarios u ON u.id = p.usuario_id
     WHERE p.categoria_id = ? ORDER BY p.criado_em DESC'
);
$s->execute([$id]);
$perguntas = $s->fetchAll();

$titulo = $cat['nome'];
include 'header.php';
?>
<p><a href="index.php">← Todas as matérias</a></p>
<h1><?= e($cat['nome']) ?></h1>

<?php if (logado()): ?>
<div class="caixa">
    <h3>Fazer uma pergunta</h3>
    <form method="post">
        <input type="text" name="titulo" maxlength="150" placeholder="Título da pergunta" required>
        <textarea name="texto" rows="4" placeholder="Explique sua dúvida..." required></textarea>
        <button type="submit" name="perguntar">Perguntar</button>
    </form>
</div>
<?php else: ?>
<p class="aviso">Visitantes não podem perguntar. <a href="login.php">Entre</a> para participar.</p>
<?php endif; ?>

<?php if (!$perguntas): ?>
    <p>Ainda não há perguntas nesta matéria.</p>
<?php endif; ?>

<?php foreach ($perguntas as $p): ?>
<div class="caixa">
    <a href="pergunta.php?id=<?= $p['id'] ?>"><h3><?= e($p['titulo']) ?></h3></a>
    <small>
        <?= avatar($p['nome'], $p['foto'], 24) ?>
        por <a href="perfil.php?id=<?= $p['usuario_id'] ?>"><?= e($p['nome']) ?></a>
        <span class="tag <?= e($p['tipo']) ?>"><?= e($p['tipo']) ?></span>
        em <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?> · <?= $p['total'] ?> resposta(s)
    </small>
    <?php if (eh_moderador()): ?>
    <form method="post" data-confirmar="Apagar esta pergunta?">
        <input type="hidden" name="pergunta_id" value="<?= $p['id'] ?>">
        <button type="submit" name="apagar" class="perigo">Apagar</button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php include 'footer.php'; ?>
