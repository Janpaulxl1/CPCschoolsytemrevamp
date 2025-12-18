-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 11:05 AM
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
-- Database: `capstone2`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `nurse_id` int(11) DEFAULT NULL,
  `appointment_time` datetime NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_emergency` tinyint(1) NOT NULL DEFAULT 0,
  `is_seen` tinyint(1) DEFAULT 0,
  `reschedule_status` enum('Pending','Accepted','Declined') DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `student_id`, `nurse_id`, `appointment_time`, `reason`, `status`, `created_at`, `updated_at`, `is_emergency`, `is_seen`, `reschedule_status`, `remarks`) VALUES
(1, 20220661, NULL, '2025-12-20 06:30:00', 'Sobra ka tisoy', 'Completed', '2025-12-18 14:10:31', '2025-12-18 14:11:07', 0, 0, NULL, NULL),
(2, 20220661, NULL, '2025-12-18 06:18:00', 'Sobra ka tisoy', 'Completed', '2025-12-18 14:18:09', '2025-12-18 14:19:26', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_logs`
--

CREATE TABLE `appointment_logs` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_medications`
--

CREATE TABLE `appointment_medications` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `action_taken` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_medications`
--

INSERT INTO `appointment_medications` (`id`, `appointment_id`, `medicine_name`, `dosage`, `quantity`, `action_taken`, `created_at`) VALUES
(1, 44, 'Bioflu', '500mg', 1, 'Prescribed medicine', '2025-12-17 10:56:57'),
(2, 2, 'Mefenamic', '500mg', 5, 'lol', '2025-12-18 06:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `clinic_utilization`
--

CREATE TABLE `clinic_utilization` (
  `id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `total_visits` int(11) DEFAULT 0,
  `return_visits` int(11) DEFAULT 0,
  `emergency_cases` int(11) DEFAULT 0,
  `health_concerns` int(11) DEFAULT 0,
  `date_generated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_utilization`
--

INSERT INTO `clinic_utilization` (`id`, `year`, `total_visits`, `return_visits`, `emergency_cases`, `health_concerns`, `date_generated`) VALUES
(2, '2025', 1, 1, 0, 0, '2025-12-18 14:13:05');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_responders`
--

CREATE TABLE `emergency_responders` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('Active','On Duty','Off Duty') NOT NULL DEFAULT 'Off Duty',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_active` timestamp NULL DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emergency_responders`
--

INSERT INTO `emergency_responders` (`id`, `student_id`, `name`, `contact_number`, `status`, `created_at`, `updated_at`, `last_active`, `phone`) VALUES
(1, NULL, 'Jonnel Inoc', NULL, 'Off Duty', '2025-12-06 00:22:47', '2025-12-18 15:14:35', '2025-12-18 06:58:27', '09611528474'),
(2, NULL, 'Jonnel Inoc', NULL, 'Off Duty', '2025-12-18 14:49:10', '2025-12-18 15:14:35', '2025-12-18 06:58:27', '09611528474');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `allDay` tinyint(1) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `reminder_minutes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `expiry_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_history`
--

CREATE TABLE `medical_history` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `medications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dosage` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `expiration_date` date DEFAULT NULL,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medications`
--

INSERT INTO `medications` (`id`, `name`, `description`, `created_at`, `updated_at`, `dosage`, `quantity`, `expiration_date`, `instructions`) VALUES
(8, 'Bioflu', NULL, '2025-12-05 14:54:42', '2025-12-18 14:14:57', '500mg', 17, '2025-12-25', 'Every 6 hours'),
(9, 'Mefenamic', NULL, '2025-12-06 17:53:10', '2025-12-18 14:19:17', '500mg', 15, '2025-12-20', '	Every 6 hours');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `expiration_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_date` date NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `action_taken` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `created_at`, `is_read`, `action_taken`) VALUES
(2, 2022001, 'New appointment #45 booked by student for 2025-12-18 07:00. Reason: Stomachache', '2025-12-17 13:18:20', 0, NULL),
(3, 2022001, 'New appointment #46 booked by student for 2025-12-20 13:26. Reason: Pangag', '2025-12-17 13:26:45', 0, NULL),
(5, 2022001, 'New appointment #1 booked by student for 2025-12-20 06:30. Reason: Sobra ka tisoy', '2025-12-18 06:10:31', 0, NULL),
(7, 2022001, 'New appointment #2 booked by student for 2025-12-18 06:18. Reason: Sobra ka tisoy', '2025-12-18 06:18:09', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `patient_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('M','F','O') NOT NULL,
  `address` varchar(250) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`) VALUES
(1, 'BSIT'),
(2, 'BSHM'),
(3, 'BEED'),
(4, 'BSED');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(2, 'nurse'),
(1, 'student');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `program_id`, `name`, `semester`, `is_archived`, `created_at`) VALUES
(1, 1, 'BSIT-4C', '1st Semester', 0, '2025-12-16 10:03:12'),
(2, 3, 'BEED-1A', '1st Semester', 0, '2025-12-16 10:03:23'),
(3, 4, 'BSED-1A', '1st Semester', 0, '2025-12-16 10:03:32'),
(4, 2, 'BSHM-1A', '1st Semester', 0, '2025-12-16 10:03:39'),
(5, 3, 'BEED-1B', '1st Semester', 0, '2025-12-16 10:58:27');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `birthday` date NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_address` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `course` varchar(50) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `section` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `vaccine_record` varchar(255) DEFAULT NULL,
  `medical_history` varchar(255) DEFAULT NULL,
  `section_id` int(11) NOT NULL,
  `requirements_completed` tinyint(1) DEFAULT 0,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `blood_type` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `is_responder` tinyint(1) DEFAULT 0,
  `responder_status` enum('Active','On Duty','Off Duty') DEFAULT 'Off Duty'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `first_name`, `middle_name`, `last_name`, `birthday`, `gender`, `email`, `phone`, `home_address`, `guardian_name`, `guardian_address`, `guardian_contact`, `relationship`, `course`, `year_level`, `section`, `password`, `profile_picture`, `vaccine_record`, `medical_history`, `section_id`, `requirements_completed`, `emergency_contact`, `created_at`, `blood_type`, `allergies`, `height`, `weight`, `bmi`, `is_responder`, `responder_status`) VALUES
(1, '20220661', 'John Paul', '', 'Yongco', '2004-03-24', 'male', 'johnpaulyongco1@gmail.com', '09272143851', 'Bangbang, Cordova, Cebu', 'Desiree Yongco', 'Bangbang, Cordova, Cebu', NULL, 'Mother', 'BSIT', '4', 'BSIT-4C', '$2y$10$UhUTkZ3T68GLfezuN.0iyeV7QYDJP017vfeU222Yme7DmfZY0reBO', 'uploads/profile_69412f09b25ac_1.jpg', NULL, NULL, 1, 0, '09272143851', '2025-12-16 10:07:39', NULL, NULL, NULL, NULL, NULL, 0, 'Active'),
(2, '20220562', 'Charlene mae', 'R.', 'Sinagpulo', '2003-03-31', 'female', 'charlenesinagpulo@gmail.com', '09950377517', 'alegria, Cordova, Cebu', 'N/A', 'N/A', NULL, 'N/A', 'BSIT', '4', 'BSIT-4C', '$2y$10$JGAFGP41lZkv9l8cORQcbu.GCqXAjnrQZdww6UGQnb2W5GqHCuWDC', 'uploads/profile_6943a0ae8f932_CHARLENE.jpg', NULL, NULL, 1, 0, '09950377517', '2025-12-18 06:38:04', NULL, NULL, NULL, NULL, NULL, 0, 'Off Duty'),
(3, '20220372', 'Jonnel', 'P.', 'Inoc', '2003-06-02', 'male', 'inocjonnel18@gmail.com', '09611528474', 'alegria, Cordova, Cebu', 'Saturnina Inoc', 'alegria, Cordova, Cebu', NULL, 'Grandmother', 'BSIT', '4', 'BSIT-4C', '$2y$10$sR/4ypastre5FCrwl9DHVe/05gHgTOGhgaotjBvh3Lk5GkVGMqyk6', 'uploads/profile_6943a2ee9c2f6_joonnelll.jpg', NULL, NULL, 1, 0, '09950377517', '2025-12-18 06:49:10', NULL, NULL, NULL, NULL, NULL, 1, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `appointment_id` int(11) DEFAULT NULL,
  `reschedule_status` enum('pending','accepted','declined') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_requests`
--

CREATE TABLE `student_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_visits`
--

CREATE TABLE `student_visits` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `visit_date` datetime NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `course` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `action_taken` text DEFAULT NULL,
  `med_id` varchar(20) DEFAULT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_visits`
--

INSERT INTO `student_visits` (`id`, `student_id`, `visit_date`, `location`, `student_name`, `course`, `reason`, `action_taken`, `med_id`, `dosage`, `quantity`, `remarks`, `created_at`, `updated_at`, `status`) VALUES
(0, 0, '2025-12-15 10:13:00', NULL, 'Jonnel Inoc', 'BSIT-4C', 'Toothache', 'Prescribed Mefenamic for pain relief', 'Mefenamic', '500mg', '1', NULL, '2025-12-15 18:14:48', '2025-12-15 18:20:43', 'Completed'),
(1, NULL, '2025-12-20 06:13:00', NULL, 'charlene', 'BSIT 4C', 'AGAY', 'given medicine', 'Bioflu', '500mg', '2', NULL, '2025-12-18 14:14:57', '2025-12-18 14:15:23', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_active` timestamp NULL DEFAULT NULL,
  `last_logout` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `created_at`, `updated_at`, `last_active`, `last_logout`) VALUES
(2022001, 'Nurse Account', 'nurse@example.com', '$2y$10$fV/LpULOtKe5w8wGuuftz.mKpo3a1I9MQzAD1HLOL25ONxOQ57eFS', 2, '2025-09-16 14:53:34', '2025-12-18 18:04:20', '2025-12-18 10:04:20', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nurse_id` (`nurse_id`),
  ADD KEY `appointments_ibfk_1` (`student_id`);

--
-- Indexes for table `appointment_logs`
--
ALTER TABLE `appointment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `appointment_medications`
--
ALTER TABLE `appointment_medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `clinic_utilization`
--
ALTER TABLE `clinic_utilization`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emergency_responders`
--
ALTER TABLE `emergency_responders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_emergency_responders_student_id` (`student_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_events_appointment_id` (`appointment_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medication_id` (`medication_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sections_program` (`program_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_requests`
--
ALTER TABLE `student_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_student_requests_student_id` (`student_id`);

--
-- Indexes for table `student_visits`
--
ALTER TABLE `student_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_student_visits_student` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointment_logs`
--
ALTER TABLE `appointment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment_medications`
--
ALTER TABLE `appointment_medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `clinic_utilization`
--
ALTER TABLE `clinic_utilization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `emergency_responders`
--
ALTER TABLE `emergency_responders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_sections_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
