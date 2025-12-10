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
$adm_nome = $_SESSION["adm_nome"];
$sql_check = "SELECT id, nome FROM administrador WHERE id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $adm_id);
$stmt_check->execute();
$stmt_check->store_result();
$stmt_check->bind_result($db_adm_id, $db_adm_nome);
$stmt_check->fetch();

if ($stmt_check->num_rows === 0) {
    session_destroy();
    header("Location: admin_login.php?erro=sessao");
    exit;
}
$stmt_check->close();

// Inicializar variáveis
$mensagem_sucesso = "";
$mensagem_erro = "";
$modo_edicao = false;
$comunicado_editando = null;
$id_editando = null;

// Processar ações: excluir, editar ou novo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // EXCLUIR COMUNICADO
    if (isset($_POST['excluir_comunicado'])) {
        $id_comunicado = intval($_POST['id_comunicado']);
        
        // Verificar se o comunicado pertence ao admin logado
        $sql_verificar = "SELECT adm_id, anexo_url FROM comunicado WHERE id_comunicado = ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("i", $id_comunicado);
        $stmt_verificar->execute();
        $stmt_verificar->store_result();
        $stmt_verificar->bind_result($adm_id_comunicado, $anexo_url);
        $stmt_verificar->fetch();
        
        if ($stmt_verificar->num_rows === 0) {
            $mensagem_erro = "Comunicado não encontrado.";
        } elseif ($adm_id_comunicado != $adm_id) {
            $mensagem_erro = "Você não tem permissão para excluir este comunicado.";
        } else {
            // Excluir o anexo se existir
            if ($anexo_url && file_exists($anexo_url)) {
                unlink($anexo_url);
            }
            
            // Excluir o comunicado
            $sql_excluir = "DELETE FROM comunicado WHERE id_comunicado = ?";
            $stmt_excluir = $conn->prepare($sql_excluir);
            $stmt_excluir->bind_param("i", $id_comunicado);
            
            if ($stmt_excluir->execute()) {
                $mensagem_sucesso = "Comunicado excluído com sucesso!";
            } else {
                $mensagem_erro = "Erro ao excluir comunicado: " . $stmt_excluir->error;
            }
            $stmt_excluir->close();
        }
        $stmt_verificar->close();
    }
    
    // SALVAR/ATUALIZAR COMUNICADO
    elseif (isset($_POST['salvar_comunicado'])) {
        $titulo = trim($_POST['titulo']);
        $mensagem = trim($_POST['mensagem']);
        $id_comunicado = isset($_POST['id_comunicado']) ? intval($_POST['id_comunicado']) : 0;
        $modo_edicao = $id_comunicado > 0;
        
        // Validações básicas
        if (empty($titulo) || empty($mensagem)) {
            $mensagem_erro = "Título e mensagem são obrigatórios!";
        } else {
            $anexo_url = null;
            $remover_anexo = isset($_POST['remover_anexo']) && $_POST['remover_anexo'] == '1';
            
            // Se estiver editando, buscar anexo atual
            if ($modo_edicao && !$remover_anexo) {
                $sql_anexo_atual = "SELECT anexo_url FROM comunicado WHERE id_comunicado = ?";
                $stmt_anexo_atual = $conn->prepare($sql_anexo_atual);
                $stmt_anexo_atual->bind_param("i", $id_comunicado);
                $stmt_anexo_atual->execute();
                $stmt_anexo_atual->bind_result($anexo_url);
                $stmt_anexo_atual->fetch();
                $stmt_anexo_atual->close();
            }
            
            // Processar upload de novo anexo
            if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
                $anexo = $_FILES['anexo'];
                $extensoes_permitidas = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
                $extensao = strtolower(pathinfo($anexo['name'], PATHINFO_EXTENSION));
                
                if (in_array($extensao, $extensoes_permitidas)) {
                    $diretorio_upload = "uploads/comunicados/";
                    
                    // Criar diretório se não existir
                    if (!is_dir($diretorio_upload)) {
                        mkdir($diretorio_upload, 0755, true);
                    }
                    
                    // Se estiver editando e já tem anexo, excluir o antigo
                    if ($modo_edicao && $anexo_url && file_exists($anexo_url)) {
                        unlink($anexo_url);
                    }
                    
                    // Gerar nome único para o arquivo
                    $nome_arquivo = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $anexo['name']);
                    $caminho_completo = $diretorio_upload . $nome_arquivo;
                    
                    if (move_uploaded_file($anexo['tmp_name'], $caminho_completo)) {
                        $anexo_url = $caminho_completo;
                    } else {
                        $mensagem_erro = "Erro ao fazer upload do anexo.";
                    }
                } else {
                    $mensagem_erro = "Tipo de arquivo não permitido. Use: " . implode(', ', $extensoes_permitidas);
                }
            }
            
            // Se estiver editando e solicitou remover anexo
            if ($modo_edicao && $remover_anexo && $anexo_url && file_exists($anexo_url)) {
                unlink($anexo_url);
                $anexo_url = null;
            }
            
            if (empty($mensagem_erro)) {
                if ($modo_edicao) {
                    // Atualizar comunicado existente
                    // Verificar se o comunicado pertence ao admin logado
                    $sql_verificar = "SELECT adm_id FROM comunicado WHERE id_comunicado = ?";
                    $stmt_verificar = $conn->prepare($sql_verificar);
                    $stmt_verificar->bind_param("i", $id_comunicado);
                    $stmt_verificar->execute();
                    $stmt_verificar->store_result();
                    $stmt_verificar->bind_result($adm_id_comunicado);
                    $stmt_verificar->fetch();
                    
                    if ($stmt_verificar->num_rows === 0) {
                        $mensagem_erro = "Comunicado não encontrado.";
                    } elseif ($adm_id_comunicado != $adm_id) {
                        $mensagem_erro = "Você não tem permissão para editar este comunicado.";
                    } else {
                        $sql_atualizar = "UPDATE comunicado SET titulo = ?, mensagem = ?, anexo_url = ? WHERE id_comunicado = ?";
                        $stmt_atualizar = $conn->prepare($sql_atualizar);
                        $stmt_atualizar->bind_param("sssi", $titulo, $mensagem, $anexo_url, $id_comunicado);
                        
                        if ($stmt_atualizar->execute()) {
                            $mensagem_sucesso = "Comunicado atualizado com sucesso!";
                        } else {
                            $mensagem_erro = "Erro ao atualizar comunicado: " . $stmt_atualizar->error;
                        }
                        $stmt_atualizar->close();
                    }
                    $stmt_verificar->close();
                } else {
                    // Inserir novo comunicado
                    $sql_inserir = "INSERT INTO comunicado (titulo, mensagem, anexo_url, adm_id) VALUES (?, ?, ?, ?)";
                    $stmt_inserir = $conn->prepare($sql_inserir);
                    $stmt_inserir->bind_param("sssi", $titulo, $mensagem, $anexo_url, $adm_id);
                    
                    if ($stmt_inserir->execute()) {
                        $mensagem_sucesso = "Comunicado enviado com sucesso!";
                    } else {
                        $mensagem_erro = "Erro ao salvar comunicado: " . $stmt_inserir->error;
                    }
                    $stmt_inserir->close();
                }
                
                // Limpar campos do formulário após sucesso
                if (empty($mensagem_erro)) {
                    $modo_edicao = false;
                    $comunicado_editando = null;
                    $id_editando = null;
                }
            }
        }
    }
}

// Processar ação GET para editar
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_editando = intval($_GET['editar']);
    
    // Buscar comunicado para edição
    $sql_editar = "SELECT * FROM comunicado WHERE id_comunicado = ? AND adm_id = ?";
    $stmt_editar = $conn->prepare($sql_editar);
    $stmt_editar->bind_param("ii", $id_editando, $adm_id);
    $stmt_editar->execute();
    $result_editar = $stmt_editar->get_result();
    
    if ($result_editar->num_rows > 0) {
        $comunicado_editando = $result_editar->fetch_assoc();
        $modo_edicao = true;
    } else {
        $mensagem_erro = "Comunicado não encontrado ou você não tem permissão para editá-lo.";
    }
    $stmt_editar->close();
}

// Cancelar edição
if (isset($_GET['cancelar'])) {
    $modo_edicao = false;
    $comunicado_editando = null;
    $id_editando = null;
}

// Buscar comunicados existentes
$sql_comunicados = "SELECT c.*, a.nome as adm_nome 
                    FROM comunicado c 
                    JOIN administrador a ON c.adm_id = a.id 
                    ORDER BY c.data_envio DESC";
$result_comunicados = $conn->query($sql_comunicados);
$comunicados = [];

while ($row = $result_comunicados->fetch_assoc()) {
    $comunicados[] = $row;
}

// Contar comunicados
$total_comunicados = count($comunicados);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carteirinha SENAI - Comunicados</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .comunicados-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-top: 2rem;
    }
    
    @media (max-width: 992px) {
      .comunicados-container {
        grid-template-columns: 1fr;
      }
    }
    
    .card-comunicado {
      background: var(--cor-vidro);
      backdrop-filter: blur(20px);
      border: 1px solid var(--cor-borda);
      border-radius: var(--raio-grande);
      padding: 2rem;
      box-shadow: var(--sombra-leve);
      margin-bottom: 1.5rem;
    }
    
    .card-comunicado h3 {
      color: var(--cor-secundaria);
      margin-bottom: 1rem;
      font-size: 1.3rem;
      border-bottom: 2px solid var(--cor-primaria);
      padding-bottom: 0.5rem;
    }
    
    .form-comunicado {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }
    
    .grupo-form {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    
    .grupo-form label {
      color: var(--cor-texto);
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    .campo-texto, .campo-textarea {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--cor-borda);
      border-radius: var(--raio-arredondado);
      padding: 0.75rem 1rem;
      color: var(--cor-texto);
      font-family: 'Inter', sans-serif;
      font-size: 1rem;
      transition: var(--transicao);
    }
    
    .campo-texto:focus, .campo-textarea:focus {
      outline: none;
      border-color: var(--cor-primaria);
      box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.1);
    }
    
    .campo-textarea {
      min-height: 200px;
      resize: vertical;
    }
    
    .campo-arquivo {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--cor-borda);
      border-radius: var(--raio-arredondado);
      padding: 0.75rem 1rem;
      color: var(--cor-texto);
      cursor: pointer;
    }
    
    .campo-arquivo:hover {
      background: rgba(255, 255, 255, 0.1);
    }
    
    .botao-enviar, .botao-atualizar {
      background: linear-gradient(135deg, var(--cor-primaria), #ff1744);
      border: none;
      color: white;
      padding: 0.85rem 1.5rem;
      border-radius: var(--raio-arredondado);
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: var(--transicao);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      box-shadow: 0 4px 15px rgba(220, 20, 60, 0.3);
    }
    
    .botao-cancelar {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid var(--cor-borda);
      color: var(--cor-texto);
      padding: 0.85rem 1.5rem;
      border-radius: var(--raio-arredondado);
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: var(--transicao);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      text-decoration: none;
    }
    
    .botao-enviar:hover, .botao-atualizar:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(220, 20, 60, 0.4);
    }
    
    .botao-cancelar:hover {
      background: rgba(255, 255, 255, 0.15);
    }
    
    .form-botoes {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    
    .lista-comunicados {
      max-height: 600px;
      overflow-y: auto;
      padding-right: 0.5rem;
    }
    
    .comunicado-item {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--cor-borda);
      border-radius: var(--raio-arredondado);
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: var(--transicao);
      position: relative;
    }
    
    .comunicado-item:hover {
      background: rgba(255, 255, 255, 0.08);
      transform: translateY(-2px);
      box-shadow: var(--sombra-leve);
    }
    
    .comunicado-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
      border-bottom: 1px solid rgba(220, 20, 60, 0.2);
      padding-bottom: 0.5rem;
    }
    
    .comunicado-titulo {
      color: var(--cor-secundaria);
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0;
    }
    
    .comunicado-data {
      color: var(--cor-texto-claro);
      font-size: 0.85rem;
      white-space: nowrap;
      margin-left: 1rem;
    }
    
    .comunicado-mensagem {
      color: var(--cor-texto);
      line-height: 1.6;
      margin-bottom: 1rem;
      white-space: pre-line;
    }
    
    .comunicado-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .comunicado-autor {
      color: var(--cor-primaria);
      font-weight: 500;
      font-size: 0.9rem;
    }
    
    .comunicado-acoes {
      display: flex;
      gap: 0.5rem;
    }
    
    .btn-anexo, .btn-editar, .btn-excluir {
      background: rgba(220, 20, 60, 0.1);
      border: 1px solid rgba(220, 20, 60, 0.3);
      color: var(--cor-primaria);
      padding: 0.4rem 0.8rem;
      border-radius: var(--raio-arredondado);
      font-size: 0.85rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: var(--transicao);
      cursor: pointer;
    }
    
    .btn-excluir {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: var(--cor-erro);
    }
    
    .btn-anexo:hover, .btn-editar:hover {
      background: rgba(220, 20, 60, 0.2);
    }
    
    .btn-excluir:hover {
      background: rgba(239, 68, 68, 0.2);
    }
    
    .mensagem-alerta {
      padding: 1rem;
      border-radius: var(--raio-arredondado);
      margin-bottom: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .mensagem-sucesso {
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #10B981;
    }
    
    .mensagem-erro {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #EF4444;
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: var(--cor-texto-claro);
    }
    
    .empty-state i {
      font-size: 3rem;
      margin-bottom: 1rem;
      color: var(--cor-texto-claro);
      opacity: 0.5;
    }
    
    .anexo-atual {
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.3);
      border-radius: var(--raio-arredondado);
      padding: 0.75rem;
      margin-top: 0.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .anexo-info {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--cor-texto);
    }
    
    .btn-remover-anexo {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: var(--cor-erro);
      padding: 0.25rem 0.5rem;
      border-radius: var(--raio-arredondado);
      font-size: 0.8rem;
      cursor: pointer;
    }
    
    .btn-remover-anexo:hover {
      background: rgba(239, 68, 68, 0.2);
    }
    
    .badge-edicao {
      background: var(--cor-primaria);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 0.5rem;
    }
    
    .comunicado-editando {
      border: 2px solid var(--cor-primaria);
      background: rgba(220, 20, 60, 0.05);
    }
  </style>
</head>

<body>

  <nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
      <a href="painel_admin.php" class="logo-nav">
        <i class="fas fa-graduation-cap"></i>
        <span>SENAI</span>
      </a>

      <ul class="menu-navegacao" id="menu-navegacao">
        <li><a href="painel_admin.php"><i class="fas fa-cog"></i> Admin</a></li>
        <li><a href="scanner.php"><i class="fas fa-qrcode"></i> Scanner</a></li>
        <li><a href="criar_comunicados.php" class="ativo"><i class="fas fa-bullhorn"></i> Comunicados</a></li>
        <li>
          <a href="admin_logout.php" class="btn-sair">
            <i class="fas fa-sign-out-alt"></i> Sair (<?php echo htmlspecialchars($adm_nome); ?>)
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <main class="conteudo-principal">
    <section class="secao-admin">
      <h2><i class="fas fa-bullhorn"></i> Gerenciar Comunicados</h2>
      <p><?php echo $modo_edicao ? 'Editando comunicado' : 'Envie comunicados para os alunos e visualize os anteriores'; ?></p>

      <!-- Mensagens de feedback -->
      <?php if (!empty($mensagem_sucesso)): ?>
        <div class="mensagem-alerta mensagem-sucesso">
          <i class="fas fa-check-circle"></i> <?php echo $mensagem_sucesso; ?>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($mensagem_erro)): ?>
        <div class="mensagem-alerta mensagem-erro">
          <i class="fas fa-exclamation-circle"></i> <?php echo $mensagem_erro; ?>
        </div>
      <?php endif; ?>

      <div class="comunicados-container">
        <!-- Formulário de envio/edição de comunicado -->
        <div class="card-comunicado">
          <h3>
            <?php echo $modo_edicao ? 'Editar Comunicado' : 'Novo Comunicado'; ?>
            <?php if ($modo_edicao): ?>
              <span class="badge-edicao">Editando</span>
            <?php endif; ?>
          </h3>
          <form class="form-comunicado" method="POST" enctype="multipart/form-data">
            <?php if ($modo_edicao): ?>
              <input type="hidden" name="id_comunicado" value="<?php echo $comunicado_editando['id_comunicado']; ?>">
            <?php endif; ?>
            
            <div class="grupo-form">
              <label for="titulo">Título do Comunicado</label>
              <input type="text" id="titulo" name="titulo" class="campo-texto" 
                     placeholder="Digite o título do comunicado" 
                     value="<?php echo $modo_edicao ? htmlspecialchars($comunicado_editando['titulo']) : (isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''); ?>" 
                     required>
            </div>
            
            <div class="grupo-form">
              <label for="mensagem">Mensagem</label>
              <textarea id="mensagem" name="mensagem" class="campo-textarea" 
                        placeholder="Digite a mensagem do comunicado" required><?php echo $modo_edicao ? htmlspecialchars($comunicado_editando['mensagem']) : (isset($_POST['mensagem']) ? htmlspecialchars($_POST['mensagem']) : ''); ?></textarea>
            </div>
            
            <div class="grupo-form">
              <label for="anexo">Anexar Arquivo (Opcional)</label>
              <input type="file" id="anexo" name="anexo" class="campo-arquivo" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
              <small style="color: var(--cor-texto-claro); margin-top: 0.25rem;">
                Formatos permitidos: PDF, DOC, DOCX, JPG, PNG, GIF (Máx: 5MB)
              </small>
              
              <?php if ($modo_edicao && !empty($comunicado_editando['anexo_url'])): 
                $nome_arquivo = basename($comunicado_editando['anexo_url']);
                $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
                $icone = match($extensao) {
                  'pdf' => 'fas fa-file-pdf',
                  'doc', 'docx' => 'fas fa-file-word',
                  'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image',
                  default => 'fas fa-file'
                };
              ?>
                <div class="anexo-atual">
                  <div class="anexo-info">
                    <i class="<?php echo $icone; ?>"></i>
                    <span><?php echo $nome_arquivo; ?></span>
                  </div>
                  <button type="button" class="btn-remover-anexo" onclick="document.getElementById('remover_anexo').value = '1'; this.parentElement.style.display = 'none';">
                    <i class="fas fa-trash"></i> Remover
                  </button>
                  <input type="hidden" id="remover_anexo" name="remover_anexo" value="0">
                </div>
              <?php endif; ?>
            </div>
            
            <div class="form-botoes">
              <?php if ($modo_edicao): ?>
                <button type="submit" name="salvar_comunicado" class="botao-atualizar">
                  <i class="fas fa-save"></i> Atualizar Comunicado
                </button>
                <a href="criar_comunicados.php?cancelar=1" class="botao-cancelar">
                  <i class="fas fa-times"></i> Cancelar
                </a>
              <?php else: ?>
                <button type="submit" name="salvar_comunicado" class="botao-enviar">
                  <i class="fas fa-paper-plane"></i> Enviar Comunicado
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- Lista de comunicados existentes -->
        <div class="card-comunicado">
          <h3>Comunicados Enviados (<?php echo $total_comunicados; ?>)</h3>
          
          <div class="lista-comunicados">
            <?php if ($total_comunicados > 0): ?>
              <?php foreach ($comunicados as $comunicado): 
                $data_envio = date('d/m/Y H:i', strtotime($comunicado['data_envio']));
                $editando_este = $modo_edicao && $comunicado['id_comunicado'] == $id_editando;
              ?>
                <div class="comunicado-item <?php echo $editando_este ? 'comunicado-editando' : ''; ?>">
                  <div class="comunicado-header">
                    <h4 class="comunicado-titulo"><?php echo htmlspecialchars($comunicado['titulo']); ?></h4>
                    <span class="comunicado-data"><?php echo $data_envio; ?></span>
                  </div>
                  
                  <div class="comunicado-mensagem">
                    <?php echo nl2br(htmlspecialchars($comunicado['mensagem'])); ?>
                  </div>
                  
                  <div class="comunicado-footer">
                    <span class="comunicado-autor">
                      <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($comunicado['adm_nome']); ?>
                      <?php if ($comunicado['adm_id'] == $adm_id): ?>
                        <small>(Você)</small>
                      <?php endif; ?>
                    </span>
                    
                    <div class="comunicado-acoes">
                      <?php if ($comunicado['adm_id'] == $adm_id): ?>
                        <a href="criar_comunicados.php?editar=<?php echo $comunicado['id_comunicado']; ?>" class="btn-editar">
                          <i class="fas fa-edit"></i> Editar
                        </a>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este comunicado? Esta ação não pode ser desfeita.');">
                          <input type="hidden" name="id_comunicado" value="<?php echo $comunicado['id_comunicado']; ?>">
                          <button type="submit" name="excluir_comunicado" class="btn-excluir">
                            <i class="fas fa-trash"></i> Excluir
                          </button>
                        </form>
                      <?php endif; ?>
                      
                      <?php if (!empty($comunicado['anexo_url'])): 
                        $nome_arquivo = basename($comunicado['anexo_url']);
                        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
                        $icone = match($extensao) {
                          'pdf' => 'fas fa-file-pdf',
                          'doc', 'docx' => 'fas fa-file-word',
                          'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image',
                          default => 'fas fa-file'
                        };
                      ?>
                        <a href="<?php echo $comunicado['anexo_url']; ?>" target="_blank" class="btn-anexo">
                          <i class="<?php echo $icone; ?>"></i> Anexo
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state">
                <i class="fas fa-bullhorn"></i>
                <h4>Nenhum comunicado enviado</h4>
                <p>Comece enviando seu primeiro comunicado usando o formulário ao lado.</p>
              </div>
            <?php endif; ?>
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
      
      // Validar tamanho do arquivo antes de enviar
      const formComunicado = document.querySelector('.form-comunicado');
      const inputArquivo = document.getElementById('anexo');
      
      if (formComunicado && inputArquivo) {
        formComunicado.addEventListener('submit', function(e) {
          if (inputArquivo.files.length > 0) {
            const arquivo = inputArquivo.files[0];
            const tamanhoMaximo = 5 * 1024 * 1024; // 5MB
            
            if (arquivo.size > tamanhoMaximo) {
              e.preventDefault();
              alert('O arquivo é muito grande. O tamanho máximo é 5MB.');
              inputArquivo.value = '';
            }
          }
        });
      }
      
      // Auto-resize para textarea
      const textarea = document.getElementById('mensagem');
      if (textarea) {
        textarea.addEventListener('input', function() {
          this.style.height = 'auto';
          this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Trigger inicial
        textarea.dispatchEvent(new Event('input'));
      }
      
      // Scroll para o formulário se estiver editando
      <?php if ($modo_edicao): ?>
        const formulario = document.querySelector('.comunicados-container');
        if (formulario) {
          formulario.scrollIntoView({ behavior: 'smooth' });
        }
      <?php endif; ?>
      
      // Preview de arquivo selecionado
      if (inputArquivo) {
        const previewContainer = document.createElement('div');
        previewContainer.className = 'arquivo-preview';
        previewContainer.style.marginTop = '0.5rem';
        previewContainer.style.display = 'none';
        inputArquivo.parentNode.appendChild(previewContainer);
        
        inputArquivo.addEventListener('change', function() {
          if (this.files.length > 0) {
            const arquivo = this.files[0];
            const tamanhoMB = (arquivo.size / (1024 * 1024)).toFixed(2);
            
            previewContainer.innerHTML = `
              <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: rgba(220, 20, 60, 0.05); border-radius: 4px;">
                <i class="fas fa-file" style="color: var(--cor-primaria);"></i>
                <span style="font-size: 0.9rem; color: var(--cor-texto);">${arquivo.name}</span>
                <small style="color: var(--cor-texto-claro); margin-left: auto;">${tamanhoMB} MB</small>
                <button type="button" class="btn-remover-arquivo" style="background: none; border: none; color: var(--cor-erro); cursor: pointer;">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            `;
            previewContainer.style.display = 'block';
            
            // Remover arquivo do input
            previewContainer.querySelector('.btn-remover-arquivo').addEventListener('click', function() {
              inputArquivo.value = '';
              previewContainer.style.display = 'none';
            });
          } else {
            previewContainer.style.display = 'none';
          }
        });
      }
      
      // Contador de caracteres para mensagem
      if (textarea) {
        const contadorContainer = document.createElement('div');
        contadorContainer.className = 'contador-caracteres';
        contadorContainer.style.fontSize = '0.8rem';
        contadorContainer.style.color = 'var(--cor-texto-claro)';
        contadorContainer.style.textAlign = 'right';
        contadorContainer.style.marginTop = '0.25rem';
        textarea.parentNode.appendChild(contadorContainer);
        
        function atualizarContador() {
          const caracteres = textarea.value.length;
          contadorContainer.textContent = `${caracteres} caracteres`;
          
          if (caracteres > 1000) {
            contadorContainer.style.color = 'var(--cor-alerta)';
          } else if (caracteres > 2000) {
            contadorContainer.style.color = 'var(--cor-erro)';
          } else {
            contadorContainer.style.color = 'var(--cor-texto-claro)';
          }
        }
        
        textarea.addEventListener('input', atualizarContador);
        atualizarContador(); // Inicializar
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