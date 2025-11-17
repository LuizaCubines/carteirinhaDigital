// ========== SERVICE WORKER PARA OFFLINE ==========
const NOME_CACHE = 'carteirinha-senai-v1';
const URLS_CACHE = [
  '/',
  '/index.html',
  '/dashboard.html',
  '/perfil.html',
  '/scanner.html',
  '/notificacoes.html',
  '/admin.html',
  '/css/style.css',
  '/js/app.js',
  '/js/scanner.js'
];

// Instalar Service Worker
self.addEventListener('install', (evento) => {
  evento.waitUntil(
    caches.open(NOME_CACHE).then((cache) => {
      return cache.addAll(URLS_CACHE).catch(err => {
        console.log('Erro ao cachear URLs:', err);
      });
    })
  );
});

// Ativar Service Worker
self.addEventListener('activate', (evento) => {
  evento.waitUntil(
    caches.keys().then((nomesCaches) => {
      return Promise.all(
        nomesCaches.map((nome) => {
          if (nome !== NOME_CACHE) {
            return caches.delete(nome);
          }
        })
      );
    })
  );
});

// Interceptar requisições
self.addEventListener('fetch', (evento) => {
  evento.respondWith(
    caches.match(evento.request).then((resposta) => {
      return (
        resposta ||
        fetch(evento.request).then((resposta) => {
          const clonagemResposta = resposta.clone();

          caches.open(NOME_CACHE).then((cache) => {
            cache.put(evento.request, clonagemResposta);
          });

          return resposta;
        }).catch(() => {
          return caches.match('/index.html');
        })
      );
    })
  );
});