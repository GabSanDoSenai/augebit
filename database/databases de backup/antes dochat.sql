-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 22/06/2025 às 22:36
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
(1, 'Projeto industrial', 'Claro! Aqui está um exemplo de **descrição de um projeto industrial** fictício, com linguagem profissional e técnica:\r\n\r\n---\r\n\r\n### **Projeto Industrial – Implantação de Sistema Automatizado de Envasamento**\r\n\r\n**Nome do Projeto:** Modernização da Linha de Produção – Fase 1\r\n**Cliente:** Indústria Alimentícia SolNatura S/A\r\n**Localização:** Planta Industrial – Contagem, MG\r\n**Início Previsto:** Julho de 2025\r\n**Duração Estimada:** 8 meses\r\n\r\n**Objetivo Geral:**\r\nImplantar um sistema automatizado de envasamento e rotulagem para a linha de produção de sucos naturais, visando o aumento da eficiência operacional, redução de perdas e melhoria nos padrões de qualidade e rastreabilidade dos produtos.\r\n\r\n**Escopo do Projeto:**\r\n\r\n* Substituição de esteiras manuais por esteiras automatizadas com sensores inteligentes.\r\n* Instalação de um conjunto de envasadoras automáticas com controle volumétrico digital.\r\n* Implementação de rotuladoras automáticas com integração ao ERP da empresa.\r\n* Desenvolvimento de um sistema de monitoramento em tempo real para controle de produtividade.\r\n* Treinamento da equipe operacional e técnica para operação dos novos equipamentos.\r\n\r\n**Tecnologias Envolvidas:**\r\n\r\n* CLPs Siemens S7-1500\r\n* Sensores fotoelétricos e indutivos da Sick AG\r\n* SCADA para supervisão da planta via protocolo OPC UA\r\n* Integração com sistema SAP para controle de estoque e produção\r\n* Internet Industrial das Coisas (IIoT) para rastreabilidade\r\n\r\n**Resultados Esperados:**\r\n\r\n* Aumento de 35% na produtividade da linha de sucos\r\n* Redução de 20% nas perdas por falhas humanas\r\n* Melhoria de 50% nos indicadores de qualidade e conformidade\r\n* Redução de 15% no consumo energético por unidade produzida\r\n\r\n**Status Atual:**\r\nProjeto em fase de compras e mobilização de equipe técnica. Obras civis para adequação do layout já iniciadas.\r\n\r\n---\r\n\r\nSe quiser que eu personalize com base em um projeto real ou adaptação para outro setor (metalúrgico, têxtil, farmacêutico, etc.), posso ajustar também.', 4, 'pendente', NULL, '2026-12-29', '2025-06-22 17:49:50'),
(2, 'Projeto industrial', 'Claro! Aqui está um exemplo de **descrição de um projeto industrial** fictício, com linguagem profissional e técnica:\r\n\r\n---\r\n\r\n### **Projeto Industrial – Implantação de Sistema Automatizado de Envasamento**\r\n\r\n**Nome do Projeto:** Modernização da Linha de Produção – Fase 1\r\n**Cliente:** Indústria Alimentícia SolNatura S/A\r\n**Localização:** Planta Industrial – Contagem, MG\r\n**Início Previsto:** Julho de 2025\r\n**Duração Estimada:** 8 meses\r\n\r\n**Objetivo Geral:**\r\nImplantar um sistema automatizado de envasamento e rotulagem para a linha de produção de sucos naturais, visando o aumento da eficiência operacional, redução de perdas e melhoria nos padrões de qualidade e rastreabilidade dos produtos.\r\n\r\n**Escopo do Projeto:**\r\n\r\n* Substituição de esteiras manuais por esteiras automatizadas com sensores inteligentes.\r\n* Instalação de um conjunto de envasadoras automáticas com controle volumétrico digital.\r\n* Implementação de rotuladoras automáticas com integração ao ERP da empresa.\r\n* Desenvolvimento de um sistema de monitoramento em tempo real para controle de produtividade.\r\n* Treinamento da equipe operacional e técnica para operação dos novos equipamentos.\r\n\r\n**Tecnologias Envolvidas:**\r\n\r\n* CLPs Siemens S7-1500\r\n* Sensores fotoelétricos e indutivos da Sick AG\r\n* SCADA para supervisão da planta via protocolo OPC UA\r\n* Integração com sistema SAP para controle de estoque e produção\r\n* Internet Industrial das Coisas (IIoT) para rastreabilidade\r\n\r\n**Resultados Esperados:**\r\n\r\n* Aumento de 35% na produtividade da linha de sucos\r\n* Redução de 20% nas perdas por falhas humanas\r\n* Melhoria de 50% nos indicadores de qualidade e conformidade\r\n* Redução de 15% no consumo energético por unidade produzida\r\n\r\n**Status Atual:**\r\nProjeto em fase de compras e mobilização de equipe técnica. Obras civis para adequação do layout já iniciadas.\r\n\r\n---\r\n\r\nSe quiser que eu personalize com base em um projeto real ou adaptação para outro setor (metalúrgico, têxtil, farmacêutico, etc.), posso ajustar também.', 4, 'pendente', NULL, '2026-12-29', '2025-06-22 17:54:42');

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
(1, 'Projeto industrial', 'Fazer a primeira parte do projeto', 'a_fazer', 'media', NULL, 1, 2, '2025-06-22 19:09:37');

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
(1, 'cafeteira.png', 'uploads/Projeto_industrial_1/6858423eee60e_1750614590_cafeteira.png', 1, '2025-06-22 17:49:50', 310975, 'image/png', '2025-06-22 17:49:50', 'ativo', 2),
(2, 'Projeto-Industrial.jpg', 'uploads/Projeto_industrial_1/6858423eefbe6_1750614590_Projeto-Industrial.jpg', 1, '2025-06-22 17:49:50', 102380, 'image/jpeg', '2025-06-22 17:49:50', 'ativo', 2),
(3, 'Anime Vanguards - Update 6.5 Log.pdf', 'uploads/Projeto_industrial_1/6858423f09e89_1750614591_Anime_Vanguards_-_Update_6.5_Log.pdf', 1, '2025-06-22 17:49:51', 14920626, 'application/pdf', '2025-06-22 17:49:51', 'ativo', 2),
(4, 'cafeteira.png', 'uploads/Projeto_industrial_2/6858436284118_1750614882_cafeteira.png', 2, '2025-06-22 17:54:42', 310975, 'image/png', '2025-06-22 17:54:42', 'ativo', 1),
(5, 'Projeto-Industrial.jpg', 'uploads/Projeto_industrial_2/68584362863a2_1750614882_Projeto-Industrial.jpg', 2, '2025-06-22 17:54:42', 102380, 'image/jpeg', '2025-06-22 17:54:42', 'ativo', 1),
(6, 'TERMO MOTION KIDS.docx', 'uploads/Projeto_industrial_2/6858436289a14_1750614882_TERMO_MOTION_KIDS.docx', 2, '2025-06-22 17:54:42', 15382, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2025-06-22 17:54:42', 'ativo', 1),
(7, 'Anime Vanguards - Update 6.5 Log.pdf', 'uploads/Projeto_industrial_2/685843628d0c2_1750614882_Anime_Vanguards_-_Update_6.5_Log.pdf', 2, '2025-06-22 17:54:42', 14920626, 'application/pdf', '2025-06-22 17:54:42', 'ativo', 1),
(8, '5f02f7d73b6e0b0ac4430215dc1b9b39.jpg', '../uploads/1750615164_5f02f7d73b6e0b0ac4430215dc1b9b39.jpg', 1, '2025-06-22 17:59:24', NULL, NULL, '2025-06-22 17:59:24', 'ativo', 2),
(9, '5f02f7d73b6e0b0ac4430215dc1b9b39.jpg', '../uploads/1750624305_5f02f7d73b6e0b0ac4430215dc1b9b39.jpg', 1, '2025-06-22 20:31:45', NULL, NULL, '2025-06-22 20:31:45', 'ativo', NULL);

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
(1, 1, 4, 'upload', 'cafeteira.png', 'Arquivo: cafeteira.png, Tamanho: 310975 bytes', NULL, '2025-06-22 17:49:50'),
(2, 1, 4, 'upload', 'Projeto-Industrial.jpg', 'Arquivo: Projeto-Industrial.jpg, Tamanho: 102380 bytes', NULL, '2025-06-22 17:49:50'),
(3, 1, 4, 'upload', 'Anime Vanguards - Update 6.5 Log.pdf', 'Arquivo: Anime Vanguards - Update 6.5 Log.pdf, Tamanho: 14920626 bytes', NULL, '2025-06-22 17:49:51'),
(4, 2, 4, 'upload', 'cafeteira.png', 'Arquivo: cafeteira.png, Tamanho: 310975 bytes', NULL, '2025-06-22 17:54:42'),
(5, 2, 4, 'upload', 'Projeto-Industrial.jpg', 'Arquivo: Projeto-Industrial.jpg, Tamanho: 102380 bytes', NULL, '2025-06-22 17:54:42'),
(6, 2, 4, 'upload', 'TERMO MOTION KIDS.docx', 'Arquivo: TERMO MOTION KIDS.docx, Tamanho: 15382 bytes', NULL, '2025-06-22 17:54:42'),
(7, 2, 4, 'upload', 'Anime Vanguards - Update 6.5 Log.pdf', 'Arquivo: Anime Vanguards - Update 6.5 Log.pdf, Tamanho: 14920626 bytes', NULL, '2025-06-22 17:54:42'),
(8, 1, 4, 'upload', '5f02f7d73b6e0b0ac4430215dc1b9b39.jpg', NULL, NULL, '2025-06-22 17:59:24'),
(9, 1, 4, 'upload', '5f02f7d73b6e0b0ac4430215dc1b9b39.jpg', NULL, NULL, '2025-06-22 20:31:45');

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
(1, 'Gabriel Santoni Espindola', 'gabriel@gmail.com', '$2y$10$r3nPF3jnqmPWeO0D9lwB9O1nYBDD2EZmMPTgCvu62QXG2DjYzmHcK', 'admin', '2025-06-22 17:39:46'),
(2, 'Vinicius', 'vinicius@gmail.com', '$2y$10$l/Gxam7Hru6fjezNYEzJpusQMyCLeyUkeALK7sweT464C8ZRGvym2', 'funcionario', '2025-06-22 17:43:41'),
(4, 'Ryan', 'ryan@gmail.com', '$2y$10$muukBbqByQrQfLaCbeTokuttEG/LWs/AvWSBbNpXtpphuiqkpHfbW', 'cliente', '2025-06-22 17:44:31');

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
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `projetos`
--
ALTER TABLE `projetos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `projetos_usuarios`
--
ALTER TABLE `projetos_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tarefas`
--
ALTER TABLE `tarefas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `upload_configuracoes`
--
ALTER TABLE `upload_configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `upload_logs`
--
ALTER TABLE `upload_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para tabelas despejadas
--

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
