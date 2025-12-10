<?php
// dashboard.php - Dashboard do Aluno com QR Code
session_start();

// Verificar se o aluno está logado
if (!isset($_SESSION['aluno_id'])) {
    header('Location: login.php');
    exit();
}

// Incluir conexão com banco de dados
include "conexao.php";

// Buscar dados do aluno
$aluno_id = $_SESSION['aluno_id'];
$sql = "SELECT id, ra, nome, turma, email, ativo FROM aluno WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Aluno não encontrado!");
}

$aluno = $result->fetch_assoc();
$stmt->close();

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

// Dados para o QR Code (permanente)
$qr_data = [
    'ra' => $aluno['ra'],
    'nome' => $aluno['nome'],
    'turma' => $aluno['turma'],
    'id' => $aluno_id,
    'instituicao' => 'SENAI',
    'hash' => md5($aluno['ra'] . 'SENAI2025_PERMANENTE')
];

// Converter para JSON
$qr_data_json = json_encode($qr_data);

// Gerar URL do QR Code permanente via API
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . 
          urlencode($qr_data_json) . 
          "&color=DC143C&bgcolor=FFFFFF&margin=10&qzone=1";

// Determinar status
$status_aluno = ($aluno['ativo'] == 1) ? 'Ativo' : 'Inativo';
$status_class = ($aluno['ativo'] == 1) ? 'status-ativo' : 'status-inativo';

// Fechar conexão
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carteirinha SENAI - Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
      <a href="dashboard.php" class="logo-nav" aria-label="Logo Carteirinha SENAI">
        <i class="fas fa-graduation-cap"></i>
        <span>SENAI</span>
      </a>


      <ul class="menu-navegacao" id="menu-navegacao" role="menubar">
        <li><a href="dashboard.php" class="ativo"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="perfil_aluno.php"><i class="fas fa-user-circle"></i> Perfil</a></li>
        <li><a href="avisos.php"><i class="fas fa-bell"></i> Avisos</a></li>
        <li><a href="login.php" id="sair-btn" class="btn-sair"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
      </ul>
    </div>
  </nav>

  <main class="conteudo-principal">
    <section class="secao-hero">
      <h2>Bem-vindo, <span id="nome-usuario"><?php echo htmlspecialchars($aluno['nome']); ?></span>!</h2>
      <p>Seu acesso rápido às funções principais</p>
    </section>

    <section class="secao-resumo">
      <div class="card-resumo">
        <div class="icone-card">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="info-card">
          <h3>Status</h3>
          <p class="<?php echo $status_class; ?>">● <?php echo $status_aluno; ?></p>
        </div>
      </div>

      <div class="card-resumo">
        <div class="icone-card">
          <i class="fas fa-calendar-check"></i>
        </div>
        <div class="info-card">
          <h3>Ver presença</h3>
          <a href="https://www.sp.senai.br/sou-aluno">Aqui</a>
        </div>
      </div>

      <div class="card-resumo">
        <div class="icone-card">
          <i class="fas fa-clock"></i>
        </div>
        <div class="info-card">
          <h3>Total de Acessos</h3>
          <p id="total-acessos"><?php echo $total_acessos; ?></p>
        </div>
      </div>
    </section>

    <section class="secao-qrcode-principal">
      <div class="card-qrcode">
        <div class="header-qrcode">
          <h3>Seu QR Code de Acesso</h3>
          <p>Válido permanentemente</p>
        </div>

        <div class="container-qrcode">
          <img src="<?php echo $qr_url; ?>" alt="QR Code de Acesso" id="qrcode-gerado" >
          <p class="info-qrcode">Passe este código no scanner para registrar entrada/saída</p>
          <p style="font-size: 0.9rem; margin-top: 10px;">
            RA: <?php echo htmlspecialchars($aluno['ra']); ?> | 
            Turma: <?php echo htmlspecialchars($aluno['turma']); ?>
          </p>
        </div>

        <button onclick="window.print()" class="botao-atualizar-qr">
          <i class="fas fa-print"></i> Imprimir QR Code
        </button>
      </div>
    </section>

    <section class="secao-timeline">
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

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const modoEscuroAtivo = localStorage.getItem('modoEscuro') === 'true';
    if (modoEscuroAtivo) {
        document.body.classList.add('dark-mode');}});
  </script>
</body>
</html>