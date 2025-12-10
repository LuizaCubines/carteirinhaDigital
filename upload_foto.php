<?php
session_start();
include "conexao.php";

if (!isset($_SESSION['id_aluno'])) {
    header("Location: login_aluno.php");
    exit;
}

$id_aluno = $_SESSION['id_aluno'];

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {

    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $novo_nome = "foto_" . $id_aluno . "." . $ext;
    $caminho = "uploads/" . $novo_nome;

    move_uploaded_file($_FILES['foto']['tmp_name'], $caminho);

    $sql = "UPDATE aluno SET foto='$caminho' WHERE id='$id_aluno'";
    $conn->query($sql);
}

header("Location: perfil_aluno.php");
exit;
?>
