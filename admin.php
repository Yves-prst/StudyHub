<?php
require 'config.php';
exigir_moderador();
$eu = logado();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)($_POST['id'] ?? 0);

    // O moderador não pode alterar a si mesmo
    if ($uid !== (int)$eu['id']) {
        if (isset($_POST['banir'])) {
            $pdo->prepare('UPDATE usuarios SET banido = 1 WHERE id = ?')->execute([$uid]);
        }
        if (isset($_POST['desbanir'])) {
            $pdo->prepare('UPDATE usuarios SET banido = 0 WHERE id = ?')->execute([$uid]);
        }
        if (isset($_POST['mudar_tipo'])) {
            $tipo = $_POST['tipo'] ?? 'normal';
            if (in_array($tipo, ['normal', 'especialista', 'moderador'], true)) {
                $pdo->prepare('UPDATE usuarios SET tipo = ? WHERE id = ?')->execute([$tipo, $uid]);
            }
        }
    }
    header('Location: admin.php');
    exit;
}

$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY criado_em DESC')->fetchAll();

$titulo = 'Painel do moderador';
include 'header.php';
?>
<h1>Painel do moderador</h1>
<p>Todo usuário novo entra como <strong>normal</strong>. Aqui você muda o tipo (especialista ou moderador) e bane usuários. Para apagar conteúdo inapropriado, use o botão "Apagar" nas matérias, perguntas e respostas.</p>

<div class="caixa">
    <table>
        <tr><th>Nome</th><th>E-mail</th><th>Tipo</th><th>Status</th><th>Ações</th></tr>
        <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><a href="perfil.php?id=<?= $u['id'] ?>"><?= e($u['nome']) ?></a></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="tag <?= e($u['tipo']) ?>"><?= e($u['tipo']) ?></span></td>
            <td><?= $u['banido'] ? '🚫 Banido' : 'Ativo' ?></td>
            <td>
                <?php if ($u['id'] != $eu['id']): ?>
                <form method="post" class="linha">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <select name="tipo">
                        <?php foreach (['normal', 'especialista', 'moderador'] as $t): ?>
                        <option value="<?= $t ?>" <?= $u['tipo'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="mudar_tipo">Salvar</button>
                    <?php if ($u['banido']): ?>
                        <button type="submit" name="desbanir" class="secundario">Desbanir</button>
                    <?php else: ?>
                        <button type="submit" name="banir" class="perigo">Banir</button>
                    <?php endif; ?>
                </form>
                <?php else: ?>
                    (você)
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php include 'footer.php'; ?>
