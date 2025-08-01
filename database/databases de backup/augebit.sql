-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 05/06/2025 às 13:54
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `augebit`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `chave` varchar(100) DEFAULT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `entregas`
--

CREATE TABLE `entregas` (
  `id` int(11) NOT NULL,
  `projeto_id` int(11) DEFAULT NULL,
  `url_visualizacao` varchar(255) DEFAULT NULL,
  `aprovado` tinyint(1) DEFAULT 0,
  `data_entrega` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `formularios_contato`
--

CREATE TABLE `formularios_contato` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `enviado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `remetente_id` int(11) DEFAULT NULL,
  `destinatario_id` int(11) DEFAULT NULL,
  `projeto_id` int(11) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `origem` enum('usuario','ia') DEFAULT 'usuario',
  `enviado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `portfolio`
--

CREATE TABLE `portfolio` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `projetos`
--

CREATE TABLE `projetos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('em_andamento','finalizado','aprovado','ajustes') DEFAULT 'em_andamento',
  `cliente_id` int(11) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `projetos`
--

INSERT INTO `projetos` (`id`, `titulo`, `descricao`, `status`, `cliente_id`, `criado_em`) VALUES
(1, 'Projeto X', 'Descrição', 'aprovado', 1, '2025-05-22 11:41:40'),
(2, 'Projeto Teste', 'Descrição', 'aprovado', 2, '2025-05-22 14:55:54'),
(3, 'Urgente', 'Urgente Teste', 'finalizado', 1, '2025-05-22 16:18:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `projetos_usuarios`
--

CREATE TABLE `projetos_usuarios` (
  `id` int(11) NOT NULL,
  `projeto_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `projetos_usuarios`
--

INSERT INTO `projetos_usuarios` (`id`, `projeto_id`, `funcionario_id`, `criado_em`) VALUES
(1, 1, 2, '2025-05-22 11:55:23'),
(2, 2, 2, '2025-05-22 15:02:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tarefas`
--

CREATE TABLE `tarefas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('a_fazer','em_progresso','concluido') DEFAULT 'a_fazer',
  `projeto_id` int(11) DEFAULT NULL,
  `funcionario_id` int(11) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `tarefas`
--

INSERT INTO `tarefas` (`id`, `titulo`, `descricao`, `status`, `projeto_id`, `funcionario_id`, `criado_em`) VALUES
(2, 'Fazer o escopo da garrafa', 'Escopo enviar em .php', 'concluido', 1, 2, '2025-05-22 13:04:00'),
(3, 'Faça tarefas tarifas', 'tarifa tarifão', 'concluido', 1, 2, '2025-05-22 13:07:32'),
(4, 'Faça uma bomba nuclear', 'reator nuclear', 'concluido', 1, 2, '2025-05-22 13:09:03'),
(5, 'Dar dois carpado para trás', 'faf', 'concluido', 2, 2, '2025-05-23 08:46:54'),
(6, 'josue destruidor de planeta', 'Sarah sofredora', 'a_fazer', 2, 2, '2025-05-23 15:02:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `uploads`
--

CREATE TABLE `uploads` (
  `id` int(11) NOT NULL,
  `projeto_id` int(11) DEFAULT NULL,
  `nome_arquivo` varchar(255) DEFAULT NULL,
  `caminho_arquivo` varchar(255) DEFAULT NULL,
  `tipo` enum('cliente','funcionario') DEFAULT 'cliente',
  `enviado_por` int(11) DEFAULT NULL,
  `enviado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `uploads`
--

INSERT INTO `uploads` (`id`, `projeto_id`, `nome_arquivo`, `caminho_arquivo`, `tipo`, `enviado_por`, `enviado_em`) VALUES
(1, 1, 'WhatsApp Image 2025-05-09 at 16.03.24.jpeg', '../../uploads/1747925104_WhatsApp Image 2025-05-09 at 16.03.24.jpeg', 'funcionario', 1, '2025-05-22 11:45:04'),
(2, 1, 'e1101899efb112838110175eed2eb4fc.jpg', '../../uploads/1747925119_e1101899efb112838110175eed2eb4fc.jpg', 'funcionario', 1, '2025-05-22 11:45:19'),
(3, 1, '803ca2f0b5f805fa39793f3a52e6f59c.jpg', '../../uploads/1747929782_803ca2f0b5f805fa39793f3a52e6f59c.jpg', 'funcionario', 1, '2025-05-22 13:03:02'),
(4, 1, '44f64c847039744da7cf356167ce1e91.jpg', '../../uploads/1747930179_44f64c847039744da7cf356167ce1e91.jpg', 'funcionario', 1, '2025-05-22 13:09:39'),
(5, 1, 'augebit (1).sql', '../../uploads/1747930216_augebit (1).sql', 'funcionario', 1, '2025-05-22 13:10:16'),
(6, 1, '1747929782_803ca2f0b5f805fa39793f3a52e6f59c.jpg', '../../uploads/1747936377_1747929782_803ca2f0b5f805fa39793f3a52e6f59c.jpg', 'funcionario', 1, '2025-05-22 14:52:57'),
(7, 1, 'f88f9dca2bc7b1779f5658ba7111bde1.jpg', '../../uploads/1747999234_f88f9dca2bc7b1779f5658ba7111bde1.jpg', 'funcionario', 1, '2025-05-23 08:20:34'),
(8, 2, '497ea8e750b684e132364fd6c66588fa.jpg', '../../uploads/1748007829_497ea8e750b684e132364fd6c66588fa.jpg', 'funcionario', 1, '2025-05-23 10:43:49'),
(9, 2, '162d6f53794784f1f75b21234fdd8f44.jpg', '../../uploads/1748019675_162d6f53794784f1f75b21234fdd8f44.jpg', 'funcionario', 1, '2025-05-23 14:01:15'),
(10, 1, '7f7777ea243871b0ca474945659db644.jpg', '../../uploads/1748020205_7f7777ea243871b0ca474945659db644.jpg', 'funcionario', 1, '2025-05-23 14:10:05'),
(11, 1, '0b3bd25b69bb4fd15da59a6d6feb1fe7.jpg', '../../uploads/1748023342_0b3bd25b69bb4fd15da59a6d6feb1fe7.jpg', 'funcionario', 1, '2025-05-23 15:02:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('cliente','funcionario','admin') NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `criado_em`) VALUES
(1, 'Gabriel', 'gabriel@gmail.com', '$2y$10$A8OB9of4MTOSDNs5/sw7L.AOSsXcWetUH1C0xXO2z4d78v5r2l5da', 'admin', '2025-05-22 08:15:44'),
(2, 'Vinicius', 'vinicius@gmail.com', '$2y$10$mOfbuFoSZDoGggM67P4u7eyvTa6EC1bosV4idi7/yWn6VBFL0DPLq', 'funcionario', '2025-05-22 08:16:16'),
(3, 'Lucas', 'lucas@gmail.com', '$2y$10$5.V2gWHClQzkZEZd/LPAQuyXRVKrERDV/rprlukGp7BmeJWhAIuMW', 'cliente', '2025-05-22 08:16:50'),
(19, 'Joãozinho', 'joao@gmail.com', '$2y$10$cJELEmNin7Lqpc3gIReeuOwEUESEIOUoGkkkBh8uxFq3ryD8H.Eka', 'funcionario', '2025-05-29 08:22:57');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `entregas`
--
ALTER TABLE `entregas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projeto_id` (`projeto_id`);

--
-- Índices de tabela `formularios_contato`
--
ALTER TABLE `formularios_contato`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `remetente_id` (`remetente_id`),
  ADD KEY `destinatario_id` (`destinatario_id`),
  ADD KEY `projeto_id` (`projeto_id`);

--
-- Índices de tabela `portfolio`
--
ALTER TABLE `portfolio`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `projetos`
--
ALTER TABLE `projetos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `projetos_usuarios`
--
ALTER TABLE `projetos_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projeto_id` (`projeto_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `tarefas`
--
ALTER TABLE `tarefas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projeto_id` (`projeto_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projeto_id` (`projeto_id`),
  ADD KEY `enviado_por` (`enviado_por`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `entregas`
--
ALTER TABLE `entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `formularios_contato`
--
ALTER TABLE `formularios_contato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `portfolio`
--
ALTER TABLE `portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `projetos`
--
ALTER TABLE `projetos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `projetos_usuarios`
--
ALTER TABLE `projetos_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `tarefas`
--
ALTER TABLE `tarefas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `entregas`
--
ALTER TABLE `entregas`
  ADD CONSTRAINT `entregas_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`);

--
-- Restrições para tabelas `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mensagens_ibfk_3` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`);

--
-- Restrições para tabelas `projetos`
--
ALTER TABLE `projetos`
  ADD CONSTRAINT `projetos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `projetos_usuarios`
--
ALTER TABLE `projetos_usuarios`
  ADD CONSTRAINT `projetos_usuarios_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`),
  ADD CONSTRAINT `projetos_usuarios_ibfk_2` FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `tarefas`
--
ALTER TABLE `tarefas`
  ADD CONSTRAINT `tarefas_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`),
  ADD CONSTRAINT `tarefas_ibfk_2` FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`),
  ADD CONSTRAINT `uploads_ibfk_2` FOREIGN KEY (`enviado_por`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
