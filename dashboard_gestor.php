
<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: login.php");
    exit;
}
include 'conexao.php';

// Total de projetos por status
$projetos = $conn->query("SELECT status, COUNT(*) AS total FROM projetos GROUP BY status");
$dadosProjetos = [];
while ($p = $projetos->fetch_assoc()) {
    $dadosProjetos[$p['status']] = $p['total'];
}

// Total de tarefas por status
$tarefas = $conn->query("SELECT status, COUNT(*) AS total FROM tarefas GROUP BY status");
$dadosTarefas = [];
while ($t = $tarefas->fetch_assoc()) {
    $dadosTarefas[$t['status']] = $t['total'];
}

// Total de funcionários
$funcionarios = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE tipo = 'funcionario'");
$totalFuncionarios = $funcionarios->fetch_assoc()['total'];

// Últimos documentos enviados
$uploads = $conn->query("SELECT nome_arquivo, caminho_arquivo, enviado_em FROM uploads ORDER BY enviado_em DESC LIMIT 5");

// Projetos mais recentes
$recentes = $conn->query("SELECT id, titulo, criado_em FROM projetos ORDER BY criado_em DESC LIMIT 5");
?>
<?php
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require 'conexao.php';

// Projetos pendentes
$projPendentes = $conn->query("SELECT COUNT(*) AS total FROM projetos WHERE status = 'pendente'")
                      ->fetch_assoc()['total'] ?? 0;

// Tarefas em andamento
$tarefasAFazer = $conn->query("SELECT COUNT(*) AS total FROM tarefas WHERE status = 'a_fazer'")
                      ->fetch_assoc()['total'] ?? 0;
$tarefasProg = $conn->query("SELECT COUNT(*) AS total FROM tarefas WHERE status = 'em_progresso'")
                      ->fetch_assoc()['total'] ?? 0;

// Novos documentos nos últimos 3 dias
$novosDocs = $conn->query("SELECT COUNT(*) AS total FROM uploads WHERE enviado_em >= NOW() - INTERVAL 3 DAY")
                  ->fetch_assoc()['total'] ?? 0;
?>


<h2>Bem-vindo, Gestor <?= htmlspecialchars($_SESSION['usuario_nome']) ?></h2>

<h3>🔔 Notificações</h3>
<ul>
    <?php if ($projPendentes > 0): ?>
        <li><strong><?= $projPendentes ?></strong> projeto(s) aguardando aprovação. <a href="avaliar_projetos.php">Avaliar</a></li>
    <?php endif; ?>

    <?php if ($tarefasAFazer > 0): ?>
        <li><strong><?= $tarefasAFazer ?></strong> tarefa(s) a fazer.</li>
    <?php endif; ?>

    <?php if ($tarefasProg > 0): ?>
        <li><strong><?= $tarefasProg ?></strong> tarefa(s) em progresso.</li>
    <?php endif; ?>

    <?php if ($novosDocs > 0): ?>
        <li><strong><?= $novosDocs ?></strong> novo(s) documento(s) enviado(s) nos últimos 3 dias.</li>
    <?php endif; ?>

    <?php if ($projPendentes + $tarefasAFazer + $tarefasProg + $novosDocs == 0): ?>
        <li style="color:gray;">Nenhuma notificação no momento.</li>
    <?php endif; ?>
</ul>
<hr>

<h3>📊 Resumo Geral</h3>

<ul>
    <li><strong>Projetos:</strong></li>
    <ul>
        <li>Pendentes: <?= $dadosProjetos['pendente'] ?? 0 ?></li>
        <li>Aprovados: <?= $dadosProjetos['aprovado'] ?? 0 ?></li>
        <li>Em andamento: <?= $dadosProjetos['em_andamento'] ?? 0 ?></li>
        <li>Finalizados: <?= $dadosProjetos['finalizado'] ?? 0 ?></li>
        <li>Em ajustes: <?= $dadosProjetos['ajustes'] ?? 0 ?></li>
    </ul>

    <li><strong>Tarefas:</strong></li>
    <ul>
        <li>A Fazer: <?= $dadosTarefas['a_fazer'] ?? 0 ?></li>
        <li>Em Progresso: <?= $dadosTarefas['em_progresso'] ?? 0 ?></li>
        <li>Concluídas: <?= $dadosTarefas['concluido'] ?? 0 ?></li>
    </ul>

    <li><strong>Funcionários cadastrados:</strong> <?= $totalFuncionarios ?></li>
</ul>

<h3>🗂 Últimos Documentos Enviados</h3>
<ul>
    <?php while ($doc = $uploads->fetch_assoc()): ?>
        <li><a href="<?= $doc['caminho_arquivo'] ?>" download><?= htmlspecialchars($doc['nome_arquivo']) ?></a> (<?= $doc['enviado_em'] ?>)</li>
    <?php endwhile; ?>
</ul>

<h3>📅 Projetos Recentes</h3>
<ul>
    <?php while ($proj = $recentes->fetch_assoc()): ?>
        <li><?= htmlspecialchars($proj['titulo']) ?> (<?= $proj['criado_em'] ?>)</li>
    <?php endwhile; ?>
</ul>

<hr>
<h3>🔗 Ações Rápidas</h3>


<ul>
    <li><a href="tarefas/cria_tarefa.php">Criar nova tarefa</a></li>
    <li><a href="tarefas/listar_tarefas.php">Visualizar tarefas</a></li>
    <li><a href="gestor/documentos/enviar_documento.php">Enviar Documento</a></li>
    <li><a href="gestor/avaliar_projetos.php">Avaliar Projetos Pendentes</a></li>
    <li><a href="gestor/atribuir_funcionario.php">Gerenciar Equipes</a></li>
    <li><a href="gestor/projetos/listar_projetos.php">Projetos</a></li>
    <li><a href="gestor/documentos/enviar_documento.php">Documentos</a></li>
    <li><a href="gestor/criar_projeto.php">Projetos</a></li>
    <li><a href="logout.php">Sair</a></li>
</ul>
