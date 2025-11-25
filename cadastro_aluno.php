<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#1a1a1a">
  <title>Cadastro de Aluno - Carteirinha SENAI</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="tela-login">
  <div class="container-login">
    <div class="box-login">

      <div class="logo-area">
        <i class="fas fa-user-plus"></i>
        <h1>Cadastro de Aluno</h1>
      </div>

      <form action="salvar_aluno.php" method="POST" class="formulario-login">

        <!-- RA -->
        <div class="grupo-campo">
          <label for="ra">RA</label>
          <div class="campo-input">
            <i class="fas fa-id-card"></i>
            <input type="text" id="ra" name="ra" placeholder="Digite o RA" required>
          </div>
        </div>

        <!-- Nome -->
        <div class="grupo-campo">
          <label for="nome">Nome</label>
          <div class="campo-input">
            <i class="fas fa-user"></i>
            <input type="text" id="nome" name="nome" placeholder="Digite o nome completo" required>
          </div>
        </div>

        <!-- Turma -->
        <div class="grupo-campo">
          <label for="turma">Turma</label>
          <div class="campo-input">
            <i class="fas fa-users"></i>
            <input type="text" id="turma" name="turma" placeholder="Ex: 3DS, 2AL, 1IN" required>
          </div>
        </div>

        <!-- Email -->
        <div class="grupo-campo">
          <label for="email">Email</label>
          <div class="campo-input">
            <i class="fas fa-envelope"></i>
            <input type="email" id="email" name="email" placeholder="Digite seu email" required>
          </div>
        </div>

        <!-- Telefone -->
        <div class="grupo-campo">
          <label for="telefone">Telefone</label>
          <div class="campo-input">
            <i class="fas fa-phone"></i>
            <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000">
          </div>
        </div>

        <!-- Nível -->
        <div class="grupo-campo">
          <label for="nivel">Nível</label>
          <div class="campo-input">
            <i class="fas fa-layer-group"></i>
            <select name="nivel" id="nivel" required>
              <option value="">Selecione o nível</option>
              <option value="Técnico em Desenvolvimento de Sistemas">Técnico em Desenvolvimento de Sistemas</option>
              <option value="Técnico em Alimentos">Técnico em Alimentos</option>
              <option value="Técnico em Instrumentação">Técnico em Instrumentação</option>
            </select>
          </div>
        </div>

        <!-- Senha -->
        <div class="grupo-campo">
          <label for="senha">Senha</label>
          <div class="campo-input">
            <i class="fas fa-lock"></i>
            <input type="password" id="senha" name="senha" placeholder="Crie uma senha" required>
          </div>
        </div>

        <!-- Botão cadastrar -->
        <button type="submit" class="botao-entrar">
          <span>Cadastrar</span>
          <i class="fas fa-check"></i>
        </button>

          <div class="grupo-checkbox">
            <input type="checkbox" id="modo-escuro-login">
            <label for="modo-escuro-login">Modo Escuro</label>
          </div>

      </form>

      <!-- Botão de ir para login -->
      <div style="text-align:center; margin-top:15px;">
        <a href="login.php" class="btn-teste" style="padding:10px 20px; display:inline-block;">
          <i class="fas fa-arrow-left"></i> Já tenho conta
        </a>
      </div>

    </div>

    <div class="decoracao-fundo"></div>
  </div>
</div>

<script src="js/app.js"></script>

</body>
</html>
