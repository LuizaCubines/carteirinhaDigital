<?php
include "conexao.php";

$sql = "SELECT * FROM aluno";
$result = $conn->query($sql);

$dados = [];

while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

echo json_encode($dados);
?>