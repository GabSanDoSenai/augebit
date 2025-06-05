<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

require '../../conexao.php';
require 'upload_functions.php';

$mensagem = '';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projeto_id = $_POST['projeto_id'] ?? null;

    if (!$projeto_id || !is_numeric($projeto_id)) {
        $erros[] = "Projeto inválido.";
    } elseif (empty($_FILES['arquivos']['name'][0])) {
        $erros[] = "Nenhum arquivo selecionado.";
    } else {
        $nomeProjeto = $conn->query("SELECT titulo FROM projetos WHERE id = $projeto_id")->fetch_assoc()['titulo'] ?? "projeto";
        $diretorioProjeto = "../../../uploads/" . sanitizarNomeDiretorio($nomeProjeto);
        criarDiretorio("../../$diretorioProjeto");

        foreach ($_FILES['arquivos']['name'] as $i => $nomeOriginal) {
            $arquivo = [
                'name'     => $_FILES['arquivos']['name'][$i],
                'type'     => $_FILES['arquivos']['type'][$i],
                'tmp_name' => $_FILES['arquivos']['tmp_name'][$i],
                'error'    => $_FILES['arquivos']['error'][$i],
                'size'     => $_FILES['arquivos']['size'][$i]
            ];

            $valErros = validarArquivo($arquivo, $ALLOWED_TYPES, $ALLOWED_EXTENSIONS, $MAX_FILE_SIZE);
            if (!empty($valErros)) {
                $erros[] = "Arquivo $nomeOriginal: " . implode(', ', $valErros);
                continue;
            }

            $caminhoFinal = "$diretorioProjeto/" . uniqid() . "_" . basename($nomeOriginal);
            if (move_uploaded_file($arquivo['tmp_name'], "../../$caminhoFinal")) {
                $stmt = $conn->prepare("INSERT INTO uploads (nome_arquivo, caminho_arquivo, projeto_id) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $nomeOriginal, $caminhoFinal, $projeto_id);
                $stmt->execute();
            } else {
                $erros[] = "Erro ao mover o arquivo $nomeOriginal.";
            }
        }

        if (empty($erros)) {
            $mensagem = "Documentos enviados com sucesso.";
        }
    }
}

// Buscar projetos para o select
$projetos = $conn->query("SELECT id, titulo FROM projetos ORDER BY criado_em DESC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Enviar Documentos</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .erros { color: red; margin-bottom: 10px; }
        .mensagem { color: green; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>📁 Enviar Documentos para Projeto</h2>

    <?php if ($mensagem): ?>
        <p class="mensagem"><?= $mensagem ?></p>
    <?php endif; ?>

    <?php if (!empty($erros)): ?>
        <div class="erros">
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Projeto:</label><br>
        <select name="projeto_id" required>
            <option value="">Selecione</option>
            <?php while ($proj = $projetos->fetch_assoc()): ?>
                <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['titulo']) ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Arquivos (PDF ou Imagens):</label><br>
        <input type="file" name="arquivos[]" multiple accept=".pdf,image/*"><br><br>

        <button type="submit">📤 Enviar Documentos</button>
    </form>
</body>
</html>
