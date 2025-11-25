<?php
session_start();

// Remove todas as variáveis da sessão
session_unset();

// Destroi a sessão
session_destroy();

// Redireciona para o login
header("Location: login.php");
exit;
?>
