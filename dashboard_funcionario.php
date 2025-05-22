<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'funcionario') {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

$id = $_SESSION['usuario_id'];

$sql = "SELECT * FROM tarefas WHERE funcionario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$tarefas = $stmt->get_result();
?>

<h2>Minhas Tarefas</h2>
<table border="1">
<tr><th>Etapa</th><th>Status</th><th>Ação</th></tr>

<?php while ($tarefa = $tarefas->fetch_assoc()): ?>
<tr>
    <td><?= $tarefa['descricao'] ?></td>
    <td><?= $tarefa['status'] ?></td>
    <td>
        <form method="POST" action="atualizar_tarefa.php">
            <input type="hidden" name="id_tarefa" value="<?= $tarefa['id'] ?>">
            <select name="status">
                <option value="em andamento">Em andamento</option>
                <option value="concluido">Concluído</option>
            </select>
            <button type="submit">Atualizar</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
