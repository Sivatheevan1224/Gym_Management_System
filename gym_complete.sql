-- phpMyAdmin SQL Dump
-- Gym Management System Complete Database Setup

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym`
--
CREATE DATABASE IF NOT EXISTS `gym`;
USE `gym`;

-- --------------------------------------------------------

--
-- Table structure for table `gym`
--

CREATE TABLE `gym` (
  `gym_id` varchar(20) NOT NULL,
  `gym_name` varchar(30) NOT NULL,
  `address` varchar(150) NOT NULL,
  `type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
('GYM8', 'TIGERS TOP GYM', 'Madival Circle', 'men');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `pay_id` varchar(20) NOT NULL,
  `amount` varchar(20) DEFAULT NULL,
  `gym_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
('Payment8', '6100', 'GYM8');

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `trainer`
--

INSERT INTO `trainer` (`trainer_id`, `name`, `time`, `mobileno`, `pay_id`) VALUES
('T1', 'George', '5:00 AM', '9999999999', 'Payment1'),
('T2', 'Tanveer', '9:00 AM', '8888888888', 'Payment2'),
('T3', 'Wong Lee', '11:00 AM', '7777777777', 'Payment3'),
('T4', 'Kiran Das', '1:00 PM', '6666666666', 'Payment6'),
('T5', 'Harry Styles', '3:00 PM', '6655665566', 'Payment5'),
('T6', 'James Corden', '5:00 PM', '6677667766', 'Payment6'),
('T7', 'Jimmy Kimmel', '7:00 PM', '6688668866', 'Payment7'),
('T8', 'Ray Berlin', '9:00 PM', '6699669966', 'Payment8');

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`mem_id`, `name`, `dob`, `age`, `mobileno`, `pay_id`, `trainer_id`, `gym_id`) VALUES
('M1', 'Aditya', '18/08/1994', '26', '8888888888', 'Payment1', 'T1', 'GYM1'),
('M2', 'Karan', '26/06/1998', '21', '9988998899', 'Payment2', 'T2', 'GYM2'),
('M3', 'Chirag', '22/07/1997', '22', '9977997799', 'Payment3', 'T3', 'GYM3'),
('M4', 'Abhishek', '21/08/1998', '21', '9966996699', 'Payment4', 'T4', 'GYM4'),
('M5', 'Veeresh', '24/06/1999', '20', '9955995599', 'Payment5', 'T5', 'GYM5');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(10) NOT NULL,
  `uname` varchar(30) NOT NULL,
  `pwd` varchar(255) NOT NULL,
  `role` ENUM('admin', 'member') DEFAULT 'member',
  `member_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping initial admin account
--

INSERT INTO `login` (`id`, `uname`, `pwd`, `role`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create member login accounts
INSERT INTO login (uname, pwd, role, member_id)
SELECT 
    LOWER(REPLACE(name, ' ', '')) as uname,
    -- Using name+age as password (will be hashed when members log in first time)
    PASSWORD(CONCAT(LOWER(REPLACE(name, ' ', '')), age)) as pwd,
    'member' as role,
    mem_id as member_id
FROM member m
WHERE NOT EXISTS (
    SELECT 1 FROM login l WHERE l.member_id = m.mem_id
);

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

ALTER TABLE `gym`
  ADD PRIMARY KEY (`gym_id`);

ALTER TABLE `login`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `member`
  ADD PRIMARY KEY (`mem_id`),
  ADD KEY `pay_id` (`pay_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `gym_id` (`gym_id`);

ALTER TABLE `payment`
  ADD PRIMARY KEY (`pay_id`),
  ADD KEY `gym_id` (`gym_id`);

ALTER TABLE `trainer`
  ADD PRIMARY KEY (`trainer_id`),
  ADD KEY `pay_id` (`pay_id`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `login`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

ALTER TABLE `login`
  ADD CONSTRAINT `login_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`mem_id`);

ALTER TABLE `member`
  ADD CONSTRAINT `member_ibfk_1` FOREIGN KEY (`pay_id`) REFERENCES `payment` (`pay_id`),
  ADD CONSTRAINT `member_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainer` (`trainer_id`),
  ADD CONSTRAINT `member_ibfk_3` FOREIGN KEY (`gym_id`) REFERENCES `gym` (`gym_id`);

ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`gym_id`) REFERENCES `gym` (`gym_id`);

ALTER TABLE `trainer`
  ADD CONSTRAINT `trainer_ibfk_1` FOREIGN KEY (`pay_id`) REFERENCES `payment` (`pay_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;