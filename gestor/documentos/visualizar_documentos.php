<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require '../../conexao.php';

$sql = "
    SELECT 
        u.nome_arquivo, 
        u.caminho_arquivo, 
        u.enviado_em, 
        p.titulo AS projeto_nome,
        u.projeto_id
    FROM uploads u
    LEFT JOIN projetos p ON u.projeto_id = p.id
    ORDER BY p.titulo ASC, u.enviado_em DESC
";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<p>Nenhum documento encontrado.</p>";
    exit;
}

$documentosPorProjeto = [];

while ($row = $result->fetch_assoc()) {
    $documentosPorProjeto[$row['projeto_id']]['nome'] = $row['projeto_nome'] ?? 'Projeto Desconhecido';
    $documentosPorProjeto[$row['projeto_id']]['docs'][] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Documentos por Projeto</title>
    <style>
        .accordion {
            background-color: #eee;
            color: #333;
            cursor: pointer;
            padding: 12px;
            width: 100%;
            border: none;
            outline: none;
            text-align: left;
            font-size: 16px;
            margin-top: 10px;
            border-radius: 6px;
        }

        .panel {
            display: none;
            padding: 10px 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #6c5ce7;
            margin-bottom: 10px;
        }

        iframe {
            width: 100%;
            height: 400px;
            margin-top: 8px;
            border: 1px solid #ccc;
        }

        img {
            max-width: 300px;
            max-height: 300px;
            display: block;
            margin-bottom: 10px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>

<h1>📁 Documentos por Projeto</h1>

<?php foreach ($documentosPorProjeto as $projetoId => $dados): ?>
    <button class="accordion">📂 <?= htmlspecialchars($dados['nome']) ?></button>
    <div class="panel">
        <?php foreach ($dados['docs'] as $doc): 
            $nome = htmlspecialchars($doc['nome_arquivo']);
            $link = htmlspecialchars($doc['caminho_arquivo']);
            $data = $doc['enviado_em'];
            $ext = strtolower(pathinfo($link, PATHINFO_EXTENSION));
        ?>
            <p><strong><?= $nome ?></strong> <small>(<?= $data ?>)</small></p>

            <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                <img src="<?= $link ?>" alt="<?= $nome ?>">
            <?php elseif ($ext === 'pdf'): ?>
                <iframe src="<?= $link ?>"></iframe>
            <?php else: ?>
                <p><a href="<?= $link ?>" download>⬇️ Baixar arquivo</a></p>
            <?php endif; ?>
            <hr>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<script>
// Accordion toggle
document.querySelectorAll('.accordion').forEach(button => {
    button.addEventListener('click', () => {
        button.classList.toggle('active');
        const panel = button.nextElementSibling;
        panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
    });
});
</script>

</body>
</html>
