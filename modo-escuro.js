// Verifica preferência salva no localStorage
function verificarModoEscuro() {
    const modoEscuroAtivo = localStorage.getItem('modoEscuro') === 'true';
    
    // Aplica ao body
    if (modoEscuroAtivo) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
    
    // Sincroniza todos os checkboxes
    document.querySelectorAll('input[type="checkbox"][id*="modo-escuro"]').forEach(checkbox => {
        checkbox.checked = modoEscuroAtivo;
    });
    
    return modoEscuroAtivo;
}

// Alterna entre modos
function alternarModoEscuro() {
    const modoAtual = document.body.classList.contains('dark-mode');
    const novoModo = !modoAtual;
    
    if (novoModo) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
    
    // Salva preferência
    localStorage.setItem('modoEscuro', novoModo);
    
    // Atualiza todos os checkboxes
    document.querySelectorAll('input[type="checkbox"][id*="modo-escuro"]').forEach(checkbox => {
        checkbox.checked = novoModo;
    });
}

// Inicializa quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    // Carrega a preferência salva
    verificarModoEscuro();
    
    // Adiciona eventos aos checkboxes
    document.querySelectorAll('input[type="checkbox"][id*="modo-escuro"]').forEach(checkbox => {
        checkbox.addEventListener('change', alternarModoEscuro);
    });
});

// Detecta preferência do sistema (opcional)
function detectarPreferenciaSistema() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        // Se usuário prefere modo escuro e não tem preferência salva
        if (localStorage.getItem('modoEscuro') === null) {
            localStorage.setItem('modoEscuro', 'true');
            verificarModoEscuro();
        }
    }
}

// Executa a detecção de preferência do sistema
document.addEventListener('DOMContentLoaded', detectarPreferenciaSistema);