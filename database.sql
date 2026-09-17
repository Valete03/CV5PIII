CREATE DATABASE IF NOT EXISTS hospital_mavalane
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE hospital_mavalane;

CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    documento VARCHAR(50) NOT NULL UNIQUE,
    contacto VARCHAR(30) NOT NULL,
    data_nascimento DATE NOT NULL,
    sexo ENUM('Masculino','Feminino') NOT NULL,
    data_registo TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS utilizadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin','medico','enfermeiro') NOT NULL,
    estado ENUM('ativo','inativo') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS atendimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    numero_atendimento VARCHAR(30) NOT NULL UNIQUE,
    motivo TEXT NOT NULL,
    prioridade ENUM(
        'Emergente',
        'Muito urgente',
        'Urgente',
        'Pouco urgente',
        'Não urgente'
    ) DEFAULT NULL,
    estado ENUM(
        'Aguardando triagem',
        'Em triagem',
        'Aguardando médico',
        'Em atendimento',
        'Concluído'
    ) DEFAULT 'Aguardando triagem',
    observacao_triagem TEXT DEFAULT NULL,
    diagnostico TEXT DEFAULT NULL,
    destino ENUM('Alta','Internamento','Transferência','Óbito') DEFAULT NULL,
    profissional_triagem_id INT DEFAULT NULL,
    profissional_medico_id INT DEFAULT NULL,
    data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_triagem DATETIME DEFAULT NULL,
    data_inicio_atendimento DATETIME DEFAULT NULL,
    data_conclusao DATETIME DEFAULT NULL,
    CONSTRAINT fk_atendimento_paciente
        FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_triagem_profissional
        FOREIGN KEY (profissional_triagem_id) REFERENCES utilizadores(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_medico_profissional
        FOREIGN KEY (profissional_medico_id) REFERENCES utilizadores(id)
        ON DELETE SET NULL
);

CREATE INDEX idx_atendimentos_estado ON atendimentos(estado);
CREATE INDEX idx_atendimentos_prioridade ON atendimentos(prioridade);
CREATE INDEX idx_atendimentos_data ON atendimentos(data_entrada);
