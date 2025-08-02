-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 02, 2025 at 07:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym`
--

-- --------------------------------------------------------

--
-- Table structure for table `gym`
--

CREATE TABLE `gym` (
  `gym_id` varchar(20) NOT NULL,
  `gym_name` varchar(30) NOT NULL,
  `address` varchar(150) NOT NULL,
  `type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `gym`
--

INSERT INTO `gym` (`gym_id`, `gym_name`, `address`, `type`) VALUES
('GYM1', 'GYM LAND', 'Shiv Nagar', 'men'),
('GYM2', 'TARGET ZONE', 'Shanthi Nagar', 'unisex'),
('GYM3', 'GEORGE GYM', 'Mahesh Nagar', 'unisex'),
('GYM4', 'SUNNY GYM FITNESS STATION', 'Rupali Complex', 'women'),
('GYM5', 'A3 FITNESS GYM', 'Ramnagar Colony', 'men'),
('GYM6', 'SHAPE GYM', 'Zion Colony', 'unisex'),
('GYM7', 'TITAN GYM', 'Old City', 'women'),
('GYM8', 'TIGERS TOP GYM', 'Madival Circle', 'men'),
('GYM9', 'Fit or Fight', 'Jaffna', 'men');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(10) NOT NULL,
  `uname` varchar(30) NOT NULL,
  `pwd` varchar(250) NOT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `member_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `uname`, `pwd`, `role`, `member_id`) VALUES
(1, 'Admin', '$2y$10$f77JiS2cS4ZoMNYpJvpWIe6cp3G5VR9CJ2Rkd2dNbYf8pr9k7AyRi', 'admin', NULL),
(2, 'aditya', '$2y$10$QDqod0dRyEXpNuC/NenzN.e536F3rN/waKqhJfHO6L/cSqyEuWP2y', 'member', 'M1'),
(3, 'karan', '$2y$10$ogcYZ0ZByAXLla.5FHFt4eAd4f8UlOjHqBm7qiGOOWOF8Xc5BzKvi', 'member', 'M2'),
(4, 'chirag', '$2y$10$DMB.DGrJ0gckq0ST0HCNCOIFywDBqbzJ5AcyRyAr5qoCdJ5WqKwSK', 'member', 'M3'),
(5, 'abhishek', '$2y$10$iXrCLbaPnLFtldo6yAoD0OvlT.g1APuCMbdLMxYJIgXXGW8je0KV.', 'member', 'M4'),
(6, 'veeresh', '$2y$10$lSBfQvzslCv6md7pZ9Pafe1QIYwj5/KKdH9oNInY5f86XA8U2AXlG', 'member', 'M5'),
(10, 'siva', '$2y$10$PpwdBzPg8XnNf44w9lkPU.CTQgI85VhYT5XiaTxinsaGZz7436Ipm', 'member', 'M6'),
(11, 'jayakulan', '$2y$10$m29tdQROeNttMx.WuPZEHO7ifBd6kj.WBs2mYSoZUGsRbEhTt35I.', 'member', 'M7');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `mem_id` varchar(20) NOT NULL,
  `name` varchar(30) DEFAULT NULL,
  `dob` varchar(20) DEFAULT NULL,
  `age` varchar(20) DEFAULT NULL,
  `mobileno` varchar(10) DEFAULT NULL,
  `pay_id` varchar(20) DEFAULT NULL,
  `trainer_id` varchar(20) DEFAULT NULL,
  `gym_id` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`mem_id`, `name`, `dob`, `age`, `mobileno`, `pay_id`, `trainer_id`, `gym_id`) VALUES
('M1', 'Aditya', '1998-02-28', '27', '0112233444', 'Payment1', 'T1', 'GYM1'),
('M2', 'Karan', '26/06/1998', '21', '9988998899', 'Payment2', 'T2', 'GYM2'),
('M3', 'Chirag', '22/07/1997', '22', '9977997799', 'Payment3', 'T3', 'GYM3'),
('M4', 'Abhishek', '21/08/1998', '21', '9966996699', 'Payment4', 'T4', 'GYM4'),
('M5', 'Veeresh', '24/06/1999', '20', '9955995599', 'Payment5', 'T5', 'GYM5'),
('M6', 'Siva', '2001-12-24', '23', '0771234567', 'Payment9', 'T4', 'GYM9'),
('M7', 'Jayakulan', '2001-08-03', '23', '0771234567', 'Payment5', 'T1', 'GYM5');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `pay_id` varchar(20) NOT NULL,
  `amount` varchar(20) DEFAULT NULL,
  `gym_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`pay_id`, `amount`, `gym_id`) VALUES
('Payment1', '5200', 'GYM1'),
('Payment2', '4800', 'GYM2'),
('Payment3', '6400', 'GYM3'),
('Payment4', '5400', 'GYM4'),
('Payment5', '6000', 'GYM5'),
('Payment6', '4500', 'GYM6'),
('Payment7', '5500', 'GYM7'),
('Payment8', '6100', 'GYM8'),
('Payment9', '2500', 'GYM8');

-- --------------------------------------------------------

--
-- Table structure for table `trainer`
--

CREATE TABLE `trainer` (
  `trainer_id` varchar(20) NOT NULL,
  `name` varchar(20) DEFAULT NULL,
  `time` varchar(10) DEFAULT NULL,
  `mobileno` varchar(10) DEFAULT NULL,
  `pay_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `trainer`
--

INSERT INTO `trainer` (`trainer_id`, `name`, `time`, `mobileno`, `pay_id`) VALUES
('T1', 'George', '5:00 AM', '1111111111', 'Payment1'),
('T2', 'Tanveer', '9:00 AM', '8888888888', 'Payment2'),
('T3', 'Wong Lee', '11:00 AM', '7777777777', 'Payment3'),
('T4', 'Kiran Das', '1:00 PM', '6666666666', 'Payment6'),
('T5', 'Harry Styles', '3:00 PM', '6655665566', 'Payment5'),
('T6', 'James Corden', '5:00 PM', '6677667766', 'Payment6'),
('T7', 'Jimmy Kimmel', '7:00 PM', '6688668866', 'Payment7'),
('T8', 'Ray Berlin', '9:00 PM', '6699669966', 'Payment8');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gym`
--
ALTER TABLE `gym`
  ADD PRIMARY KEY (`gym_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`mem_id`),
  ADD KEY `pay_id` (`pay_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `member_ibfk_3` (`gym_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`pay_id`),
  ADD KEY `gym_id` (`gym_id`);

--
-- Indexes for table `trainer`
--
ALTER TABLE `trainer`
  ADD PRIMARY KEY (`trainer_id`),
  ADD KEY `pay_id` (`pay_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `login_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`mem_id`),
  ADD CONSTRAINT `login_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `member` (`mem_id`),
  ADD CONSTRAINT `login_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `member` (`mem_id`);

--
-- Constraints for table `member`
--
ALTER TABLE `member`
  ADD CONSTRAINT `member_ibfk_1` FOREIGN KEY (`pay_id`) REFERENCES `payment` (`pay_id`),
  ADD CONSTRAINT `member_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainer_id`),
  ADD CONSTRAINT `member_ibfk_3` FOREIGN KEY (`gym_id`) REFERENCES `gym` (`gym_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`gym_id`) REFERENCES `gym` (`gym_id`);

--
-- Constraints for table `trainer`
--
ALTER TABLE `trainer`
  ADD CONSTRAINT `trainer_ibfk_1` FOREIGN KEY (`pay_id`) REFERENCES `payment` (`pay_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
