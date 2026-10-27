-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2026 at 05:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fixmate_v2`
--
CREATE DATABASE IF NOT EXISTS `fixmate_v2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fixmate_v2`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `created_at`) VALUES
(1, 'admin@fixmate.com', '$2y$10$GhLZ.CRtoxyGfred.PIxQu.VdJDhAfZ0S9cSPxm36CcuHLaGc.7zy', '2026-10-04 04:53:11');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `job_description` text NOT NULL,
  `preferred_time` varchar(120) NOT NULL,
  `status` enum('pending','accepted','declined','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `technician_id`, `job_description`, `preferred_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Gedara wathura batayak kadila hada ganna one ', 'Tomorrow morning 9-11 AM', 'completed', '2026-10-04 08:55:23', '2026-10-04 09:02:19'),
(2, 2, 1, 'i want to fix my home pipe line', 'Tomorrow morning 9-11 AM', 'cancelled', '2026-10-05 12:35:04', '2026-10-05 12:52:29'),
(3, 2, 1, 'Test 5 Test 5 Test 5 Test 5 Test 5 ', 'Tomorrow morning 9-11 AM', 'declined', '2026-10-05 12:53:09', '2026-10-05 13:22:18'),
(4, 2, 1, 'ufigbfgggggg\r\n', 'Tomorrow morning 9-11 AM', 'completed', '2026-10-05 13:13:38', '2026-10-05 14:21:04'),
(5, 2, 8, 'i want to themiya', '9-10 a.m.', 'pending', '2026-10-05 13:21:30', '2026-10-05 13:21:30'),
(6, 3, 5, 'Kitchen sink has been leaking for 2 days. Pipe under the cabinet needs\r\nreplacing.', 'Tomorrow morning 9-11 AM', 'pending', '2026-10-06 06:38:30', '2026-10-06 06:38:30');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `district_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `district_id`) VALUES
(1, 'Colombo 01', 1),
(2, 'Colombo 02', 1),
(3, 'Colombo 03', 1),
(4, 'Colombo 04', 1),
(5, 'Colombo 05', 1),
(6, 'Colombo 06', 1),
(7, 'Colombo 07', 1),
(8, 'Colombo 08', 1),
(9, 'Colombo 09', 1),
(10, 'Colombo 10', 1),
(11, 'Colombo 11', 1),
(12, 'Colombo 12', 1),
(13, 'Colombo 13', 1),
(14, 'Colombo 14', 1),
(15, 'Colombo 15', 1),
(16, 'Dehiwala', 1),
(17, 'Mount Lavinia', 1),
(18, 'Ratmalana', 1),
(19, 'Moratuwa', 1),
(20, 'Kotte', 1),
(21, 'Nugegoda', 1),
(22, 'Maharagama', 1),
(23, 'Piliyandala', 1),
(24, 'Kesbewa', 1),
(25, 'Boralesgamuwa', 1),
(26, 'Battaramulla', 1),
(27, 'Rajagiriya', 1),
(28, 'Kaduwela', 1),
(29, 'Homagama', 1),
(30, 'Kolonnawa', 1),
(31, 'Wellampitiya', 1),
(32, 'Mulleriyawa', 1),
(33, 'Gampaha', 2),
(34, 'Negombo', 2),
(35, 'Ja-Ela', 2),
(36, 'Wattala', 2),
(37, 'Kelaniya', 2),
(38, 'Katunayake', 2),
(39, 'Minuwangoda', 2),
(40, 'Divulapitiya', 2),
(41, 'Mirigama', 2),
(42, 'Biyagama', 2),
(43, 'Dompe', 2),
(44, 'Attanagalla', 2),
(45, 'Seeduwa', 2),
(46, 'Kandana', 2),
(47, 'Ragama', 2),
(48, 'Kiribathgoda', 2),
(49, 'Peliyagoda', 2),
(50, 'Hendala', 2),
(51, 'Udugampola', 2),
(52, 'Nittambuwa', 2),
(53, 'Veyangoda', 2),
(54, 'Kalutara', 3),
(55, 'Panadura', 3),
(56, 'Horana', 3),
(57, 'Beruwala', 3),
(58, 'Aluthgama', 3),
(59, 'Bandaragama', 3),
(60, 'Matugama', 3),
(61, 'Wadduwa', 3),
(62, 'Bentota', 3),
(63, 'Ingiriya', 3),
(64, 'Bulathsinhala', 3),
(65, 'Agalawatta', 3),
(66, 'Palindanuwara', 3),
(67, 'Walallavita', 3),
(68, 'Madurawala', 3),
(69, 'Dodangoda', 3),
(70, 'Millaniya', 3),
(71, 'Bombuwala', 3),
(72, 'Moronthuduwa', 3),
(73, 'Kandy', 4),
(74, 'Peradeniya', 4),
(75, 'Katugastota', 4),
(76, 'Kundasale', 4),
(77, 'Gampola', 4),
(78, 'Nawalapitiya', 4),
(79, 'Digana', 4),
(80, 'Teldeniya', 4),
(81, 'Pallekele', 4),
(82, 'Kadugannawa', 4),
(83, 'Harispattuwa', 4),
(84, 'Yatinuwara', 4),
(85, 'Udunuwara', 4),
(86, 'Pathahewaheta', 4),
(87, 'Uda Palatha', 4),
(88, 'Wattegama', 4),
(89, 'Hasalaka', 4),
(90, 'Menikhinna', 4),
(91, 'Matara', 5),
(92, 'Weligama', 5),
(93, 'Dikwella', 5),
(94, 'Deniyaya', 5),
(95, 'Akuressa', 5),
(96, 'Hakmana', 5),
(97, 'Kamburupitiya', 5),
(98, 'Kotapola', 5),
(99, 'Malimbada', 5),
(100, 'Pasgoda', 5),
(101, 'Pitabeddara', 5),
(102, 'Thihagoda', 5),
(103, 'Welipitiya', 5),
(104, 'Athuraliya', 5),
(105, 'Devinuwara', 5),
(106, 'Gandara', 5),
(107, 'Mirissa', 5),
(108, 'Galle', 6),
(109, 'Ambalangoda', 6),
(110, 'Hikkaduwa', 6),
(111, 'Elpitiya', 6),
(112, 'Baddegama', 6),
(113, 'Bentota', 6),
(114, 'Bope-Poddala', 6),
(115, 'Habaraduwa', 6),
(116, 'Nagoda', 6),
(117, 'Neluwa', 6),
(118, 'Niyagama', 6),
(119, 'Thawalama', 6),
(120, 'Welivitiya', 6),
(121, 'Yakkalamulla', 6),
(122, 'Akmeemana', 6),
(123, 'Balapitiya', 6),
(124, 'Karandeniya', 6),
(125, 'Unawatuna', 6),
(126, 'Ahangama', 6),
(127, 'Hambantota', 7),
(128, 'Tangalle', 7),
(129, 'Beliatta', 7),
(130, 'Ambalantota', 7),
(131, 'Tissamaharama', 7),
(132, 'Weeraketiya', 7),
(133, 'Angunakolapelessa', 7),
(134, 'Katuwana', 7),
(135, 'Okewela', 7),
(136, 'Sooriyawewa', 7),
(137, 'Lunugamvehera', 7),
(138, 'Walasmulla', 7),
(139, 'Middeniya', 7),
(140, 'Jaffna', 8),
(141, 'Chavakachcheri', 8),
(142, 'Point Pedro', 8),
(143, 'Karainagar', 8),
(144, 'Kayts', 8),
(145, 'Velanai', 8),
(146, 'Nallur', 8),
(147, 'Kopay', 8),
(148, 'Uduvil', 8),
(149, 'Sandilipay', 8),
(150, 'Chankanai', 8),
(151, 'Maruthankerny', 8),
(152, 'Tellippalai', 8),
(153, 'Mullaitivu', 9),
(154, 'Puthukkudiyiruppu', 9),
(155, 'Oddusuddan', 9),
(156, 'Maritimepattu', 9),
(157, 'Thunukkai', 9),
(158, 'Manthai East', 9),
(159, 'Welioya', 9),
(160, 'Mallavi', 9),
(161, 'Batticaloa', 10),
(162, 'Kattankudy', 10),
(163, 'Eravur', 10),
(164, 'Valaichchenai', 10),
(165, 'Chenkalady', 10),
(166, 'Vakarai', 10),
(167, 'Kalkudah', 10),
(168, 'Koralai Pattu', 10),
(169, 'Manmunai', 10),
(170, 'Porativu Pattu', 10),
(171, 'Kiran', 10),
(172, 'Vellavely', 10),
(173, 'Ampara', 11),
(174, 'Kalmunai', 11),
(175, 'Sammanthurai', 11),
(176, 'Akkaraipattu', 11),
(177, 'Pottuvil', 11),
(178, 'Arugam Bay', 11),
(179, 'Ninthavur', 11),
(180, 'Addalaichenai', 11),
(181, 'Damana', 11),
(182, 'Uhana', 11),
(183, 'Maha Oya', 11),
(184, 'Padiyathalawa', 11),
(185, 'Dehiattakandiya', 11),
(186, 'Sainthamaruthu', 11),
(187, 'Karaitivu', 11),
(188, 'Trincomalee', 12),
(189, 'Kantale', 12),
(190, 'Kinniya', 12),
(191, 'Muttur', 12),
(192, 'Seruvila', 12),
(193, 'Thampalakamam', 12),
(194, 'Eachchalampattu', 12),
(195, 'Gomarankadawala', 12),
(196, 'Kuchchaveli', 12),
(197, 'Padavisripura', 12),
(198, 'Nilaveli', 12),
(199, 'Pulmoddai', 12),
(200, 'Kurunegala', 13),
(201, 'Kuliyapitiya', 13),
(202, 'Narammala', 13),
(203, 'Polgahawela', 13),
(204, 'Melsiripura', 13),
(205, 'Wariyapola', 13),
(206, 'Nikaweratiya', 13),
(207, 'Pannala', 13),
(208, 'Alawwa', 13),
(209, 'Bingiriya', 13),
(210, 'Ganewatta', 13),
(211, 'Ibbagamuwa', 13),
(212, 'Katupotha', 13),
(213, 'Kotawehera', 13),
(214, 'Mahawa', 13),
(215, 'Mawathagama', 13),
(216, 'Polpithigama', 13),
(217, 'Rasnayakapura', 13),
(218, 'Rideegama', 13),
(219, 'Udubaddawa', 13),
(220, 'Weerambugedara', 13),
(221, 'Hiripitiya', 13),
(222, 'Puttalam', 14),
(223, 'Chilaw', 14),
(224, 'Wennappuwa', 14),
(225, 'Marawila', 14),
(226, 'Dankotuwa', 14),
(227, 'Anamaduwa', 14),
(228, 'Nattandiya', 14),
(229, 'Madampe', 14),
(230, 'Kalpitiya', 14),
(231, 'Mundel', 14),
(232, 'Arachchikattuwa', 14),
(233, 'Karuwalagaswewa', 14),
(234, 'Mahakumbukkadawala', 14),
(235, 'Palaviya', 14),
(236, 'Anuradhapura', 15),
(237, 'Mihintale', 15),
(238, 'Kekirawa', 15),
(239, 'Medawachchiya', 15),
(240, 'Thambuttegama', 15),
(241, 'Galnewa', 15),
(242, 'Horowpothana', 15),
(243, 'Kahatagasdigiliya', 15),
(244, 'Kebithigollewa', 15),
(245, 'Nachchaduwa', 15),
(246, 'Nochchiyagama', 15),
(247, 'Padaviya', 15),
(248, 'Palagala', 15),
(249, 'Palugaswewa', 15),
(250, 'Rajanganaya', 15),
(251, 'Rambewa', 15),
(252, 'Talawa', 15),
(253, 'Eppawala', 15),
(254, 'Galkiriyagama', 15),
(255, 'Ipalogama', 15),
(256, 'Polonnaruwa', 16),
(257, 'Hingurakgoda', 16),
(258, 'Kaduruwela', 16),
(259, 'Medirigiriya', 16),
(260, 'Dimbulagala', 16),
(261, 'Elahera', 16),
(262, 'Lankapura', 16),
(263, 'Thamankaduwa', 16),
(264, 'Welikanda', 16),
(265, 'Aralaganwila', 16),
(266, 'Badulla', 17),
(267, 'Bandarawela', 17),
(268, 'Haputale', 17),
(269, 'Ella', 17),
(270, 'Welimada', 17),
(271, 'Mahiyanganaya', 17),
(272, 'Passara', 17),
(273, 'Hali-Ela', 17),
(274, 'Kandeketiya', 17),
(275, 'Lunugala', 17),
(276, 'Meegahakivula', 17),
(277, 'Rideemaliyadda', 17),
(278, 'Soranatota', 17),
(279, 'Uva-Paranagama', 17),
(280, 'Diyatalawa', 17),
(281, 'Haldummulla', 17),
(282, 'Monaragala', 18),
(283, 'Wellawaya', 18),
(284, 'Bibile', 18),
(285, 'Kataragama', 18),
(286, 'Buttala', 18),
(287, 'Siyambalanduwa', 18),
(288, 'Medagama', 18),
(289, 'Badalkumbura', 18),
(290, 'Madulla', 18),
(291, 'Thanamalvila', 18),
(292, 'Kalawana', 18),
(293, 'Dombagahawela', 18),
(294, 'Ratnapura', 19),
(295, 'Embilipitiya', 19),
(296, 'Balangoda', 19),
(297, 'Kahawatta', 19),
(298, 'Kuruwita', 19),
(299, 'Pelmadulla', 19),
(300, 'Eheliyagoda', 19),
(301, 'Godakawela', 19),
(302, 'Nivithigala', 19),
(303, 'Ayagama', 19),
(304, 'Kiriella', 19),
(305, 'Kolonna', 19),
(306, 'Weligepola', 19),
(307, 'Rakwana', 19),
(308, 'Imbulpe', 19),
(309, 'Opanayaka', 19),
(310, 'Kegalle', 20),
(311, 'Mawanella', 20),
(312, 'Warakapola', 20),
(313, 'Rambukkana', 20),
(314, 'Galigamuwa', 20),
(315, 'Yatiyanthota', 20),
(316, 'Bulathkohupitiya', 20),
(317, 'Deraniyagala', 20),
(318, 'Aranayaka', 20),
(319, 'Ruwanwella', 20),
(320, 'Dehiowita', 20),
(321, 'Kitulgala', 20),
(322, 'Kotiyakumbura', 20),
(323, 'Nuwara Eliya', 21),
(324, 'Hatton', 21),
(325, 'Talawakele', 21),
(326, 'Ginigathhena', 21),
(327, 'Kotagala', 21),
(328, 'Bogawantalawa', 21),
(329, 'Maskeliya', 21),
(330, 'Ragala', 21),
(331, 'Walapane', 21),
(332, 'Hanguranketha', 21),
(333, 'Ambagamuwa', 21),
(334, 'Ramboda', 21),
(335, 'Agarapatana', 21),
(336, 'Dayagama', 21),
(337, 'Matale', 22),
(338, 'Dambulla', 22),
(339, 'Sigiriya', 22),
(340, 'Galewela', 22),
(341, 'Rattota', 22),
(342, 'Ukuwela', 22),
(343, 'Wilgamuwa', 22),
(344, 'Yatawatta', 22),
(345, 'Ambanganga Korale', 22),
(346, 'Laggala-Pallegama', 22),
(347, 'Naula', 22),
(348, 'Pallepola', 22),
(349, 'Palapathwela', 22),
(350, 'Kilinochchi', 23),
(351, 'Karachchi', 23),
(352, 'Kandavalai', 23),
(353, 'Pachchilaipalli', 23),
(354, 'Poonakary', 23),
(355, 'Pallai', 23),
(356, 'Mannar', 24),
(357, 'Nanattan', 24),
(358, 'Madhu', 24),
(359, 'Musali', 24),
(360, 'Manthai West', 24),
(361, 'Pesalai', 24),
(362, 'Vavuniya', 25),
(363, 'Vavuniya North', 25),
(364, 'Vavuniya South', 25),
(365, 'Vengalacheddikulam', 25),
(366, 'Omanthai', 25);

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `district_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `district_name`) VALUES
(1, 'Colombo'),
(2, 'Gampaha'),
(3, 'Kalutara'),
(4, 'Kandy'),
(5, 'Matara'),
(6, 'Galle'),
(7, 'Hambantota'),
(8, 'Jaffna'),
(9, 'Mullaitivu'),
(10, 'Batticaloa'),
(11, 'Ampara'),
(12, 'Trincomalee'),
(13, 'Kurunegala'),
(14, 'Puttalam'),
(15, 'Anuradhapura'),
(16, 'Polonnaruwa'),
(17, 'Badulla'),
(18, 'Monaragala'),
(19, 'Ratnapura'),
(20, 'Kegalle'),
(21, 'Nuwara Eliya'),
(22, 'Matale'),
(23, 'Kilinochchi'),
(24, 'Mannar'),
(25, 'Vavuniya');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `technician_id`, `rating`, `review`, `tags`, `created_at`) VALUES
(1, 1, 1, 5, 'විශිෂ්ට කාර්යයක්, බෙහෙවින් නිර්දේශ කරමි.\r\nExcellent work, highly recommended.  ', 'Punctual Arrival,Fair & Transparent Pricing,Clean Worksite,High Quality Craft,Polite & Professional,Brought Proper Tools', '2026-10-04 06:12:14');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`) VALUES
(1, 'Plumber'),
(2, 'Electrician'),
(3, 'Carpenter'),
(4, 'Mechanic'),
(5, 'Painter'),
(6, 'Welder'),
(7, 'Mason'),
(8, 'Locksmith'),
(9, 'Appliance Repair'),
(10, 'AC Technician'),
(11, 'Phone Repair'),
(12, 'Computer Technician'),
(13, 'Plumbing & Gas'),
(14, 'Electrical & Solar'),
(15, 'General Repairs');

-- --------------------------------------------------------

--
-- Table structure for table `technicians`
--

CREATE TABLE `technicians` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `district_id` int(11) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `experience` int(11) DEFAULT 0,
  `callout_fee` varchar(50) DEFAULT 'Rs. 2,000',
  `rating` decimal(2,1) DEFAULT 0.0,
  `jobs_completed` int(11) DEFAULT 0,
  `bio` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected','suspended') DEFAULT 'pending',
  `available` tinyint(1) DEFAULT 1,
  `nic_front` varchar(255) DEFAULT NULL,
  `nic_back` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `technicians`
--

INSERT INTO `technicians` (`id`, `email`, `password`, `name`, `phone`, `district_id`, `city`, `service_id`, `experience`, `callout_fee`, `rating`, `jobs_completed`, `bio`, `profile_picture`, `status`, `available`, `nic_front`, `nic_back`, `rejection_reason`, `verified_at`, `created_at`) VALUES
(1, 'themiyavishalsanthush@gmail.com', '$2y$10$EtH4MvO8fD/YbYH7w3.PROof6LSvAwDhMLd0L6Es8cAyOWRm8K2rO', 'Themiya Wishal Santhush', '94758171225', 13, 'Wariyapola', 1, 2, 'Negotiable', 5.0, 2, 'price can be negotiable ', 'uploads/avatars/tech_1_avatar_1791287486.jpg', 'verified', 0, NULL, NULL, NULL, '2026-10-06 05:37:38', '2026-10-04 05:09:02'),
(4, 'debug1@example.com', '$2y$10$6G7KSIz78CxzsfidJmUaruIhZG/KnI5iEM.KZzpDasA0ZKyCgvviO', 'Test Debug', '94771234567', 1, NULL, 2, 5, 'Rs. 2,000', 0.0, 0, NULL, NULL, 'verified', 1, 'uploads/nic/4_nic_front_1791110887.jpeg', 'uploads/nic/4_nic_back_1791110887.jpeg', NULL, '2026-10-05 14:03:23', '2026-10-04 10:48:07'),
(5, 'themiya0718574662@gmail.com', '$2y$10$EXBgn0VzB2xtCMMJoQpptulZYP5yfCA3pWtQHF9BWgZYTq5PgEtnW', 'Nuwan Wijesirin', '9494758171225', 18, NULL, 3, 10, 'Rs. 2,000', 0.0, 0, NULL, NULL, 'verified', 1, 'uploads/nic/5_nic_front_resubmit_1791111557.pdf', 'uploads/nic/5_nic_back_resubmit_1791111557.pdf', 'NIC front is blurry, please re-upload', '2026-10-05 14:05:01', '2026-10-04 10:56:25'),
(6, 'Sarath@gmail.com', '$2y$10$DD2cF8DciJWG50Ru0EisZ.DQIKmyj3oZUu/SNFCCFvEMsTuB/IV82', 'Sarath gamini', '94718574662', 13, 'Wariyapola', 15, 18, 'Rs. 500', 0.0, 0, '', NULL, 'verified', 0, 'uploads/nic/6_nic_front_resubmit_1791175309.png', 'uploads/nic/6_nic_back_resubmit_1791175309.png', NULL, '2026-10-05 04:43:54', '2026-10-04 11:41:47'),
(7, 'hirusha@gmail.com', '$2y$10$vfG5sbfkK1.hXc8N/FRdheQlkr.O5mSuW9oJogoZ16VwNKxrAAZqa', 'Hirusha Dewmina ', '9494716092792', 2, 'Gampaha', 11, 5, 'Rs. 2,000', 0.0, 0, '', 'uploads/avatars/tech_7_avatar_1791278602.jpeg', 'pending', 1, 'uploads/nic/7_nic_front_1791183786.png', 'uploads/nic/7_nic_back_1791183786.png', NULL, NULL, '2026-10-05 07:03:06'),
(8, 'chanupa@gmail.com', '$2y$10$uHypVqBh.L8Q28xOl6qJH.g2KD9yE2GOD9BdXPDehWX1G3xkgNlLW', 'chanupa de silva', '94775656565', 1, 'Kochchikade', 1, 18, 'Free Estimate', 0.0, 0, '', NULL, 'verified', 1, 'uploads/nic/8_nic_front_1791206184.png', 'uploads/nic/8_nic_back_1791206184.png', NULL, '2026-10-06 05:47:16', '2026-10-05 13:16:24'),
(9, 'themiya001@gmail.com', '$2y$10$RkkHh039HgZ5k/VyVmUb6uPzcEe/MKP/A/8BJc1BgshP5NRSIs8R2', 'nrhd bandara', '9494758171225', 11, 'Akkaraipattu', 1, 18, 'Rs. 2,000', 0.0, 0, NULL, NULL, 'verified', 1, 'uploads/nic/9_nic_front_1791342308.jpg', 'uploads/nic/9_nic_back_1791342308.jpg', NULL, '2026-10-07 03:06:54', '2026-10-07 03:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `district_id`, `city`, `profile_picture`, `status`, `created_at`) VALUES
(1, 'induwara@gmail.com', '$2y$10$SsVx5CtC4w6nkDGbggF5/OZj8FW15RcCHVRtuVXI0OiQs9pqY3RUa', 'Induwara Sachintha', '758171225', 13, 'Wariyapola', 'uploads/avatars/user_1_avatar_1791278710.jpg', 'active', '2026-10-04 05:19:47'),
(2, 'Abc@gmail.com', '$2y$10$eY1n2IRKNdgHt2So83vhSelt3H3VhVTDekM9dDZ.qECGv6uR3amUe', 'ABC', NULL, 11, 'Ampara', NULL, 'active', '2026-10-05 12:33:13'),
(3, 'Nisansala@gmail.com', '$2y$10$gmHxCb6wUI4ftkdkP111j.S92KKkxG6TmtKg1b7MGmX5VWxCixb7a', 'Tharanga Ruwani Nisansala ', NULL, 2, 'Kiribathgoda', NULL, 'active', '2026-10-06 06:38:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `district_id` (`district_id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_tech` (`user_id`, `technician_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `technicians`
--
ALTER TABLE `technicians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `district_id` (`district_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=367;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `technicians`
--
ALTER TABLE `technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`);

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`);

--
-- Constraints for table `technicians`
--
ALTER TABLE `technicians`
  ADD CONSTRAINT `technicians_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`),
  ADD CONSTRAINT `technicians_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
