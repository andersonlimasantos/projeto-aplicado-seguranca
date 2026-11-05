-- =============================================================================
-- Migração: Criação da Tabela de Trilha de Auditoria
-- Projeto Aplicado: Painel de Demandas de TI
--
-- Aplica a nova tabela em instalações existentes sem remover ou recriar
-- tabelas prévias.
-- =============================================================================

CREATE TABLE IF NOT EXISTS auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    usuario VARCHAR(100) NOT NULL,
    tipo_evento VARCHAR(50) NOT NULL,
    demanda_id INT UNSIGNED NULL,
    detalhes VARCHAR(255) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX indice_usuario (usuario_id),
    INDEX indice_tipo_evento (tipo_evento),
    INDEX indice_criado_em (criado_em),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_auditoria_demanda FOREIGN KEY (demanda_id) REFERENCES demandas (id) ON DELETE SET NULL
) ENGINE=InnoDB;
