-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: clinic_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `account_transaction_branch`
--

DROP TABLE IF EXISTS `account_transaction_branch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_transaction_branch` (
  `branch_id` bigint(20) unsigned NOT NULL,
  `account_transaction_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `atb_unique` (`branch_id`,`account_transaction_id`),
  KEY `account_transaction_branch_account_transaction_id_foreign` (`account_transaction_id`),
  CONSTRAINT `account_transaction_branch_account_transaction_id_foreign` FOREIGN KEY (`account_transaction_id`) REFERENCES `account_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `account_transaction_branch_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_transaction_branch`
--

LOCK TABLES `account_transaction_branch` WRITE;
/*!40000 ALTER TABLE `account_transaction_branch` DISABLE KEYS */;
INSERT INTO `account_transaction_branch` VALUES (1,1,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `account_transaction_branch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_transaction_clinic_material`
--

DROP TABLE IF EXISTS `account_transaction_clinic_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_transaction_clinic_material` (
  `clinic_material_id` bigint(20) unsigned NOT NULL,
  `account_transaction_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `at_cm_unique` (`clinic_material_id`,`account_transaction_id`),
  KEY `at_cm_at_id_f` (`account_transaction_id`),
  CONSTRAINT `at_cm_at_id_f` FOREIGN KEY (`account_transaction_id`) REFERENCES `account_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `at_cm_cm_id_f` FOREIGN KEY (`clinic_material_id`) REFERENCES `clinic_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_transaction_clinic_material`
--

LOCK TABLES `account_transaction_clinic_material` WRITE;
/*!40000 ALTER TABLE `account_transaction_clinic_material` DISABLE KEYS */;
INSERT INTO `account_transaction_clinic_material` VALUES (1,3,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,3,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,3,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(4,3,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(5,3,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `account_transaction_clinic_material` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_transactions`
--

DROP TABLE IF EXISTS `account_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(10) NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `reference_type` varchar(20) NOT NULL,
  `description` varchar(250) NOT NULL,
  `recorded_by_employee_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `account_transactions_recorded_by_employee_id_foreign` (`recorded_by_employee_id`),
  KEY `account_transactions_account_id_foreign` (`account_id`),
  KEY `account_transactions_branch_id_foreign` (`branch_id`),
  CONSTRAINT `account_transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `account_transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `account_transactions_recorded_by_employee_id_foreign` FOREIGN KEY (`recorded_by_employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_transactions`
--

LOCK TABLES `account_transactions` WRITE;
/*!40000 ALTER TABLE `account_transactions` DISABLE KEYS */;
INSERT INTO `account_transactions` VALUES (1,'in',1000,'2024-04-04','patient','collected from patients',1,1,1),(2,'in',1500,'2024-04-04','patient','collected from patients',2,3,2),(3,'out',2500,'2024-04-04','patient','clinic material expenses',1,2,1),(4,'out',2500,'2024-04-04','patient','paid employee salary',1,2,1),(5,'in',400,'2026-05-12','patient','Collected from John Doe',2,1,1);
/*!40000 ALTER TABLE `account_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_name` varchar(30) NOT NULL,
  `account_type` varchar(30) NOT NULL,
  `total_amount` int(10) unsigned NOT NULL,
  `status` varchar(20) NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `accounts_branch_id_foreign` (`branch_id`),
  CONSTRAINT `accounts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'patient payment','income',10400,'active',1),(2,'material expenses','outlay',12000,'active',1),(3,'patient payment','income',10000,'active',2);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `allergies`
--

DROP TABLE IF EXISTS `allergies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allergies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `allergy_type` varchar(30) NOT NULL,
  `severity` varchar(30) NOT NULL,
  `description` varchar(300) NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `allergies_branch_id_foreign` (`branch_id`),
  KEY `allergies_patient_id_foreign` (`patient_id`),
  CONSTRAINT `allergies_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `allergies_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `allergies`
--

LOCK TABLES `allergies` WRITE;
/*!40000 ALTER TABLE `allergies` DISABLE KEYS */;
INSERT INTO `allergies` VALUES (1,'surgical','important','has allergy to numbening agent',1,1);
/*!40000 ALTER TABLE `allergies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointment_employee`
--

DROP TABLE IF EXISTS `appointment_employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointment_employee` (
  `appointment_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `appointment_employee_appointment_id_employee_id_unique` (`appointment_id`,`employee_id`),
  KEY `appointment_employee_employee_id_foreign` (`employee_id`),
  CONSTRAINT `appointment_employee_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_employee_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_employee`
--

LOCK TABLES `appointment_employee` WRITE;
/*!40000 ALTER TABLE `appointment_employee` DISABLE KEYS */;
INSERT INTO `appointment_employee` VALUES (1,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,1,'2026-05-12 14:28:11','2026-05-12 14:28:11');
/*!40000 ALTER TABLE `appointment_employee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointment_patient`
--

DROP TABLE IF EXISTS `appointment_patient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointment_patient` (
  `appointment_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `appointment_patient_appointment_id_patient_id_unique` (`appointment_id`,`patient_id`),
  KEY `ap_pt_patient_id_f` (`patient_id`),
  CONSTRAINT `ap_pt_app_id_f` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ap_pt_patient_id_f` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_patient`
--

LOCK TABLES `appointment_patient` WRITE;
/*!40000 ALTER TABLE `appointment_patient` DISABLE KEYS */;
INSERT INTO `appointment_patient` VALUES (1,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,1,'2026-05-12 14:28:11','2026-05-12 14:28:11');
/*!40000 ALTER TABLE `appointment_patient` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointment_procedure`
--

DROP TABLE IF EXISTS `appointment_procedure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointment_procedure` (
  `appointment_id` bigint(20) unsigned NOT NULL,
  `procedure_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `appointment_procedure_appointment_id_foreign` (`appointment_id`),
  KEY `appointment_procedure_procedure_id_foreign` (`procedure_id`),
  CONSTRAINT `appointment_procedure_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_procedure_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointment_procedure`
--

LOCK TABLES `appointment_procedure` WRITE;
/*!40000 ALTER TABLE `appointment_procedure` DISABLE KEYS */;
INSERT INTO `appointment_procedure` VALUES (2,1,NULL,NULL);
/*!40000 ALTER TABLE `appointment_procedure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_timestamp` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `appointment_cost` int(11) NOT NULL,
  `clinical_notes` varchar(255) DEFAULT NULL,
  `procedure_id` varchar(255) NOT NULL,
  `treatment_plan_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `appointments_treatment_plan_id_foreign` (`treatment_plan_id`),
  KEY `appointments_branch_id_foreign` (`branch_id`),
  CONSTRAINT `appointments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `treatment_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,'2026-03-03 10:30:00','Completed','cavity filling',100,NULL,'1',NULL,1),(2,'2026-05-07 00:00:00','pending','hello',400,NULL,'1',NULL,1);
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_clinic_material`
--

DROP TABLE IF EXISTS `branch_clinic_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_clinic_material` (
  `branch_id` bigint(20) unsigned NOT NULL,
  `clinic_material_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `branch_clinic_material_branch_id_clinic_material_id_unique` (`branch_id`,`clinic_material_id`),
  KEY `branch_clinic_material_clinic_material_id_foreign` (`clinic_material_id`),
  CONSTRAINT `branch_clinic_material_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_clinic_material_clinic_material_id_foreign` FOREIGN KEY (`clinic_material_id`) REFERENCES `clinic_materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_clinic_material`
--

LOCK TABLES `branch_clinic_material` WRITE;
/*!40000 ALTER TABLE `branch_clinic_material` DISABLE KEYS */;
INSERT INTO `branch_clinic_material` VALUES (1,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(1,2,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(1,3,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(1,4,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(1,5,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `branch_clinic_material` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_position`
--

DROP TABLE IF EXISTS `branch_position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_position` (
  `branch_id` bigint(20) unsigned NOT NULL,
  `position_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `branch_position_branch_id_position_id_unique` (`branch_id`,`position_id`),
  KEY `branch_position_position_id_foreign` (`position_id`),
  CONSTRAINT `branch_position_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_position_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_position`
--

LOCK TABLES `branch_position` WRITE;
/*!40000 ALTER TABLE `branch_position` DISABLE KEYS */;
INSERT INTO `branch_position` VALUES (1,1,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `branch_position` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(20) NOT NULL,
  `region` varchar(100) NOT NULL,
  `phone` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'hello','lsdfjka','23223'),(2,'dandan clinic','kabul, Afghanistan','0735244242');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic_assets`
--

DROP TABLE IF EXISTS `clinic_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_name` varchar(30) NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` smallint(5) unsigned NOT NULL,
  `price` int(10) unsigned NOT NULL,
  `total_amount` int(10) unsigned NOT NULL,
  `date_of_purchase` date NOT NULL,
  `status` varchar(60) NOT NULL,
  `purchasedByEmployee_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_assets_purchasedbyemployee_id_foreign` (`purchasedByEmployee_id`),
  KEY `clinic_assets_branch_id_foreign` (`branch_id`),
  CONSTRAINT `clinic_assets_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_assets_purchasedbyemployee_id_foreign` FOREIGN KEY (`purchasedByEmployee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_assets`
--

LOCK TABLES `clinic_assets` WRITE;
/*!40000 ALTER TABLE `clinic_assets` DISABLE KEYS */;
INSERT INTO `clinic_assets` VALUES (2,'x-ray machine','device',1,15000,15000,'2024-04-04','active',1,1,'2026-05-12 12:52:05','2026-05-12 17:48:02'),(3,'desk','Furniture',7,1500,10500,'2024-04-04','active',1,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(4,'ljsdf','device',23,23,23,'2026-05-15','maintenance',1,1,'2026-05-12 17:51:24','2026-05-12 17:53:03');
/*!40000 ALTER TABLE `clinic_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic_expenses`
--

DROP TABLE IF EXISTS `clinic_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_category` varchar(40) NOT NULL,
  `unit` varchar(15) NOT NULL,
  `amount` smallint(5) unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `description` varchar(300) DEFAULT NULL,
  `paidByEmployee_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_expenses_paidbyemployee_id_foreign` (`paidByEmployee_id`),
  KEY `clinic_expenses_branch_id_foreign` (`branch_id`),
  CONSTRAINT `clinic_expenses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_expenses_paidbyemployee_id_foreign` FOREIGN KEY (`paidByEmployee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_expenses`
--

LOCK TABLES `clinic_expenses` WRITE;
/*!40000 ALTER TABLE `clinic_expenses` DISABLE KEYS */;
INSERT INTO `clinic_expenses` VALUES (1,'gas','10kg',600,'2024-04-04','filling the gas tank',1,1);
/*!40000 ALTER TABLE `clinic_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinic_materials`
--

DROP TABLE IF EXISTS `clinic_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_name` varchar(255) DEFAULT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  `total_amount` int(10) unsigned NOT NULL,
  `expense_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinic_materials`
--

LOCK TABLES `clinic_materials` WRITE;
/*!40000 ALTER TABLE `clinic_materials` DISABLE KEYS */;
INSERT INTO `clinic_materials` VALUES (1,'mask',5,400,2000,'2024-04-04','2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,'clinic gloves',5,500,2500,'2024-04-04','2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,'dental syringe',5,150,1500,'2024-04-04','2026-05-12 12:52:05','2026-05-12 12:52:05'),(4,'anesthetic',5,250,5000,'2024-04-04','2026-05-12 12:52:05','2026-05-12 12:52:05'),(5,'drill bit',5,300,2400,'2024-04-04','2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `clinic_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `condition_library`
--

DROP TABLE IF EXISTS `condition_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `condition_library` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` enum('finding','procedure','restoration','prevention') NOT NULL,
  `ui_color` varchar(255) NOT NULL,
  `svg_path` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `condition_library_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `condition_library`
--

LOCK TABLES `condition_library` WRITE;
/*!40000 ALTER TABLE `condition_library` DISABLE KEYS */;
INSERT INTO `condition_library` VALUES (1,'Dental Bridge','dental_bridge','restoration','#F59E0B','M5 50 Q 50 20, 95 50',NULL,NULL),(2,'Extraction Required','extraction','procedure','#EF4444','M20 20 L80 80 M80 20 L20 80',NULL,NULL),(3,'Fracture','fracture','finding','#EF4444','M20 50 L40 30 L60 70 L80 50',NULL,NULL),(4,'Full Crown','full_crown','restoration','#3B82F6','M15 15 H85 V85 H15 Z M15 40 H85 M15 65 H85',NULL,NULL),(5,'Gingival Recession','gingival_recession','finding','#F472B6','M10 50 Q 30 30, 50 50 T 90 50',NULL,NULL),(6,'Impacted Tooth','impacted_tooth','finding','#8B5CF6','M50 50 m-40,0 a40,40 0 1,0 80,0 a40,40 0 1,0 -80,0 M50 10 V30 M40 20 L50 10 L60 20',NULL,NULL),(7,'Implant','implant','restoration','#6366F1','M30 20 H70 M35 35 H65 M40 50 H60 M45 65 H55 M50 20 V80 M45 85 H55',NULL,NULL),(8,'Loose Tooth (Mobility)','loose_tooth','finding','#F59E0B','M20 50 H80 M30 35 L20 50 L30 65 M70 35 L80 50 L70 65',NULL,NULL),(9,'Missing Tooth','missing','finding','#9CA3AF','M20 30 L50 80 L80 30',NULL,NULL),(10,'Orthodontic Brackets','orthodontic_brackets','procedure','#10B981','M30 30 H70 V70 H30 Z M0 50 H100 M50 30 V70',NULL,NULL),(11,'Root Canal (RCT)','root_canal','procedure','#FFD700',NULL,NULL,NULL),(12,'Periapical Abscess','abscess','finding','#FF4500',NULL,NULL,NULL),(13,'Apicoectomy','apicoectomy','procedure','#9370DB',NULL,NULL,NULL),(14,'Post & Core','post_core','restoration','#808080',NULL,NULL,NULL),(15,'Sealant','sealant','procedure','#10B981',NULL,NULL,NULL),(16,'Amalgam Filling','amalgam_filling','restoration','#4B5563',NULL,NULL,NULL),(17,'Caries','caries','finding','#EF4444',NULL,NULL,NULL),(18,'Recurrent Caries','recurrent_caries','finding','#EF4444',NULL,NULL,NULL);
/*!40000 ALTER TABLE `condition_library` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dental_xrays`
--

DROP TABLE IF EXISTS `dental_xrays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dental_xrays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `xray_type` varchar(40) NOT NULL,
  `xray_timestamp` datetime NOT NULL,
  `tooth_part` varchar(50) NOT NULL,
  `side` varchar(40) NOT NULL,
  `image_path` varchar(100) NOT NULL,
  `diagnosis_notes` varchar(150) NOT NULL,
  `payment_status` varchar(50) NOT NULL,
  `results_summery` varchar(150) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `requestedByEmployee_id` bigint(20) unsigned NOT NULL,
  `takenByEmployee_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dental_xrays_diagnosis_notes_unique` (`diagnosis_notes`),
  UNIQUE KEY `dental_xrays_patient_id_unique` (`patient_id`),
  UNIQUE KEY `dental_xrays_requestedbyemployee_id_unique` (`requestedByEmployee_id`),
  UNIQUE KEY `dental_xrays_takenbyemployee_id_unique` (`takenByEmployee_id`),
  KEY `dental_xrays_branch_id_foreign` (`branch_id`),
  CONSTRAINT `dental_xrays_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dental_xrays_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dental_xrays_requestedbyemployee_id_foreign` FOREIGN KEY (`requestedByEmployee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dental_xrays_takenbyemployee_id_foreign` FOREIGN KEY (`takenByEmployee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dental_xrays`
--

LOCK TABLES `dental_xrays` WRITE;
/*!40000 ALTER TABLE `dental_xrays` DISABLE KEYS */;
INSERT INTO `dental_xrays` VALUES (1,'Periaical','2026-03-01 10:30:00','Molar','left','/img/img1.jpg','Damaged tooth','Included','Crown required',1,1,1,1);
/*!40000 ALTER TABLE `dental_xrays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_experiences`
--

DROP TABLE IF EXISTS `employee_experiences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_experiences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workplace` varchar(30) DEFAULT NULL,
  `position` varchar(15) DEFAULT NULL,
  `total_amount` int(10) unsigned DEFAULT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_experiences_employee_id_unique` (`employee_id`),
  CONSTRAINT `employee_experiences_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_experiences`
--

LOCK TABLES `employee_experiences` WRITE;
/*!40000 ALTER TABLE `employee_experiences` DISABLE KEYS */;
INSERT INTO `employee_experiences` VALUES (1,'Sama Dental Clinic','Doctor',13000,1);
/*!40000 ALTER TABLE `employee_experiences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_salaries`
--

DROP TABLE IF EXISTS `employee_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_salaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `salary_month` varchar(255) NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  `bonus` smallint(5) unsigned NOT NULL,
  `total_amount` int(10) unsigned NOT NULL,
  `remark` varchar(255) NOT NULL,
  `paidByAccountTransaction_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_salaries_paidbyaccounttransaction_id_unique` (`paidByAccountTransaction_id`),
  KEY `employee_salaries_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_salaries_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_salaries_paidbyaccounttransaction_id_foreign` FOREIGN KEY (`paidByAccountTransaction_id`) REFERENCES `account_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_salaries`
--

LOCK TABLES `employee_salaries` WRITE;
/*!40000 ALTER TABLE `employee_salaries` DISABLE KEYS */;
INSERT INTO `employee_salaries` VALUES (1,'jun 2025',10000,1500,11500,'paid',4,1,NULL,NULL);
/*!40000 ALTER TABLE `employee_salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_treatment`
--

DROP TABLE IF EXISTS `employee_treatment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_treatment` (
  `employee_id` bigint(20) unsigned NOT NULL,
  `treatment_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `employee_treatment_employee_id_treatment_id_unique` (`employee_id`,`treatment_id`),
  KEY `employee_treatment_treatment_id_foreign` (`treatment_id`),
  CONSTRAINT `employee_treatment_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_treatment_treatment_id_foreign` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_treatment`
--

LOCK TABLES `employee_treatment` WRITE;
/*!40000 ALTER TABLE `employee_treatment` DISABLE KEYS */;
INSERT INTO `employee_treatment` VALUES (1,1,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `employee_treatment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `f_name` varchar(15) NOT NULL,
  `l_name` varchar(15) NOT NULL,
  `gender` varchar(15) NOT NULL,
  `hire_date` date NOT NULL,
  `qualification` varchar(50) NOT NULL,
  `speciality` varchar(25) NOT NULL,
  `medical_license_number` varchar(13) DEFAULT NULL,
  `work_start_time` time DEFAULT NULL,
  `work_end_time` time DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `position_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employees_position_id_foreign` (`position_id`),
  KEY `employees_branch_id_foreign` (`branch_id`),
  KEY `employees_user_id_foreign` (`user_id`),
  CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'Dentist','Professional','male','2020-01-01','doctor surgeon','dental','DENT-001','08:00:00','16:00:00',NULL,1,1,1),(2,'Big','Boss','male','2019-01-01','Administrator','Management','ADM-001','09:00:00','17:00:00',NULL,4,NULL,2),(3,'Receptionist','Staff','female','2021-01-01','Diploma','Administration','REC-001','08:00:00','16:00:00',NULL,3,1,3);
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_stock`
--

DROP TABLE IF EXISTS `inventory_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stockable_type` varchar(255) NOT NULL,
  `stockable_id` bigint(20) unsigned NOT NULL,
  `shelf_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'placed',
  `branch_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_stock_stockable_type_stockable_id_index` (`stockable_type`,`stockable_id`),
  KEY `inventory_stock_shelf_id_foreign` (`shelf_id`),
  KEY `inventory_stock_branch_id_foreign` (`branch_id`),
  CONSTRAINT `inventory_stock_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_stock_shelf_id_foreign` FOREIGN KEY (`shelf_id`) REFERENCES `shelves` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_stock`
--

LOCK TABLES `inventory_stock` WRITE;
/*!40000 ALTER TABLE `inventory_stock` DISABLE KEYS */;
INSERT INTO `inventory_stock` VALUES (1,'App\\Models\\ClinicMaterial',1,1,50,'2027-05-12','SM-2026-001','placed',1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,'App\\Models\\ClinicMaterial',2,1,100,'2027-11-12','EG-2026-001','placed',1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,'App\\Models\\ClinicMaterial',3,2,25,NULL,'DS-2026-001','placed',1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(4,'App\\Models\\ClinicMaterial',4,3,30,'2026-11-12','AS-2026-001','placed',2,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(5,'App\\Models\\ClinicMaterial',5,2,15,NULL,'DB-2026-001','placed',1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(6,'App\\Models\\ClinicMaterial',1,NULL,20,'2027-05-12','SM-2026-002','pending',1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(7,'App\\Models\\ClinicMaterial',2,NULL,40,'2027-11-12','EG-2026-002','pending',1,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `inventory_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `materials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`materials`)),
  `description` text DEFAULT NULL,
  `track_stock` tinyint(1) NOT NULL DEFAULT 0,
  `requires_batch` tinyint(1) NOT NULL DEFAULT 0,
  `requires_expiry` tinyint(1) NOT NULL DEFAULT 0,
  `is_consumable` tinyint(1) NOT NULL DEFAULT 0,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `depth` decimal(10,2) DEFAULT NULL,
  `is_sterile` tinyint(1) NOT NULL DEFAULT 0,
  `expire_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (1,'Dental Crown','prosthetics','[\"Porcelain\"]','Tooth cap',0,0,0,0,1.50,1.00,1.50,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,'Dental Bridge','prosthetics','[\"Ceramic\"]','Replace missing teeth',0,0,0,0,2.00,1.20,1.80,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,'Implant Fixture','prosthetics','[\"Titanium\"]','Implant base',1,1,1,1,0.50,1.50,0.50,1,'2028-05-12','2026-05-12 12:52:05','2026-05-12 12:52:05'),(4,'Denture','prosthetics','[\"Acrylic\"]','Removable teeth',0,0,0,0,3.00,2.00,2.50,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(5,'Veneer','prosthetics','[\"Porcelain\",\"Composite\"]','Cosmetic tooth covering',0,0,0,0,1.00,0.80,1.20,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(6,'Dental Chair','devices','[\"Metal\",\"Leather\"]','Patient chair',0,0,0,0,60.00,120.00,80.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(7,'Compressor','devices','[\"Metal\"]','Air supply',0,0,0,0,40.00,80.00,30.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(8,'Autoclave','devices','[\"Steel\"]','Sterilizer',0,0,0,0,50.00,70.00,40.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(9,'Ultrasonic Cleaner','devices','[\"Metal\"]','Instrument cleaning',0,0,0,0,25.00,20.00,20.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(10,'X-Ray Unit','devices','[\"Metal\",\"Plastic\"]','Digital x-ray machine',0,0,0,0,100.00,150.00,50.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(11,'Dental Cabinet','furniture','[\"Wood\"]','Storage',0,0,0,0,80.00,180.00,40.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(12,'Dentist Stool','furniture','[\"Metal\",\"Foam\"]','Doctor seating',0,0,0,0,50.00,80.00,50.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(13,'Patient Waiting Chair','furniture','[\"Metal\",\"Fabric\"]','Reception seating',0,0,0,0,60.00,90.00,70.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(14,'Reception Desk','furniture','[\"Wood\",\"Glass\"]','Front desk',0,0,0,0,150.00,100.00,60.00,0,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(15,'Instrument Tray','furniture','[\"Steel\"]','Sterile tray for tools',0,0,0,0,30.00,5.00,20.00,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(16,'Mouth Mirror','instruments','[\"Steel\"]','Oral examination',1,0,0,0,2.00,15.00,1.00,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(17,'Explorer Probe','instruments','[\"Steel\"]','Detect decay',1,0,0,0,1.00,18.00,0.50,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(18,'Cotton Pliers','instruments','[\"Steel\"]','Handle materials',1,0,0,0,3.00,12.00,2.00,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(19,'Scaler','instruments','[\"Steel\"]','Remove calculus',1,0,0,0,2.50,16.00,1.50,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(20,'Curette','instruments','[\"Steel\"]','Periodontal cleaning',1,0,0,0,2.00,17.00,1.00,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(21,'Lidocaine Cartridge','medications','[\"Liquid\"]','Local anesthetic',1,1,1,1,2.00,4.00,2.00,1,'2027-05-12','2026-05-12 12:52:05','2026-05-12 12:52:05'),(22,'Ibuprofen Tablets','medications','[\"Tablet\"]','Pain relief',1,1,1,1,5.00,10.00,5.00,0,'2028-05-12','2026-05-12 12:52:05','2026-05-12 12:52:05'),(23,'Amoxicillin Capsules','medications','[\"Capsule\"]','Antibiotic',1,1,1,1,4.00,8.00,4.00,0,'2027-05-12','2026-05-12 12:52:05','2026-05-12 12:52:05'),(24,'Chlorhexidine Mouthwash','medications','[\"Liquid\"]','Antiseptic rinse',1,1,1,1,8.00,20.00,8.00,0,'2027-05-12','2026-05-12 12:52:05','2026-05-12 12:52:05'),(25,'Fluoride Gel','medications','[\"Gel\"]','Tooth strengthening',1,1,1,1,6.00,15.00,6.00,0,'2027-05-12','2026-05-12 12:52:05','2026-05-12 12:52:05'),(26,'Gloves','consumables','[\"Latex\"]','Hand protection',1,0,1,1,10.00,20.00,5.00,1,'2028-05-12','2026-05-12 12:52:05','2026-05-12 12:52:05'),(27,'Face Mask','consumables','[\"Fabric\"]','Air protection',1,0,1,1,15.00,10.00,1.00,0,'2027-05-12','2026-05-12 12:52:05','2026-05-12 12:52:05'),(28,'Cotton Rolls','consumables','[\"Cotton\"]','Moisture control',1,0,0,1,3.00,3.00,3.00,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(29,'Gauze Pads','consumables','[\"Cotton\"]','Wound care',1,0,0,1,10.00,10.00,2.00,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(30,'Suction Tips','consumables','[\"Plastic\"]','Fluid suction',1,0,0,1,1.00,5.00,1.00,1,NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_02_15_212706_create_personal_access_tokens_table',1),(5,'2026_02_16_000000_create_procedures_table',1),(6,'2026_02_16_062005_create_branches_table',1),(7,'2026_02_16_062019_create_positions_table',1),(8,'2026_02_16_062128_create_accounts_table',1),(9,'2026_02_16_062134_create_employees_table',1),(10,'2026_02_16_062202_create_account_transactions_table',1),(11,'2026_02_16_062316_create_patients_table',1),(12,'2026_02_16_062349_create_clinic_materials_table',1),(13,'2026_02_16_062436_create_clinic_assets_table',1),(14,'2026_02_16_062508_create_employee_salaries_table',1),(15,'2026_02_16_062537_create_employee_experiences_table',1),(16,'2026_02_16_062550_create_prescriptions_table',1),(17,'2026_02_16_062822_create_receptions_table',1),(18,'2026_02_16_063723_create_clinic_expenses_table',1),(19,'2026_02_16_063726_create_treatment_plans_table',1),(20,'2026_02_16_064511_create_allergies_table',1),(21,'2026_02_16_064543_create_dental_xrays_table',1),(22,'2026_02_16_064725_create_appointments_table',1),(23,'2026_02_16_064804_create_treatments_table',1),(24,'2026_02_16_064922_create_patient_files_table',1),(25,'2026_02_16_190422_create_branch_position_table',1),(26,'2026_02_17_055553_create_account_transaction_clinic_material_table',1),(27,'2026_02_17_070703_create_appointment_patient',1),(28,'2026_02_17_234808_create_employee_treatment_table',1),(29,'2026_02_18_051400_create_account_transaction_branch_table',1),(30,'2026_02_18_064638_create_appointment_employee_table',1),(31,'2026_02_18_065124_create_branch_clinic_material_table',1),(32,'2026_04_13_000001_create_items_table',1),(33,'2026_04_13_000002_create_suppliers_table',1),(34,'2026_04_13_000003_create_orders_table',1),(35,'2026_04_13_000004_create_order_items_table',1),(36,'2026_04_13_100001_create_shelves_table',1),(37,'2026_04_13_200002_update_inventory_stock_for_polymorphic',1),(38,'2026_04_13_200003_update_product_prices_for_polymorphic',1),(39,'2026_04_13_200004_add_timestamps_to_clinic_materials_table',1),(40,'2026_04_13_200005_add_timestamps_to_clinic_assets_table',1),(41,'2026_04_14_000002_create_supplier_item_table',1),(42,'2026_05_03_084315_create_settings_table',1),(43,'2026_05_03_091240_create_prescription_items_table',1),(44,'2026_05_04_065846_create_procedure_inventory_table',1),(45,'2026_05_06_061129_create_appointment_procedure_table',1),(46,'2026_05_06_061151_create_treatment_plan_procedure_table',1),(47,'2026_05_12_060000_align_settings_table_with_application_contract',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_item_id_foreign` (`item_id`),
  CONSTRAINT `order_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,1,42,400.00,16800.00,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,1,2,15,500.00,7500.00,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,1,3,18,150.00,2700.00,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(4,2,1,22,400.00,8800.00,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(5,2,2,15,500.00,7500.00,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(6,2,5,27,300.00,8100.00,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` varchar(250) DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_branch_id_foreign` (`branch_id`),
  KEY `orders_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'Medical Supplies Co.','2026-05-02','received','Initial supply order',1,2,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,'Dental Equipment Ltd.','2026-05-07','pending','Restocking order',1,1,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_files`
--

DROP TABLE IF EXISTS `patient_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `diagnosis` varchar(200) NOT NULL,
  `notes` text DEFAULT NULL,
  `odontogram_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`odontogram_data`)),
  `patient_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `appointmentDate_id` bigint(20) unsigned DEFAULT NULL,
  `allergy_id` bigint(20) unsigned DEFAULT NULL,
  `treatment_id` bigint(20) unsigned DEFAULT NULL,
  `diagnosis_notes` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_files_patient_id_unique` (`patient_id`),
  UNIQUE KEY `patient_files_appointmentdate_id_unique` (`appointmentDate_id`),
  UNIQUE KEY `patient_files_treatment_id_unique` (`treatment_id`),
  KEY `patient_files_employee_id_foreign` (`employee_id`),
  KEY `patient_files_allergy_id_foreign` (`allergy_id`),
  KEY `patient_files_diagnosis_notes_foreign` (`diagnosis_notes`),
  CONSTRAINT `patient_files_allergy_id_foreign` FOREIGN KEY (`allergy_id`) REFERENCES `allergies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_files_appointmentdate_id_foreign` FOREIGN KEY (`appointmentDate_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_files_diagnosis_notes_foreign` FOREIGN KEY (`diagnosis_notes`) REFERENCES `dental_xrays` (`diagnosis_notes`),
  CONSTRAINT `patient_files_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_files_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_files_treatment_id_foreign` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_files`
--

LOCK TABLES `patient_files` WRITE;
/*!40000 ALTER TABLE `patient_files` DISABLE KEYS */;
INSERT INTO `patient_files` VALUES (1,'this is good',NULL,NULL,2,2,1,1,1,'Damaged tooth');
/*!40000 ALTER TABLE `patient_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_tooth_status`
--

DROP TABLE IF EXISTS `patient_tooth_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_tooth_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `tooth_id` bigint(20) unsigned NOT NULL,
  `status` enum('present','erupting','shed','extracted','missing') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_tooth_status_patient_id_tooth_id_unique` (`patient_id`,`tooth_id`),
  KEY `patient_tooth_status_tooth_id_foreign` (`tooth_id`),
  CONSTRAINT `patient_tooth_status_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_tooth_status_tooth_id_foreign` FOREIGN KEY (`tooth_id`) REFERENCES `teeth_reference` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_tooth_status`
--

LOCK TABLES `patient_tooth_status` WRITE;
/*!40000 ALTER TABLE `patient_tooth_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_tooth_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `f_name` varchar(15) NOT NULL,
  `l_name` varchar(15) NOT NULL,
  `gender` varchar(15) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `emergency_contact` varchar(10) DEFAULT NULL,
  `registeration_date` date NOT NULL,
  `total_amount_due` int(10) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `patients_branch_id_foreign` (`branch_id`),
  CONSTRAINT `patients_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,'John','Doe','Male','1234567890','O-','000123456','2026-05-12',2222,1),(2,'Jane','Doe','Female','9876543210','O+','000123456','2026-05-12',NULL,2),(3,'name','dsklj','male','3443','A+','4343','2026-05-12',2,1);
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',2,'auth_token','17c4d24461249be57cb7cbf6bed5e44d11a543598d1ade5f4cc60bb043bae1e3','[\"*\"]','2026-05-13 13:05:05',NULL,'2026-05-12 12:52:55','2026-05-13 13:05:05'),(2,'App\\Models\\User',2,'auth_token','cb27ee8ea5270f8e7671f008fb421d6a07d5af8ca591ed779da404b6738ede38','[\"*\"]',NULL,NULL,'2026-05-12 12:52:55','2026-05-12 12:52:55'),(3,'App\\Models\\User',2,'auth_token','3ba9ace178fc9e87e56e81a94f50698840551fccf97d3ab2089eee6cf2809b76','[\"*\"]','2026-05-13 13:04:49',NULL,'2026-05-12 14:24:14','2026-05-13 13:04:49');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `position_title` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,'doctor'),(2,'assistant'),(3,'receptionist'),(4,'admin');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescription_items`
--

DROP TABLE IF EXISTS `prescription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescription_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `describer` varchar(255) NOT NULL,
  `font_size` double NOT NULL,
  `pos_x` int(11) NOT NULL,
  `pos_y` int(11) NOT NULL,
  `direction` varchar(5) NOT NULL DEFAULT 'ltr',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescription_items`
--

LOCK TABLES `prescription_items` WRITE;
/*!40000 ALTER TABLE `prescription_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `prescription_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prescription_date` date NOT NULL,
  `instructions` varchar(100) NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prescriptions_patient_id_unique` (`patient_id`),
  KEY `prescriptions_employee_id_foreign` (`employee_id`),
  KEY `prescriptions_branch_id_foreign` (`branch_id`),
  CONSTRAINT `prescriptions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
INSERT INTO `prescriptions` VALUES (1,'2024-04-04','400mg',1,1,1);
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procedure_inventory`
--

DROP TABLE IF EXISTS `procedure_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procedure_inventory` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procedure_id` bigint(20) unsigned NOT NULL,
  `inventory_stock_id` bigint(20) unsigned NOT NULL,
  `unit_count` int(11) NOT NULL DEFAULT 1,
  `is_optional` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `procedure_inventory_procedure_id_foreign` (`procedure_id`),
  KEY `procedure_inventory_inventory_stock_id_foreign` (`inventory_stock_id`),
  CONSTRAINT `procedure_inventory_inventory_stock_id_foreign` FOREIGN KEY (`inventory_stock_id`) REFERENCES `inventory_stock` (`id`),
  CONSTRAINT `procedure_inventory_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procedure_inventory`
--

LOCK TABLES `procedure_inventory` WRITE;
/*!40000 ALTER TABLE `procedure_inventory` DISABLE KEYS */;
INSERT INTO `procedure_inventory` VALUES (1,1,1,1,0,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,1,3,2,0,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `procedure_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procedures`
--

DROP TABLE IF EXISTS `procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procedures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `base_price` decimal(15,2) NOT NULL,
  `min_price` decimal(15,2) NOT NULL,
  `appointments_needed` int(10) unsigned NOT NULL,
  `dentist_commission` decimal(5,2) NOT NULL DEFAULT 0.00,
  `assistant_commission` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `procedures_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procedures`
--

LOCK TABLES `procedures` WRITE;
/*!40000 ALTER TABLE `procedures` DISABLE KEYS */;
INSERT INTO `procedures` VALUES (1,'Composite Filling (1 Surface)','composite-filling-1','Restorative',1500.00,100.00,1,20.00,50.00,1,'2026-05-12 12:52:04','2026-05-12 12:52:04'),(2,'Simple Extraction','simple-extraction','Surgery',1000.00,100.00,1,15.00,30.00,1,'2026-05-12 12:52:04','2026-05-12 13:00:00'),(3,'Scaling & Polishing','scaling-polishing','Preventive',800.00,100.00,1,25.00,20.00,1,'2026-05-12 12:52:04','2026-05-12 12:52:04'),(4,'sddsd','sddsd','fsdsds',2323.00,0.00,1,323.00,34434.00,1,'2026-05-12 18:27:13','2026-05-12 18:27:13'),(5,'klfsd','klfsd','ljskdfa',3443.00,323.00,4,33.00,3323.00,1,'2026-05-13 11:13:26','2026-05-13 11:13:26'),(6,'mama','mama','nono',434.00,0.00,10,0.00,0.00,1,'2026-05-13 11:18:22','2026-05-13 11:19:11');
/*!40000 ALTER TABLE `procedures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_prices`
--

DROP TABLE IF EXISTS `product_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pricable_type` varchar(255) NOT NULL,
  `pricable_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `effective_from` datetime NOT NULL,
  `discount_percentage` float DEFAULT NULL,
  `currency_exchange_rate` decimal(10,4) DEFAULT NULL,
  `final_price` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_prices_pricable_type_pricable_id_index` (`pricable_type`,`pricable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_prices`
--

LOCK TABLES `product_prices` WRITE;
/*!40000 ALTER TABLE `product_prices` DISABLE KEYS */;
INSERT INTO `product_prices` VALUES (1,'App\\Models\\ClinicMaterial',1,349.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,'App\\Models\\ClinicMaterial',2,363.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,'App\\Models\\ClinicMaterial',3,259.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(4,'App\\Models\\ClinicMaterial',4,348.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(5,'App\\Models\\ClinicMaterial',5,471.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(6,'App\\Models\\ClinicAsset',1,5000.00,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(7,'App\\Models\\ClinicAsset',2,15000.00,'2026-05-12 05:52:05',NULL,7.0000,NULL,1,'2026-05-12 12:52:05','2026-05-12 17:48:02'),(8,'App\\Models\\ClinicAsset',3,1500.00,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(9,'App\\Models\\Item',1,377.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(10,'App\\Models\\Item',2,439.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(11,'App\\Models\\Item',3,220.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(12,'App\\Models\\Item',4,368.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(13,'App\\Models\\Item',5,485.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(14,'App\\Models\\Item',6,4395.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(15,'App\\Models\\Item',7,10053.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(16,'App\\Models\\Item',8,1954.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(17,'App\\Models\\Item',9,7865.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(18,'App\\Models\\Item',10,7815.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(19,'App\\Models\\Item',11,1250.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(20,'App\\Models\\Item',12,2829.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(21,'App\\Models\\Item',13,2239.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(22,'App\\Models\\Item',14,542.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(23,'App\\Models\\Item',15,696.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(24,'App\\Models\\Item',16,110.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(25,'App\\Models\\Item',17,57.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(26,'App\\Models\\Item',18,119.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(27,'App\\Models\\Item',19,23.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(28,'App\\Models\\Item',20,56.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(29,'App\\Models\\Item',21,35.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(30,'App\\Models\\Item',22,46.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(31,'App\\Models\\Item',23,46.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(32,'App\\Models\\Item',24,32.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(33,'App\\Models\\Item',25,38.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(34,'App\\Models\\Item',26,26.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(35,'App\\Models\\Item',27,15.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(36,'App\\Models\\Item',28,22.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(37,'App\\Models\\Item',29,16.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(38,'App\\Models\\Item',30,12.99,'2026-05-12 05:52:05',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(39,'App\\Models\\ClinicAsset',4,23.00,'2026-05-12 10:51:24',NULL,67.0000,NULL,1,'2026-05-12 17:51:24','2026-05-12 17:53:03');
/*!40000 ALTER TABLE `product_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receptions`
--

DROP TABLE IF EXISTS `receptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(30) NOT NULL,
  `fee` int(10) unsigned NOT NULL,
  `admission_timestamp` date NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receptions_employee_id_unique` (`employee_id`),
  KEY `receptions_patient_id_foreign` (`patient_id`),
  CONSTRAINT `receptions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receptions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receptions`
--

LOCK TABLES `receptions` WRITE;
/*!40000 ALTER TABLE `receptions` DISABLE KEYS */;
INSERT INTO `receptions` VALUES (1,'scheduled',150,'2024-04-04',1,1),(2,'scheduled',150,'2024-04-04',2,2);
/*!40000 ALTER TABLE `receptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `clinic_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'USD',
  `working_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`working_hours`)),
  `wa_patient_reminder` text DEFAULT NULL,
  `wa_patient_cancel` text DEFAULT NULL,
  `wa_patient_complete` text DEFAULT NULL,
  `wa_supplier_order` text DEFAULT NULL,
  `wa_supplier_cancel` text DEFAULT NULL,
  `rec_can_edit_whatsapp` tinyint(1) NOT NULL DEFAULT 0,
  `rec_can_view_phones` tinyint(1) NOT NULL DEFAULT 1,
  `rec_show_kpi` tinyint(1) NOT NULL DEFAULT 0,
  `rec_show_suppliers` tinyint(1) NOT NULL DEFAULT 0,
  `rec_log_actions` tinyint(1) NOT NULL DEFAULT 0,
  `rec_can_void_transactions` tinyint(1) NOT NULL DEFAULT 0,
  `rec_can_edit_devices` tinyint(1) NOT NULL DEFAULT 0,
  `rec_can_contact_support` tinyint(1) NOT NULL DEFAULT 1,
  `doc_view_appointments` tinyint(1) NOT NULL DEFAULT 1,
  `doc_save_xrays` tinyint(1) NOT NULL DEFAULT 0,
  `doc_view_files` tinyint(1) NOT NULL DEFAULT 1,
  `doc_view_contact` tinyint(1) NOT NULL DEFAULT 1,
  `doc_edit_assets` tinyint(1) NOT NULL DEFAULT 0,
  `doc_issue_prescriptions` tinyint(1) NOT NULL DEFAULT 1,
  `clinic_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`clinic_items`)),
  `clinic_procedures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`clinic_procedures`)),
  `prescription_template` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prescription_template`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `settings_branch_id_foreign` (`branch_id`),
  CONSTRAINT `settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,1,'hello','lsdfjka','23223','USD','{\"monday\":{\"start\":\"09:00\",\"end\":\"17:00\",\"is_off\":false},\"tuesday\":{\"start\":\"09:00\",\"end\":\"17:00\",\"is_off\":false},\"wednesday\":{\"start\":\"09:00\",\"end\":\"17:00\",\"is_off\":false},\"thursday\":{\"start\":\"09:00\",\"end\":\"17:00\",\"is_off\":false},\"friday\":{\"start\":\"09:00\",\"end\":\"17:00\",\"is_off\":false},\"saturday\":{\"start\":null,\"end\":null,\"is_off\":true},\"sunday\":{\"start\":null,\"end\":null,\"is_off\":true}}',NULL,NULL,NULL,NULL,NULL,0,1,0,0,1,0,0,1,1,0,1,1,0,1,NULL,NULL,'{\"header\":\"{{clinic_name}}\\n{{address}}\\n{{phone}}\",\"footer\":\"Thank you for choosing {{clinic_name}}.\"}','2026-05-12 12:52:59','2026-05-12 17:09:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shelves`
--

DROP TABLE IF EXISTS `shelves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shelves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shelf_name` varchar(100) DEFAULT NULL,
  `shelf_type` varchar(20) NOT NULL,
  `access_pin` varchar(4) DEFAULT NULL,
  `total_capacity_cm3` decimal(15,2) DEFAULT NULL,
  `category_restriction` varchar(100) DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shelves_branch_id_foreign` (`branch_id`),
  CONSTRAINT `shelves_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shelves`
--

LOCK TABLES `shelves` WRITE;
/*!40000 ALTER TABLE `shelves` DISABLE KEYS */;
INSERT INTO `shelves` VALUES (1,'Glass Cabinet A1','glass','1234',50000.00,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,'Metal Cabinet B1','metal','5678',75000.00,'Surgical',1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,'Refrigerator R1','refrigerator','9012',30000.00,'Consumable',1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(4,'Wood Cabinet C1','wood','3456',60000.00,NULL,2,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `shelves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_item`
--

DROP TABLE IF EXISTS `supplier_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_item` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_item_supplier_id_item_id_unique` (`supplier_id`,`item_id`),
  KEY `supplier_item_item_id_foreign` (`item_id`),
  CONSTRAINT `supplier_item_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_item_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_item`
--

LOCK TABLES `supplier_item` WRITE;
/*!40000 ALTER TABLE `supplier_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contact_person_name` varchar(255) NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `address` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `business_id` varchar(255) DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suppliers_branch_id_foreign` (`branch_id`),
  CONSTRAINT `suppliers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'John Smith','Medical Supplies Co.','+1234567890','john@medicalsupplies.com','active',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(2,'Jane Doe','Dental Equipment Ltd.','+0987654321','jane@dentalequipment.com','active',NULL,NULL,NULL,2,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,'Robert Johnson','Pharma Distributors Inc.','+1122334455','robert@pharmadist.com','inactive',NULL,NULL,NULL,1,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teeth_reference`
--

DROP TABLE IF EXISTS `teeth_reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teeth_reference` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fdi_code` int(11) NOT NULL,
  `universal_code` varchar(2) NOT NULL,
  `type` enum('permanent','primary') NOT NULL,
  `quadrant` int(11) NOT NULL,
  `default_position` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teeth_reference_fdi_code_unique` (`fdi_code`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teeth_reference`
--

LOCK TABLES `teeth_reference` WRITE;
/*!40000 ALTER TABLE `teeth_reference` DISABLE KEYS */;
INSERT INTO `teeth_reference` VALUES (1,11,'','permanent',1,11,'2026-05-12 12:52:05',NULL),(2,12,'','permanent',1,12,'2026-05-12 12:52:05',NULL),(3,13,'','permanent',1,13,'2026-05-12 12:52:05',NULL),(4,14,'','permanent',1,14,'2026-05-12 12:52:05',NULL),(5,15,'','permanent',1,15,'2026-05-12 12:52:05',NULL),(6,16,'','permanent',1,16,'2026-05-12 12:52:05',NULL),(7,17,'','permanent',1,17,'2026-05-12 12:52:05',NULL),(8,18,'','permanent',1,18,'2026-05-12 12:52:05',NULL),(9,21,'','permanent',2,21,'2026-05-12 12:52:05',NULL),(10,22,'','permanent',2,22,'2026-05-12 12:52:05',NULL),(11,23,'','permanent',2,23,'2026-05-12 12:52:05',NULL),(12,24,'','permanent',2,24,'2026-05-12 12:52:05',NULL),(13,25,'','permanent',2,25,'2026-05-12 12:52:05',NULL),(14,26,'','permanent',2,26,'2026-05-12 12:52:05',NULL),(15,27,'','permanent',2,27,'2026-05-12 12:52:05',NULL),(16,28,'','permanent',2,28,'2026-05-12 12:52:05',NULL),(17,31,'','permanent',3,31,'2026-05-12 12:52:05',NULL),(18,32,'','permanent',3,32,'2026-05-12 12:52:05',NULL),(19,33,'','permanent',3,33,'2026-05-12 12:52:05',NULL),(20,34,'','permanent',3,34,'2026-05-12 12:52:05',NULL),(21,35,'','permanent',3,35,'2026-05-12 12:52:05',NULL),(22,36,'','permanent',3,36,'2026-05-12 12:52:05',NULL),(23,37,'','permanent',3,37,'2026-05-12 12:52:05',NULL),(24,38,'','permanent',3,38,'2026-05-12 12:52:05',NULL),(25,41,'','permanent',4,41,'2026-05-12 12:52:05',NULL),(26,42,'','permanent',4,42,'2026-05-12 12:52:05',NULL),(27,43,'','permanent',4,43,'2026-05-12 12:52:05',NULL),(28,44,'','permanent',4,44,'2026-05-12 12:52:05',NULL),(29,45,'','permanent',4,45,'2026-05-12 12:52:05',NULL),(30,46,'','permanent',4,46,'2026-05-12 12:52:05',NULL),(31,47,'','permanent',4,47,'2026-05-12 12:52:05',NULL),(32,48,'','permanent',4,48,'2026-05-12 12:52:05',NULL),(33,51,'','primary',5,51,'2026-05-12 12:52:05',NULL),(34,52,'','primary',5,52,'2026-05-12 12:52:05',NULL),(35,53,'','primary',5,53,'2026-05-12 12:52:05',NULL),(36,54,'','primary',5,54,'2026-05-12 12:52:05',NULL),(37,55,'','primary',5,55,'2026-05-12 12:52:05',NULL),(38,61,'','primary',6,61,'2026-05-12 12:52:05',NULL),(39,62,'','primary',6,62,'2026-05-12 12:52:05',NULL),(40,63,'','primary',6,63,'2026-05-12 12:52:05',NULL),(41,64,'','primary',6,64,'2026-05-12 12:52:05',NULL),(42,65,'','primary',6,65,'2026-05-12 12:52:05',NULL),(43,71,'','primary',7,71,'2026-05-12 12:52:05',NULL),(44,72,'','primary',7,72,'2026-05-12 12:52:05',NULL),(45,73,'','primary',7,73,'2026-05-12 12:52:05',NULL),(46,74,'','primary',7,74,'2026-05-12 12:52:05',NULL),(47,75,'','primary',7,75,'2026-05-12 12:52:05',NULL),(48,81,'','primary',8,81,'2026-05-12 12:52:05',NULL),(49,82,'','primary',8,82,'2026-05-12 12:52:05',NULL),(50,83,'','primary',8,83,'2026-05-12 12:52:05',NULL),(51,84,'','primary',8,84,'2026-05-12 12:52:05',NULL),(52,85,'','primary',8,85,'2026-05-12 12:52:05',NULL);
/*!40000 ALTER TABLE `teeth_reference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tooth_conditions`
--

DROP TABLE IF EXISTS `tooth_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tooth_conditions` (
  `id` char(36) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `tooth_id` bigint(20) unsigned NOT NULL,
  `condition_id` bigint(20) unsigned NOT NULL,
  `surfaces` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`surfaces`)),
  `drawing_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`drawing_data`)),
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tooth_conditions_tooth_id_foreign` (`tooth_id`),
  KEY `tooth_conditions_condition_id_foreign` (`condition_id`),
  KEY `tooth_conditions_patient_id_is_active_index` (`patient_id`,`is_active`),
  CONSTRAINT `tooth_conditions_condition_id_foreign` FOREIGN KEY (`condition_id`) REFERENCES `condition_library` (`id`),
  CONSTRAINT `tooth_conditions_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tooth_conditions_tooth_id_foreign` FOREIGN KEY (`tooth_id`) REFERENCES `teeth_reference` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tooth_conditions`
--

LOCK TABLES `tooth_conditions` WRITE;
/*!40000 ALTER TABLE `tooth_conditions` DISABLE KEYS */;
INSERT INTO `tooth_conditions` VALUES ('019e1aec-42b1-7348-ad7a-115deb739935',1,5,11,'[\"ROOT-2\"]',NULL,NULL,1,'2026-05-12 13:42:31','2026-05-12 13:42:31'),('019e1aec-5576-7056-ba95-36eea3a40547',1,5,11,'[\"ROOT-1\"]',NULL,NULL,1,'2026-05-12 13:42:36','2026-05-12 13:42:36');
/*!40000 ALTER TABLE `tooth_conditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `treatment_plan_procedure`
--

DROP TABLE IF EXISTS `treatment_plan_procedure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `treatment_plan_procedure` (
  `treatment_plan_id` bigint(20) unsigned NOT NULL,
  `procedure_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `treatment_plan_procedure_treatment_plan_id_foreign` (`treatment_plan_id`),
  KEY `treatment_plan_procedure_procedure_id_foreign` (`procedure_id`),
  CONSTRAINT `treatment_plan_procedure_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `treatment_plan_procedure_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `treatment_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treatment_plan_procedure`
--

LOCK TABLES `treatment_plan_procedure` WRITE;
/*!40000 ALTER TABLE `treatment_plan_procedure` DISABLE KEYS */;
/*!40000 ALTER TABLE `treatment_plan_procedure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `treatment_plans`
--

DROP TABLE IF EXISTS `treatment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `treatment_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint(20) unsigned NOT NULL,
  `procedure_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `total_estimated_cost` int(10) unsigned NOT NULL,
  `total_amount_paid` int(10) unsigned DEFAULT NULL,
  `appointments_needed` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `treatment_plans_patient_id_foreign` (`patient_id`),
  KEY `treatment_plans_procedure_id_foreign` (`procedure_id`),
  KEY `treatment_plans_branch_id_foreign` (`branch_id`),
  CONSTRAINT `treatment_plans_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `treatment_plans_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `treatment_plans_procedure_id_foreign` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treatment_plans`
--

LOCK TABLES `treatment_plans` WRITE;
/*!40000 ALTER TABLE `treatment_plans` DISABLE KEYS */;
INSERT INTO `treatment_plans` VALUES (1,1,1,1,1500,100,4,'2026-07-07','completed','2026-05-12 12:52:05','2026-05-12 14:31:55'),(2,1,1,1,400,NULL,NULL,'2026-05-12','proposed','2026-05-12 16:07:37','2026-05-12 16:07:37'),(3,1,3,1,322,NULL,NULL,'2026-05-12','proposed','2026-05-12 16:08:09','2026-05-12 16:08:09'),(4,3,3,1,2,NULL,NULL,'2026-05-12','proposed','2026-05-12 16:08:41','2026-05-12 16:08:41');
/*!40000 ALTER TABLE `treatment_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `treatments`
--

DROP TABLE IF EXISTS `treatments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `treatments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `treatment_type` varchar(50) NOT NULL,
  `diagnosis` varchar(100) NOT NULL,
  `treatment_date` date NOT NULL,
  `duration` varchar(6) NOT NULL,
  `cost` int(10) unsigned NOT NULL,
  `description` varchar(250) NOT NULL,
  `patient_id` bigint(20) unsigned NOT NULL,
  `treatment_plan_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `treatments_patient_id_unique` (`patient_id`),
  UNIQUE KEY `treatments_treatment_plan_id_unique` (`treatment_plan_id`),
  KEY `treatments_branch_id_foreign` (`branch_id`),
  CONSTRAINT `treatments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `treatments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `treatments_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `treatment_plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treatments`
--

LOCK TABLES `treatments` WRITE;
/*!40000 ALTER TABLE `treatments` DISABLE KEYS */;
INSERT INTO `treatments` VALUES (1,'Crown','Damged Tooth','2026-03-03','45m',1500,'Treatment done successfully',1,1,1,NULL,NULL);
/*!40000 ALTER TABLE `treatments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `profile_image_path` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Dentist',NULL,'dentist@clinic.com',NULL,'$2y$12$ircrTNFWdD2v42hQN3COJu0VwHtD/a1nKIBFzJ7dsxlMKnrIW8Inu',NULL,'2026-05-12 12:52:04','2026-05-12 12:52:04'),(2,'Big Boss',NULL,'bigboss@clinic.com',NULL,'$2y$12$n171FG/ARsS3uWr/bMD1YuiFYYZy.DnlAK6Ox6srC1sw38lbO45DW',NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05'),(3,'Receptionist',NULL,'receptionist@clinic.com',NULL,'$2y$12$yyue4dulDttawZhMqwPnHOSX2Mwdi1UV7LdEG672/PfZZ.YMsIFQS',NULL,'2026-05-12 12:52:05','2026-05-12 12:52:05');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-12 23:05:06
