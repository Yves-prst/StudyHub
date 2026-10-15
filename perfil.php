<?php
require 'config.php';
$eu = logado();

// Sem ?id= mostra o perfil de quem está logado
$id = (int)($_GET['id'] ?? ($eu['id'] ?? 0));
if (!$id) {
    header('Location: login.php');
    exit;
}

$s = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
$s->execute([$id]);
$perfil = $s->fetch();
if (!$perfil) {
    http_response_code(404);
    exit('Usuário não encontrado.');
}

$meu       = $eu && (int)$eu['id'] === $id;
$erro      = '';
$erro_foto = '';
$pasta     = __DIR__ . '/uploads/fotos/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Editar o próprio perfil (nome e bio)
    if (isset($_POST['salvar'])) {
        if (!$meu) {
            http_response_code(403);
            exit('Acesso negado.');
        }
        $nome = mb_substr(trim($_POST['nome'] ?? ''), 0, 60);
        $bio  = mb_substr(trim($_POST['bio'] ?? ''), 0, 255);
        if ($nome === '') {
            $erro = 'O nome não pode ficar vazio.';
        } else {
            $s = $pdo->prepare('UPDATE usuarios SET nome = ?, bio = ? WHERE id = ?');
            $s->execute([$nome, $bio, $id]);
            header('Location: perfil.php?id=' . $id);
            exit;
        }
    }

    // Enviar foto de perfil (só o dono do perfil)
    if (isset($_POST['enviar_foto'])) {
        if (!$meu) {
            http_response_code(403);
            exit('Acesso negado.');
        }
        $f = $_FILES['foto'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) {
            $erro_foto = 'Escolha uma imagem de até 2 MB.';
        } else {
            $permitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            $info = @getimagesize($f['tmp_name']); // confirma que é uma imagem de verdade
            if (!$info || !isset($permitidos[$info['mime']])) {
                $erro_foto = 'Formato inválido. Use JPG, PNG, GIF ou WEBP.';
            } else {
                if (!is_dir($pasta)) {
                    mkdir($pasta, 0755, true);
                }
                // Nome gerado pelo sistema (nunca usa o nome enviado pelo usuário)
                $arquivo = 'u' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $permitidos[$info['mime']];
                if (move_uploaded_file($f['tmp_name'], $pasta . $arquivo)) {
                    if ($perfil['foto']) {
                        @unlink($pasta . basename($perfil['foto']));
                    }
                    $s = $pdo->prepare('UPDATE usuarios SET foto = ? WHERE id = ?');
                    $s->execute([$arquivo, $id]);
                    header('Location: perfil.php?id=' . $id);
                    exit;
                }
                $erro_foto = 'Não foi possível salvar a imagem. Verifique a permissão da pasta uploads/fotos.';
            }
        }
    }

    // Remover foto (o dono ou um moderador, caso a foto seja inapropriada)
    if (isset($_POST['remover_foto'])) {
        if (!$meu && !eh_moderador()) {
            http_response_code(403);
            exit('Acesso negado.');
        }
        if ($perfil['foto']) {
            @unlink($pasta . basename($perfil['foto']));
            $pdo->prepare('UPDATE usuarios SET foto = NULL WHERE id = ?')->execute([$id]);
        }
        header('Location: perfil.php?id=' . $id);
        exit;
    }
}

// Estatísticas
$s = $pdo->prepare('SELECT COUNT(*) FROM perguntas WHERE usuario_id = ?');
$s->execute([$id]);
$total_perguntas = $s->fetchColumn();

$s = $pdo->prepare('SELECT COUNT(*) FROM respostas WHERE usuario_id = ?');
$s->execute([$id]);
$total_respostas = $s->fetchColumn();

// Média das notas que as respostas dessa pessoa receberam
$s = $pdo->prepare(
    'SELECT AVG(a.nota) AS media, COUNT(*) AS qtd
     FROM avaliacoes a JOIN respostas r ON r.id = a.resposta_id
     WHERE r.usuario_id = ?'
);
$s->execute([$id]);
$nota = $s->fetch();

// Últimas perguntas e respostas
$s = $pdo->prepare(
    'SELECT p.id, p.titulo, p.criado_em, c.nome AS categoria
     FROM perguntas p JOIN categorias c ON c.id = p.categoria_id
     WHERE p.usuario_id = ? ORDER BY p.criado_em DESC LIMIT 10'
);
$s->execute([$id]);
$perguntas = $s->fetchAll();

$s = $pdo->prepare(
    'SELECT r.id, r.texto, r.criado_em, r.pergunta_id, p.titulo,
            (SELECT AVG(a.nota) FROM avaliacoes a WHERE a.resposta_id = r.id) AS media,
            (SELECT COUNT(*) FROM avaliacoes a WHERE a.resposta_id = r.id) AS qtd
     FROM respostas r JOIN perguntas p ON p.id = r.pergunta_id
     WHERE r.usuario_id = ? ORDER BY r.criado_em DESC LIMIT 10'
);
$s->execute([$id]);
$respostas = $s->fetchAll();

$titulo = 'Perfil de ' . $perfil['nome'];
include 'header.php';
?>
<div class="caixa">
    <div class="perfil-cabecalho">
        <?= avatar($perfil['nome'], $perfil['foto'], 96) ?>
        <div>
            <h1>
                <?= e($perfil['nome']) ?>
                <span class="tag <?= e($perfil['tipo']) ?>"><?= e($perfil['tipo']) ?></span>
                <?php if ($perfil['banido']): ?><span class="tag banido">banido</span><?php endif; ?>
            </h1>
            <small>Membro desde <?= date('d/m/Y', strtotime($perfil['criado_em'])) ?></small>
        </div>
    </div>

    <p><?= $perfil['bio'] !== '' ? nl2br(e($perfil['bio'])) : '<em>Sem descrição.</em>' ?></p>

    <div class="estatisticas">
        <div><strong><?= $total_perguntas ?></strong><small>perguntas</small></div>
        <div><strong><?= $total_respostas ?></strong><small>respostas</small></div>
        <div><strong><?= $nota['qtd'] > 0 ? number_format($nota['media'], 1, ',', '') : '–' ?></strong><small>nota média (<?= (int)$nota['qtd'] ?>)</small></div>
    </div>

    <?php if (eh_moderador() && !$meu): ?>
        <p><a href="admin.php">Gerenciar este usuário no painel</a></p>
        <?php if ($perfil['foto']): ?>
        <form method="post" data-confirmar="Remover a foto deste usuário?">
            <button type="submit" name="remover_foto" class="perigo">Remover foto (moderação)</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($meu): ?>
<div class="caixa">
    <h3>Foto de perfil</h3>
    <?php if ($erro_foto): ?><p class="erro"><?= e($erro_foto) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="linha">
        <input type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp" required>
        <button type="submit" name="enviar_foto">Enviar foto</button>
    </form>
    <small>JPG, PNG, GIF ou WEBP, até 2 MB.</small>
    <?php if ($perfil['foto']): ?>
    <form method="post" data-confirmar="Remover sua foto de perfil?">
        <button type="submit" name="remover_foto" class="perigo">Remover foto</button>
    </form>
    <?php endif; ?>
</div>

<div class="caixa">
    <h3>Editar meu perfil</h3>
    <?php if ($erro): ?><p class="erro"><?= e($erro) ?></p><?php endif; ?>
    <form method="post">
        <label>Nome</label>
        <input type="text" name="nome" maxlength="60" value="<?= e($perfil['nome']) ?>" required>
        <label>Sobre mim</label>
        <textarea name="bio" rows="3" maxlength="255" placeholder="Série, matérias favoritas..."><?= e($perfil['bio']) ?></textarea>
        <button type="submit" name="salvar">Salvar</button>
    </form>
    <?php if ($perfil['tipo'] === 'normal'): ?>
        <small>Seu tipo de conta é definido pelos moderadores.</small>
    <?php endif; ?>
</div>
<?php endif; ?>

<h2>Últimas perguntas</h2>
<?php if (!$perguntas): ?><p>Nenhuma pergunta ainda.</p><?php endif; ?>
<?php foreach ($perguntas as $q): ?>
<div class="caixa">
    <a href="pergunta.php?id=<?= $q['id'] ?>"><strong><?= e($q['titulo']) ?></strong></a><br>
    <small><?= e($q['categoria']) ?> · <?= date('d/m/Y H:i', strtotime($q['criado_em'])) ?></small>
</div>
<?php endforeach; ?>

<h2>Últimas respostas</h2>
<?php if (!$respostas): ?><p>Nenhuma resposta ainda.</p><?php endif; ?>
<?php foreach ($respostas as $r): ?>
<div class="caixa">
    <small>Em: <a href="pergunta.php?id=<?= $r['pergunta_id'] ?>#r<?= $r['id'] ?>"><?= e($r['titulo']) ?></a>
        · <?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?></small>
    <p><?= nl2br(e(mb_strimwidth($r['texto'], 0, 200, '...'))) ?></p>
    <div class="nota"><?= mostrar_nota((float)$r['media'], (int)$r['qtd']) ?></div>
</div>
<?php endforeach; ?>
<?php include 'footer.php'; ?>
