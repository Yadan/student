-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2025 at 02:01 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tapintime`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigned_grade_subjects`
--

CREATE TABLE `assigned_grade_subjects` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `student_type` varchar(10) NOT NULL,
  `grade_level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_grade_subjects`
--

INSERT INTO `assigned_grade_subjects` (`id`, `subject_id`, `student_type`, `grade_level`) VALUES
(1, 5, 'Regular', 7),
(3, 5, 'Regular', 10),
(2, 6, 'Regular', 7),
(4, 6, 'Regular', 10);

-- --------------------------------------------------------

--
-- Table structure for table `assigned_subjects`
--

CREATE TABLE `assigned_subjects` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_type` enum('Regular','STI','Both') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_subjects`
--

INSERT INTO `assigned_subjects` (`id`, `subject_id`, `teacher_id`, `student_type`, `created_at`) VALUES
(3, 6, 1, 'Regular', '2025-04-17 19:10:08');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(6) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `dob` date NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `teacher_id`, `name`, `email`, `contact`, `dob`, `password`) VALUES
(1, '252480', 'Kha', 'jaylinfernandez03@gmail.com', '123456782', '2025-04-12', '$2y$10$SsufId.xA5SNjxL.4IIFnOzDo5ybaY7loQdu/YAhXX2h7IpS1VnSe');

-- --------------------------------------------------------

--
-- Table structure for table `pending_students`
--

CREATE TABLE `pending_students` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `lrn` varchar(12) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `citizenship` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `school_year` varchar(9) NOT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `student_type` varchar(20) NOT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `guardian_contact` varchar(15) NOT NULL,
  `guardian_address` varchar(250) NOT NULL,
  `guardian_relationship` varchar(50) NOT NULL,
  `elementary_school` varchar(100) NOT NULL,
  `year_graduated` year(4) NOT NULL,
  `birth_certificate` varchar(255) DEFAULT NULL,
  `id_photo` varchar(255) DEFAULT NULL,
  `good_moral` varchar(255) DEFAULT NULL,
  `student_signature` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_students`
--

INSERT INTO `pending_students` (`id`, `first_name`, `middle_name`, `last_name`, `lrn`, `date_of_birth`, `gender`, `citizenship`, `address`, `contact_number`, `email`, `section`, `school_year`, `grade_level`, `student_type`, `guardian_name`, `guardian_contact`, `guardian_address`, `guardian_relationship`, `elementary_school`, `year_graduated`, `birth_certificate`, `id_photo`, `good_moral`, `student_signature`, `created_at`) VALUES
(55, 'Marvita', 'Mallillin', 'Yadan', '136741121481', '2003-05-13', 'Female', 'Filipino', 'Purok 2, Narra Street, Casibarag Sur, Cabagan, Isabela', '09943528909', 'marvita.2003@gmail.com', 'Mabini', '2024-2028', 'Grade 7', 'Regular Student', 'Flordeliza Yadan', '09943528908', 'Purok 2, Narra Street, Casibarag Sur, Cabagan, Isabela', 'Mother', 'Cabagan Science Elementary School', '2010', 'uploads/68044c2cc600d_birth_certificate.jpg', 'uploads/68044c2cc7206_id_photo.jpg', 'uploads/68044c2cc7fd1_good_moral.jpg', 'uploads/68044c2cc95ca_student_signature.jpg', '2025-04-19 19:21:48'),
(56, 'Jaylin', 'Dela Cruz', 'Fernandez', '136741121482', '2003-10-24', 'Female', 'Filipino', 'Purok 3, Narra Street, Pilig Alto, Cabagan, Isabela', '09659837951', 'fernandez@gmail.com', 'Mangga', '2024-2028', 'Grade 7', 'Regular Student', 'Genalyn Fernandez', '09659837952', 'Purok 3, Narra Street, Pilig Alto, Cabagan, Isabela', 'Mother', 'Pilig Alto Elementary School', '2010', 'uploads/68044cf8d491d_birth_certificate.png', 'uploads/68044cf8d4bd3_id_photo.jpg', 'uploads/68044cf8d58b2_good_moral.png', 'uploads/68044cf8d5b95_student_signature.png', '2025-04-19 19:25:12'),
(57, 'Joyce', 'Maca', 'Baquiran', '136741121483', '2003-08-03', 'Female', 'Filipino', 'Purok 3, Kalamagi Street, Pilig Alto, Cabagan, Isabela', '09111239807', 'joyce@gmail.com', 'Mangga', '2024-2028', 'Grade 7', 'STI', 'Mathilda Baquiran', '09111239878', 'Purok 3, Kalamagi Street, Pilig Alto, Cabagan, Isabela', 'Mother', 'Pilig Alto Elementary School', '2010', 'uploads/68044dac6141a_birth_certificate.png', 'uploads/68044dac61a5d_id_photo.jpg', 'uploads/68044dac62a2e_good_moral.png', 'uploads/68044dac62d33_student_signature.png', '2025-04-19 19:28:12'),
(58, 'Elmarie', 'Macapia', 'Cataggatan', '136741121484', '2003-04-01', 'Female', 'Filipino', 'Purok 1, San Antonio, Cabagan, Isabela', '09907812556', 'elmariecataggatan@gmail.com', 'Mangga', '2024-2028', 'Grade 7', 'STI', 'Jasmine Cataggatan', '09907812456', 'Purok 1, San Antonio, Cabagan, Isabela', 'Mother', 'San Antonio Elementary School', '2010', 'uploads/68044e86c0cbb_birth_certificate.png', 'uploads/68044e86c0f5d_id_photo.jpg', 'uploads/68044e86c61ab_good_moral.png', 'uploads/68044e86c67c9_student_signature.png', '2025-04-19 19:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` int(11) NOT NULL,
  `year` varchar(20) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `lrn` varchar(12) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `citizenship` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `guardian_contact` varchar(15) NOT NULL,
  `guardian_address` varchar(250) NOT NULL,
  `guardian_relationship` varchar(50) NOT NULL,
  `elementary_school` varchar(100) NOT NULL,
  `year_graduated` year(4) NOT NULL,
  `birth_certificate` varchar(255) NOT NULL,
  `id_photo` varchar(255) NOT NULL,
  `good_moral` varchar(255) DEFAULT NULL,
  `student_signature` varchar(255) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `school_year` varchar(9) NOT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `student_type` varchar(50) NOT NULL,
  `verified_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rfid` varchar(50) DEFAULT NULL,
  `promotion_status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `student_type` enum('Regular','STI','Both') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `student_type`, `created_at`) VALUES
(5, 'Filipino', 'Regular', '2025-04-17 10:28:45'),
(6, 'P.e', 'Regular', '2025-04-17 10:28:45');

-- --------------------------------------------------------

--
-- Table structure for table `subject_teacher`
--

CREATE TABLE `subject_teacher` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','counselor') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$EE3TzR1MXADx7I4VOKXJMuuFkTI9e0Sgfhci5HddC.CHj8MdB/8ca', 'superadmin', '2025-02-20 15:02:38'),
(2, 'jaylin', '$2y$10$dT6ASoT6angT5yRkPEdDc.YhtwXT8C82AxTrpkq5JvoZogwenC/LW', 'counselor', '2025-04-17 03:16:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned_grade_subjects`
--
ALTER TABLE `assigned_grade_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_id` (`subject_id`,`student_type`,`grade_level`);

--
-- Indexes for table `assigned_subjects`
--
ALTER TABLE `assigned_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `pending_students`
--
ALTER TABLE `pending_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lrn` (`lrn`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lrn` (`lrn`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `rfid` (`rfid`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subject_teacher`
--
ALTER TABLE `subject_teacher`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assigned_grade_subjects`
--
ALTER TABLE `assigned_grade_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `assigned_subjects`
--
ALTER TABLE `assigned_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pending_students`
--
ALTER TABLE `pending_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subject_teacher`
--
ALTER TABLE `subject_teacher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_subjects`
--
ALTER TABLE `assigned_subjects`
  ADD CONSTRAINT `assigned_subjects_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `assigned_subjects_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `faculty` (`id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `subject_teacher`
--
ALTER TABLE `subject_teacher`
  ADD CONSTRAINT `subject_teacher_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `subject_teacher_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `faculty` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
