CREATE DATABASE escola;

USE escola;
CREATE TABLE aluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ra VARCHAR(20) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    turma VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    nivel VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT 1,
    data_inscricao DATE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    
);

USE escola;
CREATE TABLE comunicado (
    id_comunicado INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    anexo_url VARCHAR(500),

    -- Relacionamento com administrador
    adm_id INT NOT NULL,
    FOREIGN KEY (adm_id) REFERENCES administrador(id)
);

CREATE TABLE administrador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);


CREATE TABLE acesso (
    id_acesso INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    data_entrada TIMESTAMP NULL,
    data_saida TIMESTAMP NULL,
    
    -- Relacionamento com alunos
    FOREIGN KEY (aluno_id) REFERENCES aluno(id)
);

USE escola;
INSERT INTO administrador (nome, email, senha)
VALUES (
    'Professor Teste',
    'professor@senai.com',
    'senai1234'
);

SELECT * FROM aluno WHERE id = 2;

