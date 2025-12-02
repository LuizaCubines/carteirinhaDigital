<?php
session_start();
include "conexao.php";

// Impede acesso sem login admin
if (!isset($_SESSION["adm_id"]) || $_SESSION["tipo_usuario"] !== "admin") {
    header("Location: admin_login.php?erro=sessao");
    exit;
}

// Verifica se o administrador ainda existe no banco
$adm_id = $_SESSION["adm_id"];
$sql_check = "SELECT id FROM administrador WHERE id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $adm_id);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows === 0) {
    // Admin não existe mais
    session_destroy();
    header("Location: admin_login.php?erro=sessao");
    exit;
}
$stmt_check->close();

// Resto do código continua igual...
// BUSCAR ALUNOS COM DETALHES
$sql_alunos = "SELECT a.*, 
               (SELECT COUNT(*) FROM acesso WHERE aluno_id = a.id) as total_acessos,
               (SELECT COUNT(*) FROM acesso WHERE aluno_id = a.id AND DATE(data_entrada) = CURDATE()) as acessos_hoje
               FROM aluno a 
               ORDER BY nome ASC";
$result_alunos = $conn->query($sql_alunos);

// ... resto do código permanece igual

// PARA ESTATÍSTICAS
$total_alunos = $result_alunos->num_rows;
$ativos = 0;

$lista_alunos = [];
while ($row = $result_alunos->fetch_assoc()) {
    $lista_alunos[] = $row;
    if ($row["ativo"] == 1) $ativos++;
}

// BUSCAR ÚLTIMOS ACESSOS
$sql_acessos = "SELECT ac.*, a.nome, a.ra, a.turma 
                FROM acesso ac 
                JOIN aluno a ON ac.aluno_id = a.id 
                ORDER BY ac.data_entrada DESC 
                LIMIT 50";
$result_acessos = $conn->query($sql_acessos);
$lista_acessos = [];
while ($row = $result_acessos->fetch_assoc()) {
    $lista_acessos[] = $row;
}

// Estatísticas gerais
$sql_stats = "SELECT 
              COUNT(DISTINCT aluno_id) as alunos_com_acesso,
              COUNT(*) as total_registros_acesso,
              COUNT(CASE WHEN DATE(data_entrada) = CURDATE() THEN 1 END) as acessos_hoje,
              COUNT(CASE WHEN data_saida IS NULL THEN 1 END) as acessos_ativos
              FROM acesso";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carteirinha SENAI - Painel Administrativo</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
    }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge-info { background: #d1ecf1; color: #0c5460; }
    
    .data-cell {
      font-family: 'Courier New', monospace;
      font-size: 13px;
    }
    
    .online-dot {
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin-right: 5px;
    }
    .online { background: #28a745; }
    .offline { background: #dc3545; }
  </style>
</head>

<body>

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
        <li><a href="scanner.html"><i class="fas fa-qrcode"></i> Scanner</a></li>
        <li><a href="comunicados.html"><i class="fas fa-bullhorn"></i> Comunicados</a></li>
        <li>
          <a href="admin_logout.php" class="btn-sair">
            <i class="fas fa-sign-out-alt"></i> Sair (<?php echo htmlspecialchars($_SESSION['adm_nome']); ?>)
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <main class="conteudo-principal">
    <section class="secao-admin">
      <h2>Painel Administrativo</h2>
      <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['adm_nome']); ?> | Gerenciar alunos e acessos</p>

      <div class="abas-admin">
        <button class="aba-btn ativo" data-aba="alunos"><i class="fas fa-users"></i> Alunos</button>
        <button class="aba-btn" data-aba="acessos"><i class="fas fa-door-open"></i> Acessos</button>
        <button class="aba-btn" data-aba="estatisticas"><i class="fas fa-chart-bar"></i> Estatísticas</button>
      </div>

      <div id="aba-alunos" class="conteudo-aba ativo">
        <div class="toolbar-admin">
          <input type="text" id="busca-alunos" placeholder="Buscar aluno por nome, RA ou turma..." class="campo-busca">
        </div>
        
        <div class="table-responsive">
          <table class="tabela-admin">
            <thead>
              <tr>
                <th>RA</th>
                <th>Nome</th>
                <th>Turma</th>
                <th>Nível</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Status</th>
                <th>Acessos</th>
                <th>Inscrição</th>
              </tr>
            </thead>
            <tbody id="tbody-alunos">
              <?php foreach ($lista_alunos as $a): ?>
                <tr>
                  <td><strong><?= $a["ra"] ?></strong></td>
                  <td><?= htmlspecialchars($a["nome"]) ?></td>
                  <td><span class="badge badge-info"><?= $a["turma"] ?></span></td>
                  <td><?= $a["nivel"] ?></td>
                  <td><small><?= $a["email"] ?></small></td>
                  <td><?= $a["telefone"] ?: '-' ?></td>
                  <td>
                    <?php if ($a["ativo"] == 1): ?>
                      <span class="badge badge-success">
                        <span class="online-dot online"></span> Ativo
                      </span>
                    <?php else: ?>
                      <span class="badge badge-danger">
                        <span class="online-dot offline"></span> Inativo
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge badge-warning">
                      <?= $a["total_acessos"] ?> total<br>
                      <small><?= $a["acessos_hoje"] ?> hoje</small>
                    </span>
                  </td>
                  <td class="data-cell">
                    <?= date('d/m/Y', strtotime($a["data_inscricao"])) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div id="aba-acessos" class="conteudo-aba">
        <div class="toolbar-admin">
          <input type="text" id="busca-acessos" placeholder="Buscar acesso por nome, RA..." class="campo-busca">
        </div>
        
        <div class="table-responsive">
          <table class="tabela-admin">
            <thead>
              <tr>
                <th>Aluno</th>
                <th>RA</th>
                <th>Turma</th>
                <th>Data Entrada</th>
                <th>Data Saída</th>
                <th>Status</th>
                <th>Tempo</th>
              </tr>
            </thead>
            <tbody id="tbody-acessos">
              <?php foreach ($lista_acessos as $ac): 
                $entrada = strtotime($ac["data_entrada"]);
                $saida = $ac["data_saida"] ? strtotime($ac["data_saida"]) : null;
                $tempo = $saida ? gmdate("H:i:s", $saida - $entrada) : null;
              ?>
                <tr>
                  <td><?= htmlspecialchars($ac["nome"]) ?></td>
                  <td><strong><?= $ac["ra"] ?></strong></td>
                  <td><?= $ac["turma"] ?></td>
                  <td class="data-cell">
                    <?= date('d/m/Y H:i:s', $entrada) ?>
                  </td>
                  <td class="data-cell">
                    <?= $ac["data_saida"] ? date('d/m/Y H:i:s', strtotime($ac["data_saida"])) : '<span class="badge badge-warning">EM ANDAMENTO</span>' ?>
                  </td>
                  <td>
                    <?php if ($ac["data_saida"]): ?>
                      <span class="badge badge-success">FINALIZADO</span>
                    <?php else: ?>
                      <span class="badge badge-danger">DENTRO DO SENAI</span>
                    <?php endif; ?>
                  </td>
                  <td class="data-cell">
                    <?= $tempo ?: '-' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div id="aba-estatisticas" class="conteudo-aba">
        <div class="grid-stats">
          <div class="card-stat">
            <i class="fas fa-users"></i>
            <h3>Total de Alunos</h3>
            <p id="total-alunos"><?= $total_alunos ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-user-check"></i>
            <h3>Alunos Ativos</h3>
            <p id="alunos-ativos"><?= $ativos ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-percent"></i>
            <h3>% Ativos</h3>
            <p id="percent-ativos"><?= $total_alunos > 0 ? round(($ativos / $total_alunos) * 100) . "%" : "0%" ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-door-open"></i>
            <h3>Total Acessos</h3>
            <p id="total-acessos"><?= $stats['total_registros_acesso'] ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-calendar-day"></i>
            <h3>Acessos Hoje</h3>
            <p id="acessos-hoje"><?= $stats['acessos_hoje'] ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-user-clock"></i>
            <h3>Alunos com Acesso</h3>
            <p id="alunos-acesso"><?= $stats['alunos_com_acesso'] ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-building"></i>
            <h3>Dentro Agora</h3>
            <p id="dentro-agora"><?= $stats['acessos_ativos'] ?></p>
          </div>

          <div class="card-stat">
            <i class="fas fa-chart-line"></i>
            <h3>Média Diária</h3>
            <p id="media-diaria">
              <?php 
              $dias = $total_alunos > 0 ? round($stats['total_registros_acesso'] / $total_alunos, 1) : 0;
              echo $dias;
              ?>
            </p>
          </div>
        </div>

        <div class="stats-extra">
          <h3><i class="fas fa-info-circle"></i> Informações do Sistema</h3>
          <div class="info-grid">
            <div class="info-item">
              <strong>Última Atualização:</strong>
              <span><?= date('d/m/Y H:i:s') ?></span>
            </div>
            <div class="info-item">
              <strong>Administrador Logado:</strong>
              <span><?= htmlspecialchars($_SESSION['adm_nome']) ?></span>
            </div>
            <div class="info-item">
              <strong>Sessão Ativa Desde:</strong>
              <span><?= date('H:i:s') ?></span>
            </div>
            <div class="info-item">
              <strong>Alunos Inscritos Hoje:</strong>
              <span>
                <?php
                $sql_hoje = "SELECT COUNT(*) as total FROM aluno WHERE DATE(data_inscricao) = CURDATE()";
                $result_hoje = $conn->query($sql_hoje);
                $hoje = $result_hoje->fetch_assoc();
                echo $hoje['total'];
                ?>
              </span>
            </div>
          </div>
        </div>
      </div>

    </section>
  </main>

  <footer class="rodape">
    <p>&copy; 2025 Carteirinha Digital SENAI. Todos os direitos reservados.</p>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Navegação por abas
      document.querySelectorAll(".aba-btn").forEach(btn => {
        btn.addEventListener("click", () => {
          document.querySelectorAll(".aba-btn").forEach(b => b.classList.remove("ativo"));
          document.querySelectorAll(".conteudo-aba").forEach(c => c.classList.remove("ativo"));
          
          btn.classList.add("ativo");
          document.getElementById("aba-" + btn.dataset.aba).classList.add("ativo");
        });
      });

      // Filtro de busca para alunos
      const buscaAlunos = document.getElementById('busca-alunos');
      if (buscaAlunos) {
        buscaAlunos.addEventListener('input', function() {
          const filter = this.value.toLowerCase();
          const rows = document.querySelectorAll('#tbody-alunos tr');
          
          rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
          });
        });
      }

      // Filtro de busca para acessos
      const buscaAcessos = document.getElementById('busca-acessos');
      if (buscaAcessos) {
        buscaAcessos.addEventListener('input', function() {
          const filter = this.value.toLowerCase();
          const rows = document.querySelectorAll('#tbody-acessos tr');
          
          rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
          });
        });
      }

      // Atualizar estatísticas periodicamente
      setInterval(() => {
        // Atualizar hora da sessão
        const horaAtual = new Date().toLocaleTimeString('pt-BR');
        const sessaoItem = document.querySelector('.info-item:nth-child(3) span');
        if (sessaoItem) {
          sessaoItem.textContent = horaAtual;
        }
      }, 1000);
    });

    // Logout com confirmação
    document.querySelector('.btn-sair')?.addEventListener('click', function(e) {
      if (!confirm('Tem certeza que deseja sair do sistema?')) {
        e.preventDefault();
      }
    });
  </script>

</body>
</html>
<?php $conn->close(); ?>