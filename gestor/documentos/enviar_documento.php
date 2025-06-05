<?php include '../sidebar.php'; ?>
<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../../conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $projeto_id = $_POST['projeto_id'];
    $tipo = "funcionario";
    $enviado_por = $_SESSION['usuario_id'];

    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === 0) {
        $nome_original = $_FILES['arquivo']['name'];
        $temp = $_FILES['arquivo']['tmp_name'];
        $destino = '../../uploads/' . time() . '_' . basename($nome_original);

        if (move_uploaded_file($temp, $destino)) {
            $stmt = $conn->prepare("INSERT INTO uploads (projeto_id, nome_arquivo, caminho_arquivo, tipo, enviado_por) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $projeto_id, $nome_original, $destino, $tipo, $enviado_por);
            $stmt->execute();
            echo "Arquivo enviado com sucesso!";
        } else {
            echo "Erro ao mover o arquivo.";
        }
    } else {
        echo "Erro no upload.";
    }
}

$projetos = $conn->query("SELECT id, titulo FROM projetos");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
   <h2>Enviar Documento</h2>
<form method="post" enctype="multipart/form-data">
    Projeto:
    <select name="projeto_id" required>
        <?php while ($p = $projetos->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"><?= $p['titulo'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    Arquivo: <input type="file" name="arquivo" required><br><br>
    <button type="submit">Enviar</button>
</form> 
</body>
</html>

