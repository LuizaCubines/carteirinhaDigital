<?php
session_start();
include "conexao.php";

// Verificar se o aluno está logado
if (!isset($_SESSION["aluno_id"]) || $_SESSION["tipo_usuario"] !== "aluno") {
  header("Location: login.php?erro=sessao");
  exit;
}

// Verificar se o aluno ainda existe no banco e está ativo
$aluno_id = $_SESSION["aluno_id"];
$sql_check = "SELECT id, nome, ra, turma FROM aluno WHERE id = ? AND ativo = 1";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $aluno_id);
$stmt_check->execute();
$stmt_check->store_result();
$stmt_check->bind_result($db_aluno_id, $db_aluno_nome, $db_aluno_ra, $db_aluno_turma);
$stmt_check->fetch();

if ($stmt_check->num_rows === 0) {
    session_destroy();
    header("Location: login.php?erro=conta");
    exit;
}
$stmt_check->close();

// Buscar comunicados (avisos) do banco de dados
$sql_comunicado = "SELECT 
                    c.id_comunicado,
                    c.titulo,
                    c.mensagem,
                    c.data_envio,
                    c.anexo_url,
                    a.nome as adm_nome,
                    DATE(c.data_envio) as data_envio_date
                    FROM comunicado c 
                    JOIN administrador a ON c.adm_id = a.id 
                    ORDER BY c.data_envio DESC";
$result_comunicado = $conn->query($sql_comunicado);
$comunicado = [];
$total_comunicado = 0;
$comunicados_por_data = []; 

while ($row = $result_comunicado->fetch_assoc()) {
    $comunicado[] = $row;
    $total_comunicado++;
    
    // Agrupar por data
    $data_formatada = date('d/m/Y', strtotime($row['data_envio_date']));
    $comunicados_por_data[$data_formatada][] = $row;
}

// Buscar estatísticas
$sql_stats = "SELECT 
              COUNT(*) as total,
              COUNT(DISTINCT DATE(data_envio)) as dias_com_avisos,
              MIN(data_envio) as primeiro_aviso,
              MAX(data_envio) as ultimo_aviso
              FROM comunicado";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// Buscar comunicados mais recentes (últimos 5)
$sql_recentes = "SELECT c.titulo, c.data_envio, a.nome as adm_nome 
                 FROM comunicado c 
                 JOIN administrador a ON c.adm_id = a.id 
                 ORDER BY c.data_envio DESC 
                 LIMIT 5";
$result_recentes = $conn->query($sql_recentes);
$recentes = [];
while ($row = $result_recentes->fetch_assoc()) {
    $recentes[] = $row;
}

// Buscar comunicados com anexo
$sql_com_anexo = "SELECT COUNT(*) as total FROM comunicado WHERE anexo_url IS NOT NULL";
$result_anexo = $conn->query($sql_com_anexo);
$total_com_anexo = $result_anexo->fetch_assoc()['total'];


$total_comunicados = $total_comunicado;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carteirinha SENAI - Avisos e Comunicados</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body class="pagina-avisos">
  <nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
      <a href="dashboard_aluno.php" class="logo-nav">
        <i class="fas fa-graduation-cap"></i>
        <span>SENAI</span>
      </a>

      <button class="botao-menu-hamburger" id="btn-menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>

      <ul class="menu-navegacao" id="menu-navegacao">
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="perfil_aluno.php"><i class="fas fa-user-circle"></i> Perfil</a></li>
        <li><a href="avisos.php" class="ativo"><i class="fas fa-bullhorn"></i> Avisos</a></li>
        <li>
          <a href="logout.php" class="btn-sair">
            <i class="fas fa-sign-out-alt"></i> Sair (<?php echo htmlspecialchars($db_aluno_nome); ?>)
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <main class="conteudo-principal">
    <section class="secao-admin">
      <h2><i class="fas fa-bullhorn"></i> Avisos e Comunicados</h2>
      <p>Fique por dentro de todos os comunicados da escola</p>

      <div class="avisos-container">
        <!-- Área principal com lista de avisos -->
        <div class="card-avisos">
          <div class="toolbar-avisos">
            <input type="text" id="busca-avisos" placeholder="Buscar avisos..." class="campo-busca">
            <div class="filtro-avisos">
              <button class="filtro-btn ativo" data-filtro="todos">Todos</button>
              <button class="filtro-btn" data-filtro="com-anexo">
                <i class="fas fa-paperclip"></i> Com Anexo
              </button>
              <button class="filtro-btn" data-filtro="ultima-semana">
                <i class="fas fa-calendar-week"></i> Última Semana
              </button>
            </div>
          </div>
          
          <div class="lista-avisos" id="lista-avisos">
            <?php if ($total_comunicado > 0): ?>
              <?php foreach ($comunicados_por_data as $data => $avisos_dia): ?>
                <div class="grupo-data">
                  <div class="data-header">
                    <h4>
                      <i class="fas fa-calendar-day"></i>
                      <?php echo $data; ?>
                      <small style="color: var(--cor-texto-claro); margin-left: auto;">
                        <?php echo count($avisos_dia); ?> aviso<?php echo count($avisos_dia) > 1 ? 's' : ''; ?>
                      </small>
                    </h4>
                  </div>
                  
                  <?php foreach ($avisos_dia as $aviso): 
                    $hora_envio = date('H:i', strtotime($aviso['data_envio']));
                  ?>
                    <div class="aviso-item" data-titulo="<?php echo htmlspecialchars(strtolower($aviso['titulo'])); ?>"
                         data-data="<?php echo $data; ?>"
                         data-anexo="<?php echo !empty($aviso['anexo_url']) ? 'sim' : 'nao'; ?>">
                      <div class="aviso-header">
                        <h4 class="aviso-titulo"><?php echo htmlspecialchars($aviso['titulo']); ?></h4>
                        <span class="aviso-hora"><?php echo $hora_envio; ?></span>
                      </div>
                      
                      <div class="aviso-mensagem">
                        <?php echo nl2br(htmlspecialchars($aviso['mensagem'])); ?>
                      </div>
                      
                      <div class="aviso-footer">
                        <span class="aviso-autor">
                          <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($aviso['adm_nome']); ?>
                        </span>
                        
                        <?php if (!empty($aviso['anexo_url'])): 
                          $nome_arquivo = basename($aviso['anexo_url']);
                          $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
                          $icone = match($extensao) {
                            'pdf' => 'fas fa-file-pdf',
                            'doc', 'docx' => 'fas fa-file-word',
                            'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image',
                            default => 'fas fa-file'
                          };
                        ?>
                          <a href="<?php echo $aviso['anexo_url']; ?>" target="_blank" class="btn-anexo">
                            <i class="<?php echo $icone; ?>"></i> Visualizar Anexo
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-bullhorn"></i>
                <h4>Nenhum aviso disponível</h4>
                <p>Quando houver novos comunicados da escola, eles aparecerão aqui.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Sidebar com estatísticas e histórico -->
        <div class="sidebar-stats">
          <div class="stat-card">
            <i class="fas fa-bullhorn"></i>
            <h4>Total de Avisos</h4>
            <p><?php echo $total_comunicados; ?></p>
          </div>
          
          <div class="stat-card">
            <i class="fas fa-paperclip"></i>
            <h4>Com Anexos</h4>
            <p><?php echo $total_com_anexo; ?></p>
          </div>
          
          <div class="stat-card">
            <i class="fas fa-calendar-alt"></i>
            <h4>Desde</h4>
            <p><?php echo $stats['primeiro_aviso'] ? date('d/m/Y', strtotime($stats['primeiro_aviso'])) : '-'; ?></p>
          </div>
          
          <div class="card-avisos">
            <h3><i class="fas fa-history"></i> Histórico Recente</h3>
            
            <div class="recentes-list">
              <?php if (count($recentes) > 0): ?>
                <?php foreach ($recentes as $recente): 
                  $data_recente = date('d/m', strtotime($recente['data_envio']));
                  $hora_recente = date('H:i', strtotime($recente['data_envio']));
                ?>
                  <div class="recente-item">
                    <i class="fas fa-bullhorn" style="color: var(--cor-primaria); margin-top: 0.25rem;"></i>
                    <div class="recente-conteudo">
                      <div class="recente-titulo" title="<?php echo htmlspecialchars($recente['titulo']); ?>">
                        <?php echo strlen($recente['titulo']) > 30 ? substr($recente['titulo'], 0, 30) . '...' : htmlspecialchars($recente['titulo']); ?>
                      </div>
                      <div class="recente-info">
                        <span><?php echo $data_recente; ?> às <?php echo $hora_recente; ?></span>
                        <span><?php echo htmlspecialchars($recente['adm_nome']); ?></span>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="text-align: center; color: var(--cor-texto-claro); padding: 1rem;">
                  Nenhum aviso recente
                </div>
              <?php endif; ?>
            </div>
            
            <a href="#lista-avisos" class="btn-ver-todos" id="scroll-to-top">
              <i class="fas fa-arrow-up"></i> Voltar ao Topo
            </a>
          </div>
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

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Menu responsivo
      const btnMenu = document.getElementById('btn-menu');
      const menuNavegacao = document.getElementById('menu-navegacao');
      
      if (btnMenu) {
        btnMenu.addEventListener('click', () => {
          const isExpanded = btnMenu.getAttribute('aria-expanded') === 'true';
          btnMenu.setAttribute('aria-expanded', !isExpanded);
          menuNavegacao.classList.toggle('ativo');
        });
      }
      
      // Filtro de busca
      const buscaAvisos = document.getElementById('busca-avisos');
      if (buscaAvisos) {
        buscaAvisos.addEventListener('input', function() {
          const filter = this.value.toLowerCase();
          const avisos = document.querySelectorAll('.aviso-item');
          
          avisos.forEach(aviso => {
            const titulo = aviso.dataset.titulo;
            const mensagem = aviso.querySelector('.aviso-mensagem').textContent.toLowerCase();
            
            if (titulo.includes(filter) || mensagem.includes(filter)) {
              aviso.style.display = '';
              // Mostrar o grupo de data se tiver algum aviso visível
              const grupoData = aviso.closest('.grupo-data');
              if (grupoData) {
                const avisosVisiveis = grupoData.querySelectorAll('.aviso-item[style=""]');
                grupoData.style.display = avisosVisiveis.length > 0 ? '' : 'none';
              }
            } else {
              aviso.style.display = 'none';
              // Verificar se o grupo de data ainda tem avisos visíveis
              const grupoData = aviso.closest('.grupo-data');
              if (grupoData) {
                const avisosVisiveis = grupoData.querySelectorAll('.aviso-item[style=""]');
                grupoData.style.display = avisosVisiveis.length > 0 ? '' : 'none';
              }
            }
          });
        });
      }
      
      // Filtros por botões
      const filtroBtns = document.querySelectorAll('.filtro-btn');
      filtroBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          // Remover classe ativa de todos
          filtroBtns.forEach(b => b.classList.remove('ativo'));
          // Adicionar classe ativa ao botão clicado
          this.classList.add('ativo');
          
          const filtro = this.dataset.filtro;
          aplicarFiltro(filtro);
        });
      });
      
      function aplicarFiltro(filtro) {
        const hoje = new Date();
        const umaSemanaAtras = new Date(hoje.getTime() - 7 * 24 * 60 * 60 * 1000);
        const hojeFormatado = hoje.toLocaleDateString('pt-BR');
        const umaSemanaAtrasFormatado = umaSemanaAtras.toLocaleDateString('pt-BR');
        
        const avisos = document.querySelectorAll('.aviso-item');
        avisos.forEach(aviso => {
          const temAnexo = aviso.dataset.anexo === 'sim';
          const dataAviso = aviso.dataset.data;
          
          let mostrar = true;
          
          switch(filtro) {
            case 'todos':
              mostrar = true;
              break;
              
            case 'com-anexo':
              mostrar = temAnexo;
              break;
              
            case 'ultima-semana':
              // Converter data do aviso para objeto Date para comparação
              const [dia, mes, ano] = dataAviso.split('/');
              const dataAvisoObj = new Date(ano, mes - 1, dia);
              mostrar = dataAvisoObj >= umaSemanaAtras && dataAvisoObj <= hoje;
              break;
          }
          
          aviso.style.display = mostrar ? '' : 'none';
          
          // Atualizar visibilidade dos grupos de data
          const grupoData = aviso.closest('.grupo-data');
          if (grupoData) {
            const avisosVisiveis = grupoData.querySelectorAll('.aviso-item[style=""]');
            grupoData.style.display = avisosVisiveis.length > 0 ? '' : 'none';
          }
        });
      }
      
      // Scroll suave para o topo
      const scrollBtn = document.getElementById('scroll-to-top');
      if (scrollBtn) {
        scrollBtn.addEventListener('click', function(e) {
          e.preventDefault();
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        });
      }
      
      // Auto-expandir grupo de data do dia atual
      const hoje = new Date().toLocaleDateString('pt-BR');
      const grupoHoje = Array.from(document.querySelectorAll('.data-header h4'))
        .find(el => el.textContent.includes(hoje));
      
      if (grupoHoje) {
        grupoHoje.closest('.grupo-data').scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
    
    // Modo escuro
    document.addEventListener('DOMContentLoaded', function() {
    const modoEscuroAtivo = localStorage.getItem('modoEscuro') === 'true';
    if (modoEscuroAtivo) {
        document.body.classList.add('dark-mode');}});
  </script>

</body>
</html>
<?php $conn->close(); ?>