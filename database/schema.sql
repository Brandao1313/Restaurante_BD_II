-- =====================================================================
-- Sistema de Gerenciamento - Restaurante Bom Sabor
-- Script completo: tabelas existentes + novas tabelas + alterações + dados iniciais
--
-- IMPORTANTE: este arquivo é UTF-8 e contém nomes acentuados. Ao importar via
-- linha de comando no Windows, declare sempre o charset do cliente para evitar
-- corrupção de acentuação (mojibake):
--   mysql --default-character-set=utf8mb4 -u root -P 3307 -h 127.0.0.1 < schema.sql
-- (sem --default-character-set, o mysql.exe no Windows costuma usar o codepage
-- OEM do console (CP437/CP850), corrompendo bytes UTF-8 multi-byte no import.)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS Restaurante CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Restaurante;

-- =====================================================================
-- TABELAS EXISTENTES (estrutura original preservada)
-- =====================================================================

CREATE TABLE IF NOT EXISTS Clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS Funcionarios (
    id_funcionario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cargo VARCHAR(50),
    salario DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS Mesas (
    id_mesa INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL,
    status VARCHAR(20) DEFAULT 'Livre'
);

CREATE TABLE IF NOT EXISTS Produtos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10,2) NOT NULL
);

CREATE TABLE IF NOT EXISTS Pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    id_funcionario INT,
    id_mesa INT,
    status VARCHAR(20) DEFAULT 'Aberto',
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (id_funcionario) REFERENCES Funcionarios(id_funcionario),
    FOREIGN KEY (id_mesa) REFERENCES Mesas(id_mesa)
);

CREATE TABLE IF NOT EXISTS ItensPedido (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_produto INT,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido),
    FOREIGN KEY (id_produto) REFERENCES Produtos(id_produto)
);

CREATE TABLE IF NOT EXISTS Pagamentos (
    id_pagamento INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    valor DECIMAL(10,2),
    forma_pagamento VARCHAR(50),
    FOREIGN KEY (id_pedido) REFERENCES Pedidos(id_pedido)
);

-- =====================================================================
-- ALTERAÇÕES NAS TABELAS EXISTENTES (apenas adição de colunas)
-- =====================================================================

ALTER TABLE Funcionarios ADD COLUMN IF NOT EXISTS senha VARCHAR(255) NULL;
ALTER TABLE Funcionarios ADD COLUMN IF NOT EXISTS perfil ENUM('administrador','garcom') DEFAULT 'garcom';
ALTER TABLE Funcionarios ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL UNIQUE;
ALTER TABLE Funcionarios ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT TRUE;

ALTER TABLE Mesas ADD COLUMN IF NOT EXISTS capacidade INT DEFAULT 4;

ALTER TABLE Pedidos ADD COLUMN IF NOT EXISTS data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE Pedidos ADD COLUMN IF NOT EXISTS observacao TEXT NULL;

ALTER TABLE ItensPedido ADD COLUMN IF NOT EXISTS observacao VARCHAR(255) NULL;
ALTER TABLE ItensPedido ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'Pendente';

ALTER TABLE Pagamentos ADD COLUMN IF NOT EXISTS data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE Produtos ADD COLUMN IF NOT EXISTS id_categoria INT NULL;
ALTER TABLE Produtos ADD COLUMN IF NOT EXISTS disponivel BOOLEAN DEFAULT TRUE;
ALTER TABLE Produtos ADD COLUMN IF NOT EXISTS imagem VARCHAR(255) NULL;

-- =====================================================================
-- NOVAS TABELAS
-- =====================================================================

CREATE TABLE IF NOT EXISTS Categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    ordem INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS UsuariosClientes (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente)
);

CREATE TABLE IF NOT EXISTS Insumos (
    id_insumo INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL DEFAULT 0,
    unidade VARCHAR(20) NOT NULL,
    quantidade_minima DECIMAL(10,2) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS ProdutoInsumo (
    id_produto_insumo INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT,
    id_insumo INT,
    quantidade_necessaria DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_produto) REFERENCES Produtos(id_produto),
    FOREIGN KEY (id_insumo) REFERENCES Insumos(id_insumo)
);

CREATE TABLE IF NOT EXISTS Reservas (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    id_mesa INT,
    data_reserva DATE NOT NULL,
    hora_reserva TIME NOT NULL,
    quantidade_pessoas INT NOT NULL,
    status VARCHAR(20) DEFAULT 'Confirmada',
    codigo_confirmacao VARCHAR(20) UNIQUE,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (id_mesa) REFERENCES Mesas(id_mesa)
);

CREATE TABLE IF NOT EXISTS Despesas (
    id_despesa INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    data_vencimento DATE NULL,
    data_pagamento DATE NULL,
    status VARCHAR(20) DEFAULT 'Pendente',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_funcionario INT NULL,
    FOREIGN KEY (id_funcionario) REFERENCES Funcionarios(id_funcionario)
);

CREATE TABLE IF NOT EXISTS LogsAuditoria (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_funcionario INT NULL,
    id_cliente INT NULL,
    acao VARCHAR(100) NOT NULL,
    detalhes TEXT NULL,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45) NULL,
    FOREIGN KEY (id_funcionario) REFERENCES Funcionarios(id_funcionario),
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente)
);

-- =====================================================================
-- DADOS INICIAIS
-- =====================================================================

-- CLIENTES
INSERT INTO Clientes (nome) VALUES
('João Marcelo Silva'), ('Maria Eduarda Rocha'), ('Antônio Carlos Souza'),
('Francisca das Chagas Lima'), ('Pedro Augusto Ribeiro'), ('Adriana Maria Conceição'),
('José Wellington Costa'), ('Sebastião Alves Neto'), ('Maria de Fátima Carvalho'),
('Luís Fernando Gomes'), ('Ana Paula Nascimento'), ('Raimundo Nonato Santos'),
('Manoel Messias Oliveira'), ('Maria José Barbosa'), ('Francisco das Chagas'),
('Antônia Silva Ferreira'), ('Paulo Roberto Cruz'), ('Carlos Eduardo Andrade'),
('Luíza Maria Cavalcante'), ('Marcos Vinícius Cunha'), ('Raimunda Nonata Pires'),
('Sebastiana Rodrigues'), ('Geraldo Magela Nogueira'), ('Tereza Cristina Vieira'),
('Luiz Carlos Fonseca'), ('Sônia Maria Teixeira'), ('Alexandre Henrique Ramos'),
('Luciana Martins Castro'), ('André Luiz Guimarães'), ('Simone Aparecida Lopes');

-- FUNCIONARIOS
INSERT INTO Funcionarios (nome, cargo, salario) VALUES
('Felipe', 'Churrasqueiro', 1499.42),
('Clara', 'Garçonete', 1499.42),
('Mateus', 'Garçom', 1499.42),
('Mariana', 'Recepcionista', 1499.42),
('Samara', 'Auxiliar de Cozinha', 1499.42),
('Jéssica', 'Cozinheira', 2199.42),
('Vagner', 'Garçom', 1499.42),
('Elaine', 'Auxiliar de Limpeza', 1700.00),
('Taylane', 'Gerente', 3199.35);

-- MESAS
INSERT INTO Mesas (numero, capacidade) VALUES
(1,2),(2,2),(3,4),(4,4),(5,4),(6,6),(7,4),(8,2),(9,4),(10,6),
(11,4),(12,2),(13,4),(14,4),(15,8),(16,4),(17,2),(18,4),(19,6),(20,4);

-- CATEGORIAS
INSERT INTO Categorias (nome, ordem) VALUES
('Entradas', 1),
('Pratos Principais', 2),
('Vegetarianos', 3),
('Sobremesas', 4),
('Bebidas', 5);

-- PRODUTOS
INSERT INTO Produtos (nome, preco, id_categoria) VALUES
-- Entradas (id_categoria = 1)
('Tartare de Salmão com Abacate e Azeite de Trufas', 68.00, 1),
('Carpaccio de Mignon com Rúcula e Lascas de Grana Padano', 62.00, 1),
('Burrata Artesanal com Pesto de Manjericão e Tomates Confit', 74.00, 1),
('Vieiras Grelhadas na Manteiga de Ervas com Purê de Mandioquinha', 95.00, 1),
-- Pratos Principais (id_categoria = 2)
('Medalhão de Filé Mignon ao Molho Roti com Risotinho de Cogumelos', 120.00, 2),
('Tomahawk Black Angus Grelhado com Legumes Defumados', 240.00, 2),
('Carré de Cordeiro em Crosta de Ervas com Aligot de Batata', 145.00, 2),
('Pato ao Molho de Laranja e Cointreau com Arroz Selvagem', 115.00, 2),
('Risoto de Lagosta com Infusão de Açafrão Italiano', 160.00, 2),
('Posta de Bacalhau Gadus Morhua Confitado em Azeite Extra Virgem', 155.00, 2),
('Polvo Grelhado na Brasa com Batatas Murro e Páprica Defumada', 138.00, 2),
('Salmão em Crosta de Gergelim com Aspargos Grelhados', 98.00, 2),
-- Vegetarianos (id_categoria = 3)
('Nhoque de Batata Baroa com Fonduta de Queijo Gorgonzola Dolce', 78.00, 3),
('Risoto de Aspargos Frescos com Queijo Brie e Nozes Glaceadas', 85.00, 3),
-- Sobremesas (id_categoria = 4)
('Petit Gâteau de Doce de Leite Viçosa com Sorvete de Baunilha em Fava', 38.00, 4),
('Mil-Folhas Clássico com Creme Patissière e Frutas Vermelhas', 42.00, 4),
('Crème Brûlée Tradicional com Baunilha de Madagascar', 35.00, 4),
('Torta Ópera com Camadas de Café e Chocolate Belga', 45.00, 4),
-- Bebidas (id_categoria = 5)
('Água Mineral San Pellegrino com Gás 750ml', 22.00, 5),
('Vinho Tinto Brunello di Montalcino (Garrafa)', 450.00, 5),
('Champagne Dom Pérignon Vintage (Garrafa)', 1800.00, 5);

-- INSUMOS DE EXEMPLO
INSERT INTO Insumos (nome, quantidade, unidade, quantidade_minima) VALUES
('Filé Mignon', 50.00, 'kg', 5.00),
('Tomahawk Black Angus', 30.00, 'kg', 3.00),
('Cordeiro', 20.00, 'kg', 2.00),
('Pato', 15.00, 'kg', 2.00),
('Lagosta', 10.00, 'kg', 1.00),
('Bacalhau', 20.00, 'kg', 2.00),
('Polvo', 15.00, 'kg', 2.00),
('Salmão', 25.00, 'kg', 3.00),
('Batata', 100.00, 'kg', 10.00),
('Arroz', 80.00, 'kg', 10.00),
('Queijo Gorgonzola', 15.00, 'kg', 2.00),
('Queijo Brie', 10.00, 'kg', 1.00),
('Chocolate Belga', 20.00, 'kg', 2.00),
('Café', 5.00, 'kg', 1.00);

-- USUÁRIO ADMIN (senha: admin123)
UPDATE Funcionarios SET
    email = 'admin@bomsabor.com',
    senha = '$2y$10$xB.l7VQtSNMWtn1FqiU/8uOm7AytyI46HUGknsTcBuORCuXrsgp0W',
    perfil = 'administrador'
WHERE cargo = 'Gerente';

-- USUÁRIOS GARÇONS (senha: garcom123)
UPDATE Funcionarios SET
    email = CONCAT(LOWER(nome), '@bomsabor.com'),
    senha = '$2y$10$4aN9tUUaUxRKaiJX5WFhOus0H9OZM5DQ24tDU8znl0zga1qbfOH9O',
    perfil = 'garcom'
WHERE cargo IN ('Garçom', 'Garçonete') AND email IS NULL;
