<?php
session_start();
include "conexao.php";

// Impede acesso sem login admin
if (!isset($_SESSION["adm_id"])) {
    header("Location: admin_login.php");
    exit;
}

// BUSCAR ALUNOS
$sql = "SELECT * FROM aluno ORDER BY nome ASC";
$result = $conn->query($sql);

// PARA ESTATÍSTICAS
$total_alunos = $result->num_rows;
$ativos = 0;

$lista_alunos = [];

while ($row = $result->fetch_assoc()) {
    $lista_alunos[] = $row;
    if ($row["ativo"] == 1) $ativos++;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carteirinha SENAI - Painel Administrativo</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <!-- NAVBAR IGUAL AO ANEXADO -->
  <nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
      <a class="logo-nav">
        <i class="fas fa-graduation-cap"></i>
        <span>SENAI</span>
      </a>

      <button class="botao-menu-hamburger" id="btn-menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>

      <ul class="menu-navegacao" id="menu-navegacao">
        <li><a href="#" class="ativo"><i class="fas fa-cog"></i> Admin</a></li>
        <li><a href="admin_logout.php" class="btn-sair">
            <i class="fas fa-sign-out-alt"></i> Sair</a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- CONTEÚDO PRINCIPAL -->
  <main class="conteudo-principal">
    <section class="secao-admin">
      <h2>Painel Administrativo</h2>
      <p>Gerenciar alunos e estatísticas</p>

      <!-- ABAS -->
      <div class="abas-admin">
        <button class="aba-btn ativo" data-aba="alunos"><i class="fas fa-users"></i> Alunos</button>
        <button class="aba-btn" data-aba="estatisticas"><i class="fas fa-chart-bar"></i> Estatísticas</button>
      </div>

      <!-- ABA ALUNOS -->
      <div id="aba-alunos" class="conteudo-aba ativo">

        <div class="toolbar-admin">
          <input type="text" id="busca-alunos" placeholder="Buscar aluno..." class="campo-busca">
        </div>

        <table class="tabela-admin">
          <thead>
            <tr>
              <th>RA</th>
              <th>Nome</th>
              <th>Turma</th>
              <th>Email</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="tbody-alunos">
            <?php foreach ($lista_alunos as $a): ?>
              <tr>
                <td><?= $a["ra"] ?></td>
                <td><?= $a["nome"] ?></td>
                <td><?= $a["turma"] ?></td>
                <td><?= $a["email"] ?></td>
                <td>
                  <?php if ($a["ativo"] == 1): ?>
                    <span style="color: green; font-weight:600;">Ativo</span>
                  <?php else: ?>
                    <span style="color: red; font-weight:600;">Inativo</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>

      <!-- ABA ESTATÍSTICAS -->
      <div id="aba-estatisticas" class="conteudo-aba">

        <div class="grid-stats">
          <div class="card-stat">
            <i class="fas fa-users"></i>
            <h3>Total de Alunos</h3>
            <p><?= $total_alunos ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-user-check"></i>
            <h3>Alunos Ativos</h3>
            <p><?= $ativos ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-percent"></i>
            <h3>% Ativos</h3>
            <p><?= $total_alunos > 0 ? round(($ativos / $total_alunos) * 100) . "%" : "0%" ?></p>
          </div>
        </div>

      </div>

    </section>
  </main>

  <footer class="rodape">
    <p>&copy; 2025 Carteirinha Digital SENAI. Todos os direitos reservados.</p>
  </footer>

  <script src="js/app.js"></script>

  <!-- SCRIPT PARA ABAS -->
  <script>
    document.querySelectorAll(".aba-btn").forEach(btn => {
      btn.addEventListener("click", () => {

        document.querySelectorAll(".aba-btn").forEach(b => b.classList.remove("ativo"));
        document.querySelectorAll(".conteudo-aba").forEach(c => c.classList.remove("ativo"));

        btn.classList.add("ativo");
        document.getElementById("aba-" + btn.dataset.aba).classList.add("ativo");
      });
    });
  </script>

</body>
</html>
