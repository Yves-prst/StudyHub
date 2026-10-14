<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);

$s = $pdo->prepare(
    'SELECT p.*, u.nome, u.tipo, c.nome AS categoria
     FROM perguntas p
     JOIN usuarios u ON u.id = p.usuario_id
     JOIN categorias c ON c.id = p.categoria_id
     WHERE p.id = ?'
);
$s->execute([$id]);
$p = $s->fetch();
if (!$p) {
    http_response_code(404);
    exit('Pergunta não encontrada.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Responder (usuário logado: normal, especialista ou moderador)
    if (isset($_POST['responder'])) {
        exigir_login();
        $texto = trim($_POST['texto'] ?? '');
        if ($texto !== '') {
            $s = $pdo->prepare('INSERT INTO respostas (pergunta_id, usuario_id, texto) VALUES (?, ?, ?)');
            $s->execute([$id, logado()['id'], $texto]);
        }
    }
    // Apagar resposta (moderador)
    if (isset($_POST['apagar'])) {
        exigir_moderador();
        $s = $pdo->prepare('DELETE FROM respostas WHERE id = ?');
        $s->execute([(int)$_POST['resposta_id']]);
    }
    header('Location: pergunta.php?id=' . $id);
    exit;
}

// Respostas de especialistas aparecem primeiro
$s = $pdo->prepare(
    "SELECT r.*, u.nome, u.tipo FROM respostas r
     JOIN usuarios u ON u.id = r.usuario_id
     WHERE r.pergunta_id = ?
     ORDER BY (u.tipo = 'especialista') DESC, r.criado_em ASC"
);
$s->execute([$id]);
$respostas = $s->fetchAll();

$titulo = $p['titulo'];
include 'header.php';
?>
<p><a href="categoria.php?id=<?= $p['categoria_id'] ?>">← <?= e($p['categoria']) ?></a></p>

<div class="caixa">
    <h1><?= e($p['titulo']) ?></h1>
    <small>
        por <?= e($p['nome']) ?> <span class="tag <?= e($p['tipo']) ?>"><?= e($p['tipo']) ?></span>
        em <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?>
    </small>
    <p><?= nl2br(e($p['texto'])) ?></p>
</div>

<h2><?= count($respostas) ?> resposta(s)</h2>

<?php foreach ($respostas as $r): ?>
<div class="caixa <?= $r['tipo'] === 'especialista' ? 'destaque' : '' ?>">
    <?php if ($r['tipo'] === 'especialista'): ?>
        <div class="selo">⭐ Resposta de especialista</div>
    <?php endif; ?>
    <small>
        <?= e($r['nome']) ?> <span class="tag <?= e($r['tipo']) ?>"><?= e($r['tipo']) ?></span>
        em <?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?>
    </small>
    <p><?= nl2br(e($r['texto'])) ?></p>
    <?php if (eh_moderador()): ?>
    <form method="post" data-confirmar="Apagar esta resposta?">
        <input type="hidden" name="resposta_id" value="<?= $r['id'] ?>">
        <button type="submit" name="apagar" class="perigo">Apagar</button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (logado()): ?>
<div class="caixa">
    <h3>Sua resposta</h3>
    <form method="post">
        <textarea name="texto" rows="4" placeholder="Escreva sua resposta..." required></textarea>
        <button type="submit" name="responder">Responder</button>
    </form>
</div>
<?php else: ?>
<p class="aviso">Visitantes não podem responder. <a href="login.php">Entre</a> para participar.</p>
<?php endif; ?>
<?php include 'footer.php'; ?>
