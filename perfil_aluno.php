<?php
session_start();
include "conexao.php";

// Verifica se o aluno está logado
if (!isset($_SESSION['id_aluno'])) {
    header("Location: login_aluno.php"); // Redireciona se não estiver logado
    exit;
}

// Pega o ID do aluno logado
$id_aluno = $_SESSION['id_aluno'];

// Consulta os dados do aluno no banco
$sql = "SELECT * FROM aluno WHERE id = '$id_aluno'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $aluno = $result->fetch_assoc();
} else {
    echo "Aluno não encontrado!";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Perfil - Carteirinha Digital SENAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Barra de navegação -->
<nav class="barra-navegacao" role="navigation">
    <div class="nav-container">
        <a href="dashboard.php" class="logo-nav" aria-label="Logo Carteirinha SENAI">
            <i class="fas fa-graduation-cap"></i>
            <span>SENAI</span>
        </a>
        <button class="botao-menu-hamburger" id="btn-menu" aria-label="Abrir menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <ul class="menu-navegacao" id="menu-navegacao" role="menubar">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="perfil_aluno.php" class="ativo"><i class="fas fa-user-circle"></i> Perfil</a></li>
            <li><a href="scanner.php"><i class="fas fa-qrcode"></i> Scanner</a></li>
            <li><a href="notificacoes.html"><i class="fas fa-bell"></i> Notificações</a></li>
            <li><a href="logout.php" id="sair-btn" class="btn-sair"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
        </ul>
    </div>
</nav>

<main class="conteudo-principal">
    <div class="card-perfil">
        <div class="foto-aluno">
            <img src="https://via.placeholder.com/120" alt="Foto do Aluno">
            <div class="badge-foto">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <div class="dados-aluno">
            <h2 id="nome-perfil"><?= $aluno['nome'] ?></h2>
            <p class="ra-aluno">RA: <span id="ra-perfil"><?= $aluno['ra'] ?></span></p>
            <p class="turma-aluno">Turma: <span id="turma-perfil"><?= $aluno['turma'] ?></span></p>
            <span class="status-ativo">● Ativo</span>
        </div>
    </div>

    <section class="secao-informacoes">
        <h3>Informações Pessoais</h3>
        <div class="grid-info">
            <div class="item-info">
                <label>Email</label>
                <p id="email-aluno"><?= $aluno['email'] ?></p>
            </div>
            <div class="item-info">
                <label>Telefone</label>
                <p id="telefone-aluno"><?= $aluno['telefone'] ?></p>
            </div>
            <div class="item-info">
                <label>Data de Inscrição</label>
                <p id="data-inscricao"><?= date("d \de F \de Y", strtotime($aluno['data_inscricao'])) ?></p>
            </div>
            <div class="item-info">
                <label>Nível</label>
                <p id="nivel-aluno"><?= $aluno['nivel'] ?></p>
            </div>
        </div>
    </section>

    <section class="secao-documentos">
        <h3>Documentos</h3>
        <div class="lista-documentos">
            <div class="item-documento">
                <i class="fas fa-file-pdf"></i>
                <div class="info-doc">
                    <p>Carteira Digital</p>
                    <span>PDF • 2.5 MB</span>
                </div>
                <a href="#" class="btn-download"><i class="fas fa-download"></i></a>
            </div>

            <div class="item-documento">
                <i class="fas fa-certificate"></i>
                <div class="info-doc">
                    <p>Certificado de Frequência</p>
                    <span>PDF • 1.8 MB</span>
                </div>
                <a href="#" class="btn-download"><i class="fas fa-download"></i></a>
            </div>
        </div>
    </section>

    <section class="secao-configuracoes">
        <h3>Configurações</h3>
        <div class="lista-config">
            <div class="item-config">
                <div class="info-config">
                    <p>Notificações por Email</p>
                    <span>Receba atualizações no seu email</span>
                </div>
                <input type="checkbox" id="notif-email" checked>
            </div>

            <div class="item-config">
                <div class="info-config">
                    <p>Modo Escuro</p>
                    <span>Ativar tema escuro</span>
                </div>
                <input type="checkbox" id="modo-escuro">
            </div>
        </div>
    </section>
</main>

<footer class="rodape">
    <p>&copy; 2025 Carteirinha Digital SENAI. Todos os direitos reservados.</p>
</footer>


</body>
</html>
