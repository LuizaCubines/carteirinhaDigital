<?php
// scanner.php

// Incluir conexão com o banco
require_once 'conexao.php';

$mensagem = '';
$tipo = '';

// Processamento do QR code via POST (do scanner JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qrData = json_decode($_POST['qr_data'], true);
    
    // Validar estrutura dos dados
    if (!$qrData || !isset($qrData['ra'], $qrData['hash'], $qrData['instituicao'])) {
        $mensagem = "QR Code inválido ou malformado!";
        $tipo = "error";
    } else {
        // Validar hash do QR code
        $expectedHash = md5($qrData['ra'] . 'SENAI2025_PERMANENTE');
        
        if ($qrData['hash'] === $expectedHash && $qrData['instituicao'] === 'SENAI') {
            $aluno_id = $qrData['id'];
            $nome = $qrData['nome'];
            $ra = $qrData['ra'];
            $turma = $qrData['turma'];
            
            // Registrar entrada/saída no banco usando MySQLi
            try {
                // 1. Verificar se existe entrada sem saída
                $sql = "SELECT id_acesso, data_entrada FROM acesso 
                        WHERE aluno_id = ? AND data_saida IS NULL 
                        ORDER BY data_entrada DESC LIMIT 1";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $aluno_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $ultimo_acesso = $result->fetch_assoc();
                
                if ($ultimo_acesso) {
                    // Registrar saída
                    $sql_saida = "UPDATE acesso SET data_saida = NOW() 
                                  WHERE id_acesso = ?";
                    $stmt_saida = $conn->prepare($sql_saida);
                    $stmt_saida->bind_param("i", $ultimo_acesso['id_acesso']);
                    
                    if ($stmt_saida->execute()) {
                        $mensagem = "Saída registrada para: $nome (RA: $ra)";
                        $tipo = "success";
                    } else {
                        throw new Exception("Erro ao registrar saída");
                    }
                    
                } else {
                    // Registrar entrada
                    $sql_entrada = "INSERT INTO acesso (aluno_id, data_entrada) 
                                    VALUES (?, NOW())";
                    $stmt_entrada = $conn->prepare($sql_entrada);
                    $stmt_entrada->bind_param("i", $aluno_id);
                    
                    if ($stmt_entrada->execute()) {
                        $mensagem = "Entrada válida registrada para: $nome (RA: $ra - Turma: $turma)";
                        $tipo = "success";
                    } else {
                        throw new Exception("Erro ao registrar entrada");
                    }
                }
                
            } catch (Exception $e) {
                $mensagem = "Erro ao registrar acesso: " . $e->getMessage();
                $tipo = "error";
            }
        } else {
            $mensagem = "QR Code inválido ou não autorizado!";
            $tipo = "error";
        }
    }
}

// Função para carregar histórico de scans
function carregarHistoricoScans($conn) {
    try {
        $sql = "SELECT a.*, al.nome, al.ra, al.turma 
                FROM acesso a
                JOIN aluno al ON a.aluno_id = al.id
                ORDER BY COALESCE(data_saida, data_entrada) DESC
                LIMIT 5";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $historico = [];
            while ($row = $result->fetch_assoc()) {
                $historico[] = $row;
            }
            return $historico;
        }
        return [];
        
    } catch (Exception $e) {
        // Se houver erro, retorna array vazio
        error_log("Erro ao carregar histórico: " . $e->getMessage());
        return [];
    }
}

// Carregar histórico inicial se a conexão existir
if (isset($conn) && $conn) {
    $historico = carregarHistoricoScans($conn);
} else {
    $historico = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carteirinha SENAI - Scanner de QR Code</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    /* Estilos para o scanner */
    .container-camera {
        border: 2px solid #ddd;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .mensagem-flutuante {
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
        max-width: 400px;
    }
    
    .mensagem-flutuante.success {
        background-color: #4CAF50;
    }
    
    .mensagem-flutuante.error {
        background-color: #f44336;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .item-scan {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .info-aluno {
        flex: 1;
    }
    
    .info-acesso {
        text-align: right;
    }
    
    .entrada { color: #28a745; font-weight: 600; }
    .saida { color: #dc3545; font-weight: 600; }
    
    .botao-terciario {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
    }
    
    .botao-terciario:hover {
        background-color: #5a6268;
    }
    
    .alertas-sistema {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        color: #856404;
    }
  </style>
</head>
<body>
  <nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
      <a href="scanner.php" class="logo-nav" aria-label="Logo Carteirinha SENAI">
        <i class="fas fa-graduation-cap"></i>
        <span>SENAI</span>
      </a>

      <button class="botao-menu-hamburger" id="btn-menu" aria-label="Abrir menu" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <ul class="menu-navegacao" id="menu-navegacao" role="menubar">
        <li><a href="scanner.php" class="ativo"><i class="fas fa-qrcode"></i> Scanner</a></li>
        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
      </ul>
    </div>
  </nav>

  <main class="conteudo-principal">
    <?php if ($mensagem): ?>
      <div class="mensagem-flutuante <?php echo $tipo; ?>" id="mensagem">
        <i class="fas fa-<?php echo $tipo === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <p><?php echo htmlspecialchars($mensagem); ?></p>
      </div>
      <script>
        setTimeout(() => {
          const mensagem = document.getElementById('mensagem');
          if (mensagem) {
            mensagem.style.display = 'none';
          }
        }, 5000);
      </script>
    <?php endif; ?>

    <?php if (!isset($conn) || $conn->connect_error): ?>
      <div class="alertas-sistema">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Atenção:</strong> Não foi possível conectar ao banco de dados. Verifique o arquivo conexao.php.
      </div>
    <?php endif; ?>

    <section class="secao-scanner">
      <h2>Leitor de QR Code</h2>
      <p>Escaneie um código QR para registrar entrada/saída</p>

      <div id="reader" class="container-camera" style="width: 100%; max-width: 500px; margin: 2rem auto;"></div>

      <div class="controles-scanner">
        <button id="btn-iniciar-scanner" class="botao-primario">
          <i class="fas fa-camera"></i> Iniciar Scanner
        </button>
        <button id="btn-parar-scanner" class="botao-secundario" style="display: none;">
          <i class="fas fa-stop"></i> Parar Scanner
        </button>
        <button id="btn-simular-scan" class="botao-terciario" style="margin-left: 10px;">
          <i class="fas fa-qrcode"></i> Simular Scan (Teste)
        </button>
      </div>

      <div id="resultado-scan" class="resultado-scan" style="display: none;">
        <div class="feedback-scan">
          <i class="fas fa-check-circle"></i>
          <h3>QR Code Lido com Sucesso!</h3>
          <p id="info-scan"></p>
          <p id="status-scan" style="font-style: italic;"></p>
        </div>
      </div>
    </section>

    <section class="secao-historico">
      <h3>Últimos 5 Scans</h3>
      <div class="lista-scans">
        <div id="historico-scans" class="historico-scans">
          <?php if (empty($historico)): ?>
            <p class="vazio">Nenhum scan registrado ainda</p>
          <?php else: ?>
            <?php foreach ($historico as $scan): ?>
              <div class="item-scan">
                <div class="info-aluno">
                  <strong><?php echo htmlspecialchars($scan['nome']); ?></strong><br>
                  <span>RA: <?php echo htmlspecialchars($scan['ra']); ?> - Turma: <?php echo htmlspecialchars($scan['turma']); ?></span>
                </div>
                <div class="info-acesso">
                  <?php if ($scan['data_entrada']): ?>
                    <span class="entrada">Entrada: <?php echo date('H:i', strtotime($scan['data_entrada'])); ?></span><br>
                  <?php endif; ?>
                  <?php if ($scan['data_saida']): ?>
                    <span class="saida">Saída: <?php echo date('H:i', strtotime($scan['data_saida'])); ?></span>
                  <?php else: ?>
                    <span class="entrada" style="color: orange;">Em aula</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <footer class="rodape">
    <p>&copy; 2025 Carteirinha Digital SENAI. Todos os direitos reservados.</p>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elementos da interface
        const scannerBtn = document.getElementById('btn-iniciar-scanner');
        const stopBtn = document.getElementById('btn-parar-scanner');
        const simulateBtn = document.getElementById('btn-simular-scan');
        const resultDiv = document.getElementById('resultado-scan');
        const infoScan = document.getElementById('info-scan');
        const statusScan = document.getElementById('status-scan');
        
        let html5QrcodeScanner = null;
        let isScanning = false;

        // Iniciar scanner
        if (scannerBtn) {
            scannerBtn.addEventListener('click', iniciarScanner);
        }

        // Parar scanner
        if (stopBtn) {
            stopBtn.addEventListener('click', pararScanner);
        }

        // Simular scan para testes
        if (simulateBtn) {
            simulateBtn.addEventListener('click', function() {
                // Dados de exemplo para teste
                const qrDataTeste = {
                    ra: '2023001',
                    nome: 'João Silva',
                    turma: 'DS2023',
                    id: 1,
                    instituicao: 'SENAI',
                    hash: '<?php echo md5("2023001" . "SENAI2025_PERMANENTE"); ?>'
                };
                
                processarQRCode(qrDataTeste, true);
            });
        }

        function iniciarScanner() {
            if (isScanning) return;
            
            // Verificar permissão da câmera
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    // Inicia o scanner
                    html5QrcodeScanner = new Html5QrcodeScanner(
                        "reader", 
                        { 
                            fps: 10, 
                            qrbox: 250,
                            rememberLastUsedCamera: true,
                            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
                        }
                    );
                    
                    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                    
                    scannerBtn.style.display = 'none';
                    stopBtn.style.display = 'inline-block';
                    isScanning = true;
                })
                .catch(function(error) {
                    alert("Erro ao acessar a câmera. Verifique as permissões do navegador.");
                });
        }

        function pararScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().then(() => {
                    scannerBtn.style.display = 'inline-block';
                    stopBtn.style.display = 'none';
                    isScanning = false;
                }).catch(error => {
                    console.error("Erro ao parar scanner:", error);
                });
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            try {
                // Parse dos dados do QR code
                const qrData = JSON.parse(decodedText);
                
                // Validar estrutura mínima
                if (!qrData.ra || !qrData.hash || !qrData.instituicao) {
                    throw new Error("QR Code inválido");
                }
                
                // Exibir resultado
                infoScan.textContent = `Aluno: ${qrData.nome} (RA: ${qrData.ra})`;
                statusScan.textContent = "Processando...";
                resultDiv.style.display = 'block';
                
                // Processar QR code
                processarQRCode(qrData);
                
                // Parar scanner após sucesso
                setTimeout(() => {
                    pararScanner();
                    // Limpar resultado após 3 segundos
                    setTimeout(() => {
                        resultDiv.style.display = 'none';
                    }, 3000);
                }, 1000);
                
            } catch (error) {
                console.error("Erro ao processar QR code:", error);
                alert("QR Code inválido ou corrompido!");
                pararScanner();
            }
        }

        function onScanFailure(error) {
            // Não mostra erro no console a menos que seja necessário
        }

        function processarQRCode(qrData, isSimulado = false) {
            // Enviar para o servidor
            fetch('scanner.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `qr_data=${encodeURIComponent(JSON.stringify(qrData))}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na rede');
                }
                return response.text();
            })
            .then(html => {
                // Recarrega a página para mostrar a mensagem e atualizar histórico
                window.location.reload();
            })
            .catch(error => {
                console.error('Erro:', error);
                if (statusScan) {
                    statusScan.textContent = "Erro ao processar. Tente novamente.";
                    statusScan.style.color = "red";
                }
            });
        }

        // Auto-iniciar scanner em dispositivos móveis
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            setTimeout(iniciarScanner, 1000);
        }
    });
  </script>
</body>
</html>