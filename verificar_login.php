<?php
session_start();
include "conexao.php";

$usuario = $_POST['usuario']; // pode ser RA ou email
$senha = $_POST['senha'];

// Proteger contra SQL Injection
$sql = "SELECT * FROM aluno 
        WHERE (email = ? OR ra = ?)
        AND ativo = 1
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $usuario, $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $aluno = $result->fetch_assoc();

    if (password_verify($senha, $aluno['senha'])) {
        
        $_SESSION['aluno_id'] = $aluno['id'];
        $_SESSION['tipo_usuario'] = 'aluno';  
        $_SESSION['aluno_nome'] = $aluno['nome'];
        $_SESSION['aluno_ra'] = $aluno['ra'];
        $_SESSION['aluno_turma'] = $aluno['turma'];
        

        echo "<script>console.log('Sessão configurada: aluno_id=" . $aluno['id'] . ", tipo=aluno');</script>";
        
        header("Location: perfil_aluno.php");
        exit;

    } else {
        echo "<script>alert('Senha incorreta!'); history.back();</script>";
    }

} else {
    echo "<script>alert('Usuário não encontrado!'); history.back();</script>";
}

$stmt->close();
$conn->close();
?>