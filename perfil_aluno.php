<?php
session_start();
include "conexao.php";

// Verifica se o aluno está logado
if (!isset($_SESSION['aluno_id'])) {
    header("Location: login.php"); 
    exit;
}

// Pega o ID do aluno logado
$aluno_id = $_SESSION['aluno_id'];

// Consulta os dados do aluno no banco
$sql = "SELECT * FROM aluno WHERE id = '$aluno_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $aluno = $result->fetch_assoc();
} else {
    echo "Aluno não encontrado!";
    exit;
}

// Buscar total de acessos
$sql_acessos = "SELECT COUNT(*) as total FROM acesso WHERE aluno_id = ?";
$stmt = $conn->prepare($sql_acessos);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result_acessos = $stmt->get_result();
$total_acessos = $result_acessos->fetch_assoc()['total'];
$stmt->close();

// Buscar últimos acessos
$sql_ultimos = "SELECT data_entrada, data_saida FROM acesso WHERE aluno_id = ? ORDER BY data_entrada DESC LIMIT 5";
$stmt = $conn->prepare($sql_ultimos);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$ultimos_acessos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Perfil - Carteirinha Digital SENAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Barra de navegação -->
<nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
        <a href="dashboard.php" class="logo-nav" aria-label="Logo Carteirinha SENAI">
            <i class="fas fa-graduation-cap"></i>
            <span>SENAI</span>
        </a>
        <button class="botao-menu-hamburger" id="btn-menu" aria-label="Abrir menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <ul class="menu-navegacao" id="menu-navegacao" role="menubar">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="perfil_aluno.php" class="ativo"><i class="fas fa-user-circle"></i> Perfil</a></li>
            <li><a href="avisos.php"><i class="fas fa-bell"></i> Avisos </a></li>
            <li><a href="logout.php" id="sair-btn" class="btn-sair"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </div>
</nav>

<main class="conteudo-principal">
    <div class="card-perfil">

        <div class="foto-aluno">
          <img src="<?= $aluno['foto'] ? $aluno['foto'] : 'https://via.placeholder.com/120' ?>" alt="Foto do Aluno">

    <form action="upload_foto.php" method="POST" enctype="multipart/form-data">
        <label class="btn-upload-foto">
            <i class="fas fa-camera"></i> Alterar foto
            <input type="file" name="foto" accept="image/*" onchange="this.form.submit()">
        </label>
    </form>
</div>

        <div class="dados-aluno">
            <h2 id="nome-perfil"><?= $aluno['nome'] ?></h2>
            <p class="ra-aluno">RA: <span id="ra-perfil"><?= $aluno['ra'] ?></span></p>
            <p class="turma-aluno">Turma: <span id="turma-perfil"><?= $aluno['turma'] ?></span></p>
            <span class="status-ativo">● Ativo</span>
        </div>
    </div>

    <section class="secao-informacoes">
        <h3>Informações Pessoais</h3>
        <div class="grid-info">
            <div class="item-info">
                <label>Email</label>
                <p id="email-aluno"><?= $aluno['email'] ?></p>
            </div>
            <div class="item-info">
                <label>Telefone</label>
                <p id="telefone-aluno"><?= $aluno['telefone'] ?></p>
            </div>
            <div class="item-info">
                <label>Data de Inscrição</label>
                <p id="data-inscricao"><?= date('d \d\e F \d\e Y', strtotime($aluno['data_inscricao'])) ?></p>
            </div>
            <div class="item-info">
                <label>Nível</label>
                <p id="nivel-aluno"><?= $aluno['nivel'] ?></p>
            </div>
        </div>
    </section>

    <section class="secao-documentos">
         <h3>Últimos Acessos</h3>
      <div id="timeline-acessos" class="timeline-acessos">
        <?php if (empty($ultimos_acessos)): ?>
          <div class="item-timeline">
            <div class="ponto-timeline"></div>
            <div class="conteudo-timeline">
              <p class="horario-acesso">Nenhum acesso registrado ainda</p>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($ultimos_acessos as $acesso): ?>
          <div class="item-timeline">
            <div class="ponto-timeline"></div>
            <div class="conteudo-timeline">
              <p class="horario-acesso">
                <strong>Entrada:</strong> 
                <?php echo date('d/m/Y H:i', strtotime($acesso['data_entrada'])); ?>
              </p>
              <?php if ($acesso['data_saida']): ?>
              <p class="horario-acesso">
                <strong>Saída:</strong> 
                <?php echo date('d/m/Y H:i', strtotime($acesso['data_saida'])); ?>
              </p>
              <?php else: ?>
              <p style="color: #28a745; font-weight: 600;">
                <strong>Status:</strong> Dentro da escola
              </p>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <section class="secao-configuracoes">
        <h3>Configurações</h3>
        <div class="lista-config">

            <div class="item-config">
                <div class="info-config">
                    <p>Modo Escuro</p>
                    <span>Ativar tema escuro</span>
                </div>
                <input type="checkbox" id="modo-escuro">
            </div>
        </div>
    </section>
</main>

<footer class="rodape">
    <div class="rodape-container">

        <nav class="rodape-links">
            <a href="https://github.com/LuizaCubines/carteirinhaDigital.git" target="_blank">GitHub</a>
            <a href="https://sesisenaispedu-my.sharepoint.com/:w:/g/personal/luiza_cubines2_senaisp_edu_br/IQBfLng_UpdOT4rbqF4QnXd9AYTZB3-n6QJgW-jmCik7amc?e=6t0jpt">Documentação</a>
        </nav>

        <p class="rodape-copy">
            © 2025 Carteirinha Digital SENAI — Todos os direitos reservados.
        </p>

        <p class="rodape-creditos">
            <span class="icon-codigo"></span> Desenvolvido por <strong>EGLA Squad</strong>
        </p>

    </div>
</footer>


<script src="js/modo-escuro.js"></script>
</body>
</html>
