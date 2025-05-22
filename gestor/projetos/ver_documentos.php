<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require '../../conexao.php';

$projeto_id = $_GET['projeto_id'] ?? null;
if (!$projeto_id || !is_numeric($projeto_id)) {
    die("Projeto inválido.");
}

// Buscar nome do projeto
$projeto = $conn->query("SELECT titulo FROM projetos WHERE id = $projeto_id")->fetch_assoc();
if (!$projeto) {
    die("Projeto não encontrado.");
}

// Buscar documentos
$stmt = $conn->prepare("SELECT nome_arquivo, caminho_arquivo, enviado_em FROM uploads WHERE projeto_id = ?");
$stmt->bind_param("i", $projeto_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>Documentos do Projeto: <?= htmlspecialchars($projeto['titulo']) ?></h2>

<?php if ($result->num_rows === 0): ?>
    <p>Nenhum documento enviado para este projeto.</p>
<?php else: ?>
    <ul>
        <?php while ($doc = $result->fetch_assoc()): ?>
            <li>
                <a href="<?= htmlspecialchars($doc['caminho_arquivo']) ?>" download>
                    <?= htmlspecialchars($doc['nome_arquivo']) ?>
                </a> (<?= $doc['enviado_em'] ?>)
            </li>
        <?php endwhile; ?>
    </ul>
<?php endif; ?>
