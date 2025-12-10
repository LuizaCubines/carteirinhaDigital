<?php
session_start();
include "conexao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_login.php");
    exit;
}

$email = trim($_POST["email"] ?? '');
$senha = $_POST["senha"] ?? '';

if (empty($email) || empty($senha)) {
    header("Location: admin_login.php?erro=credenciais&email=" . urlencode($email));
    exit;
}

$sql = "SELECT id, nome, email, senha FROM administrador WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $adm = $result->fetch_assoc();

       
        if (password_verify($senha, $adm["senha"])) {
            
            $_SESSION["adm_id"] = $adm["id"];
            $_SESSION["adm_nome"] = $adm["nome"];
            $_SESSION["adm_email"] = $adm["email"];
            $_SESSION["tipo_usuario"] = "admin";

            header("Location: painel_admin.php");
            exit;
        } 
       
        else if ($senha === $adm["senha"] || md5($senha) === $adm["senha"]) {
          
            $_SESSION["adm_id"] = $adm["id"];
            $_SESSION["adm_nome"] = $adm["nome"];
            $_SESSION["adm_email"] = $adm["email"];
            $_SESSION["tipo_usuario"] = "admin";

            $novo_hash = password_hash($senha, PASSWORD_DEFAULT);
            $update_sql = "UPDATE administrador SET senha = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $novo_hash, $adm["id"]);
            $update_stmt->execute();
            $update_stmt->close();

            header("Location: painel_admin.php");
            exit;
        } else {
            header("Location: admin_login.php?erro=credenciais&email=" . urlencode($email));
            exit;
        }
    } else {
        header("Location: admin_login.php?erro=credenciais&email=" . urlencode($email));
        exit;
    }

    $stmt->close();
} else {
    header("Location: admin_login.php?erro=erro");
    exit;
}

$conn->close();
?>