-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2026 at 05:54 AM
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
-- Database: `urology_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `beds`
--

CREATE TABLE `beds` (
  `id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chief_complaints`
--

CREATE TABLE `chief_complaints` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `luts` text DEFAULT NULL COMMENT 'Comma-separated LUTS selections',
  `luts_specify` text DEFAULT NULL,
  `luts_duration` varchar(100) DEFAULT NULL,
  `retention_present` tinyint(1) NOT NULL DEFAULT 0,
  `retention_duration` varchar(100) DEFAULT NULL,
  `retention` enum('None','Acute','Chronic') DEFAULT 'None',
  `pain` text DEFAULT NULL,
  `pain_duration` varchar(100) DEFAULT NULL,
  `hematuria` text DEFAULT NULL,
  `hematuria_duration` varchar(100) DEFAULT NULL,
  `fever` enum('No','Yes') DEFAULT 'No',
  `fever_present` tinyint(1) NOT NULL DEFAULT 0,
  `fever_details` text DEFAULT NULL,
  `fever_duration` varchar(100) DEFAULT NULL,
  `others` text DEFAULT NULL,
  `others_duration` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chief_complaints`
--

INSERT INTO `chief_complaints` (`id`, `patient_id`, `luts`, `luts_specify`, `luts_duration`, `retention_present`, `retention_duration`, `retention`, `pain`, `pain_duration`, `hematuria`, `hematuria_duration`, `fever`, `fever_present`, `fever_details`, `fever_duration`, `others`, `others_duration`, `created_by`, `created_at`) VALUES
(2, 1, 'Frequency, Burning micturition', 'Checking', '3', 1, '2', 'Acute', 'Checking', '2', 'Checking', '4', 'Yes', 1, 'Checking', '3', 'Checking', '6', 1, '2026-09-14 06:26:59'),
(3, 2, 'Straining, Urge incontinence, Terminal dribble, Burning micturition', 'Checking', '1 Year', 1, '2 Months', 'Chronic', 'Checking', '2 Months', 'Checking', '2 Months', 'Yes', 1, 'Checking', '2 Months', 'Checking', '2 Months', 1, '2026-09-17 03:43:19');

-- --------------------------------------------------------

--
-- Table structure for table `comorbidity`
--

CREATE TABLE `comorbidity` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `htn` tinyint(1) DEFAULT 0,
  `dm` tinyint(1) DEFAULT 0,
  `ihd` tinyint(1) DEFAULT 0,
  `ckd` tinyint(1) DEFAULT 0,
  `copd` tinyint(1) DEFAULT 0,
  `neurological` tinyint(1) DEFAULT 0,
  `neurological_details` text DEFAULT NULL,
  `others` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comorbidity`
--

INSERT INTO `comorbidity` (`id`, `patient_id`, `htn`, `dm`, `ihd`, `ckd`, `copd`, `neurological`, `neurological_details`, `others`, `created_by`, `created_at`) VALUES
(1, 1, 1, 0, 1, 0, 1, 0, 'Checking', 'Checking', 1, '2026-09-13 09:10:55'),
(2, 1, 1, 0, 0, 1, 0, 1, 'Checking', 'Checking', 1, '2026-09-14 06:59:06');

-- --------------------------------------------------------

--
-- Table structure for table `diagnosis`
--

CREATE TABLE `diagnosis` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `renal_stone` enum('Right','Left','Bilateral','None') DEFAULT 'None',
  `ureteric_stone` varchar(50) DEFAULT NULL,
  `ureteric_location` varchar(50) DEFAULT NULL,
  `bladder_stone` tinyint(1) DEFAULT 0,
  `bph` tinyint(1) DEFAULT 0,
  `carcinoma_prostate` tinyint(1) DEFAULT 0,
  `renal_mass` enum('Right','Left','Bilateral','None') DEFAULT 'None',
  `utuc` enum('Right','Left','Bilateral','None') DEFAULT 'None',
  `bladder_mass` tinyint(1) DEFAULT 0,
  `stricture_urethra` tinyint(1) DEFAULT 0,
  `stricture_details` text DEFAULT NULL,
  `pelvic_fracture` tinyint(1) DEFAULT 0,
  `hypospadias` tinyint(1) DEFAULT 0,
  `hypospadias_details` text DEFAULT NULL,
  `puj_obstruction` enum('Right','Left','Bilateral','None') DEFAULT 'None',
  `testicular_tumor` enum('Right','Left','Bilateral','None') DEFAULT 'None',
  `carcinoma_penis` tinyint(1) DEFAULT 0,
  `varicocele` enum('Right','Left','Bilateral','None') DEFAULT 'None',
  `others` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diagnosis`
--

INSERT INTO `diagnosis` (`id`, `patient_id`, `renal_stone`, `ureteric_stone`, `ureteric_location`, `bladder_stone`, `bph`, `carcinoma_prostate`, `renal_mass`, `utuc`, `bladder_mass`, `stricture_urethra`, `stricture_details`, `pelvic_fracture`, `hypospadias`, `hypospadias_details`, `puj_obstruction`, `testicular_tumor`, `carcinoma_penis`, `varicocele`, `others`, `created_by`, `created_at`) VALUES
(1, 1, 'Right', 'Right', 'Mid', 0, 1, 1, 'Left', '', 1, 1, 'Checking', 1, 0, '0', 'Right', '', 1, '', 'Checking', 1, '2026-09-13 08:56:14'),
(2, 1, 'Left', 'Right', 'Upper', 1, 1, 1, 'Left', 'Left', 1, 1, 'Checking', 1, 1, 'Checking', 'Left', 'Right', 1, 'Left', 'Checking', 1, '2026-09-14 07:43:58');

-- --------------------------------------------------------

--
-- Table structure for table `discharge_summary`
--

CREATE TABLE `discharge_summary` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `admission_date` date DEFAULT NULL,
  `discharge_date` date DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `relevant_investigations` text DEFAULT NULL,
  `operation_date` date DEFAULT NULL,
  `operation_time` time DEFAULT NULL,
  `operation_name` varchar(255) DEFAULT NULL,
  `operation_laterality` varchar(50) DEFAULT NULL,
  `operative_findings` text DEFAULT NULL,
  `procedure_details` text DEFAULT NULL,
  `medication` text DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `followup_date` date DEFAULT NULL,
  `followup_instructions` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discharge_summary`
--

INSERT INTO `discharge_summary` (`id`, `patient_id`, `admission_date`, `discharge_date`, `diagnosis`, `relevant_investigations`, `operation_date`, `operation_time`, `operation_name`, `operation_laterality`, `operative_findings`, `procedure_details`, `medication`, `advice`, `followup_date`, `followup_instructions`, `created_by`, `created_at`) VALUES
(1, 1, '2026-09-13', '2026-09-13', 'Checking', 'Checking', '2026-09-12', '07:30:00', 'Checking', 'Right', 'Checking', 'Checking', 'Checking', 'Checking', '2026-09-29', 'Checking', 1, '2026-09-13 12:29:45'),
(2, 1, '2026-09-13', '2026-09-14', 'Renal Stone (Left), Ureteric Stone (Right), Bladder Stone, BPH, Carcinoma Prostate, Renal Mass (Left), UTUC (Left), Bladder Mass, Stricture Urethra, PUJ Obstruction, Testicular Tumor, Varicocele, Checking', 'Hb: 50 g/dL | Creatinine: 50 mg/dL | PSA: 50 ng/mL | Urine C/S: Growth — 50', '2026-09-14', '14:30:00', 'Cystolitholapaxy', 'Bilateral', 'Checking', 'Checking', 'Checking', 'Checking', NULL, 'Checking', 1, '2026-09-14 08:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `drug_history`
--

CREATE TABLE `drug_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `drug_history` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drug_history`
--

INSERT INTO `drug_history` (`id`, `patient_id`, `drug_history`, `created_by`, `created_at`) VALUES
(1, 1, 'Napa', 1, '2026-09-14 06:59:42');

-- --------------------------------------------------------

--
-- Table structure for table `family_history`
--

CREATE TABLE `family_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `family_history` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `family_history`
--

INSERT INTO `family_history` (`id`, `patient_id`, `family_history`, `created_by`, `created_at`) VALUES
(1, 1, 'Checking\r\nChecking\r\nChecking', 1, '2026-09-14 07:10:46');

-- --------------------------------------------------------

--
-- Table structure for table `followup`
--

CREATE TABLE `followup` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `followup_date` date DEFAULT NULL,
  `visit_type` enum('Pre Operative','Post Operative','Routine','Emergency') DEFAULT 'Routine',
  `chief_complain` text DEFAULT NULL,
  `exam_finding` text DEFAULT NULL,
  `investigation_finding` text DEFAULT NULL,
  `investigation_file` varchar(255) DEFAULT NULL,
  `investigation_file_type` varchar(50) DEFAULT NULL,
  `management_plan` text DEFAULT NULL,
  `scoring` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `followup`
--

INSERT INTO `followup` (`id`, `patient_id`, `followup_date`, `visit_type`, `chief_complain`, `exam_finding`, `investigation_finding`, `investigation_file`, `investigation_file_type`, `management_plan`, `scoring`, `notes`, `file_path`, `created_by`, `created_at`) VALUES
(1, 1, '2026-09-14', 'Post Operative', NULL, NULL, NULL, NULL, NULL, NULL, 'Checking', 'Checking', 'fu_6aa7b165cba20.jpeg', 1, '2026-09-14 08:33:41'),
(2, 1, '2026-09-14', 'Pre Operative', 'Checking', 'Checking', 'Checking', NULL, NULL, 'Checking', 'Checking', 'Checking', NULL, 1, '2026-09-14 09:59:28');

-- --------------------------------------------------------

--
-- Table structure for table `general_exam`
--

CREATE TABLE `general_exam` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `anaemia` enum('Present','Absent') DEFAULT 'Absent',
  `anaemia_details` text DEFAULT NULL,
  `jaundice` enum('Present','Absent') DEFAULT 'Absent',
  `jaundice_details` text DEFAULT NULL,
  `edema` enum('Present','Absent') DEFAULT 'Absent',
  `edema_details` text DEFAULT NULL,
  `pulse` int(11) DEFAULT NULL,
  `bp` varchar(20) DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `lymph_node` text DEFAULT NULL,
  `others` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `general_exam`
--

INSERT INTO `general_exam` (`id`, `patient_id`, `anaemia`, `anaemia_details`, `jaundice`, `jaundice_details`, `edema`, `edema_details`, `pulse`, `bp`, `temperature`, `lymph_node`, `others`, `created_by`, `created_at`) VALUES
(1, 1, 'Present', 'Checking', 'Present', 'Checking', 'Present', 'Checking', 98, '130/60', '98', 'Checking', 'Checking', 1, '2026-09-13 08:55:36'),
(2, 1, 'Present', 'Checking', 'Present', 'Checking', 'Present', 'Checking', 98, '130/60', '98', 'Checking', 'Checking', 1, '2026-09-14 07:12:05');

-- --------------------------------------------------------

--
-- Table structure for table `genitourinary_exam`
--

CREATE TABLE `genitourinary_exam` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `renal_angle` enum('Tender','Non-tender') DEFAULT 'Non-tender',
  `kidney` enum('Palpable','Not palpable') DEFAULT 'Not palpable',
  `kidney_details` text DEFAULT NULL,
  `bladder_fullness` enum('Full','Not full') DEFAULT 'Not full',
  `suprapubic_tenderness` enum('Tender','Non-tender') DEFAULT 'Non-tender',
  `hernial_orifice` enum('Intact','Not intact') DEFAULT 'Intact',
  `meatus` enum('Adequate','Not adequate') DEFAULT 'Adequate',
  `meatus_details` text DEFAULT NULL,
  `testis` enum('Normal','Abnormal') DEFAULT 'Normal',
  `testis_details` text DEFAULT NULL,
  `scrotum` enum('Normal','Abnormal') DEFAULT 'Normal',
  `scrotum_details` text DEFAULT NULL,
  `penis` enum('Normal','Abnormal') DEFAULT 'Normal',
  `penis_details` text DEFAULT NULL,
  `dre_prostate` text DEFAULT NULL,
  `anal_tone` text DEFAULT NULL,
  `bulbocavernosus` text DEFAULT NULL,
  `others` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `genitourinary_exam`
--

INSERT INTO `genitourinary_exam` (`id`, `patient_id`, `renal_angle`, `kidney`, `kidney_details`, `bladder_fullness`, `suprapubic_tenderness`, `hernial_orifice`, `meatus`, `meatus_details`, `testis`, `testis_details`, `scrotum`, `scrotum_details`, `penis`, `penis_details`, `dre_prostate`, `anal_tone`, `bulbocavernosus`, `others`, `created_by`, `created_at`) VALUES
(1, 1, 'Tender', 'Palpable', 'Checking', 'Full', 'Tender', 'Not intact', 'Not adequate', 'Checking', 'Abnormal', 'Checking', 'Abnormal', 'Checking', 'Abnormal', 'Checking', 'Checking', 'Checking', 'Checking', 'Checking', 1, '2026-09-14 07:15:33');

-- --------------------------------------------------------

--
-- Table structure for table `imaging_reports`
--

CREATE TABLE `imaging_reports` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `investigation_name` varchar(150) NOT NULL,
  `investigation_date` date DEFAULT NULL,
  `report_text` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `imaging_reports`
--

INSERT INTO `imaging_reports` (`id`, `patient_id`, `investigation_name`, `investigation_date`, `report_text`, `file_path`, `file_type`, `created_by`, `created_at`) VALUES
(1, 1, 'X-ray KUB', '2026-09-13', '', 'img_6aa6985f1492f.jpeg', 'image/jpeg', 1, '2026-09-13 12:34:39'),
(2, 1, 'USG Whole Abdomen', '2026-09-14', '', 'img_6aa7a6dd6b04e.jpeg', 'image/jpeg', 1, '2026-09-14 07:48:45'),
(3, 2, 'X-ray KUB', '2026-09-15', NULL, 'img_6aa8b5cd69545.jpg', 'image/jpeg', 1, '2026-09-15 03:04:45'),
(4, 2, 'CT Scan Chest', '2026-09-13', NULL, 'img_6aa8b5d568273.jpg', 'image/jpeg', 1, '2026-09-15 03:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `lab_investigations`
--

CREATE TABLE `lab_investigations` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `hemoglobin` varchar(30) DEFAULT NULL,
  `wbc` varchar(30) DEFAULT NULL,
  `platelet` varchar(30) DEFAULT NULL,
  `esr` varchar(30) DEFAULT NULL,
  `creatinine` varchar(30) DEFAULT NULL,
  `electrolytes` text DEFAULT NULL,
  `psa` varchar(30) DEFAULT NULL,
  `urine_rbc` varchar(50) DEFAULT NULL,
  `urine_pus` varchar(50) DEFAULT NULL,
  `rbs` varchar(30) DEFAULT NULL,
  `urine_cs_growth` enum('Growth','No growth','Pending') DEFAULT 'Pending',
  `urine_cs_sensitivity` text DEFAULT NULL,
  `others` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_investigations`
--

INSERT INTO `lab_investigations` (`id`, `patient_id`, `hemoglobin`, `wbc`, `platelet`, `esr`, `creatinine`, `electrolytes`, `psa`, `urine_rbc`, `urine_pus`, `rbs`, `urine_cs_growth`, `urine_cs_sensitivity`, `others`, `created_by`, `created_at`) VALUES
(1, 1, '50', '50', '50', '50', '50', '50', '50', '50', '50', '50', 'Growth', '50', '50', 1, '2026-09-14 07:47:31');

-- --------------------------------------------------------

--
-- Table structure for table `menstrual_history`
--

CREATE TABLE `menstrual_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `menstrual_flow` text DEFAULT NULL,
  `menstrual_cycle` text DEFAULT NULL,
  `lmp` date DEFAULT NULL,
  `lmp_free` text DEFAULT NULL,
  `para` varchar(50) DEFAULT NULL,
  `gravidity` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menstrual_history`
--

INSERT INTO `menstrual_history` (`id`, `patient_id`, `menstrual_flow`, `menstrual_cycle`, `lmp`, `lmp_free`, `para`, `gravidity`, `created_by`, `created_at`) VALUES
(1, 1, 'Checking', 'Checking', '2026-09-14', 'Checking', 'Checking', 'Checking', 1, '2026-09-14 07:11:04');

-- --------------------------------------------------------

--
-- Table structure for table `operations`
--

CREATE TABLE `operations` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `operation_date` date DEFAULT NULL,
  `operation_time` time DEFAULT NULL,
  `laterality` enum('Right','Left','Bilateral','Not applicable') DEFAULT 'Not applicable',
  `approach` enum('Open','Laparoscopic','Endoscopic','Percutaneous','Other') DEFAULT 'Open',
  `operation_name` varchar(255) DEFAULT NULL,
  `operative_findings` text DEFAULT NULL,
  `procedure_details` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `operation_name_specify` varchar(255) DEFAULT NULL,
  `op_duration` time DEFAULT NULL,
  `op_type` enum('Open','Laparoscopic','Endoscopic') DEFAULT NULL,
  `op_subtype` varchar(255) DEFAULT NULL,
  `op_subtype_other` varchar(255) DEFAULT NULL,
  `findings_file` varchar(255) DEFAULT NULL,
  `findings_file_type` varchar(50) DEFAULT NULL,
  `procedure_file` varchar(255) DEFAULT NULL,
  `procedure_file_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `operations`
--

INSERT INTO `operations` (`id`, `patient_id`, `operation_date`, `operation_time`, `laterality`, `approach`, `operation_name`, `operative_findings`, `procedure_details`, `created_by`, `created_at`, `operation_name_specify`, `op_duration`, `op_type`, `op_subtype`, `op_subtype_other`, `findings_file`, `findings_file_type`, `procedure_file`, `procedure_file_type`) VALUES
(1, 1, '2026-09-14', '14:30:00', 'Bilateral', 'Laparoscopic', 'Cystolitholapaxy', 'Checking', 'Checking', 1, '2026-09-14 08:31:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `op_subtypes`
--

CREATE TABLE `op_subtypes` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `op_subtypes`
--

INSERT INTO `op_subtypes` (`id`, `type_id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'PCNL', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(2, 1, 'URS ± UCL', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(3, 1, 'TURP', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(4, 1, 'TURBT', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(5, 1, 'Cystolithotomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(6, 1, 'DJ Stenting', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(7, 1, 'PCN', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(8, 1, 'URS Biopsy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(9, 1, 'TUR Biopsy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(10, 1, 'OIU', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(11, 2, 'Decortication of Renal Cyst', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(12, 2, 'Pyeloplasty', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(13, 2, 'Partial Nephrectomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(14, 2, 'Simple Nephrectomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(15, 2, 'Radical Nephrectomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(16, 2, 'Radical Nephrectomy with Excision of Cuff of Bladder', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(17, 2, 'Radical Prostatectomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(18, 2, 'Ureterolithotomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(19, 2, 'Adrenalectomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(20, 2, 'Radical Cystectomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(21, 2, 'Ureteric Reimplantation', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(22, 3, 'Suprapubic Cystolithotomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(23, 3, 'Anastomotic Urethroplasty', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(24, 3, 'BMG Urethroplasty', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(25, 3, 'Orthoplasty', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(26, 3, 'TIPU', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(27, 3, 'Bilateral Orchiectomy', NULL, 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19');

-- --------------------------------------------------------

--
-- Table structure for table `op_types`
--

CREATE TABLE `op_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `op_types`
--

INSERT INTO `op_types` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Endoscopic', 'Endoscopic procedures', 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(2, 'Laparoscopic', 'Laparoscopic procedures', 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19'),
(3, 'Open', 'Open surgical procedures', 1, '2026-09-23 02:57:19', '2026-09-23 02:57:19');

-- --------------------------------------------------------

--
-- Table structure for table `other_system_exam`
--

CREATE TABLE `other_system_exam` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `other_system` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `other_system_exam`
--

INSERT INTO `other_system_exam` (`id`, `patient_id`, `other_system`, `created_by`, `created_at`) VALUES
(1, 1, 'Checking\r\nChecking\r\nChecking\r\nChecking', 1, '2026-09-14 07:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `uro_id` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `unit` varchar(10) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `sex` enum('Male','Female','Others') DEFAULT NULL,
  `guardian_name` varchar(150) DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `nid` varchar(50) DEFAULT NULL,
  `first_admission_date` date DEFAULT NULL,
  `current_visit_date` date DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `ward` varchar(50) DEFAULT NULL,
  `bed` varchar(50) DEFAULT NULL,
  `hospital_reg_no` varchar(50) DEFAULT NULL,
  `mode_of_admission` enum('Emergency','OPD','Transfer') DEFAULT NULL,
  `discharge_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unit_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `bed_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `uro_id`, `name`, `unit`, `age`, `dob`, `sex`, `guardian_name`, `occupation`, `address`, `mobile`, `nid`, `first_admission_date`, `current_visit_date`, `blood_group`, `ward`, `bed`, `hospital_reg_no`, `mode_of_admission`, `discharge_date`, `created_by`, `created_at`, `updated_at`, `unit_id`, `ward_id`, `bed_id`) VALUES
(1, 'URO-2026-00001', 'Ridwanul Alam', '3', 35, '1991-04-16', 'Male', NULL, NULL, NULL, '01623235709', NULL, '2026-09-13', '2026-09-13', 'O+', '3', '12', 'DMCH-U-123', 'Emergency', '2026-09-14', 1, '2026-09-13 07:40:21', '2026-09-14 08:33:02', NULL, NULL, NULL),
(2, 'URO-2026-00002', 'Md. Shazzad Bappy', '2', 35, '1991-08-04', 'Male', 'Ridwanul Alam', 'Private Job', 'Dhaka', '01989996898', '', '2026-09-14', '2026-09-14', 'B+', '3', '13', 'DMCH-U-124', 'Emergency', NULL, 1, '2026-09-14 09:58:27', '2026-09-14 09:58:27', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personal_history`
--

CREATE TABLE `personal_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `smoking` enum('Current','Former','Never') DEFAULT 'Never',
  `smoking_details` text DEFAULT NULL,
  `alcohol` enum('Yes','No') DEFAULT 'No',
  `alcohol_details` text DEFAULT NULL,
  `betel` enum('Yes','No') DEFAULT 'No',
  `betel_details` text DEFAULT NULL,
  `others` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_history`
--

INSERT INTO `personal_history` (`id`, `patient_id`, `smoking`, `smoking_details`, `alcohol`, `alcohol_details`, `betel`, `betel_details`, `others`, `created_by`, `created_at`) VALUES
(1, 1, 'Current', '3', 'Yes', '4', 'Yes', '45', 'Checking', 1, '2026-09-14 07:00:14');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `code`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Unit 1', 'U1', 'Urology Unit 1', 1, '2026-09-23 02:39:34', '2026-09-23 02:39:34'),
(2, 'Unit 2', 'U2', 'Urology Unit 2', 1, '2026-09-23 02:39:34', '2026-09-23 02:39:34'),
(3, 'Unit 3', 'U3', 'Urology Unit 3', 1, '2026-09-23 02:39:34', '2026-09-23 02:39:34'),
(4, 'Unit 4', 'U4', 'Urology Unit 4', 1, '2026-09-23 02:39:34', '2026-09-23 02:39:34'),
(5, 'Unit 5', 'U5', 'Urology Unit 5', 1, '2026-09-23 02:39:34', '2026-09-23 02:39:34'),
(6, 'Unit 6', 'U6', 'Urology Unit 6', 1, '2026-09-23 02:39:34', '2026-09-23 02:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `urological_history`
--

CREATE TABLE `urological_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `trauma` enum('Yes','No') DEFAULT 'No',
  `trauma_details` text DEFAULT NULL,
  `instrumentation` enum('Yes','No') DEFAULT 'No',
  `instrumentation_details` text DEFAULT NULL,
  `catheterization` enum('No','Yes') DEFAULT 'No',
  `catheterization_details` text DEFAULT NULL,
  `surgery` enum('Yes','No') DEFAULT 'No',
  `surgery_details` text DEFAULT NULL,
  `stone_disease` enum('Yes','No') DEFAULT 'No',
  `stone_details` text DEFAULT NULL,
  `malignancy` enum('Yes','No') DEFAULT 'No',
  `malignancy_details` text DEFAULT NULL,
  `others` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `urological_history`
--

INSERT INTO `urological_history` (`id`, `patient_id`, `trauma`, `trauma_details`, `instrumentation`, `instrumentation_details`, `catheterization`, `catheterization_details`, `surgery`, `surgery_details`, `stone_disease`, `stone_details`, `malignancy`, `malignancy_details`, `others`, `created_by`, `created_at`) VALUES
(1, 1, 'Yes', 'Checking', 'Yes', 'Checking', 'Yes', 'Checking', 'Yes', 'Checking', NULL, 'Checking', 'Yes', 'Checking', 'Checking', 1, '2026-09-14 06:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','doctor') NOT NULL DEFAULT 'doctor',
  `phone` varchar(30) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `specialization`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System Admin', 'admin@urology.com', '$2y$10$Vwk1KtWcOMHi5poPqrzabe.LgJqzRVanyseI5LBg9VUKOT8rysa/2', 'admin', NULL, NULL, 1, '2026-09-13 07:22:58', '2026-09-15 03:11:50'),
(2, 'Bappy', 'bappy@urology.com', '$2y$10$j67XBDkiKEYNqxzE41bJq.65FmnwFcT0MUyzhKeqX6aG7WsJoNwAG', 'doctor', '', '', 1, '2026-09-15 03:08:53', '2026-09-21 09:01:13');

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `beds`
--
ALTER TABLE `beds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ward_id` (`ward_id`);

--
-- Indexes for table `chief_complaints`
--
ALTER TABLE `chief_complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `comorbidity`
--
ALTER TABLE `comorbidity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `diagnosis`
--
ALTER TABLE `diagnosis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `discharge_summary`
--
ALTER TABLE `discharge_summary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `drug_history`
--
ALTER TABLE `drug_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `family_history`
--
ALTER TABLE `family_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `followup`
--
ALTER TABLE `followup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `general_exam`
--
ALTER TABLE `general_exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `genitourinary_exam`
--
ALTER TABLE `genitourinary_exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `imaging_reports`
--
ALTER TABLE `imaging_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `lab_investigations`
--
ALTER TABLE `lab_investigations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `menstrual_history`
--
ALTER TABLE `menstrual_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `operations`
--
ALTER TABLE `operations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `op_subtypes`
--
ALTER TABLE `op_subtypes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `op_types`
--
ALTER TABLE `op_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_op_type_name` (`name`);

--
-- Indexes for table `other_system_exam`
--
ALTER TABLE `other_system_exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uro_id` (`uro_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sex` (`sex`),
  ADD KEY `idx_unit` (`unit`),
  ADD KEY `idx_blood` (`blood_group`),
  ADD KEY `idx_mobile` (`mobile`),
  ADD KEY `idx_unit_id` (`unit_id`),
  ADD KEY `idx_ward_id` (`ward_id`),
  ADD KEY `idx_bed_id` (`bed_id`);

--
-- Indexes for table `personal_history`
--
ALTER TABLE `personal_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_unit_name` (`name`);

--
-- Indexes for table `urological_history`
--
ALTER TABLE `urological_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `beds`
--
ALTER TABLE `beds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chief_complaints`
--
ALTER TABLE `chief_complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comorbidity`
--
ALTER TABLE `comorbidity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `diagnosis`
--
ALTER TABLE `diagnosis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `discharge_summary`
--
ALTER TABLE `discharge_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `drug_history`
--
ALTER TABLE `drug_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `family_history`
--
ALTER TABLE `family_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `followup`
--
ALTER TABLE `followup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `general_exam`
--
ALTER TABLE `general_exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `genitourinary_exam`
--
ALTER TABLE `genitourinary_exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `imaging_reports`
--
ALTER TABLE `imaging_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lab_investigations`
--
ALTER TABLE `lab_investigations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `menstrual_history`
--
ALTER TABLE `menstrual_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `operations`
--
ALTER TABLE `operations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `op_subtypes`
--
ALTER TABLE `op_subtypes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `op_types`
--
ALTER TABLE `op_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `other_system_exam`
--
ALTER TABLE `other_system_exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `personal_history`
--
ALTER TABLE `personal_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `urological_history`
--
ALTER TABLE `urological_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `beds`
--
ALTER TABLE `beds`
  ADD CONSTRAINT `beds_ibfk_1` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chief_complaints`
--
ALTER TABLE `chief_complaints`
  ADD CONSTRAINT `chief_complaints_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comorbidity`
--
ALTER TABLE `comorbidity`
  ADD CONSTRAINT `comorbidity_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `diagnosis`
--
ALTER TABLE `diagnosis`
  ADD CONSTRAINT `diagnosis_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discharge_summary`
--
ALTER TABLE `discharge_summary`
  ADD CONSTRAINT `discharge_summary_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `drug_history`
--
ALTER TABLE `drug_history`
  ADD CONSTRAINT `drug_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `family_history`
--
ALTER TABLE `family_history`
  ADD CONSTRAINT `family_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `followup`
--
ALTER TABLE `followup`
  ADD CONSTRAINT `followup_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `general_exam`
--
ALTER TABLE `general_exam`
  ADD CONSTRAINT `general_exam_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `genitourinary_exam`
--
ALTER TABLE `genitourinary_exam`
  ADD CONSTRAINT `genitourinary_exam_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `imaging_reports`
--
ALTER TABLE `imaging_reports`
  ADD CONSTRAINT `imaging_reports_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_investigations`
--
ALTER TABLE `lab_investigations`
  ADD CONSTRAINT `lab_investigations_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menstrual_history`
--
ALTER TABLE `menstrual_history`
  ADD CONSTRAINT `menstrual_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `operations`
--
ALTER TABLE `operations`
  ADD CONSTRAINT `operations_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `op_subtypes`
--
ALTER TABLE `op_subtypes`
  ADD CONSTRAINT `op_subtypes_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `op_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `other_system_exam`
--
ALTER TABLE `other_system_exam`
  ADD CONSTRAINT `other_system_exam_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `personal_history`
--
ALTER TABLE `personal_history`
  ADD CONSTRAINT `personal_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `urological_history`
--
ALTER TABLE `urological_history`
  ADD CONSTRAINT `urological_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wards`
--
ALTER TABLE `wards`
  ADD CONSTRAINT `wards_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
