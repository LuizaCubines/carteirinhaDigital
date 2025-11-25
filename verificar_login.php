<?php
session_start();
include "conexao.php";

$usuario = $_POST['usuario']; // pode ser RA ou email
$senha = $_POST['senha'];

// Verifica se corresponde a RA ou a Email
$sql = "SELECT * FROM aluno 
        WHERE email = '$usuario' 
        OR ra = '$usuario'
        LIMIT 1";

$result = $conn->query($sql);

if ($result->num_rows > 0) {

    $aluno = $result->fetch_assoc();

    if (password_verify($senha, $aluno['senha'])) {
        
        $_SESSION['id_aluno']  = $aluno['id'];
        $_SESSION['nome_aluno'] = $aluno['nome'];

        header("Location: perfil_aluno.php");
        exit;

    } else {
        echo "<script>alert('Senha incorreta!'); history.back();</script>";
    }

} else {
    echo "<script>alert('Usuário não encontrado!'); history.back();</script>";
}

$conn->close();
?>
