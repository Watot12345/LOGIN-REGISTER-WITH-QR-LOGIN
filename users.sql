-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 06, 2025 at 04:46 PM
-- Server version: 5.7.34
-- PHP Version: 8.2.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `user_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `name` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `age` varchar(255) NOT NULL,
  `qr_secret` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`name`, `password`, `age`, `qr_secret`) VALUES
('user', '$2y$10$P7QU2aCOeK2u9K09ZYmRBu7r0KQ3NNqmmuUWAKKaCiTc6Cn1dRqFe', 'user', ''),
('rhyzon', '1234', '18', ''),
('joshua', '123', '18', ''),
('Hahaah', '$2y$10$j0BWj0mhBgBfVueT3FpoieC8gb5zbNS1vQBB7okFuLTA6YmT/.RdC', '18', '6edf5b2f8caa924f793dfa3473f673474f7bb632868bb5ab2b5881a37291ab8d'),
('Nskcjhco', '$2y$10$IcBBaRr6y1b9s3yo2TyqzO5D45Dow4GueXXWWFrqeQDXa.k.U5g1O', '21', '1f220415504c1b249224de09b0262e2534577a4a9e9c68caa791072ee5b3e929'),
('Joshua pogi', '$2y$10$i.NuG0fjakXHIkjy8uN5xOvdF6OM3PE9djfT2tWh7mCc6eJhv88tO', '18', 'e9e89cf3e65ac9933c76219a4e5297cfe5038239f958a23503fd3f9874a283f9'),
('Joshua Arncel podi', '$2y$10$k4B8YT5HgYbaYH26zV71lek4I.YfLO5N5oSZPFhy3IGCqfWVGU/Se', '18', 'cf34996e0778890057f3c1cd8d762985718f244897bb1f9c5ce979807ac55d52'),
('Raffy', '$2y$10$mIxFFZe9592IeIulFner/.3pZ78wufFd8WaxrMPyBbaRMQASuftFK', '18', '0aef1d12582d7316c05627d658f29654119998b8fe7544a8e88cca18dedec0ce');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`name`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
