<?php
session_start();
include "conexao.php";

$email = $_POST["email"];
$senha = $_POST["senha"];

// buscar administrador
$sql = "SELECT * FROM administrador WHERE email = '$email' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {

    $adm = $result->fetch_assoc();

    if (password_verify($senha, $adm["senha"])) {

        $_SESSION["adm_id"]   = $adm["id"];
        $_SESSION["adm_nome"] = $adm["nome"];

        header("Location: painel_admin.php");
        exit;

    } else {
        echo "<script>alert('Senha incorreta!'); history.back();</script>";
    }

} else {
    echo "<script>alert('Administrador não encontrado!'); history.back();</script>";
}

$conn->close();
?>
