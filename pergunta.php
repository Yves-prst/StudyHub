<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
$eu = logado();

$s = $pdo->prepare(
    'SELECT p.*, u.nome, u.tipo, u.foto, c.nome AS categoria
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
    $ancora = '';

    // Responder a pergunta (usuário logado: normal, especialista ou moderador)
    if (isset($_POST['responder'])) {
        exigir_login();
        $texto = trim($_POST['texto'] ?? '');
        if ($texto !== '') {
            $s = $pdo->prepare('INSERT INTO respostas (pergunta_id, usuario_id, texto) VALUES (?, ?, ?)');
            $s->execute([$id, logado()['id'], $texto]);
        }
    }

    // Responder uma resposta (comentário dentro da resposta)
    if (isset($_POST['comentar'])) {
        exigir_login();
        $rid   = (int)($_POST['resposta_id'] ?? 0);
        $texto = mb_substr(trim($_POST['texto'] ?? ''), 0, 500);
        $s = $pdo->prepare('SELECT id FROM respostas WHERE id = ? AND pergunta_id = ?');
        $s->execute([$rid, $id]);
        if ($texto !== '' && $s->fetch()) {
            $s = $pdo->prepare('INSERT INTO comentarios (resposta_id, usuario_id, texto) VALUES (?, ?, ?)');
            $s->execute([$rid, logado()['id'], $texto]);
        }
        $ancora = '#r' . $rid;
    }

    // Apagar comentário (moderador)
    if (isset($_POST['apagar_comentario'])) {
        exigir_moderador();
        $s = $pdo->prepare('DELETE FROM comentarios WHERE id = ?');
        $s->execute([(int)$_POST['comentario_id']]);
        $ancora = '#r' . (int)($_POST['resposta_id'] ?? 0);
    }

    // Avaliar resposta de 1 a 5 (não pode avaliar a própria resposta)
    if (isset($_POST['nota'])) {
        exigir_login();
        $nota = (int)$_POST['nota'];
        $rid  = (int)($_POST['resposta_id'] ?? 0);
        $s = $pdo->prepare('SELECT usuario_id FROM respostas WHERE id = ? AND pergunta_id = ?');
        $s->execute([$rid, $id]);
        $resp = $s->fetch();
        if ($resp && $nota >= 1 && $nota <= 5 && (int)$resp['usuario_id'] !== (int)logado()['id']) {
            $s = $pdo->prepare(
                'INSERT INTO avaliacoes (resposta_id, usuario_id, nota) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE nota = VALUES(nota)'
            );
            $s->execute([$rid, logado()['id'], $nota]);
        }
        $ancora = '#r' . $rid;
    }

    // Apagar resposta (moderador)
    if (isset($_POST['apagar'])) {
        exigir_moderador();
        $s = $pdo->prepare('DELETE FROM respostas WHERE id = ?');
        $s->execute([(int)$_POST['resposta_id']]);
    }
    header('Location: pergunta.php?id=' . $id . $ancora);
    exit;
}

// Especialistas primeiro, depois as mais bem avaliadas
$s = $pdo->prepare(
    "SELECT r.*, u.nome, u.tipo, u.foto,
            (SELECT AVG(a.nota) FROM avaliacoes a WHERE a.resposta_id = r.id) AS media,
            (SELECT COUNT(*) FROM avaliacoes a WHERE a.resposta_id = r.id) AS qtd,
            (SELECT a.nota FROM avaliacoes a WHERE a.resposta_id = r.id AND a.usuario_id = ?) AS minha_nota
     FROM respostas r
     JOIN usuarios u ON u.id = r.usuario_id
     WHERE r.pergunta_id = ?
     ORDER BY (u.tipo = 'especialista') DESC, COALESCE(media, 0) DESC, r.criado_em ASC"
);
$s->execute([$eu['id'] ?? 0, $id]);
$respostas = $s->fetchAll();

// Comentários de todas as respostas desta pergunta, agrupados por resposta
$s = $pdo->prepare(
    'SELECT c.*, u.nome, u.tipo, u.foto
     FROM comentarios c
     JOIN usuarios u ON u.id = c.usuario_id
     JOIN respostas r ON r.id = c.resposta_id
     WHERE r.pergunta_id = ?
     ORDER BY c.criado_em ASC'
);
$s->execute([$id]);
$comentarios = [];
foreach ($s->fetchAll() as $c) {
    $comentarios[$c['resposta_id']][] = $c;
}

$titulo = $p['titulo'];
include 'header.php';
?>
<p><a href="categoria.php?id=<?= $p['categoria_id'] ?>">← <?= e($p['categoria']) ?></a></p>

<div class="caixa">
    <h1><?= e($p['titulo']) ?></h1>
    <small>
        <?= avatar($p['nome'], $p['foto'], 24) ?>
        por <a href="perfil.php?id=<?= $p['usuario_id'] ?>"><?= e($p['nome']) ?></a>
        <span class="tag <?= e($p['tipo']) ?>"><?= e($p['tipo']) ?></span>
        em <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?>
    </small>
    <p><?= nl2br(e($p['texto'])) ?></p>
</div>

<h2><?= count($respostas) ?> resposta(s)</h2>

<?php foreach ($respostas as $r): ?>
<?php $lista = $comentarios[$r['id']] ?? []; ?>
<div class="caixa <?= $r['tipo'] === 'especialista' ? 'destaque' : '' ?>" id="r<?= $r['id'] ?>">
    <?php if ($r['tipo'] === 'especialista'): ?>
        <div class="selo">⭐ Resposta de especialista</div>
    <?php endif; ?>
    <small>
        <?= avatar($r['nome'], $r['foto'], 24) ?>
        <a href="perfil.php?id=<?= $r['usuario_id'] ?>"><?= e($r['nome']) ?></a>
        <span class="tag <?= e($r['tipo']) ?>"><?= e($r['tipo']) ?></span>
        em <?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?>
    </small>
    <p><?= nl2br(e($r['texto'])) ?></p>

    <div class="nota"><?= mostrar_nota((float)$r['media'], (int)$r['qtd']) ?></div>

    <?php if ($eu && (int)$r['usuario_id'] !== (int)$eu['id']): ?>
    <form method="post" class="avaliar">
        <input type="hidden" name="resposta_id" value="<?= $r['id'] ?>">
        <small>Avaliar:</small>
        <?php for ($n = 1; $n <= 5; $n++): ?>
            <button type="submit" name="nota" value="<?= $n ?>"
                    class="<?= (int)$r['minha_nota'] === $n ? 'ativa' : '' ?>"><?= $n ?>★</button>
        <?php endfor; ?>
    </form>
    <?php elseif (!$eu): ?>
        <small><a href="login.php">Entre</a> para avaliar.</small>
    <?php else: ?>
        <small>Você não pode avaliar a própria resposta.</small>
    <?php endif; ?>

    <!-- Respostas a esta resposta -->
    <div class="comentarios">
        <h4>💬 <?= count($lista) ?> resposta(s) a esta resposta</h4>

        <?php foreach ($lista as $c): ?>
        <div class="comentario">
            <?= avatar($c['nome'], $c['foto'], 28) ?>
            <div class="corpo">
                <small>
                    <a href="perfil.php?id=<?= $c['usuario_id'] ?>"><?= e($c['nome']) ?></a>
                    <span class="tag <?= e($c['tipo']) ?>"><?= e($c['tipo']) ?></span>
                    em <?= date('d/m/Y H:i', strtotime($c['criado_em'])) ?>
                </small>
                <p><?= nl2br(e($c['texto'])) ?></p>
                <?php if (eh_moderador()): ?>
                <form method="post" data-confirmar="Apagar este comentário?">
                    <input type="hidden" name="comentario_id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="resposta_id" value="<?= $r['id'] ?>">
                    <button type="submit" name="apagar_comentario" class="perigo">Apagar</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($eu): ?>
        <form method="post" class="comentar-form">
            <input type="hidden" name="resposta_id" value="<?= $r['id'] ?>">
            <input type="text" name="texto" maxlength="500" placeholder="Responder a <?= e($r['nome']) ?>..." required>
            <button type="submit" name="comentar">Responder</button>
        </form>
        <?php else: ?>
            <small><a href="login.php">Entre</a> para responder.</small>
        <?php endif; ?>
    </div>

    <?php if (eh_moderador()): ?>
    <form method="post" data-confirmar="Apagar esta resposta (e os comentários dela)?">
        <input type="hidden" name="resposta_id" value="<?= $r['id'] ?>">
        <button type="submit" name="apagar" class="perigo">Apagar resposta</button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if ($eu): ?>
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
