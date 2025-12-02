<?php
session_start();
require_once 'conexao.php'; // Inclui a conexão com o banco

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Processar formulário de envio de comunicado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $mensagem = filter_input(INPUT_POST, 'mensagem', FILTER_SANITIZE_STRING);
    $adm_id = $_SESSION['admin_id']; // Supondo que admin_id está na sessão
    
    // Processar upload de anexo
    $anexo_url = null;
    if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
        $extensao = strtolower(pathinfo($_FILES['anexo']['name'], PATHINFO_EXTENSION));
        $nome_arquivo = uniqid('anexo_') . '.' . $extensao;
        $caminho = 'uploads/comunicados/' . $nome_arquivo;
        
        // Criar diretório se não existir
        if (!is_dir('uploads/comunicados')) {
            mkdir('uploads/comunicados', 0777, true);
        }
        
        if (move_uploaded_file($_FILES['anexo']['tmp_name'], $caminho)) {
            $anexo_url = $caminho;
        }
    }
    
    // Inserir no banco
    $sql = "INSERT INTO comunicado (titulo, mensagem, adm_id, anexo_url) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssis", $titulo, $mensagem, $adm_id, $anexo_url);
    
    if ($stmt->execute()) {
        $sucesso = "Comunicado enviado com sucesso!";
    } else {
        $erro = "Erro ao enviar comunicado: " . $conn->error;
    }
}

// Buscar comunicados existentes
$sql_comunicados = "SELECT c.*, a.nome as admin_nome 
                   FROM comunicado c 
                   JOIN administrador a ON c.adm_id = a.id 
                   ORDER BY c.data_envio DESC";
$result_comunicados = $conn->query($sql_comunicados);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carteirinha SENAI - Comunicados</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
      <a href="dashboard.html" class="logo-nav" aria-label="Logo Carteirinha SENAI">
        <i class="fas fa-graduation-cap"></i>
        <span>SENAI</span>
      </a>

      <button class="botao-menu-hamburger" id="btn-menu" aria-label="Abrir menu" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <ul class="menu-navegacao" id="menu-navegacao" role="menubar">
        <li><a href="dashboard.html"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="perfil.html"><i class="fas fa-user-circle"></i> Perfil</a></li>
        <li><a href="scanner.html"><i class="fas fa-qrcode"></i> Scanner</a></li>
        <li><a href="notificacoes.html"><i class="fas fa-bell"></i> Notificações</a></li>
        <li><a href="admin.html"><i class="fas fa-cog"></i> Admin</a></li>
        <li><a href="comunicados.php" class="ativo"><i class="fas fa-megaphone"></i> Comunicados</a></li>
        <li><a href="#" id="sair-btn" class="btn-sair"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
      </ul>
    </div>
  </nav>

  <main class="conteudo-principal">
    <section class="secao-hero">
      <h2>Gerenciar <span>Comunicados</span></h2>
      <p>Envie comunicados e avisos importantes para todos os alunos</p>
    </section>

    <div class="grid-duas-colunas" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
      <!-- Formulário de Envio -->
      <section class="secao-admin">
        <h3><i class="fas fa-paper-plane"></i> Novo Comunicado</h3>
        
        <?php if (isset($sucesso)): ?>
          <div class="mensagem-sucesso" style="background: var(--cor-sucesso); color: white; padding: 1rem; border-radius: var(--raio-arredondado); margin-bottom: 1rem;">
            <?php echo $sucesso; ?>
          </div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
          <div class="mensagem-erro" style="background: var(--cor-erro); color: white; padding: 1rem; border-radius: var(--raio-arredondado); margin-bottom: 1rem;">
            <?php echo $erro; ?>
          </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="form-comunicado">
          <div class="grupo-campo" style="margin-bottom: 1.5rem;">
            <label for="titulo"><i class="fas fa-heading"></i> Título</label>
            <input type="text" id="titulo" name="titulo" class="campo-input" placeholder="Digite o título do comunicado" required>
          </div>
          
          <div class="grupo-campo" style="margin-bottom: 1.5rem;">
            <label for="mensagem"><i class="fas fa-comment-alt"></i> Mensagem</label>
            <textarea id="mensagem" name="mensagem" class="campo-input" rows="5" placeholder="Digite a mensagem completa..." required style="resize: vertical; min-height: 120px;"></textarea>
          </div>
          
          <div class="grupo-campo" style="margin-bottom: 1.5rem;">
            <label for="anexo"><i class="fas fa-paperclip"></i> Anexo (Opcional)</label>
            <div class="campo-input" style="padding: 0.5rem;">
              <input type="file" id="anexo" name="anexo" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
              <small style="color: var(--cor-texto-claro); display: block; margin-top: 0.5rem;">
                Tipos permitidos: PDF, Word, JPG, PNG (Máx: 5MB)
              </small>
            </div>
          </div>
          
          <button type="submit" class="botao-primario" style="width: 100%;">
            <i class="fas fa-paper-plane"></i> Enviar Comunicado
          </button>
        </form>
      </section>

      <!-- Lista de Comunicados Enviados -->
      <section class="secao-admin">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <h3><i class="fas fa-history"></i> Comunicados Enviados</h3>
          <span class="badge-notif"><?php echo $result_comunicados->num_rows; ?></span>
        </div>
        
        <div class="lista-comunicados" style="max-height: 500px; overflow-y: auto;">
          <?php if ($result_comunicados->num_rows > 0): ?>
            <?php while ($comunicado = $result_comunicados->fetch_assoc()): ?>
              <div class="item-notificacao" style="margin-bottom: 1rem; border-left-color: var(--cor-alerta);">
                <div class="icone-notif">
                  <i class="fas fa-megaphone"></i>
                </div>
                <div class="conteudo-notif">
                  <h4><?php echo htmlspecialchars($comunicado['titulo']); ?></h4>
                  <p><?php echo nl2br(htmlspecialchars($comunicado['mensagem'])); ?></p>
                  
                  <?php if ($comunicado['anexo_url']): ?>
                    <div style="margin-top: 0.5rem;">
                      <a href="<?php echo $comunicado['anexo_url']; ?>" target="_blank" class="btn-download" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-download"></i> Baixar Anexo
                      </a>
                    </div>
                  <?php endif; ?>
                  
                  <div class="data-notif" style="margin-top: 0.5rem;">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($comunicado['admin_nome']); ?>
                    • 
                    <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($comunicado['data_envio'])); ?>
                  </div>
                </div>
                <div>
                  <form method="POST" action="excluir_comunicado.php" onsubmit="return confirm('Tem certeza que deseja excluir este comunicado?');">
                    <input type="hidden" name="id_comunicado" value="<?php echo $comunicado['id_comunicado']; ?>">
                    <button type="submit" class="btn-acao" title="Excluir">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="vazio" style="padding: 3rem;">
              <i class="fas fa-megaphone" style="font-size: 3rem; color: var(--cor-texto-claro); margin-bottom: 1rem;"></i>
              <p>Nenhum comunicado enviado ainda.</p>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </main>

  <footer class="rodape">
    <p>&copy; 2025 Carteirinha Digital SENAI. Todos os direitos reservados.</p>
  </footer>

  <script src="js/app.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Carregar funcionalidades comuns
      if (typeof verificarAutenticacao === 'function') verificarAutenticacao();
      if (typeof carregarModoEscuro === 'function') carregarModoEscuro();
      if (typeof configurarMenu === 'function') configurarMenu();
      
      // Validação do formulário
      const form = document.getElementById('form-comunicado');
      if (form) {
        form.addEventListener('submit', (e) => {
          const titulo = document.getElementById('titulo').value.trim();
          const mensagem = document.getElementById('mensagem').value.trim();
          
          if (!titulo || !mensagem) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
          }
        });
      }
    });
  </script>
</body>
</html>