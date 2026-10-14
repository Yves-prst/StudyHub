<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Moderador cria enquete para uma nova matéria
    if (isset($_POST['criar'])) {
        exigir_moderador();
        $nome = trim($_POST['nome'] ?? '');
        if ($nome !== '') {
            $s = $pdo->prepare('INSERT INTO enquetes (nome_categoria, criado_por) VALUES (?, ?)');
            $s->execute([$nome, logado()['id']]);
        }
    }
    // Usuário logado vota (pode mudar o voto)
    if (isset($_POST['votar'])) {
        exigir_login();
        $voto = $_POST['voto'] === 'sim' ? 'sim' : 'nao';
        $s = $pdo->prepare(
            'INSERT INTO votos (enquete_id, usuario_id, voto) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE voto = VALUES(voto)'
        );
        $s->execute([(int)$_POST['id'], logado()['id'], $voto]);
    }
    // Moderador encerra: se "sim" ganhar, a matéria é criada
    if (isset($_POST['encerrar'])) {
        exigir_moderador();
        $eid = (int)$_POST['id'];
        $s = $pdo->prepare('SELECT * FROM enquetes WHERE id = ? AND ativa = 1');
        $s->execute([$eid]);
        $enq = $s->fetch();
        if ($enq) {
            $s = $pdo->prepare("SELECT SUM(voto='sim') AS sim, SUM(voto='nao') AS nao FROM votos WHERE enquete_id = ?");
            $s->execute([$eid]);
            $v = $s->fetch();
            if ((int)$v['sim'] > (int)$v['nao']) {
                $s = $pdo->prepare('INSERT IGNORE INTO categorias (nome, criado_por) VALUES (?, ?)');
                $s->execute([$enq['nome_categoria'], $enq['criado_por']]);
            }
            $pdo->prepare('UPDATE enquetes SET ativa = 0 WHERE id = ?')->execute([$eid]);
        }
    }
    // Moderador apaga enquete
    if (isset($_POST['apagar'])) {
        exigir_moderador();
        $pdo->prepare('DELETE FROM enquetes WHERE id = ?')->execute([(int)$_POST['id']]);
    }
    header('Location: enquetes.php');
    exit;
}

$enquetes = $pdo->query(
    "SELECT e.*,
            (SELECT COUNT(*) FROM votos v WHERE v.enquete_id = e.id AND v.voto = 'sim') AS sim,
            (SELECT COUNT(*) FROM votos v WHERE v.enquete_id = e.id AND v.voto = 'nao') AS nao
     FROM enquetes e ORDER BY e.ativa DESC, e.criado_em DESC"
)->fetchAll();

$titulo = 'Enquetes';
include 'header.php';
?>
<h1>Enquetes de novas matérias</h1>

<?php if (eh_moderador()): ?>
<div class="caixa">
    <h3>Criar enquete</h3>
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
    <p>👍 Sim: <strong><?= $q['sim'] ?></strong> &nbsp; 👎 Não: <strong><?= $q['nao'] ?></strong>
       <?= $q['ativa'] ? '' : ' — <em>encerrada</em>' ?></p>

    <?php if ($q['ativa'] && logado()): ?>
    <form method="post" class="linha">
        <input type="hidden" name="id" value="<?= $q['id'] ?>">
        <button type="submit" name="votar" value="1" onclick="this.form.voto.value='sim'">Votar Sim</button>
        <button type="submit" name="votar" value="1" onclick="this.form.voto.value='nao'" class="secundario">Votar Não</button>
        <input type="hidden" name="voto" value="sim">
    </form>
    <?php elseif ($q['ativa']): ?>
        <p class="aviso">Entre para votar.</p>
    <?php endif; ?>

    <?php if (eh_moderador()): ?>
    <form method="post" class="linha" data-confirmar="Confirmar ação?">
        <input type="hidden" name="id" value="<?= $q['id'] ?>">
        <?php if ($q['ativa']): ?>
            <button type="submit" name="encerrar">Encerrar enquete</button>
        <?php endif; ?>
        <button type="submit" name="apagar" class="perigo">Apagar</button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php include 'footer.php'; ?>
