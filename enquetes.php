<?php
require 'config.php';
$erro = '';
$eu = logado();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Moderador cria enquete para uma nova matéria
    if (isset($_POST['criar'])) {
        exigir_moderador();
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $erro = 'Digite o nome da matéria proposta.';
        } else {
            $s = $pdo->prepare('SELECT id FROM categorias WHERE nome = ?');
            $s->execute([$nome]);
            $existe = $s->fetch();

            $s = $pdo->prepare('SELECT id FROM enquetes WHERE nome_categoria = ? AND ativa = 1');
            $s->execute([$nome]);
            $aberta = $s->fetch();

            if ($existe) {
                $erro = 'Essa matéria já existe.';
            } elseif ($aberta) {
                $erro = 'Já existe uma enquete aberta para essa matéria.';
            } else {
                $s = $pdo->prepare('INSERT INTO enquetes (nome_categoria, criado_por) VALUES (?, ?)');
                $s->execute([$nome, $eu['id']]);
            }
        }
    }

    // Usuário logado vota (pode mudar o voto enquanto a enquete estiver aberta)
    if (isset($_POST['votar'])) {
        exigir_login();
        $voto = ($_POST['voto'] ?? '') === 'sim' ? 'sim' : 'nao';
        $s = $pdo->prepare('SELECT id FROM enquetes WHERE id = ? AND ativa = 1');
        $s->execute([(int)$_POST['id']]);
        if ($s->fetch()) {
            $s = $pdo->prepare(
                'INSERT INTO votos (enquete_id, usuario_id, voto) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE voto = VALUES(voto)'
            );
            $s->execute([(int)$_POST['id'], $eu['id'], $voto]);
        }
    }

    // Moderador encerra: se "Sim" ganhar, a matéria é CRIADA automaticamente
    if (isset($_POST['encerrar'])) {
        exigir_moderador();
        $eid = (int)$_POST['id'];
        $s = $pdo->prepare('SELECT * FROM enquetes WHERE id = ? AND ativa = 1');
        $s->execute([$eid]);
        $enq = $s->fetch();
        if ($enq) {
            $s = $pdo->prepare(
                "SELECT COALESCE(SUM(voto = 'sim'), 0) AS sim, COALESCE(SUM(voto = 'nao'), 0) AS nao
                 FROM votos WHERE enquete_id = ?"
            );
            $s->execute([$eid]);
            $v = $s->fetch();

            $aprovada = (int)$v['sim'] > (int)$v['nao']; // empate = rejeitada
            if ($aprovada) {
                $s = $pdo->prepare('INSERT IGNORE INTO categorias (nome, criado_por) VALUES (?, ?)');
                $s->execute([$enq['nome_categoria'], $enq['criado_por']]);
            }
            $s = $pdo->prepare('UPDATE enquetes SET ativa = 0, resultado = ? WHERE id = ?');
            $s->execute([$aprovada ? 'aprovada' : 'rejeitada', $eid]);
        }
    }

    // Moderador apaga enquete
    if (isset($_POST['apagar'])) {
        exigir_moderador();
        $pdo->prepare('DELETE FROM enquetes WHERE id = ?')->execute([(int)$_POST['id']]);
    }

    if ($erro === '') {
        header('Location: enquetes.php');
        exit;
    }
}

$s = $pdo->prepare(
    "SELECT e.*,
            (SELECT COUNT(*) FROM votos v WHERE v.enquete_id = e.id AND v.voto = 'sim') AS sim,
            (SELECT COUNT(*) FROM votos v WHERE v.enquete_id = e.id AND v.voto = 'nao') AS nao,
            (SELECT v.voto FROM votos v WHERE v.enquete_id = e.id AND v.usuario_id = ?) AS meu_voto,
            (SELECT c.id FROM categorias c WHERE c.nome = e.nome_categoria) AS cat_id
     FROM enquetes e ORDER BY e.ativa DESC, e.criado_em DESC"
);
$s->execute([$eu['id'] ?? 0]);
$enquetes = $s->fetchAll();

$titulo = 'Enquetes';
include 'header.php';
?>
<h1>Enquetes de novas matérias</h1>
<p>Os moderadores propõem matérias novas. Se o "Sim" vencer quando a enquete for encerrada, a matéria é criada automaticamente.</p>

<?php if (eh_moderador()): ?>
<div class="caixa">
    <h3>Criar enquete</h3>
    <?php if ($erro): ?><p class="erro"><?= e($erro) ?></p><?php endif; ?>
    <form method="post" class="linha">
        <input type="text" name="nome" maxlength="60" placeholder="Nome da matéria proposta" required>
        <button type="submit" name="criar">Criar enquete</button>
    </form>
</div>
<?php endif; ?>

<?php if (!$enquetes): ?><p>Nenhuma enquete por enquanto.</p><?php endif; ?>

<?php foreach ($enquetes as $q): ?>
<div class="caixa">
    <h3>Criar a matéria "<?= e($q['nome_categoria']) ?>"?</h3>
    <p>👍 Sim: <strong><?= $q['sim'] ?></strong> &nbsp; 👎 Não: <strong><?= $q['nao'] ?></strong></p>

    <?php if ($q['ativa']): ?>
        <?php if ($eu): ?>
            <form method="post" class="linha">
                <input type="hidden" name="id" value="<?= $q['id'] ?>">
                <input type="hidden" name="votar" value="1">
                <button type="submit" name="voto" value="sim" class="<?= $q['meu_voto'] === 'sim' ? '' : 'secundario' ?>">Votar Sim</button>
                <button type="submit" name="voto" value="nao" class="<?= $q['meu_voto'] === 'nao' ? '' : 'secundario' ?>">Votar Não</button>
            </form>
            <?php if ($q['meu_voto']): ?>
                <small>Seu voto: <?= $q['meu_voto'] === 'sim' ? 'Sim' : 'Não' ?></small>
            <?php endif; ?>
        <?php else: ?>
            <p class="aviso">Entre para votar.</p>
        <?php endif; ?>
    <?php elseif ($q['resultado'] === 'aprovada'): ?>
        <p class="ok">✅ Aprovada! A matéria foi criada.
            <?php if ($q['cat_id']): ?><a href="categoria.php?id=<?= $q['cat_id'] ?>">Ir para <?= e($q['nome_categoria']) ?></a><?php endif; ?>
        </p>
    <?php else: ?>
        <p class="erro">❌ Rejeitada. A matéria não foi criada.</p>
    <?php endif; ?>

    <?php if (eh_moderador()): ?>
    <form method="post" class="linha" data-confirmar="Apagar esta enquete?">
        <input type="hidden" name="id" value="<?= $q['id'] ?>">
        <?php if ($q['ativa']): ?>
            <button type="submit" name="encerrar">Encerrar e aplicar resultado</button>
        <?php endif; ?>
        <button type="submit" name="apagar" class="perigo">Apagar</button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php include 'footer.php'; ?>
