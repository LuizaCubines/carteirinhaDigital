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
           $ra = $qrData['ra'];

            $sql = "SELECT * FROM aluno WHERE ra = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $ra);
            $stmt->execute();
            $aluno = $stmt->get_result()->fetch_assoc();

            if (!$aluno) {
                $mensagem = "Aluno não encontrado no banco!";
                $tipo = "error";
            } else {
                $aluno_id = $aluno['id'];
                $nome = $aluno['nome'];
                $turma = $aluno['turma'];
            }
            
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

</head>
<body>
  <nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
      <a href="painel_admin.php" class="logo-nav" aria-label="Logo Carteirinha SENAI">
        <i class="fas fa-graduation-cap"></i>
        <span>SENAI</span>
      </a>

      <button class="botao-menu-hamburger" id="btn-menu" aria-label="Abrir menu" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <ul class="menu-navegacao" id="menu-navegacao" role="menubar">
        <li><a href="painel_admin.php"><i class="fas fa-cog"></i> Admin</a></li>
        <li><a href="scanner.php" class="ativo"><i class="fas fa-qrcode"></i> Scanner</a></li>
         <li><a href="criar_comunicados.php"><i class="fas fa-bullhorn"></i> Comunicados</a></li>
         <li>
          <a href="admin_logout.php" class="btn-sair">
            <i class="fas fa-sign-out-alt"></i> Sair </a>
        </li>
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

      <div id="simulacao-container" style="display:none; margin-top:20px;">
    <label>Selecione um aluno:</label>
    <select id="select-aluno" class="botao-terciario">
        <option value="">-- Escolha --</option>
        <?php
        $res = $conn->query("SELECT id, nome, ra, turma FROM aluno ORDER BY nome");
        while ($a = $res->fetch_assoc()):
        ?>
            <option value="<?= $a['id'] ?>" 
                data-ra="<?= $a['ra'] ?>"
                data-nome="<?= $a['nome'] ?>"
                data-turma="<?= $a['turma'] ?>">
                <?= $a['nome'] ?> (<?= $a['ra'] ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <button id="btn-confirmar-simulacao" class="botao-primario" style="margin-top:10px;">
        Usar este aluno
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
                <div class="info-aluno"  style="color:#5a5a5a">
                  <strong><?php echo htmlspecialchars($scan['nome']); ?></strong><br>
                  <span>RA: <?php echo htmlspecialchars($scan['ra']); ?> - Turma: <?php echo htmlspecialchars($scan['turma']); ?></span>
                </div>
                <div class="info-acesso">
                  <?php if ($scan['data_entrada']): ?>
                    <span class="entradaaluno">Entrada: <?php echo date('H:i', strtotime($scan['data_entrada'])); ?></span><br>
                  <?php endif; ?>
                  <?php if ($scan['data_saida']): ?>
                    <span class="saidaaluno">Saída: <?php echo date('H:i', strtotime($scan['data_saida'])); ?></span>
                  <?php else: ?>
                    <span class="entradaaluno" style="color: orange;">Em aula</span>
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
    <div class="rodape-container">

        <nav class="rodape-links">
            <a href="https://github.com/LuizaCubines/carteirinhaDigital.git" target="_blank">GitHub </a>
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

  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
  <script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos da interface
    const scannerBtn = document.getElementById('btn-iniciar-scanner');
    const stopBtn = document.getElementById('btn-parar-scanner');
    const simulateBtn = document.getElementById('btn-simular-scan');
    const simContainer = document.getElementById('simulacao-container');
    const confirmarSimBtn = document.getElementById('btn-confirmar-simulacao');
    const selectAluno = document.getElementById('select-aluno');
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

    // Mostrar container de simulação (apenas um listener)
    if (simulateBtn && simContainer) {
        simulateBtn.addEventListener('click', () => {
            simContainer.style.display = 'block';
        });
    }

    // Confirmar simulação (gera QR válido e envia)
    if (confirmarSimBtn && selectAluno) {
        confirmarSimBtn.addEventListener('click', () => {
            const opt = selectAluno.options[selectAluno.selectedIndex];
            if (!opt || !opt.value) {
                return alert('Selecione um aluno!');
            }

            const ra = opt.dataset.ra;
            const nome = opt.dataset.nome;
            const turma = opt.dataset.turma;
            const id = opt.value;

            // Gerar hash MD5 do RA + chave fixa (mesma lógica do servidor)
            const chave = 'SENAI2025_PERMANENTE';
            const hash = CryptoJS.MD5(ra + chave).toString();

            const qrData = {
                ra: ra,
                nome: nome,
                turma: turma,
                id: id,
                instituicao: 'SENAI',
                hash: hash
            };

            // mostrar info temporária na UI
            if (infoScan) infoScan.textContent = `Simulando: ${nome} (RA ${ra})`;
            if (statusScan) {
                statusScan.textContent = 'Enviando simulação...';
                statusScan.style.color = '';
            }
            resultDiv.style.display = 'block';

            processarQRCode(qrData, true);
        });
    }

    function iniciarScanner() {
        if (isScanning) return;

        // Inicia o scanner sem checks extras de tipos
        html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);

        scannerBtn.style.display = 'none';
        stopBtn.style.display = 'inline-block';
        isScanning = true;
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
            const qrData = JSON.parse(decodedText);

            if (!qrData.ra || !qrData.hash || !qrData.instituicao) {
                throw new Error("QR Code inválido");
            }

            if (infoScan) infoScan.textContent = `Aluno: ${qrData.nome || '—'} (RA: ${qrData.ra})`;
            if (statusScan) statusScan.textContent = "Processando...";

            resultDiv.style.display = 'block';
            processarQRCode(qrData);

            // parar scanner e ocultar feedback depois
            setTimeout(() => {
                pararScanner();
                setTimeout(() => { resultDiv.style.display = 'none'; }, 3000);
            }, 1000);

        } catch (error) {
            console.error("Erro ao processar QR code:", error);
            alert("QR Code inválido ou corrompido!");
            try { pararScanner(); } catch {}
        }
    }

    function onScanFailure(error) {
        // manter limpo — logs opcionais
        // console.warn("Scan failure:", error);
    }

    function processarQRCode(qrData, isSimulado = false) {
        fetch('scanner.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `qr_data=${encodeURIComponent(JSON.stringify(qrData))}`
        })
        .then(response => {
            if (!response.ok) throw new Error('Erro na rede');
            return response.text();
        })
        .then(html => {
            // recarrega para ver histórico e mensagens do servidor
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

    // Auto-iniciar scanner em dispositivos móveis (opcional)
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        setTimeout(iniciarScanner, 1000);
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