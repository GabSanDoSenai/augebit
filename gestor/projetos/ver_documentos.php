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
<?php include '../sidebar.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos do Projeto</title>
    <link rel="stylesheet" href="../css/geral.css">
</head>
<body>
    <div class="main-content">
    <h2>Documentos do Projeto: <?= htmlspecialchars($projeto['titulo']) ?></h2>

    <?php if ($result->num_rows === 0): ?>
        <p>Nenhum documento enviado para este projeto.</p>
    <?php else: ?>
        <ul>
            <?php while ($doc = $result->fetch_assoc()): 
                $nome = htmlspecialchars($doc['nome_arquivo']);
                $caminho = htmlspecialchars($doc['caminho_arquivo']);
                $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
            ?>
                <li>
                    <p><strong><?= $nome ?></strong> (<?= $doc['enviado_em'] ?>)</p>

                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <img src="<?= $caminho ?>" alt="<?= $nome ?>" style="max-width:300px; max-height:300px;"><br>
                    <?php elseif ($ext === 'pdf'): ?>
                        <iframe src="<?= $caminho ?>" width="100%" height="500px" style="border:1px solid #ccc;"></iframe><br>
                    <?php else: ?>
                        <p><em>Visualização indisponível para este tipo de arquivo.</em></p>
                    <?php endif; ?>

                    <p><a href="<?= $caminho ?>" download>⬇️ Baixar arquivo</a></p>
                    <hr>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>
</div>
</body>
</html>
