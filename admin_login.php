<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login do Administrador - Carteirinha SENAI</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="tela-login">
  <div class="container-login">
    <div class="box-login">

      <div class="logo-area">
        <i class="fas fa-user-shield"></i>
        <h1>Administrador</h1>
      </div>

      <form action="verificar_admin.php" method="POST" class="formulario-login">

        <!-- Email -->
        <div class="grupo-campo">
          <label for="email">Email</label>
          <div class="campo-input">
            <i class="fas fa-envelope"></i>
            <input 
              type="email"
              id="email"
              name="email"
              placeholder="Digite o email do administrador"
              required
            >
          </div>
        </div>

        <!-- Senha -->
        <div class="grupo-campo">
          <label for="senha">Senha</label>
          <div class="campo-input">
            <i class="fas fa-lock"></i>
            <input
              type="password"
              id="senha"
              name="senha"
              placeholder="Digite sua senha"
              required
            >
          </div>
        </div>

        <!-- Botão -->
        <button type="submit" class="botao-entrar">
          <span>Entrar</span>
          <i class="fas fa-arrow-right"></i>
        </button>

      </form>

      <!-- Voltar para login do aluno -->
      <div style="text-align:center; margin-top:15px;">
        <a href="login.php" class="btn-teste" style="padding:10px 20px; display:inline-block;">
          <i class="fas fa-arrow-left"></i> Voltar ao Login de Aluno
        </a>
      </div>

    </div>

    <div class="decoracao-fundo"></div>
  </div>
</div>

</body>
</html>
