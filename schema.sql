CREATE DATABASE IF NOT EXISTS forum_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE forum_escolar;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(60) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('normal','moderador','especialista') NOT NULL DEFAULT 'normal',
    bio VARCHAR(255) NOT NULL DEFAULT '',
    foto VARCHAR(255) NULL DEFAULT NULL,
    banido TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(60) NOT NULL UNIQUE,
    criado_por INT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE perguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    texto TEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    texto TEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Respostas às respostas (comentários dentro de cada resposta)
CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resposta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    texto VARCHAR(500) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resposta_id) REFERENCES respostas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Avaliação de respostas: nota de 1 a 5, uma por usuário em cada resposta
CREATE TABLE avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resposta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nota TINYINT NOT NULL,
    UNIQUE KEY uma_nota (resposta_id, usuario_id),
    FOREIGN KEY (resposta_id) REFERENCES respostas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE enquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(60) NOT NULL,
    criado_por INT NOT NULL,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    resultado ENUM('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enquete_id INT NOT NULL,
    usuario_id INT NOT NULL,
    voto ENUM('sim','nao') NOT NULL,
    UNIQUE KEY um_voto (enquete_id, usuario_id),
    FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

INSERT INTO categorias (nome) VALUES ('Matemática'), ('Português'), ('História'), ('Ciências');
