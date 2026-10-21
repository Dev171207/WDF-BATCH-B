-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 21, 2026 at 01:38 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `studenthub`
--

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `course` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'student',
  `remember_token_hash` char(64) DEFAULT NULL,
  `remember_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `email`, `course`, `year`, `mobile`, `gender`, `password_hash`, `role`, `remember_token_hash`, `remember_token_expires`) VALUES
(1, 'Aarav Patel', 'aarav@example.com', 'CSE', 3, NULL, NULL, NULL, 'student', NULL, NULL),
(2, 'Riya Shah', 'riya@example.com', 'IT', 2, NULL, NULL, NULL, 'student', NULL, NULL),
(3, 'Dev Pandya', 'dev@example.com', 'AIML', 3, NULL, NULL, '$2y$10$9dvR/hvYq/4NCaf5seRsXeMV8DBGNd2ZlU0YJQN/tDFcDOoIEtgC6', 'admin', NULL, NULL),
(4, 'Dev Pandya', 'dev@gmail.com', 'IT', 2, '1234567890', 'Male', '$2y$10$wRVWwqi3OSi3BvfY9QoAtOBv/6nKTDsk8iAot5bZ2BYjfuWZRLMIm', 'student', NULL, NULL),
(5, 'Karan Shah', 'practical11.student05@example.com', 'CSE', 1, NULL, NULL, NULL, 'student', NULL, NULL),
(6, 'Meera Patel', 'practical11.student06@example.com', 'IT', 2, NULL, NULL, NULL, 'student', NULL, NULL),
(7, 'Rahul Desai', 'practical11.student07@example.com', 'CE', 3, NULL, NULL, NULL, 'student', NULL, NULL),
(8, 'Priya Joshi', 'practical11.student08@example.com', 'AIML', 4, NULL, NULL, NULL, 'student', NULL, NULL),
(9, 'Arjun Mehta', 'practical11.student09@example.com', 'CSE', 2, NULL, NULL, NULL, 'student', NULL, NULL),
(10, 'Neha Trivedi', 'practical11.student10@example.com', 'IT', 1, NULL, NULL, NULL, 'student', NULL, NULL),
(11, 'Rohan Shah', 'practical11.student11@example.com', 'CE', 4, NULL, NULL, NULL, 'student', NULL, NULL),
(12, 'Ananya Patel', 'practical11.student12@example.com', 'AIML', 3, NULL, NULL, NULL, 'student', NULL, NULL),
(13, 'Dhruv Modi', 'practical11.student13@example.com', 'CSE', 3, NULL, NULL, NULL, 'student', NULL, NULL),
(14, 'Isha Dave', 'practical11.student14@example.com', 'IT', 4, NULL, NULL, NULL, 'student', NULL, NULL),
(15, 'Yash Parmar', 'practical11.student15@example.com', 'CE', 1, NULL, NULL, NULL, 'student', NULL, NULL),
(16, 'Diya Shah', 'practical11.student16@example.com', 'AIML', 2, NULL, NULL, NULL, 'student', NULL, NULL),
(17, 'Harsh Patel', 'practical11.student17@example.com', 'CSE', 4, NULL, NULL, NULL, 'student', NULL, NULL),
(18, 'Kavya Mehta', 'practical11.student18@example.com', 'IT', 3, NULL, NULL, NULL, 'student', NULL, NULL),
(19, 'Om Joshi', 'practical11.student19@example.com', 'CE', 2, NULL, NULL, NULL, 'student', NULL, NULL),
(20, 'Tanvi Desai', 'practical11.student20@example.com', 'AIML', 1, NULL, NULL, NULL, 'student', NULL, NULL),
(21, 'deep', 'deep@gmail.com', 'IT', 3, NULL, NULL, NULL, 'student', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
