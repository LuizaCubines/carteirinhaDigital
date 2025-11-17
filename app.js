// ========== DADOS E ESTADO ==========
const usuariosTeste = {
  '20240001': {
    ra: '20240001',
    senha: 'senai123',
    tipo: 'aluno',
    nome: 'João Silva Santos',
    email: 'joao.silva@senai.sp.br',
    telefone: '(11) 98765-4321',
    turma: 'DSMT2024',
    nivel: 'Técnico em Desenvolvimento de Sistemas',
    dataInscricao: '15 de Janeiro de 2024'
  },
  admin: {
    usuario: 'admin',
    senha: 'admin123',
    tipo: 'gestor',
    nome: 'Admin SENAI'
  }
};

let usuarioLogado = null;
let acessosRegistrados = [];
let notificacoes = [
  {
    id: 1,
    titulo: 'Falta Registrada',
    mensagem: 'Você faltou na aula de Programação em Python',
    tipo: 'faltas',
    data: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
    lida: false
  },
  {
    id: 2,
    titulo: 'Atraso Detectado',
    mensagem: 'Você chegou 15 minutos atrasado na aula',
    tipo: 'atrasos',
    data: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
    lida: false
  },
  {
    id: 3,
    titulo: 'Comunicado Importante',
    mensagem: 'Reunião de alunos amanhã às 14:00 no auditório',
    tipo: 'comunicados',
    data: new Date(Date.now() - 1 * 60 * 60 * 1000),
    lida: false
  },
  {
    id: 4,
    titulo: 'Presença Confirmada',
    mensagem: 'Sua presença foi registrada com sucesso',
    tipo: 'comunicados',
    data: new Date(Date.now() - 30 * 60 * 1000),
    lida: true
  }
];

let tempoValidadeQR = 30;

// ========== AUTENTICAÇÃO ==========
function verificarAutenticacao(tipoRequerido = null) {
  const sessao = localStorage.getItem('sessao_carteirinha');
  
  if (!sessao) {
    window.location.href = 'index.html';
    return;
  }

  usuarioLogado = JSON.parse(sessao);

  if (tipoRequerido && usuarioLogado.tipo !== tipoRequerido) {
    alert('Acesso restrito. Você não tem permissão para acessar esta página.');
    window.location.href = 'dashboard.html';
  }
}

function fazerLogin(usuario, senha) {
  // Validar entrada
  if (!usuario || !senha) {
    return { sucesso: false, erro: 'Preencha todos os campos' };
  }

  // Verificar em usuários teste
  for (const chave in usuariosTeste) {
    const dados = usuariosTeste[chave];
    if ((dados.ra === usuario || dados.usuario === usuario) && dados.senha === senha) {
      usuarioLogado = {
        ...dados,
        dataLogin: new Date()
      };
      
      localStorage.setItem('sessao_carteirinha', JSON.stringify(usuarioLogado));
      
      return {
        sucesso: true,
        tipo: dados.tipo,
        nome: dados.nome || dados.usuario
      };
    }
  }

  return { sucesso: false, erro: 'RA/Usuário ou senha inválidos' };
}

function fazerLogout() {
  localStorage.removeItem('sessao_carteirinha');
  window.location.href = 'index.html';
}

// ========== FORMULÁRIO DE LOGIN ==========
document.addEventListener('DOMContentLoaded', () => {
  const formularioLogin = document.getElementById('formulario-login');
  
  if (formularioLogin) {
    formularioLogin.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const usuario = document.getElementById('usuario').value.trim();
      const senha = document.getElementById('senha').value;
      
      const resultado = fazerLogin(usuario, senha);
      
      if (resultado.sucesso) {
        if (resultado.tipo === 'gestor') {
          window.location.href = 'admin.html';
        } else {
          window.location.href = 'dashboard.html';
        }
      } else {
        mostrarErro('usuario', resultado.erro);
      }
    });

    // Botões de teste
    document.querySelectorAll('.btn-teste').forEach(btn => {
      btn.addEventListener('click', () => {
        const tipo = btn.dataset.tipo;
        
        if (tipo === 'aluno') {
          document.getElementById('usuario').value = '20240001';
          document.getElementById('senha').value = 'senai123';
        } else if (tipo === 'admin') {
          document.getElementById('usuario').value = 'admin';
          document.getElementById('senha').value = 'admin123';
        }
        
        const resultado = fazerLogin(
          document.getElementById('usuario').value,
          document.getElementById('senha').value
        );
        
        if (resultado.sucesso) {
          if (resultado.tipo === 'gestor') {
            window.location.href = 'admin.html';
          } else {
            window.location.href = 'dashboard.html';
          }
        }
      });
    });
  }

  // Botão Sair
  const btnSair = document.getElementById('sair-btn');
  if (btnSair) {
    btnSair.addEventListener('click', (e) => {
      e.preventDefault();
      fazerLogout();
    });
  }
});

function mostrarErro(campoId, mensagem) {
  const erroElement = document.getElementById(`erro-${campoId}`);
  if (erroElement) {
    erroElement.textContent = mensagem;
    erroElement.classList.add('ativa');
    
    setTimeout(() => {
      erroElement.classList.remove('ativa');
    }, 5000);
  }
}

// ========== MENU HAMBÚRGUER ==========
function configurarMenu() {
  const btnMenu = document.getElementById('btn-menu');
  const menuNav = document.getElementById('menu-navegacao');
  
  if (!btnMenu || !menuNav) return;

  btnMenu.addEventListener('click', () => {
    menuNav.classList.toggle('ativo');
    btnMenu.setAttribute('aria-expanded', menuNav.classList.contains('ativo'));
  });

  // Fechar menu ao clicar em um link
  menuNav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      if (link.id !== 'sair-btn') {
        menuNav.classList.remove('ativo');
        btnMenu.setAttribute('aria-expanded', 'false');
      }
    });
  });
}

// ========== DASHBOARD ==========
function inicializarDashboard() {
  if (document.getElementById('nome-usuario')) {
    document.getElementById('nome-usuario').textContent = usuarioLogado.nome.split(' ')[0];
  }

  gerarQRCodeDinamico();
  carregarAcessos();
  atualizarTimeline();

  // Atualizar QR Code automaticamente a cada 30 segundos
  setInterval(() => {
    gerarQRCodeDinamico();
  }, 30000);

  // Botão atualizar QR
  const btnAtualizar = document.getElementById('btn-atualizar-qr');
  if (btnAtualizar) {
    btnAtualizar.addEventListener('click', gerarQRCodeDinamico);
  }
}

function gerarQRCodeDinamico() {
  const container = document.getElementById('qrcode-gerado');
  if (!container) return;

  // Limpar QR anterior
  container.innerHTML = '';

  // Gerar dados do QR Code
  const agora = new Date();
  const timestamp = agora.getTime();
  const ra = usuarioLogado.ra || 'USER';
  const dadosQR = `${ra}|${timestamp}|SENAI`;

  // Gerar QR Code
  new QRCode(container, {
    text: dadosQR,
    width: 250,
    height: 250,
    colorDark: '#1a1a1a',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H
  });

  // Atualizar tempo de validade
  atualizarTempoValidade();
}

let contagemTempo = 30;

function atualizarTempoValidade() {
  contagemTempo = 30;
  const elemento = document.getElementById('tempo-validade');

  const intervalo = setInterval(() => {
    contagemTempo--;
    if (elemento) {
      elemento.textContent = contagemTempo + 's';
    }

    if (contagemTempo <= 0) {
      clearInterval(intervalo);
    }
  }, 1000);
}

function carregarAcessos() {
  // Simular acessos
  acessosRegistrados = [
    {
      data: new Date(Date.now() - 2 * 60 * 60 * 1000),
      tipo: 'entrada',
      horario: '08:00'
    },
    {
      data: new Date(Date.now() - 60 * 60 * 1000),
      tipo: 'saida',
      horario: '12:00'
    }
  ];

  document.getElementById('total-acessos').textContent = acessosRegistrados.length;
}

function atualizarTimeline() {
  const timeline = document.getElementById('timeline-acessos');
  if (!timeline) return;

  timeline.innerHTML = '';

  if (acessosRegistrados.length === 0) {
    timeline.innerHTML = '<div class="item-timeline"><p class="vazio">Nenhum acesso registrado hoje</p></div>';
    return;
  }

  acessosRegistrados.forEach(acesso => {
    const item = document.createElement('div');
    item.className = 'item-timeline';
    item.innerHTML = `
      <div class="ponto-timeline"></div>
      <div class="conteudo-timeline">
        <p class="horario-acesso">${acesso.tipo === 'entrada' ? 'Entrada' : 'Saída'} - ${acesso.horario}</p>
        <p class="data-acesso">${acesso.data.toLocaleDateString('pt-BR')}</p>
      </div>
    `;
    timeline.appendChild(item);
  });
}

// ========== PERFIL ==========
function inicializarPerfil() {
  if (!usuarioLogado) return;

  document.getElementById('nome-perfil').textContent = usuarioLogado.nome;
  document.getElementById('ra-perfil').textContent = usuarioLogado.ra;
  document.getElementById('turma-perfil').textContent = usuarioLogado.turma;
  document.getElementById('email-aluno').textContent = usuarioLogado.email;
  document.getElementById('telefone-aluno').textContent = usuarioLogado.telefone;
  document.getElementById('data-inscricao').textContent = usuarioLogado.dataInscricao;
  document.getElementById('nivel-aluno').textContent = usuarioLogado.nivel;

  // Modo escuro
  const modoEscuro = document.getElementById('modo-escuro');
  if (modoEscuro) {
    modoEscuro.addEventListener('change', () => {
      document.body.classList.toggle('dark-mode');
    });
  }
}

// ========== NOTIFICAÇÕES ==========
function inicializarNotificacoes() {
  renderizarNotificacoes('todas');

  const filtros = document.querySelectorAll('.filtro-btn');
  filtros.forEach(filtro => {
    filtro.addEventListener('click', () => {
      filtros.forEach(f => f.classList.remove('ativo'));
      filtro.classList.add('ativo');
      renderizarNotificacoes(filtro.dataset.filtro);
    });
  });

  // Botão limpar tudo
  const btnLimpar = document.getElementById('limpar-tudo');
  if (btnLimpar) {
    btnLimpar.addEventListener('click', () => {
      if (confirm('Tem certeza que deseja deletar todas as notificações?')) {
        notificacoes = [];
        renderizarNotificacoes('todas');
      }
    });
  }
}

function renderizarNotificacoes(filtro) {
  const container = document.getElementById('lista-notificacoes');
  if (!container) return;

  container.innerHTML = '';

  const notificacoesFiltradas = notificacoes.filter(notif => {
    return filtro === 'todas' || notif.tipo === filtro;
  });

  if (notificacoesFiltradas.length === 0) {
    container.innerHTML = '<p class="vazio">Nenhuma notificação neste filtro</p>';
    return;
  }

  notificacoesFiltradas.forEach(notif => {
    const item = document.createElement('div');
    item.className = `item-notificacao ${notif.lida ? '' : 'nao-lida'}`;

    const iconMap = {
      'faltas': 'fas fa-exclamation-circle',
      'atrasos': 'fas fa-hourglass-end',
      'comunicados': 'fas fa-bell'
    };

    item.innerHTML = `
      <div class="icone-notif">
        <i class="${iconMap[notif.tipo] || 'fas fa-bell'}"></i>
      </div>
      <div class="conteudo-notif">
        <h4>${notif.titulo}</h4>
        <p>${notif.mensagem}</p>
        <p class="data-notif">${notif.data.toLocaleDateString('pt-BR')} às ${notif.data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</p>
      </div>
      <button class="btn-marcar-lido" onclick="marcarComoLido(${notif.id})">
        ${notif.lida ? 'Lido' : 'Marcar como lido'}
      </button>
    `;

    container.appendChild(item);
  });
}

function marcarComoLido(id) {
  const notif = notificacoes.find(n => n.id === id);
  if (notif) {
    notif.lida = true;
    renderizarNotificacoes('todas');
  }
}

// ========== ADMIN ==========
function inicializarAdmin() {
  const abas = document.querySelectorAll('.aba-btn');
  const conteudos = document.querySelectorAll('.conteudo-aba');

  abas.forEach(aba => {
    aba.addEventListener('click', () => {
      abas.forEach(a => a.classList.remove('ativo'));
      conteudos.forEach(c => c.classList.remove('ativo'));

      aba.classList.add('ativo');
      document.getElementById(`aba-${aba.dataset.aba}`).classList.add('ativo');
    });
  });

  carregarAlunos();
  carregarGestores();
  carregarEstatisticas();
}

function carregarAlunos() {
  const tbody = document.getElementById('tbody-alunos');
  if (!tbody) return;

  const alunos = [
    {
      ra: '20240001',
      nome: 'João Silva Santos',
      turma: 'DSMT2024',
      email: 'joao.silva@senai.sp.br',
      status: 'Ativo'
    },
    {
      ra: '20240002',
      nome: 'Maria Oliveira Costa',
      turma: 'DSMT2024',
      email: 'maria.costa@senai.sp.br',
      status: 'Ativo'
    },
    {
      ra: '20240003',
      nome: 'Pedro Ferreira Dias',
      turma: 'ELMT2024',
      email: 'pedro.dias@senai.sp.br',
      status: 'Inativo'
    }
  ];

  tbody.innerHTML = alunos.map(aluno => `
    <tr>
      <td>${aluno.ra}</td>
      <td>${aluno.nome}</td>
      <td>${aluno.turma}</td>
      <td>${aluno.email}</td>
      <td><span class="status-${aluno.status.toLowerCase()}">${aluno.status}</span></td>
      <td>
        <button class="btn-acao" title="Editar"><i class="fas fa-edit"></i></button>
        <button class="btn-acao" title="Deletar"><i class="fas fa-trash-alt"></i></button>
      </td>
    </tr>
  `).join('');
}

function carregarGestores() {
  const tbody = document.getElementById('tbody-gestores');
  if (!tbody) return;

  const gestores = [
    {
      usuario: 'admin',
      nome: 'Administrator',
      email: 'admin@senai.sp.br',
      permissoes: 'Total',
      status: 'Ativo'
    }
  ];

  tbody.innerHTML = gestores.map(gestor => `
    <tr>
      <td>${gestor.usuario}</td>
      <td>${gestor.nome}</td>
      <td>${gestor.email}</td>
      <td>${gestor.permissoes}</td>
      <td><span class="status-${gestor.status.toLowerCase()}">${gestor.status}</span></td>
      <td>
        <button class="btn-acao" title="Editar"><i class="fas fa-edit"></i></button>
        <button class="btn-acao" title="Deletar"><i class="fas fa-trash-alt"></i></button>
      </td>
    </tr>
  `).join('');
}

function carregarEstatisticas() {
  document.getElementById('total-alunos').textContent = '145';
  document.getElementById('alunos-ativos').textContent = '138';
  document.getElementById('total-gestores').textContent = '5';
  document.getElementById('taxa-presenca').textContent = '94%';
}

// ========== SERVICE WORKER (OFFLINE) ==========
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js').catch(err => {
    console.log('Service Worker não disponível:', err);
  });
}