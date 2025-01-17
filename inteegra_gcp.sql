-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/01/2025 às 15:47
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
-- Banco de dados: `inteegra_gcp`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `contato` varchar(100) DEFAULT NULL,
  `status` enum('Ativo','Inativo') DEFAULT 'Ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `contato`, `status`, `created_at`, `updated_at`, `ativo`) VALUES
(1, 'Inteegra', 'Rafaassas', 'Ativo', '2024-12-26 18:02:18', '2024-12-26 18:03:44', 0),
(2, 'Inteegra', 'Rafael', 'Ativo', '2024-12-26 18:03:54', '2024-12-26 18:03:54', 1),
(3, 'Nestle', 'asdasds', 'Ativo', '2024-12-26 18:23:50', '2024-12-26 18:23:50', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `custos_projeto`
--

CREATE TABLE `custos_projeto` (
  `id` int(11) NOT NULL,
  `projeto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_custo` enum('Horas','Diaria') NOT NULL DEFAULT 'Horas',
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `horas_trabalhadas` decimal(10,2) NOT NULL,
  `valor_hora` decimal(10,2) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `tipo_origem` enum('Previsto em Proposta','Venda Adicional','Custo Operacional') NOT NULL DEFAULT 'Previsto em Proposta',
  `justificativa` text NOT NULL,
  `aprovador_id` int(11) NOT NULL,
  `status` enum('Pendente','Aprovado','Reprovado','Pago') DEFAULT NULL,
  `comentario_aprovador` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `custos_projeto`
--

INSERT INTO `custos_projeto` (`id`, `projeto_id`, `usuario_id`, `tipo_custo`, `data_inicio`, `data_fim`, `horas_trabalhadas`, `valor_hora`, `valor_total`, `tipo_origem`, `justificativa`, `aprovador_id`, `status`, `comentario_aprovador`, `created_at`, `updated_at`, `ativo`) VALUES
(10, 13, 32, 'Horas', '2024-11-18 18:00:00', '2024-11-18 19:00:00', 1.00, 20.00, 20.00, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-11-13 14:40:59', '2024-11-14 15:23:59', 1),
(11, 13, 32, 'Horas', '2024-11-18 18:00:00', '2024-11-18 22:00:00', 4.00, 20.00, 80.00, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-11-13 14:40:59', '2024-11-14 15:20:25', 1),
(12, 14, 32, 'Horas', '2024-11-18 18:00:00', '2024-11-18 19:00:00', 1.00, 20.00, 20.00, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-11-13 14:40:59', '2024-11-14 15:20:24', 1),
(13, 14, 32, 'Horas', '2024-11-18 18:00:00', '2024-11-18 22:00:00', 4.00, 20.00, 80.00, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-11-13 14:40:59', '2024-11-14 15:20:23', 1),
(14, 13, 34, 'Horas', '2024-11-18 18:00:00', '2024-11-18 19:00:00', 1.00, 20.00, 20.00, 'Previsto em Proposta', 'Teste', 34, 'Reprovado', 'Teste', '2024-11-13 14:40:59', '2024-11-14 15:21:14', 1),
(15, 13, 34, 'Horas', '2024-11-18 18:00:00', '2024-11-18 22:00:00', 4.00, 20.00, 80.00, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-11-13 14:40:59', '2024-11-14 15:20:27', 1),
(16, 14, 34, 'Horas', '2024-11-18 18:00:00', '2024-11-18 19:00:00', 1.00, 20.00, 20.00, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-11-13 14:40:59', '2024-11-14 15:20:29', 1),
(17, 14, 34, 'Horas', '2024-11-18 18:00:00', '2024-11-18 22:00:00', 4.00, 20.00, 80.00, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-11-13 14:40:59', '2024-11-14 15:20:30', 1),
(18, 13, 31, 'Horas', '2024-12-22 09:24:00', '2024-12-22 10:24:00', 1.00, 100.00, 100.00, 'Previsto em Proposta', 'Teste', 35, 'Aprovado', 'teste', '2024-12-26 12:24:47', '2024-12-26 12:25:19', 1),
(19, 14, 31, 'Horas', '2024-12-23 09:26:00', '2024-12-23 12:26:00', 3.00, 100.00, 300.00, 'Previsto em Proposta', 'Teste', 35, 'Aprovado', 'teste', '2024-12-26 12:26:22', '2024-12-26 12:26:38', 1),
(20, 13, 32, 'Horas', '2024-12-23 10:12:00', '2024-12-23 14:12:00', 4.00, 20.00, 80.00, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-12-26 13:12:23', '2024-12-26 13:13:20', 1),
(21, 14, 32, 'Horas', '2024-12-24 10:12:00', '2024-12-24 10:14:00', 0.03, 20.00, 0.60, 'Previsto em Proposta', 'Teste', 34, 'Aprovado', 'Teste', '2024-12-26 13:12:45', '2024-12-26 13:13:32', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(50) NOT NULL,
  `data_hora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `logs`
--

INSERT INTO `logs` (`id`, `usuario_id`, `acao`, `data_hora`) VALUES
(174, 31, 'Atualizou o usuário com ID 31', '2024-11-04 17:37:40'),
(175, 31, 'Atualizou o usuário com ID 31', '2024-11-04 17:38:10'),
(176, 31, 'Atualizou o usuário com ID 31', '2024-11-04 17:38:13'),
(177, 31, 'Criou um novo projeto com ID 9', '2024-11-04 17:43:34'),
(178, 31, 'Editou o projeto com ID 9', '2024-11-04 17:43:38'),
(179, 31, 'Editou o projeto com ID 9', '2024-11-04 17:43:50'),
(180, 31, 'Editou o projeto com ID 9', '2024-11-04 17:44:38'),
(181, 31, 'Criou custo de projeto ID 2', '2024-11-04 18:15:49'),
(182, 31, 'Atualizou custo de projeto ID 2', '2024-11-04 18:20:39'),
(183, 31, 'Atualizou custo de projeto ID 2', '2024-11-04 18:20:52'),
(184, 31, 'Atualizou custo de projeto ID 2', '2024-11-04 18:21:24'),
(185, 31, 'Atualizou custo de projeto ID 2', '2024-11-04 18:21:34'),
(186, 31, 'Atualizou custo de projeto ID 2', '2024-11-04 18:21:50'),
(187, 31, 'Atualizou custo de projeto ID 2', '2024-11-04 18:21:57'),
(188, 31, 'Atualizou custo de projeto ID 2', '2024-11-04 18:22:06'),
(189, 31, 'Criou um novo usuário com ID 32', '2024-11-04 18:23:05'),
(190, 31, 'Atualizou o usuário com ID 32', '2024-11-04 18:23:15'),
(191, 31, 'Criou um novo usuário com ID 33', '2024-11-04 18:23:47'),
(192, 31, 'Criou um novo usuário com ID 34', '2024-11-04 18:24:11'),
(193, 31, 'Criou um novo usuário com ID 35', '2024-11-04 18:24:32'),
(194, 31, 'Logout realizado', '2024-11-04 18:24:44'),
(195, 32, 'Criou custo de projeto ID 3', '2024-11-04 18:45:24'),
(196, 32, 'Criou custo de projeto ID 4', '2024-11-04 18:46:29'),
(197, 32, 'Atualizou custo de projeto ID 4', '2024-11-04 18:46:45'),
(198, 32, 'Logout realizado', '2024-11-04 18:52:26'),
(199, 34, 'Atualizou custo de projeto ID 3', '2024-11-04 18:58:20'),
(200, 34, 'Atualizou custo de projeto ID 4', '2024-11-04 18:58:27'),
(201, 34, 'Logout realizado', '2024-11-04 18:59:41'),
(202, 34, 'Logout realizado', '2024-11-04 19:00:05'),
(203, 32, 'Logout realizado', '2024-11-04 19:00:22'),
(204, 33, 'Criou um novo usuário com ID 36', '2024-11-04 19:01:10'),
(205, 33, 'Logout realizado', '2024-11-04 19:01:12'),
(209, 34, 'Atualizou custo de projeto ID 5', '2024-11-04 19:21:02'),
(210, 34, 'Criou custo de projeto ID 6', '2024-11-04 19:21:44'),
(211, 34, 'Logout realizado', '2024-11-04 19:22:28'),
(212, 35, 'Atualizou custo de projeto ID 6', '2024-11-04 19:23:01'),
(213, 35, 'Logout realizado', '2024-11-04 19:25:30'),
(214, 33, 'Logout realizado', '2024-11-04 19:32:17'),
(215, 32, 'Logout realizado', '2024-11-04 19:32:38'),
(216, 34, 'Logout realizado', '2024-11-04 19:32:55'),
(217, 33, 'Atualizou custo de projeto ID 1', '2024-11-04 19:33:20'),
(218, 33, 'Atualizou custo de projeto ID 2', '2024-11-04 19:33:28'),
(219, 33, 'Logout realizado', '2024-11-04 19:33:52'),
(220, 32, 'Alterou sua senha', '2024-11-04 19:39:10'),
(221, 32, 'Logout realizado', '2024-11-04 19:39:54'),
(225, 32, 'Logout realizado', '2024-11-04 19:41:15'),
(226, 32, 'Criou custo de projeto ID 7', '2024-11-04 19:52:25'),
(227, 32, 'Atualizou custo de projeto ID 7', '2024-11-04 19:55:16'),
(228, 31, 'Alterou sua senha', '2024-11-07 12:33:44'),
(229, 31, 'Atualizou o usuário com ID 36 (senha alterada)', '2024-11-07 13:15:40'),
(230, 31, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-11-07 13:15:52'),
(231, 31, 'Logout realizado', '2024-11-07 13:16:21'),
(232, 31, 'Atualizou o usuário com ID 36', '2024-11-07 13:19:17'),
(233, 31, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-11-07 13:46:07'),
(234, 31, 'Criou um novo usuário com ID 37', '2024-11-07 13:46:10'),
(235, 31, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-11-07 13:56:44'),
(236, 31, 'Atualizou o usuário com ID 31', '2024-11-07 14:07:11'),
(237, 31, 'Atualizou o usuário com ID 31', '2024-11-07 14:07:23'),
(238, 31, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-11-07 14:07:31'),
(239, 31, 'Criou um novo usuário com ID 38', '2024-11-07 14:07:58'),
(240, 31, 'Editou o projeto com ID 9', '2024-11-07 16:47:44'),
(241, 31, 'Editou o projeto com ID 9', '2024-11-07 16:48:00'),
(242, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 16:51:18'),
(243, 31, 'Atualizou o usuário com ID 37 (senha alterada)', '2024-11-07 16:58:11'),
(244, 31, 'Atualizou o usuário com ID 37 (senha alterada)', '2024-11-07 16:58:19'),
(245, 31, 'Editou o projeto com ID 9', '2024-11-07 16:58:35'),
(246, 31, 'Editou o projeto com ID 9', '2024-11-07 16:58:38'),
(247, 31, 'Criou um novo projeto com ID 10', '2024-11-07 16:59:04'),
(248, 31, 'Editou o projeto com ID 9', '2024-11-07 16:59:12'),
(249, 31, 'Editou o projeto com ID 9', '2024-11-07 16:59:16'),
(250, 31, 'Atualizou o usuário com ID 38', '2024-11-07 17:00:46'),
(251, 31, 'Editou o projeto com ID 9', '2024-11-07 17:00:54'),
(252, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 17:01:05'),
(253, 31, 'Atualizou o usuário com ID 38', '2024-11-07 17:10:23'),
(254, 31, 'Atualizou o usuário com ID 38 (senha alterada)', '2024-11-07 17:13:41'),
(255, 31, 'Atualizou o usuário com ID 38', '2024-11-07 17:14:21'),
(256, 31, 'Editou o projeto com ID 9', '2024-11-07 17:14:26'),
(257, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 17:15:33'),
(258, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 17:16:01'),
(259, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 17:18:38'),
(260, 31, 'Editou o projeto com ID 9', '2024-11-07 17:19:40'),
(261, 31, 'Editou o projeto com ID 10', '2024-11-07 17:19:43'),
(262, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 17:21:10'),
(263, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 17:22:51'),
(264, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 17:25:08'),
(265, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 17:25:22'),
(266, 31, 'Criou custo de projeto ID 8', '2024-11-07 17:25:46'),
(267, 31, 'Editou o projeto com ID 10', '2024-11-07 17:28:12'),
(268, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 17:28:23'),
(269, 31, 'Editou o projeto com ID 10', '2024-11-07 17:31:03'),
(270, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 17:31:24'),
(271, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 17:41:30'),
(272, 31, 'Atualizou o usuário com ID 38 (senha alterada)', '2024-11-07 17:45:59'),
(273, 31, 'Criou um novo usuário com ID 39', '2024-11-07 17:46:12'),
(274, 31, 'Editou o projeto com ID 9', '2024-11-07 17:46:18'),
(275, 31, 'Criou um novo projeto com ID 11', '2024-11-07 17:46:38'),
(276, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 17:46:45'),
(277, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 17:46:59'),
(278, 31, 'Criou custo de projeto ID 9', '2024-11-07 17:47:30'),
(279, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 17:47:56'),
(280, 31, 'Editou o projeto com ID 11', '2024-11-07 17:54:33'),
(281, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 17:54:46'),
(282, 31, 'Editou o projeto com ID 9', '2024-11-07 18:06:34'),
(283, 31, 'Editou o projeto com ID 11', '2024-11-07 18:22:36'),
(284, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:22:49'),
(285, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:26:47'),
(286, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:27:05'),
(287, 31, 'Editou o projeto com ID 11', '2024-11-07 18:27:14'),
(288, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:33:16'),
(289, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 18:33:28'),
(290, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 18:33:39'),
(291, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:33:52'),
(292, 31, 'Editou o projeto com ID 11', '2024-11-07 18:34:14'),
(293, 31, 'Atualizou o usuário com ID 31', '2024-11-07 18:34:23'),
(294, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:35:07'),
(295, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:35:34'),
(296, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:47:05'),
(297, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 18:49:32'),
(298, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 19:02:51'),
(299, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:06:11'),
(300, 31, 'Atualizou o usuário com ID 31', '2024-11-07 19:06:34'),
(301, 31, 'Editou o projeto com ID 11', '2024-11-07 19:06:39'),
(302, 31, 'Atualizou o usuário com ID 39', '2024-11-07 19:10:42'),
(303, 31, 'Atualizou o usuário com ID 39', '2024-11-07 19:10:49'),
(304, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:13:14'),
(305, 31, 'Atualizou o usuário com ID 31', '2024-11-07 19:13:23'),
(306, 31, 'Editou o projeto com ID 11', '2024-11-07 19:13:27'),
(307, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:13:36'),
(308, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:13:40'),
(309, 31, 'Editou o projeto com ID 11', '2024-11-07 19:15:25'),
(310, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 19:15:31'),
(311, 31, 'Atualizou custo de projeto ID 7', '2024-11-07 19:15:35'),
(312, 31, 'Atualizou o usuário com ID 39', '2024-11-07 19:16:12'),
(313, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 19:21:41'),
(314, 31, 'Editou o projeto com ID 11', '2024-11-07 19:21:49'),
(315, 31, 'Atualizou o usuário com ID 31', '2024-11-07 19:21:53'),
(316, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:21:59'),
(317, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:22:57'),
(318, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:23:26'),
(319, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:24:22'),
(320, 31, 'Atualizou custo de projeto ID 8', '2024-11-07 19:24:34'),
(321, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:25:40'),
(322, 31, 'Atualizou o usuário com ID 39', '2024-11-07 19:26:31'),
(323, 31, 'Atualizou o usuário com ID 31', '2024-11-07 19:27:47'),
(324, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:27:52'),
(325, 31, 'Atualizou o usuário com ID 39', '2024-11-07 19:31:26'),
(326, 31, 'Atualizou o usuário com ID 39', '2024-11-07 19:33:10'),
(327, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:33:21'),
(328, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:34:57'),
(329, 31, 'Atualizou o usuário com ID 31', '2024-11-07 19:38:06'),
(330, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:38:13'),
(331, 31, 'Atualizou custo de projeto ID 9', '2024-11-07 19:40:10'),
(332, 33, 'Alterou sua senha', '2024-11-11 11:14:39'),
(333, 33, 'Atualizou o usuário com ID 31', '2024-11-11 11:14:47'),
(334, 33, 'Atualizou o usuário com ID 39', '2024-11-11 11:26:57'),
(335, 33, 'Editou o projeto com ID 9', '2024-11-11 11:27:43'),
(336, 33, 'Editou o projeto com ID 10', '2024-11-11 11:27:46'),
(337, 33, 'Editou o projeto com ID 9', '2024-11-11 11:28:49'),
(338, 33, 'Atualizou o usuário com ID 39', '2024-11-11 11:31:52'),
(339, 33, 'Atualizou custo de projeto ID 7', '2024-11-11 11:32:06'),
(340, 33, 'Criou um novo usuário com ID 40', '2024-11-11 11:33:13'),
(341, 33, 'Atualizou o usuário com ID 40', '2024-11-11 11:33:18'),
(342, 33, 'Atualizou o usuário com ID 40', '2024-11-11 11:33:24'),
(343, 33, 'Atualizou o usuário com ID 40', '2024-11-11 11:33:29'),
(344, 33, 'Atualizou o usuário com ID 40', '2024-11-11 11:33:35'),
(345, 33, 'Excluiu custo de projeto ID 9', '2024-11-11 11:36:34'),
(346, 33, 'Criou um novo projeto com ID 12', '2024-11-11 11:37:25'),
(347, 33, 'Excluiu projeto ID 12', '2024-11-11 11:37:30'),
(348, 33, 'Excluiu usuário ID 38', '2024-11-11 11:38:14'),
(349, 33, 'Excluiu usuário ID 40', '2024-11-11 11:38:17'),
(350, 33, 'Excluiu usuário ID 39', '2024-11-11 11:38:19'),
(351, 33, 'Excluiu usuário ID 37', '2024-11-11 11:38:21'),
(352, 33, 'Excluiu usuário ID 36', '2024-11-11 11:38:24'),
(353, 33, 'Excluiu usuário ID 35', '2024-11-11 11:38:26'),
(354, 33, 'Excluiu usuário ID 34', '2024-11-11 11:38:28'),
(355, 33, 'Excluiu usuário ID 32', '2024-11-11 11:38:31'),
(356, 33, 'Editou o projeto com ID 9', '2024-11-11 11:48:51'),
(357, 33, 'Editou o projeto com ID 9', '2024-11-11 11:48:58'),
(358, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 11:49:18'),
(359, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:26:07'),
(360, 33, 'Atualizou o usuário com ID 33', '2024-11-11 12:27:35'),
(361, 33, 'Editou o projeto com ID 9', '2024-11-11 12:27:44'),
(362, 33, 'Editou o projeto com ID 9', '2024-11-11 12:27:51'),
(363, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:28:56'),
(364, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:31:30'),
(365, 33, 'Editou o projeto com ID 11', '2024-11-11 12:33:45'),
(366, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:33:52'),
(367, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:34:19'),
(368, 33, 'Editou o projeto com ID 9', '2024-11-11 12:35:34'),
(369, 33, 'Editou o projeto com ID 11', '2024-11-11 12:35:40'),
(370, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:35:49'),
(371, 33, 'Atualizou o usuário com ID 33', '2024-11-11 12:38:07'),
(372, 33, 'Atualizou o usuário com ID 33', '2024-11-11 12:38:12'),
(373, 33, 'Editou o projeto com ID 9', '2024-11-11 12:38:22'),
(374, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:38:33'),
(375, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:40:46'),
(376, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:42:33'),
(377, 33, 'Atualizou o usuário com ID 31', '2024-11-11 12:46:09'),
(378, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:46:30'),
(379, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:46:54'),
(380, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:47:22'),
(381, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 12:49:23'),
(382, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:20:34'),
(383, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:23:04'),
(384, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:26:21'),
(385, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:26:47'),
(386, 33, 'Editou o projeto com ID 11', '2024-11-11 13:28:40'),
(387, 33, 'Editou o projeto com ID 11', '2024-11-11 13:28:49'),
(388, 33, 'Editou o projeto com ID 11', '2024-11-11 13:28:55'),
(389, 33, 'Editou o projeto com ID 11', '2024-11-11 13:29:29'),
(390, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:29:45'),
(391, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:40:50'),
(392, 33, 'Editou o projeto com ID 11', '2024-11-11 13:42:35'),
(393, 33, 'Editou o projeto com ID 9', '2024-11-11 13:45:46'),
(394, 33, 'Editou o projeto com ID 9', '2024-11-11 13:51:13'),
(395, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:52:25'),
(396, 33, 'Editou o projeto com ID 11', '2024-11-11 13:53:47'),
(397, 33, 'Editou o projeto com ID 11', '2024-11-11 13:54:41'),
(398, 33, 'Editou o projeto com ID 11', '2024-11-11 13:55:28'),
(399, 33, 'Editou o projeto com ID 11', '2024-11-11 13:56:17'),
(400, 33, 'Editou o projeto com ID 11', '2024-11-11 13:56:23'),
(401, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:57:58'),
(402, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 13:59:57'),
(403, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 14:02:12'),
(404, 33, 'Editou o projeto com ID 9', '2024-11-11 14:02:32'),
(405, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 15:52:04'),
(406, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 15:52:19'),
(407, 33, 'Editou o projeto com ID 11', '2024-11-11 15:53:01'),
(408, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 15:53:56'),
(409, 33, 'Atualizou custo de projeto ID 7', '2024-11-11 15:54:08'),
(410, 33, 'Editou o projeto com ID 11', '2024-11-11 15:55:49'),
(411, 33, 'Editou o projeto com ID 11', '2024-11-11 15:57:55'),
(412, 33, 'Editou o projeto com ID 9', '2024-11-11 15:58:01'),
(413, 33, 'Editou o projeto com ID 10', '2024-11-11 15:58:11'),
(414, 33, 'Editou o projeto com ID 11', '2024-11-11 15:58:14'),
(415, 33, 'Editou o projeto com ID 9', '2024-11-11 15:58:26'),
(416, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 16:00:05'),
(417, 33, 'Editou o projeto com ID 9', '2024-11-11 16:01:16'),
(418, 33, 'Editou o projeto com ID 9', '2024-11-11 16:01:19'),
(419, 33, 'Editou o projeto com ID 10', '2024-11-11 16:01:25'),
(420, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 16:02:39'),
(421, 33, 'Atualizou o usuário com ID 33', '2024-11-11 17:59:54'),
(422, 33, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-11-11 18:08:01'),
(423, 33, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-11-11 18:08:38'),
(424, 33, 'Atualizou o usuário com ID 33 (senha alterada)', '2024-11-11 18:08:46'),
(425, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 18:45:25'),
(426, 33, 'Atualizou custo de projeto ID 8', '2024-11-11 18:48:03'),
(427, 31, 'Logout realizado', '2024-11-13 11:33:01'),
(428, 31, 'Logout realizado', '2024-11-13 11:33:34'),
(429, 32, 'Criou um novo projeto com ID 13', '2024-11-13 11:34:25'),
(430, 32, 'Logout realizado', '2024-11-13 11:34:32'),
(431, 31, 'Atualizou o usuário com ID 31', '2024-11-13 11:34:54'),
(432, 31, 'Logout realizado', '2024-11-13 11:35:01'),
(433, 32, 'Criou custo de projeto ID 10', '2024-11-13 11:40:59'),
(434, 32, 'Logout realizado', '2024-11-13 11:41:56'),
(435, 31, 'Criou um novo projeto com ID 14', '2024-11-13 11:45:54'),
(436, 31, 'Editou o projeto com ID 14', '2024-11-13 11:46:24'),
(437, 31, 'Logout realizado', '2024-11-14 09:48:40'),
(438, 31, 'Logout realizado', '2024-11-14 10:02:39'),
(439, 31, 'Atualizou custo de projeto ID 11', '2024-11-14 12:17:46'),
(440, 31, 'Atualizou custo de projeto ID 10', '2024-11-14 12:19:15'),
(441, 31, 'Criou pagamento ID 1', '2024-11-14 12:22:22'),
(442, 31, 'Criou pagamento ID 2', '2024-11-14 12:23:38'),
(443, 31, 'Atualizou custo de projeto ID 10', '2024-11-14 12:23:59'),
(444, 31, 'Excluiu pagamento ID 2', '2024-11-14 12:26:41'),
(445, 31, 'Criou pagamento ID 3', '2024-11-14 12:26:55'),
(446, 31, 'Atualizou pagamento ID 3', '2024-11-14 12:27:51'),
(447, 31, 'Logout realizado', '2024-11-14 12:28:09'),
(448, 32, 'Logout realizado', '2024-11-14 12:28:38'),
(449, 34, 'Logout realizado', '2024-11-14 12:29:06'),
(450, 32, 'Logout realizado', '2024-11-14 12:29:56'),
(451, 31, 'Logout realizado', '2024-11-14 12:41:01'),
(452, 32, 'Logout realizado', '2024-11-14 13:10:37'),
(453, 31, 'Atualizou pagamento ID 3', '2024-11-14 14:17:43'),
(454, 31, 'Logout realizado', '2024-11-14 14:17:52'),
(455, 32, 'Logout realizado', '2024-11-14 14:18:09'),
(456, 31, 'Logout realizado', '2024-11-14 14:40:54'),
(457, 32, 'Logout realizado', '2024-11-14 14:41:18'),
(458, 31, 'Logout realizado', '2024-11-14 14:43:45'),
(459, 32, 'Logout realizado', '2024-11-14 14:52:38'),
(460, 32, 'Logout realizado', '2024-11-14 14:55:29'),
(461, 31, 'Logout realizado', '2024-11-14 14:56:37'),
(462, 32, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-14 14:58:28'),
(463, 32, 'Logout realizado', '2024-11-14 15:04:16'),
(464, 31, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-14 15:04:46'),
(465, 31, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-14 15:05:03'),
(466, 31, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-14 15:10:28'),
(467, 31, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-14 15:10:35'),
(468, 31, 'Logout realizado', '2024-11-14 15:10:40'),
(469, 32, 'Logout realizado', '2024-11-14 15:11:25'),
(470, 31, 'Atualizou pagamento ID 3 para status Aprovado', '2024-11-14 15:11:42'),
(471, 31, 'Atualizou pagamento ID 3 para status Pendente NF', '2024-11-14 15:12:05'),
(472, 31, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-14 15:12:13'),
(473, 31, 'Atualizou pagamento ID 3 para status Aprovado', '2024-11-14 15:14:06'),
(474, 31, 'Atualizou pagamento ID 3 para status Pendente NF', '2024-11-14 15:14:16'),
(475, 31, 'Atualizou pagamento ID 3 para status Aprovado', '2024-11-14 15:49:55'),
(476, 31, 'Atualizou pagamento ID 3 para status Pendente NF', '2024-11-14 15:50:24'),
(477, 31, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-14 15:50:34'),
(478, 31, 'Atualizou pagamento ID 3 para status Pendente NF', '2024-11-14 15:50:50'),
(479, 31, 'Logout realizado', '2024-11-14 15:50:54'),
(480, 32, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-14 15:51:10'),
(481, 32, 'Logout realizado', '2024-11-14 15:54:30'),
(482, 31, 'Atualizou pagamento ID 3 para status Pendente Paga', '2024-11-21 10:57:52'),
(483, 31, 'Excluiu pagamento ID 1', '2024-11-21 12:09:19'),
(484, 31, 'Criou pagamento ID 4', '2024-11-21 12:12:11'),
(485, 31, 'Excluiu pagamento ID 4', '2024-11-21 12:12:39'),
(486, 31, 'Criou pagamento ID 5', '2024-11-21 12:13:32'),
(487, 31, 'Logout realizado', '2024-11-21 12:14:44'),
(488, 31, 'Logout realizado', '2024-11-21 12:15:06'),
(489, 33, 'Atualizou pagamento ID 5 para status Pendente', '2024-11-21 12:22:43'),
(490, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:22:58'),
(491, 33, 'Atualizou pagamento ID 5 para status Pendente', '2024-11-21 12:23:10'),
(492, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:23:24'),
(493, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:26:16'),
(494, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:28:26'),
(495, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:28:36'),
(496, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:35:21'),
(497, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:37:31'),
(498, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:37:38'),
(499, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:37:42'),
(500, 33, 'Atualizou pagamento ID 5 para status Aprovado', '2024-11-21 12:37:46'),
(501, 33, 'Logout realizado', '2024-11-21 12:38:32'),
(502, 32, 'Logout realizado', '2024-11-21 12:39:33'),
(503, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 12:42:24'),
(504, 33, 'Atualizou pagamento ID 5 para status Pendente', '2024-11-21 12:42:38'),
(505, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 12:42:44'),
(506, 33, 'Logout realizado', '2024-11-21 12:42:50'),
(507, 32, 'Atualizou pagamento ID 5 para status Pendente Paga', '2024-11-21 12:47:30'),
(508, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 12:56:00'),
(509, 32, 'Atualizou pagamento ID 5 para status Pendente Paga', '2024-11-21 13:02:54'),
(510, 33, 'Atualizou pagamento ID 5 para status Pago', '2024-11-21 13:04:32'),
(511, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:06:48'),
(512, 33, 'Atualizou pagamento ID 5 para status Pendente', '2024-11-21 13:07:08'),
(513, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:07:18'),
(514, 32, 'Atualizou pagamento ID 5 para status Pendente PG', '2024-11-21 13:07:34'),
(515, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:08:01'),
(516, 32, 'Atualizou pagamento ID 5 para status Pendente PG', '2024-11-21 13:08:13'),
(517, 33, 'Atualizou pagamento ID 5 para status Pendente', '2024-11-21 13:08:38'),
(518, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:12:26'),
(519, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:24:47'),
(520, 32, 'Atualizou pagamento ID 5 para status Pendente PG', '2024-11-21 13:25:39'),
(521, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:27:21'),
(522, 32, 'Atualizou pagamento ID 5 para status Pendente PG', '2024-11-21 13:27:34'),
(523, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:30:34'),
(524, 32, 'Atualizou pagamento ID 5 para status Pendente PG', '2024-11-21 13:30:54'),
(525, 33, 'Atualizou pagamento ID 5 para status Pago', '2024-11-21 13:32:23'),
(526, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:33:26'),
(527, 32, 'Atualizou pagamento ID 5 para status Pendente PG', '2024-11-21 13:33:47'),
(528, 33, 'Atualizou pagamento ID 5 para status Pendente PG', '2024-11-21 13:34:25'),
(529, 33, 'Atualizou pagamento ID 5 para status Pago', '2024-11-21 13:34:32'),
(530, 33, 'Atualizou pagamento ID 5 para status Pendente NF', '2024-11-21 13:37:08'),
(531, 32, 'Atualizou pagamento ID 5 para status Pendente PG', '2024-11-21 13:37:19'),
(532, 33, 'Atualizou pagamento ID 5 para status Pago', '2024-11-21 13:37:29'),
(533, 33, 'Criou pagamento ID 6', '2024-11-21 13:45:18'),
(534, 32, 'Logout realizado', '2024-11-21 13:45:53'),
(535, 33, 'Atualizou pagamento ID 6 para status Pendente NF', '2024-11-21 13:46:34'),
(536, 33, 'Atualizou pagamento ID 6 para status Pendente NF', '2024-11-21 13:50:44'),
(537, 34, 'Atualizou pagamento ID 6 para status Pendente PG', '2024-11-21 14:03:05'),
(538, 33, 'Atualizou pagamento ID 6 para status Pendente NF', '2024-11-21 14:05:04'),
(539, 34, 'Atualizou pagamento ID 6 para status Pendente PG', '2024-11-21 14:06:04'),
(540, 33, 'Atualizou pagamento ID 6 para status Pendente NF', '2024-11-21 14:07:47'),
(541, 34, 'Atualizou pagamento ID 6 para status Pendente PG', '2024-11-21 14:08:15'),
(542, 33, 'Atualizou pagamento ID 6 para status Pago', '2024-11-21 14:08:46'),
(543, 33, 'Criou pagamento ID 7', '2024-11-21 14:20:48'),
(544, 33, 'Atualizou pagamento ID 7 para status Pendente NF', '2024-11-21 14:22:34'),
(545, 34, 'Logout realizado', '2024-11-21 14:23:35'),
(546, 32, 'Atualizou pagamento ID 7 para status Pendente PG', '2024-11-21 14:24:19'),
(547, 33, 'Atualizou pagamento ID 7 para status Pago', '2024-11-21 14:25:09'),
(548, 33, 'Criou pagamento ID 8', '2024-11-21 15:01:08'),
(549, 33, 'Atualizou pagamento ID 8 para status Pendente NF', '2024-11-21 15:01:44'),
(550, 32, 'Atualizou pagamento ID 8 para status Pendente PG', '2024-11-21 15:02:16'),
(551, 33, 'Atualizou pagamento ID 8 para status Pago', '2024-11-21 15:03:06'),
(552, 33, 'Criou pagamento ID 9', '2024-11-21 15:10:47'),
(553, 33, 'Atualizou pagamento ID 9 para status Pendente NF', '2024-11-21 15:11:32'),
(554, 32, 'Logout realizado', '2024-11-21 15:11:57'),
(555, 34, 'Atualizou pagamento ID 9 para status Pendente PG', '2024-11-21 15:12:24'),
(556, 33, 'Atualizou pagamento ID 9 para status Pago', '2024-11-21 15:12:46'),
(557, 33, 'Criou pagamento ID 10', '2024-11-21 15:19:12'),
(558, 33, 'Atualizou pagamento ID 10 para status Pendente NF', '2024-11-21 15:20:06'),
(559, 34, 'Logout realizado', '2024-11-21 15:20:37'),
(560, 32, 'Atualizou pagamento ID 10 para status Pendente PG', '2024-11-21 15:21:01'),
(561, 33, 'Atualizou pagamento ID 10 para status Pago', '2024-11-21 15:22:26'),
(562, 33, 'Criou pagamento ID 11', '2024-11-21 15:27:13'),
(563, 33, 'Criou pagamento ID 12', '2024-11-21 15:27:33'),
(564, 32, 'Logout realizado', '2024-11-21 15:52:56'),
(565, 31, 'Criou pagamento ID 13', '2024-11-25 17:49:12'),
(566, 31, 'Excluiu pagamento ID 13', '2024-11-25 17:50:51'),
(567, 31, 'Criou pagamento ID 14', '2024-11-25 18:11:10'),
(568, 31, 'Atualizou o usuário com ID 34', '2024-11-25 18:20:32'),
(569, 31, 'Atualizou o usuário com ID 32', '2024-11-25 19:24:19'),
(570, 31, 'Atualizou o usuário com ID 34', '2024-11-25 19:24:27'),
(571, 31, 'Criou pagamento ID 15', '2024-11-25 19:29:09'),
(572, 31, 'Criou custo de projeto ID 18', '2024-12-26 09:24:47'),
(573, 31, 'Atualizou custo de projeto ID 18', '2024-12-26 09:25:19'),
(574, 31, 'Criou custo de projeto ID 19', '2024-12-26 09:26:22'),
(575, 31, 'Atualizou custo de projeto ID 19', '2024-12-26 09:26:38'),
(576, 31, 'Atualizou o usuário com ID 33 (senha alterada)', '2024-12-26 09:27:16'),
(577, 31, 'Logout realizado', '2024-12-26 09:27:37'),
(578, 33, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-12-26 09:29:50'),
(579, 33, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-12-26 09:29:56'),
(580, 33, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-12-26 09:30:03'),
(581, 33, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-12-26 09:30:07'),
(582, 33, 'Logout realizado', '2024-12-26 09:30:09'),
(583, 31, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-12-26 09:30:41'),
(584, 31, 'Logout realizado', '2024-12-26 09:30:46'),
(585, 31, 'Logout realizado', '2024-12-26 09:31:18'),
(586, 33, 'Atualizou o usuário com ID 31 (senha alterada)', '2024-12-26 10:10:48'),
(587, 33, 'Atualizou o usuário com ID 35', '2024-12-26 10:11:17'),
(588, 33, 'Logout realizado', '2024-12-26 10:11:57'),
(589, 32, 'Criou custo de projeto ID 20', '2024-12-26 10:12:23'),
(590, 32, 'Criou custo de projeto ID 21', '2024-12-26 10:12:45'),
(591, 32, 'Logout realizado', '2024-12-26 10:13:03'),
(592, 31, 'Atualizou custo de projeto ID 20', '2024-12-26 10:13:20'),
(593, 31, 'Atualizou custo de projeto ID 21', '2024-12-26 10:13:32'),
(594, 31, 'Criou pagamento ID 16', '2024-12-26 10:16:51'),
(595, 31, 'Atualizou pagamento ID 16 para status Pendente', '2024-12-26 10:19:00'),
(596, 31, 'Logout realizado', '2024-12-26 10:19:15'),
(597, 32, 'Logout realizado', '2024-12-26 10:20:14'),
(598, 31, 'Aprovou pagamento ID 16', '2024-12-26 10:38:58'),
(599, 31, 'Atualizou pagamento ID 16 para status Pendente', '2024-12-26 10:40:56'),
(600, 31, 'Aprovou pagamento ID 16', '2024-12-26 10:41:06'),
(601, 31, 'Atualizou pagamento ID 16 para status Pendente', '2024-12-26 10:41:21'),
(602, 31, 'Excluiu pagamento ID 16', '2024-12-26 10:43:23'),
(603, 31, 'Criou pagamento ID 17', '2024-12-26 10:43:53'),
(604, 31, 'Aprovou pagamento ID 17', '2024-12-26 10:44:05'),
(605, 31, 'Atualizou pagamento ID 17 para status Pendente', '2024-12-26 10:47:56'),
(606, 31, 'Excluiu pagamento ID 17', '2024-12-26 10:47:59'),
(607, 31, 'Criou pagamento ID 18', '2024-12-26 10:48:17'),
(608, 31, 'Aprovou pagamento ID 18', '2024-12-26 10:48:34'),
(609, 31, 'Atualizou pagamento ID 18 para status Pendente', '2024-12-26 10:54:13'),
(610, 31, 'Excluiu pagamento ID 18', '2024-12-26 10:54:18'),
(611, 31, 'Criou pagamento ID 19', '2024-12-26 10:54:38'),
(612, 31, 'Aprovou pagamento ID 19', '2024-12-26 10:54:52'),
(613, 31, 'Atualizou pagamento ID 19 para status Pendente', '2024-12-26 10:58:24'),
(614, 31, 'Excluiu pagamento ID 19', '2024-12-26 10:58:27'),
(615, 31, 'Criou pagamento ID 20', '2024-12-26 10:58:48'),
(616, 31, 'Aprovou pagamento ID 20', '2024-12-26 10:58:57'),
(617, 31, 'Atualizou pagamento ID 20 para status Pendente', '2024-12-26 10:59:54'),
(618, 31, 'Aprovou pagamento ID 20', '2024-12-26 10:59:59'),
(619, 31, 'Atualizou pagamento ID 20 para status Pendente', '2024-12-26 11:00:10'),
(620, 31, 'Excluiu pagamento ID 20', '2024-12-26 11:00:15'),
(621, 31, 'Criou pagamento ID 21', '2024-12-26 11:00:32'),
(622, 31, 'Aprovou pagamento ID 21', '2024-12-26 11:01:33'),
(623, 31, 'Atualizou pagamento ID 21 para status Pendente', '2024-12-26 11:11:13'),
(624, 31, 'Excluiu pagamento ID 21', '2024-12-26 11:11:16'),
(625, 31, 'Criou pagamento ID 22', '2024-12-26 11:11:47'),
(626, 31, 'Aprovou pagamento ID 22', '2024-12-26 11:14:37'),
(627, 31, 'Atualizou pagamento ID 22 para status Pendente', '2024-12-26 11:19:23'),
(628, 31, 'Excluiu pagamento ID 22', '2024-12-26 11:19:29'),
(629, 31, 'Criou pagamento ID 23', '2024-12-26 11:19:44'),
(630, 31, 'Aprovou pagamento ID 23', '2024-12-26 11:19:56'),
(631, 31, 'Logout realizado', '2024-12-26 11:20:30'),
(632, 32, 'Criou um novo projeto com ID 15', '2024-12-26 13:50:04'),
(633, 32, 'Criou um novo projeto com ID 16', '2024-12-26 13:56:06'),
(634, 32, 'Criou um novo projeto com ID 17', '2024-12-26 13:58:30'),
(635, 32, 'Excluiu projeto ID 16', '2024-12-26 13:58:43'),
(636, 32, 'Excluiu projeto ID 15', '2024-12-26 13:58:46'),
(637, 32, 'Criou um novo projeto com ID 18', '2024-12-26 14:00:59'),
(638, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:12:30'),
(639, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:13:14'),
(640, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:13:34'),
(641, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:14:16'),
(642, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:14:36'),
(643, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:14:46'),
(644, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:15:49'),
(645, 32, 'Atualizou o projeto ID 13', '2024-12-26 14:17:01'),
(646, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:17:08'),
(647, 32, 'Atualizou o projeto ID 13', '2024-12-26 14:27:55'),
(648, 32, 'Atualizou o projeto ID 18', '2024-12-26 14:28:03'),
(649, 32, 'Atualizou o projeto ID 17', '2024-12-26 14:28:12'),
(650, 32, 'Criou um novo projeto com ID 19', '2024-12-26 14:33:36'),
(651, 32, 'Atualizou o projeto ID 19', '2024-12-26 14:34:10'),
(652, 32, 'Atualizou o projeto ID 13', '2024-12-26 14:36:16'),
(653, 32, 'Atualizou o projeto ID 19', '2024-12-26 14:38:32'),
(654, 32, 'Criou cliente ID 1', '2024-12-26 15:02:18'),
(655, 32, 'Atualizou cliente ID 1', '2024-12-26 15:03:38'),
(656, 32, 'Excluiu cliente ID 1', '2024-12-26 15:03:44'),
(657, 32, 'Criou cliente ID 2', '2024-12-26 15:03:54'),
(658, 32, 'Criou um novo projeto com ID 20', '2024-12-26 15:21:25'),
(659, 32, 'Criou cliente ID 3', '2024-12-26 15:23:50'),
(660, 32, 'Atualizou o projeto ID 20', '2024-12-26 15:23:59'),
(661, 32, 'Atualizou o projeto ID 20', '2024-12-26 15:24:06'),
(662, 32, 'Atualizou o projeto ID 20', '2024-12-26 15:24:13'),
(663, 32, 'Atualizou o projeto ID 20', '2024-12-26 15:24:22'),
(664, 32, 'Atualizou o projeto ID 20', '2024-12-26 15:26:10'),
(665, 32, 'Atualizou o projeto ID 20', '2024-12-26 15:26:15'),
(666, 32, 'Atualizou o projeto ID 17', '2024-12-26 15:28:21'),
(667, 32, 'Atualizou o projeto ID 20', '2024-12-26 15:28:28'),
(668, 32, 'Criou um novo projeto com ID 21', '2024-12-26 17:22:50'),
(669, 32, 'Atualizou o projeto ID 21', '2024-12-26 17:23:13'),
(670, 31, 'Criou prestador ID 1', '2024-12-27 09:53:49'),
(671, 31, 'Atualizou prestador ID 1', '2024-12-27 10:04:38'),
(672, 31, 'Atualizou prestador ID 1', '2024-12-27 10:04:44'),
(673, 31, 'Atualizou prestador ID 1', '2024-12-27 10:04:48'),
(674, 31, 'Atualizou prestador ID 1', '2024-12-27 10:05:32'),
(675, 31, 'Atualizou prestador ID 1', '2024-12-27 10:14:42'),
(676, 31, 'Atualizou prestador ID 1', '2024-12-27 10:14:51'),
(677, 31, 'Criou pagamento ID 1', '2024-12-31 09:46:38'),
(678, 31, 'Criou pagamento ID 1', '2024-12-31 11:24:36'),
(679, 31, 'Criou pagamento ID 2', '2024-12-31 11:27:58'),
(680, 31, 'Criou pagamento ID 3', '2024-12-31 11:59:02'),
(681, 31, 'Atualizou pagamento ID 3 para status Aprovado', '2024-12-31 12:00:41'),
(682, 31, 'Atualizou pagamento ID 3 para status Pendente NF', '2024-12-31 12:01:09'),
(683, 31, 'Criou pagamento ID 4', '2025-01-02 09:57:08'),
(684, 31, 'Atualizou pagamento ID 4 para status Aprovado', '2025-01-02 09:57:41'),
(685, 31, 'Atualizou pagamento ID 4 para status Aprovado', '2025-01-02 09:57:48'),
(686, 31, 'Atualizou pagamento ID 4 para status Aprovado', '2025-01-02 09:57:58'),
(687, 31, 'Atualizou pagamento ID 4 para status Aprovado', '2025-01-02 09:58:07'),
(688, 31, 'Atualizou pagamento ID 3', '2025-01-02 10:21:48'),
(689, 31, 'Atualizou pagamento ID 3', '2025-01-02 10:21:57'),
(690, 31, 'Atualizou pagamento ID 4', '2025-01-02 10:22:02'),
(691, 31, 'Criou pagamento ID 5', '2025-01-02 10:59:49'),
(692, 31, 'Criou pagamento ID 6', '2025-01-02 11:00:56'),
(693, 31, 'Aprovou pagamento ID 5', '2025-01-02 11:11:22'),
(694, 31, 'Aprovou pagamento ID 6', '2025-01-02 11:12:59'),
(695, 31, 'Aprovou pagamento ID 5', '2025-01-02 11:16:39'),
(696, 31, 'Aprovou pagamento ID 6', '2025-01-02 11:16:41'),
(697, 31, 'Aprovou pagamento ID 5', '2025-01-02 11:17:16'),
(698, 31, 'Atualizou pagamento ID 5', '2025-01-02 11:17:41'),
(699, 31, 'Atualizou pagamento ID 6', '2025-01-02 11:17:46'),
(700, 31, 'Aprovou pagamento ID 5', '2025-01-02 11:18:00'),
(701, 31, 'Atualizou pagamento ID 5', '2025-01-02 11:19:13'),
(702, 31, 'Aprovou pagamento ID 5', '2025-01-02 11:19:17'),
(703, 31, 'Aprovou pagamento ID 5', '2025-01-02 11:19:51'),
(704, 31, 'Criou pagamento ID 7', '2025-01-02 11:25:03'),
(705, 31, 'Criou pagamento ID 8', '2025-01-02 11:28:19'),
(706, 31, 'Criou pagamento ID 9', '2025-01-02 11:28:33'),
(707, 31, 'Criou pagamento ID 10', '2025-01-02 11:28:38'),
(708, 31, 'Aprovou pagamento ID 9', '2025-01-02 11:31:39'),
(709, 31, 'Aprovou pagamento ID 10', '2025-01-02 11:31:42'),
(710, 31, 'Aprovou pagamento ID 7', '2025-01-02 11:36:54'),
(711, 31, 'Aprovou pagamento ID 8', '2025-01-02 11:36:56'),
(712, 31, 'Criou pagamento ID 11', '2025-01-02 11:45:07'),
(713, 31, 'Criou pagamento ID 12', '2025-01-02 11:45:14'),
(714, 31, 'Atualizou pagamento ID 11', '2025-01-02 11:45:17'),
(715, 31, 'Aprovou pagamento ID 11', '2025-01-02 11:45:20'),
(716, 31, 'Atualizou pagamento ID 12', '2025-01-02 11:45:23'),
(717, 31, 'Aprovou pagamento ID 12', '2025-01-02 11:45:28'),
(718, 31, 'Atualizou pagamento ID 12', '2025-01-02 11:45:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `solicitante_id` int(11) NOT NULL,
  `ordem_pagamento` varchar(50) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `mes` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `status` enum('Pendente','Aprovado','Nota Fiscal','Pago') NOT NULL DEFAULT 'Pendente',
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `data_aprovacao` datetime DEFAULT NULL,
  `data_nf` datetime DEFAULT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `nota_fiscal` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `aprovador_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `pagamentos`
--

INSERT INTO `pagamentos` (`id`, `solicitante_id`, `ordem_pagamento`, `valor_total`, `mes`, `ano`, `status`, `data_criacao`, `data_aprovacao`, `data_nf`, `data_pagamento`, `observacoes`, `nota_fiscal`, `ativo`, `aprovador_id`, `updated_at`) VALUES
(11, 34, '1', 180.00, 11, 2024, 'Aprovado', '2025-01-02 11:45:07', '2025-01-02 11:45:20', NULL, NULL, NULL, NULL, 1, 31, '2025-01-02 14:45:20'),
(12, 32, '2', 200.00, 11, 2024, 'Aprovado', '2025-01-02 11:45:14', '2025-01-02 11:45:28', NULL, NULL, NULL, NULL, 1, 31, '2025-01-02 14:45:28');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos_custos`
--

CREATE TABLE `pagamentos_custos` (
  `pagamento_id` int(11) NOT NULL,
  `custo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `pagamentos_custos`
--

INSERT INTO `pagamentos_custos` (`pagamento_id`, `custo_id`) VALUES
(11, 15),
(11, 16),
(11, 17),
(12, 10),
(12, 11),
(12, 12),
(12, 13);

-- --------------------------------------------------------

--
-- Estrutura para tabela `prestadores`
--

CREATE TABLE `prestadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `data_nascimento` date NOT NULL,
  `cpf` varchar(11) NOT NULL,
  `rg` varchar(20) NOT NULL,
  `cnpj` varchar(14) DEFAULT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `celular` varchar(15) NOT NULL,
  `endereco` varchar(255) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` varchar(2) NOT NULL,
  `tamanho_camiseta` varchar(3) NOT NULL,
  `habilitacao` enum('Sim','Não') NOT NULL,
  `genero` varchar(50) NOT NULL,
  `deficiencia` varchar(50) NOT NULL,
  `blacklist` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `prestadores`
--

INSERT INTO `prestadores` (`id`, `nome`, `email`, `data_nascimento`, `cpf`, `rg`, `cnpj`, `razao_social`, `celular`, `endereco`, `cidade`, `estado`, `tamanho_camiseta`, `habilitacao`, `genero`, `deficiencia`, `blacklist`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Rafael Arantes', 'rafael@inteegra.com.br', '1982-09-11', '11111111111', '1', '11111111111111', '1', '11993931815', '1', 'São Paulo', 'SP', 'GG', 'Sim', 'Masculino', 'Outra', 0, 1, '2024-12-27 12:53:49', '2024-12-27 13:14:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `projetos`
--

CREATE TABLE `projetos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `id_evento` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `responsavel_comercial` int(11) DEFAULT NULL,
  `responsavel_atendimento` int(11) DEFAULT NULL,
  `responsavel_tecnico` int(11) DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_fim` datetime DEFAULT NULL,
  `local` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` int(1) NOT NULL DEFAULT 1,
  `data_inicio_evento` datetime DEFAULT NULL,
  `data_fim_evento` datetime DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `contratante_id` int(11) DEFAULT NULL,
  `cliente_final_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `projetos`
--

INSERT INTO `projetos` (`id`, `nome`, `id_evento`, `descricao`, `responsavel_comercial`, `responsavel_atendimento`, `responsavel_tecnico`, `valor_total`, `data_inicio`, `data_fim`, `local`, `created_at`, `updated_at`, `ativo`, `data_inicio_evento`, `data_fim_evento`, `estado`, `cidade`, `contratante_id`, `cliente_final_id`) VALUES
(13, 'Teste', 123123, 'Isso é um teste', 31, 31, 31, 10.00, '2024-11-25 11:34:00', '2024-11-26 11:34:00', 'Teste', '2024-11-13 14:34:25', '2024-12-26 17:17:01', 1, '1969-12-31 21:00:00', '1969-12-31 22:00:00', 'SP', 'qwqwqw', NULL, NULL),
(14, 'Teste 2', 321321, 'Teste 2', 33, 33, 33, 15000.00, '2024-11-27 11:45:00', '2024-11-28 11:45:00', 'Teste', '2024-11-13 14:45:54', '2024-11-13 14:45:54', 1, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'rww', 12, 'asdasd', 35, 35, 35, 123131.00, '0000-00-00 00:00:00', '2024-12-31 13:44:00', 'asdasdasd', '2024-12-26 16:50:04', '2024-12-26 16:58:46', 0, '2024-12-30 13:44:00', '2024-12-31 13:44:00', 'SP', 'asdasdas', NULL, NULL),
(16, 'Teste', 123, 'asdasd', 33, 34, 32, 12.00, '0000-00-00 00:00:00', '2024-12-27 13:55:00', 'sdasd', '2024-12-26 16:56:06', '2024-12-26 16:58:43', 0, '2024-12-24 13:55:00', '2024-12-25 13:55:00', 'SP', 'asdasd', NULL, NULL),
(17, '3', 123, '123', 35, NULL, NULL, 1231.23, '2024-12-30 13:58:00', '2024-12-31 13:58:00', '123123', '2024-12-26 16:58:30', '2024-12-26 18:28:21', 1, '2024-12-30 13:58:00', '2024-12-31 13:58:00', 'SE', '123123', 2, 2),
(18, '1', 1, '1', 33, NULL, NULL, 1000.00, '2024-12-01 00:00:00', '2024-12-07 00:00:00', 'Expo', '2024-12-26 17:00:59', '2024-12-26 17:28:03', 1, '2024-12-02 00:00:00', '2024-12-06 00:00:00', 'SP', 'SP', NULL, NULL),
(19, '4', 4, '4', 33, NULL, NULL, 4.00, '2024-12-30 14:04:00', '2024-12-31 15:04:00', '4', '2024-12-26 17:33:36', '2024-12-26 17:34:10', 1, '2024-12-30 14:04:00', '2024-12-31 15:04:00', 'SP', '4', NULL, NULL),
(20, 'Teste', 102030, 'Teste', 33, NULL, NULL, 1231231.23, '2025-01-06 15:21:00', '2025-01-07 15:21:00', '123123', '2024-12-26 18:21:25', '2024-12-26 18:28:28', 1, '2025-01-06 15:21:00', '2025-01-07 15:21:00', 'SP', 'asdasdasa', 2, 3),
(21, 'Teste01', 121, 'Teste', 33, 34, 32, 1000.00, '2025-01-06 17:22:00', '2025-01-10 17:22:00', 'Teste', '2024-12-26 20:22:50', '2024-12-26 20:23:13', 1, '2025-01-07 17:22:00', '2025-01-07 22:22:00', 'SP', 'SP', 2, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `projeto_arquivos`
--

CREATE TABLE `projeto_arquivos` (
  `id` int(11) NOT NULL,
  `projeto_id` int(11) DEFAULT NULL,
  `nome_arquivo` varchar(255) DEFAULT NULL,
  `caminho_arquivo` varchar(500) DEFAULT NULL,
  `tipo_arquivo` varchar(100) DEFAULT NULL,
  `tamanho_arquivo` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `projeto_arquivos`
--

INSERT INTO `projeto_arquivos` (`id`, `projeto_id`, `nome_arquivo`, `caminho_arquivo`, `tipo_arquivo`, `tamanho_arquivo`, `usuario_id`, `data_upload`) VALUES
(1, 18, 'CADASTRO DE CONTRATAÇÃO 2024.docx', 'uploads/projetos/18/676d8bcb67822.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 19079, 32, '2024-12-26 17:00:59'),
(2, 18, 'Allergan.txt', 'uploads/projetos/18/676d8e7e4a6cc.txt', 'text/plain', 647, 32, '2024-12-26 17:12:30'),
(3, 13, 'Allergan.txt', 'uploads/projetos/13/676d9410d772d.txt', 'text/plain', 647, 32, '2024-12-26 17:36:16'),
(4, 19, 'Relatorio-Vibra-12-12.xlsx', 'uploads/projetos/19/676d9498bc080.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 850432, 32, '2024-12-26 17:38:32'),
(5, 21, 'CADASTRO DE CONTRATAÇÃO 2024.docx', 'uploads/projetos/21/676dbb1a5f67a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 19079, 32, '2024-12-26 20:22:50');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperacao_senha`
--

CREATE TABLE `recuperacao_senha` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiracao` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `recuperacao_senha`
--

INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token`, `expiracao`, `usado`, `created_at`) VALUES
(1, 31, '23c5a621f7a2178cb50cfcb444a46642aa18b328e2ce7bab9e7196dfd8f1ea78', '2024-11-14 15:05:15', 0, '2024-11-14 13:05:15');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status` enum('Ativo','Inativo') NOT NULL,
  `perfil` enum('Administrador','AprovadorN2','AprovadorN1','Usuario') NOT NULL,
  `valor_hora` decimal(10,2) NOT NULL,
  `departamento` enum('Administracao','Comercial','Operacoes','Diretoria','Marketing','Desenvolvimento') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` int(1) NOT NULL DEFAULT 1,
  `primeiro_acesso` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `status`, `perfil`, `valor_hora`, `departamento`, `created_at`, `updated_at`, `ativo`, `primeiro_acesso`) VALUES
(31, 'Rafael Arantes', 'rafael@inteegra.com.br', '$2y$10$wOr5kUmp4J/.owN0G6c99.5ZNtlh40th1DviczDZr.cNg2RvekXQ2', 'Ativo', 'Administrador', 10.00, 'Operacoes', '2024-11-04 20:25:00', '2024-12-26 13:10:48', 1, 0),
(32, 'Denis (Usuário)', 'usuario@inteegra.com.br', '$2y$10$2My2BbA0816isLpftDd4pOrHeY1hq5cUmEeNtojJ7HCarpbx6foMS', 'Ativo', 'Usuario', 20.00, 'Operacoes', '2024-11-04 21:23:05', '2024-11-25 22:24:19', 1, 0),
(33, 'Administrador', 'administrador@inteegra.com.br', '$2y$10$PwKkOb/psV0v3iYE6zI7heaG7/N/x.Ov7lQVTJvOjAq/uGhQp3dim', 'Ativo', 'Administrador', 20.00, 'Administracao', '2024-11-04 21:23:47', '2024-12-26 12:27:16', 1, 0),
(34, 'Carol (Aprovador N1)', 'aprovadorn1@inteegra.com.br', '$2y$10$2My2BbA0816isLpftDd4pOrHeY1hq5cUmEeNtojJ7HCarpbx6foMS', 'Ativo', 'AprovadorN1', 20.00, 'Operacoes', '2024-11-04 21:24:11', '2024-11-25 22:24:27', 1, 0),
(35, 'Aprovador N2', 'aprovadorn2@inteegra.com.br', '$2y$10$2My2BbA0816isLpftDd4pOrHeY1hq5cUmEeNtojJ7HCarpbx6foMS', 'Ativo', 'Administrador', 20.00, 'Operacoes', '2024-11-04 21:24:32', '2024-12-26 13:11:17', 1, 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `custos_projeto`
--
ALTER TABLE `custos_projeto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projeto_id` (`projeto_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `aprovador_id` (`aprovador_id`);

--
-- Índices de tabela `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitante_id` (`solicitante_id`),
  ADD KEY `aprovador_id` (`aprovador_id`);

--
-- Índices de tabela `pagamentos_custos`
--
ALTER TABLE `pagamentos_custos`
  ADD PRIMARY KEY (`pagamento_id`,`custo_id`),
  ADD UNIQUE KEY `custo_id` (`custo_id`);

--
-- Índices de tabela `prestadores`
--
ALTER TABLE `prestadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `unique_cpf` (`cpf`,`ativo`),
  ADD UNIQUE KEY `unique_email` (`email`,`ativo`);

--
-- Índices de tabela `projetos`
--
ALTER TABLE `projetos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `responsavel_comercial` (`responsavel_comercial`),
  ADD KEY `projetos_ibfk_2` (`responsavel_atendimento`),
  ADD KEY `projetos_ibfk_3` (`responsavel_tecnico`),
  ADD KEY `contratante_id` (`contratante_id`),
  ADD KEY `cliente_final_id` (`cliente_final_id`);

--
-- Índices de tabela `projeto_arquivos`
--
ALTER TABLE `projeto_arquivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projeto_id` (`projeto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expiracao` (`expiracao`);

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
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `custos_projeto`
--
ALTER TABLE `custos_projeto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=719;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `prestadores`
--
ALTER TABLE `prestadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `projetos`
--
ALTER TABLE `projetos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `projeto_arquivos`
--
ALTER TABLE `projeto_arquivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `custos_projeto`
--
ALTER TABLE `custos_projeto`
  ADD CONSTRAINT `custos_projeto_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`),
  ADD CONSTRAINT `custos_projeto_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `custos_projeto_ibfk_3` FOREIGN KEY (`aprovador_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pagamentos_ibfk_2` FOREIGN KEY (`aprovador_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pagamentos_custos`
--
ALTER TABLE `pagamentos_custos`
  ADD CONSTRAINT `pagamentos_custos_ibfk_1` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`),
  ADD CONSTRAINT `pagamentos_custos_ibfk_2` FOREIGN KEY (`custo_id`) REFERENCES `custos_projeto` (`id`);

--
-- Restrições para tabelas `projetos`
--
ALTER TABLE `projetos`
  ADD CONSTRAINT `projetos_ibfk_1` FOREIGN KEY (`responsavel_comercial`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `projetos_ibfk_2` FOREIGN KEY (`responsavel_atendimento`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projetos_ibfk_3` FOREIGN KEY (`responsavel_tecnico`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projetos_ibfk_4` FOREIGN KEY (`contratante_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `projetos_ibfk_5` FOREIGN KEY (`cliente_final_id`) REFERENCES `clientes` (`id`);

--
-- Restrições para tabelas `projeto_arquivos`
--
ALTER TABLE `projeto_arquivos`
  ADD CONSTRAINT `projeto_arquivos_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`),
  ADD CONSTRAINT `projeto_arquivos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `recuperacao_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
