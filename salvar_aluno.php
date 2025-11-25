<?php
include "conexao.php";

$ra = $_POST["ra"];
$nome = $_POST["nome"];
$turma = $_POST["turma"];
$email = $_POST["email"];
$telefone = $_POST["telefone"];
$nivel = $_POST["nivel"];
$senha = password_hash($_POST["senha"], PASSWORD_DEFAULT); // Criptografa a senha
$data_inscricao = date("Y-m-d");

// Inserção correta conforme sua tabela:
$sql = "INSERT INTO aluno 
(ra, nome, turma, email, telefone, nivel, senha, ativo, data_inscricao, data_criacao)
VALUES 
('$ra', '$nome', '$turma', '$email', '$telefone', '$nivel', '$senha', 1, '$data_inscricao', NOW())";

if ($conn->query($sql) === TRUE) {
    echo "Aluno cadastrado com sucesso!";
} else {
    echo "Erro ao cadastrar: " . $conn->error;
}

$conn->close();
?>
