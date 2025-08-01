-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 01/08/2025 às 23:09
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

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `LimparArquivosOrfaos` ()   BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE arquivo_caminho VARCHAR(500);
  DECLARE cur CURSOR FOR 
    SELECT caminho_arquivo FROM uploads 
    WHERE projeto_id NOT IN (SELECT id FROM projetos)
    OR status_arquivo = 'excluido';
    
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  
  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO arquivo_caminho;
    IF done THEN
      LEAVE read_loop;
    END IF;
    UPDATE uploads SET status_arquivo = 'excluido' 
    WHERE caminho_arquivo = arquivo_caminho;
  END LOOP;
  CLOSE cur;

  DELETE FROM uploads 
  WHERE status_arquivo = 'excluido' 
  AND data_upload < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_mensagens`
--

CREATE TABLE `chat_mensagens` (
  `id` int(11) NOT NULL,
  `conversa_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `enviado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `chat_mensagens`
--
DELIMITER $$
CREATE TRIGGER `update_conversa_timestamp` AFTER INSERT ON `chat_mensagens` FOR EACH ROW BEGIN
    UPDATE `conversas` 
    SET `ultima_mensagem` = NEW.enviado_em 
    WHERE `id` = NEW.conversa_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `conversas`
--

CREATE TABLE `conversas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `status` enum('ativa','fechada','pausada') DEFAULT 'ativa',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_mensagem` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `conversas`
--

INSERT INTO `conversas` (`id`, `cliente_id`, `titulo`, `status`, `criado_em`, `ultima_mensagem`) VALUES
(3, 3, 'Conversa - cliente', 'ativa', '2025-08-01 18:50:10', '2025-08-01 20:58:35');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `projetos`
--

CREATE TABLE `projetos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `status` enum('pendente','aprovado','em_andamento','finalizado','ajustes') DEFAULT 'pendente',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `projetos`
--

INSERT INTO `projetos` (`id`, `titulo`, `descricao`, `cliente_id`, `status`, `data_inicio`, `data_fim`, `criado_em`) VALUES
(4, 'Projeto de teste', 'teste', 3, 'em_andamento', NULL, '2025-09-05', '2025-08-01 18:49:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `projetos_usuarios`
--

CREATE TABLE `projetos_usuarios` (
  `id` int(11) NOT NULL,
  `projeto_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tarefas`
--

CREATE TABLE `tarefas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('a_fazer','em_progresso','concluido') DEFAULT 'a_fazer',
  `prioridade` enum('baixa','media','alta') DEFAULT 'media',
  `prazo` date DEFAULT NULL,
  `projeto_id` int(11) DEFAULT NULL,
  `funcionario_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tarefas`
--

INSERT INTO `tarefas` (`id`, `titulo`, `descricao`, `status`, `prioridade`, `prazo`, `projeto_id`, `funcionario_id`, `criado_em`) VALUES
(3, 'Tarefa teste', 'Teste', 'a_fazer', 'media', NULL, 4, 2, '2025-08-01 18:58:01');

-- --------------------------------------------------------

--
-- Estrutura para tabela `uploads`
--

CREATE TABLE `uploads` (
  `id` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `projeto_id` int(11) DEFAULT NULL,
  `enviado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `tamanho_arquivo` bigint(20) DEFAULT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_arquivo` enum('ativo','excluido') DEFAULT 'ativo',
  `enviado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `uploads`
--

INSERT INTO `uploads` (`id`, `nome_arquivo`, `caminho_arquivo`, `projeto_id`, `enviado_em`, `tamanho_arquivo`, `tipo_mime`, `data_upload`, `status_arquivo`, `enviado_por`) VALUES
(26, 'industria-5.0.png', 'uploads/Projeto_de_teste_4/688d0c48a7814_1754074184_industria-5.0.png', 4, '2025-08-01 18:49:44', 995553, 'image/png', '2025-08-01 18:49:44', 'ativo', 3),
(27, 'cafeteira (2).png', 'uploads/Projeto_de_teste_4/688d0c48ab6af_1754074184_cafeteira__2_.png', 4, '2025-08-01 18:49:44', 310975, 'image/png', '2025-08-01 18:49:44', 'ativo', 3);

--
-- Acionadores `uploads`
--
DELIMITER $$
CREATE TRIGGER `after_upload_insert` AFTER INSERT ON `uploads` FOR EACH ROW BEGIN
  INSERT INTO upload_logs (projeto_id, usuario_id, acao, arquivo_nome, detalhes)
  SELECT NEW.projeto_id, p.cliente_id, 'upload', NEW.nome_arquivo,
         CONCAT('Arquivo: ', NEW.nome_arquivo, ', Tamanho: ', NEW.tamanho_arquivo, ' bytes')
  FROM projetos p WHERE p.id = NEW.projeto_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `upload_configuracoes`
--

CREATE TABLE `upload_configuracoes` (
  `id` int(11) NOT NULL,
  `max_file_size` bigint(20) DEFAULT 52428800,
  `max_files_per_project` int(11) DEFAULT 0,
  `allowed_types` text DEFAULT 'image/jpeg,image/png,image/gif,image/webp,image/bmp,application/pdf',
  `upload_path` varchar(255) DEFAULT 'uploads/',
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `upload_logs`
--

CREATE TABLE `upload_logs` (
  `id` int(11) NOT NULL,
  `projeto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` enum('upload','delete','error') NOT NULL,
  `arquivo_nome` varchar(255) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `data_acao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `upload_logs`
--

INSERT INTO `upload_logs` (`id`, `projeto_id`, `usuario_id`, `acao`, `arquivo_nome`, `detalhes`, `ip_address`, `data_acao`) VALUES
(26, 4, 3, 'upload', 'industria-5.0.png', 'Arquivo: industria-5.0.png, Tamanho: 995553 bytes', NULL, '2025-08-01 18:49:44'),
(27, 4, 3, 'upload', 'cafeteira (2).png', 'Arquivo: cafeteira (2).png, Tamanho: 310975 bytes', NULL, '2025-08-01 18:49:44'),
(28, 4, 3, 'upload', 'cafeteira (2).png', 'Arquivo: cafeteira (2).png, Tamanho: 310975 bytes', NULL, '2025-08-01 20:33:14'),
(29, 4, 3, 'upload', 'desenhos-de-shadow-the-hedgehog-para-imprimir-e-colorir-04.jpg', 'Arquivo: desenhos-de-shadow-the-hedgehog-para-imprimir-e-colorir-04.jpg, Tamanho: 142263 bytes', NULL, '2025-08-01 20:33:26'),
(30, 4, 3, 'upload', 'Bob-Esponja-5-250x250.png', 'Arquivo: Bob-Esponja-5-250x250.png, Tamanho: 24206 bytes', NULL, '2025-08-01 20:38:40'),
(31, 4, 3, 'upload', 'desenhos-do-homem-aranha-para-colorir-agachado.png', 'Arquivo: desenhos-do-homem-aranha-para-colorir-agachado.png, Tamanho: 66514 bytes', NULL, '2025-08-01 20:41:39');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `upload_stats`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `upload_stats` (
`projeto_id` int(11)
,`projeto_titulo` varchar(255)
,`total_arquivos` bigint(21)
,`tamanho_total` decimal(41,0)
,`ultimo_upload` timestamp
,`tipos_arquivo` mediumtext
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('cliente','admin','funcionario') NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `criado_em`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$Zf/Q96S5Bmm98syrqIhOd.vPwtKmTQBNI2An/26WvlhPXEiU.cS6.', 'admin', '2025-08-01 18:41:01'),
(2, 'funcionario', 'funcionario@gmail.com', '$2y$10$8dVwYOGb/iVB7VNXehlH4.wVQPPR0SGcJMTfJf5H7ReIN3hKihXkq', 'funcionario', '2025-08-01 18:43:19'),
(3, 'cliente', 'cliente@gmail.com', '$2y$10$AH.jN1szDTw3S0cMfieGGuSR326SEEy/hM8MWVy6tuNF8CbfiXj7O', 'cliente', '2025-08-01 18:43:41');

-- --------------------------------------------------------

--
-- Estrutura para view `upload_stats`
--
DROP TABLE IF EXISTS `upload_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `upload_stats`  AS SELECT `p`.`id` AS `projeto_id`, `p`.`titulo` AS `projeto_titulo`, count(`u`.`id`) AS `total_arquivos`, sum(`u`.`tamanho_arquivo`) AS `tamanho_total`, max(`u`.`data_upload`) AS `ultimo_upload`, group_concat(distinct `u`.`tipo_mime` separator ',') AS `tipos_arquivo` FROM (`projetos` `p` left join `uploads` `u` on(`p`.`id` = `u`.`projeto_id` and `u`.`status_arquivo` = 'ativo')) GROUP BY `p`.`id`, `p`.`titulo` ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversa_id` (`conversa_id`),
  ADD KEY `idx_remetente_id` (`remetente_id`),
  ADD KEY `idx_enviado_em` (`enviado_em`),
  ADD KEY `idx_lida` (`lida`),
  ADD KEY `idx_mensagem_conversa_enviado` (`conversa_id`,`enviado_em`),
  ADD KEY `idx_mensagem_nao_lida` (`conversa_id`,`lida`,`remetente_id`);

--
-- Índices de tabela `conversas`
--
ALTER TABLE `conversas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_ultima_mensagem` (`ultima_mensagem`),
  ADD KEY `idx_conversa_status_ultima` (`status`,`ultima_mensagem`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `remetente_id` (`remetente_id`),
  ADD KEY `destinatario_id` (`destinatario_id`),
  ADD KEY `projeto_id` (`projeto_id`);

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
  ADD KEY `projeto_id` (`projeto_id`);

--
-- Índices de tabela `upload_configuracoes`
--
ALTER TABLE `upload_configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `upload_logs`
--
ALTER TABLE `upload_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projeto_id` (`projeto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

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
-- AUTO_INCREMENT de tabela `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `conversas`
--
ALTER TABLE `conversas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tarefas`
--
ALTER TABLE `tarefas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `upload_configuracoes`
--
ALTER TABLE `upload_configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `upload_logs`
--
ALTER TABLE `upload_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `chat_mensagens`
--
ALTER TABLE `chat_mensagens`
  ADD CONSTRAINT `fk_chat_mensagens_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_mensagens_remetente` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `conversas`
--
ALTER TABLE `conversas`
  ADD CONSTRAINT `fk_conversas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `projetos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `tarefas_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tarefas_ibfk_2` FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `upload_logs`
--
ALTER TABLE `upload_logs`
  ADD CONSTRAINT `upload_logs_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `upload_logs_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
