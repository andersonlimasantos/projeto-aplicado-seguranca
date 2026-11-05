CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS demandas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    prioridade ENUM('Baixa', 'Média', 'Alta') NOT NULL,
    situacao ENUM('Aberta', 'Em andamento', 'Concluída') NOT NULL DEFAULT 'Aberta',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tentativas_login (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARBINARY(16) NOT NULL,
    ocorreu_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX indice_ip_data (ip, ocorreu_em)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    usuario VARCHAR(100) NOT NULL,
    tipo_evento VARCHAR(50) NOT NULL,
    demanda_id INT UNSIGNED NULL,
    detalhes VARCHAR(255) NULL,
    ip VARCHAR(45) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX indice_usuario (usuario_id),
    INDEX indice_tipo_evento (tipo_evento),
    INDEX indice_criado_em (criado_em),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_auditoria_demanda FOREIGN KEY (demanda_id) REFERENCES demandas (id) ON DELETE SET NULL
) ENGINE=InnoDB;
