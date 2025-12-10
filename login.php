<?php
session_start();
?>
<!--http://localhost/carteirinhaDigital-main/login.php-->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#1a1a1a">
  <title>Carteirinha SENAI - Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="tela-login">
    <div class="container-login">
      <div class="box-login">

        <div class="logo-area">
          <i class="fas fa-graduation-cap"></i>
          <h1>Carteirinha SENAI</h1>
        </div>

        
        <form action="verificar_login.php" method="POST" class="formulario-login">

          <div class="grupo-campo">
            <label for="usuario">RA ou Email</label>
            <div class="campo-input">
              <i class="fas fa-user"></i>
              <input 
                type="text" 
                id="usuario" 
                name="usuario"
                placeholder="Digite seu RA ou Email"
                required
              >
            </div>
          </div>

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

        
          <div class="grupo-checkbox">
            <input type="checkbox" id="modo-escuro-login">
            <label for="modo-escuro-login">Modo Escuro</label>
          </div>

          <button type="submit" class="botao-entrar">
            <span>Entrar</span>
            <i class="fas fa-arrow-right"></i>
          </button>

          <div style="text-align:center; margin-top:15px;">
        <a href="cadastro_aluno.php" class="btn-teste" style="padding:10px 20px; display:inline-block;">
          <i class="fas fa-arrow-left"></i> Não tenho conta
        </a>

        <a href="admin_login.php" class="btn-teste">
        <i class="fas fa-user-shield"></i> Área do Administrador
        </a>
      </div>

        </form>

      </div>
      <div class="decoracao-fundo"></div>
    </div>
  </div>

  <script src="js/modo-escuro.js"></script>

</body>
</html>
