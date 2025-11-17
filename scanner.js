// ========== SCANNER DE QR CODE ==========
let scannerHtml5 = null;

function inicializarScanner() {
  const btnIniciar = document.getElementById('btn-iniciar-scanner');
  const btnParar = document.getElementById('btn-parar-scanner');
  const resultadoDiv = document.getElementById('resultado-scan');

  if (!btnIniciar) return;

  btnIniciar.addEventListener('click', async () => {
    try {
      const permissoes = await navigator.permissions.query({ name: 'camera' });
      
      if (permissoes.state === 'denied') {
        alert('Permissão de câmera negada. Por favor, habilite nas configurações do navegador.');
        return;
      }

      btnIniciar.style.display = 'none';
      btnParar.style.display = 'inline-flex';

      scannerHtml5 = new Html5Qrcode('reader');

      const config = {
        fps: 10,
        qrbox: { width: 250, height: 250},
        aspectRatio: 1.0
      };

      scannerHtml5.start(
        { facingMode: 'environment' },
        config,
        (decodedText) => {
          processarQRCode(decodedText);
          pausarScanner();
        },
        (errorMessage) => {
          // Erro de scanning - continuar tentando
        }
      );
    } catch (err) {
      console.error('Erro ao iniciar scanner:', err);
      alert('Erro ao acessar a câmera: ' + err.message);
      btnIniciar.style.display = 'inline-flex';
      btnParar.style.display = 'none';
    }
  });

  btnParar.addEventListener('click', pausarScanner);

  carregarHistoricoScans();
}

function pausarScanner() {
  if (scannerHtml5) {
    scannerHtml5.stop().then(() => {
      document.getElementById('btn-iniciar-scanner').style.display = 'inline-flex';
      document.getElementById('btn-parar-scanner').style.display = 'none';
      scannerHtml5 = null;
    });
  }
}

function processarQRCode(dadosQR) {
  const partesQR = dadosQR.split('|');
  
  if (partesQR.length === 3 && partesQR[2] === 'SENAI') {
    const ra = partesQR[0];
    const timestamp = parseInt(partesQR[1]);
    const agora = Date.now();
    const diferenca = agora - timestamp;

    // Validar se QR não está expirado (5 minutos)
    if (diferenca > 5 * 60 * 1000) {
      mostrarResultadoScan(false, 'QR Code expirado');
      return;
    }

    // Registrar scan
    const scan = {
      ra: ra,
      timestamp: new Date(),
      tipo: atualizarStatusAcesso(ra),
      verificado: true
    };

    registrarScan(scan);
    mostrarResultadoScan(true, `Acesso de ${scan.tipo} registrado para ${ra}`);
  } else {
    mostrarResultadoScan(false, 'QR Code inválido');
  }
}

let ultimoTipoAcesso = null;

function atualizarStatusAcesso(ra) {
  // Alternar entre entrada e saída
  ultimoTipoAcesso = ultimoTipoAcesso === 'entrada' ? 'saida' : 'entrada';
  return ultimoTipoAcesso || 'entrada';
}

function registrarScan(scan) {
  let scans = JSON.parse(localStorage.getItem('scans_registrados') || '[]');
  scans.unshift(scan);
  
  // Manter apenas os 5 últimos
  if (scans.length > 5) {
    scans = scans.slice(0, 5);
  }

  localStorage.setItem('scans_registrados', JSON.stringify(scans));
  carregarHistoricoScans();
}

function carregarHistoricoScans() {
  const historicoDiv = document.getElementById('historico-scans');
  if (!historicoDiv) return;

  const scans = JSON.parse(localStorage.getItem('scans_registrados') || '[]');

  if (scans.length === 0) {
    historicoDiv.innerHTML = '<p class="vazio">Nenhum scan registrado</p>';
    return;
  }

  historicoDiv.innerHTML = scans.map(scan => {
    const data = new Date(scan.timestamp);
    const hora = data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    const dataStr = data.toLocaleDateString('pt-BR');

    return `
      <div class="item-scan">
        <div class="info-scan">
          <p class="dados-scan">RA: ${scan.ra}</p>
          <p class="hora-scan">${dataStr} às ${hora}</p>
        </div>
        <span class="status-scan ${scan.tipo === 'entrada' ? 'status-entrada' : 'status-saida'}">
          ${scan.tipo === 'entrada' ? 'Entrada' : 'Saída'}
        </span>
      </div>
    `;
  }).join('');
}

function mostrarResultadoScan(sucesso, mensagem) {
  const resultadoDiv = document.getElementById('resultado-scan');
  const infoDiv = document.getElementById('info-scan');

  if (sucesso) {
    resultadoDiv.style.background = 'rgba(16, 185, 129, 0.1)';
    resultadoDiv.style.borderColor = 'rgba(16, 185, 129, 0.3)';
    resultadoDiv.querySelector('i').style.color = '#10B981';
    resultadoDiv.querySelector('h3').textContent = 'Sucesso!';
  } else {
    resultadoDiv.style.background = 'rgba(239, 68, 68, 0.1)';
    resultadoDiv.style.borderColor = 'rgba(239, 68, 68, 0.3)';
    resultadoDiv.querySelector('i').style.color = '#EF4444';
    resultadoDiv.querySelector('h3').textContent = 'Erro!';
  }

  infoDiv.textContent = mensagem;
  resultadoDiv.style.display = 'block';

  setTimeout(() => {
    resultadoDiv.style.display = 'none';
  }, 5000);
}