-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: energy_tracker
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
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
-- Table structure for table `daily_energy_summaries`
--

DROP TABLE IF EXISTS `daily_energy_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_energy_summaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `machine_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `kwh_usage` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_energy_summaries_machine_id_date_unique` (`machine_id`,`date`),
  CONSTRAINT `daily_energy_summaries_machine_id_foreign` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_energy_summaries`
--

LOCK TABLES `daily_energy_summaries` WRITE;
/*!40000 ALTER TABLE `daily_energy_summaries` DISABLE KEYS */;
INSERT INTO `daily_energy_summaries` VALUES (67,3,'2026-04-25',3782.34,'2026-04-25 09:41:56','2026-04-25 09:41:56');
/*!40000 ALTER TABLE `daily_energy_summaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('power_meter','temperature_sensor','humidity_sensor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `machine_id` bigint unsigned DEFAULT NULL,
  `slave_id` int NOT NULL,
  `communication_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RS485',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `devices_machine_id_foreign` (`machine_id`),
  CONSTRAINT `devices_machine_id_foreign` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
INSERT INTO `devices` VALUES (1,'Meter PM-01','power_meter',1,1,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(2,'Meter PM-02','power_meter',2,2,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(3,'Meter PM-03','power_meter',3,3,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(4,'Meter PM-04','power_meter',4,4,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(5,'Meter PM-05','power_meter',5,5,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(6,'Meter PM-06','power_meter',6,6,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(7,'Meter PM-07','power_meter',7,7,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(8,'Meter PM-08','power_meter',8,8,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(9,'Meter PM-09','power_meter',9,9,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(10,'Meter PM-10','power_meter',10,10,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(11,'Meter PM-11','power_meter',11,11,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(12,'Meter PM-12','power_meter',12,12,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(13,'Meter PM-13','power_meter',13,13,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(14,'Meter PM-14','power_meter',14,14,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(15,'Meter PM-15','power_meter',15,15,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(16,'Meter PM-16','power_meter',16,16,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(17,'Meter PM-17','power_meter',17,17,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(18,'Meter PM-18','power_meter',18,18,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(19,'Meter PM-19','power_meter',19,19,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(20,'Meter PM-20','power_meter',20,20,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(21,'Meter PM-21','power_meter',21,21,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(22,'Meter PM-22','power_meter',22,22,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(23,'Factory Floor DHT Sensor','temperature_sensor',NULL,100,'RS485',1,'2026-04-02 00:42:19','2026-04-02 00:42:19');
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `environmental_readings`
--

DROP TABLE IF EXISTS `environmental_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `environmental_readings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint unsigned NOT NULL,
  `temperature` double DEFAULT NULL,
  `humidity` double DEFAULT NULL,
  `recorded_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `environmental_readings_device_id_recorded_at_index` (`device_id`,`recorded_at`),
  CONSTRAINT `environmental_readings_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=336 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `environmental_readings`
--

LOCK TABLES `environmental_readings` WRITE;
/*!40000 ALTER TABLE `environmental_readings` DISABLE KEYS */;
/*!40000 ALTER TABLE `environmental_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
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
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (1,'Peroni Karya Sentra','Main Manufacturing Plant','2026-04-02 00:42:19','2026-04-02 00:42:19');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `machines`
--

DROP TABLE IF EXISTS `machines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `machines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `machines_code_unique` (`code`),
  KEY `machines_location_id_foreign` (`location_id`),
  CONSTRAINT `machines_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `machines`
--

LOCK TABLES `machines` WRITE;
/*!40000 ALTER TABLE `machines` DISABLE KEYS */;
INSERT INTO `machines` VALUES (1,'BAHAN BAKU','PM-01',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(2,'BAHAN BAKU','PM-02',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(3,'COR PASIR','PM-03',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(4,'COR PASIR','PM-04',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(5,'COR PASIR','PM-05',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(6,'COR LOST WAX','PM-06',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(7,'NETTO FLANGE','PM-07',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(8,'NETTO FITTING','PM-08',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(9,'BUBUT FLANGE','PM-09',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(10,'BUBUT FITTING','PM-10',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(11,'BUBUT BESI','PM-11',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(12,'BOR FLANGE','PM-12',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(13,'FINISH FLANGE','PM-13',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(14,'GUDANG JADI FLANGE','PM-14',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(15,'GUDANG JADI FITTING','PM-15',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(16,'SPECTRO','PM-16',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(17,'HEAT TREATMENT','PM-17',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(18,'KIMIA FITTING','PM-18',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(19,'MAINTENANCE','PM-19',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(20,'TPS NON B3','PM-20',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(21,'MILLING','PM-21',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19'),(22,'CETAK LOST WAX','PM-22',1,1,'2026-04-02 00:42:19','2026-04-02 00:42:19');
/*!40000 ALTER TABLE `machines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'0_create_locations_table',1),(5,'1_create_machines_table',1),(6,'2_create_devices_table',1),(7,'3_create_power_readings_table',1),(8,'4_create_environmental_readings_table',1),(9,'5_create_daily_energy_summaries_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `power_readings`
--

DROP TABLE IF EXISTS `power_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `power_readings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint unsigned NOT NULL,
  `kwh_total` double NOT NULL,
  `power_kw` double DEFAULT NULL,
  `voltage` double DEFAULT NULL,
  `current` double DEFAULT NULL,
  `power_factor` double DEFAULT NULL,
  `recorded_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `power_readings_device_id_recorded_at_index` (`device_id`,`recorded_at`),
  CONSTRAINT `power_readings_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7531 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `power_readings`
--

LOCK TABLES `power_readings` WRITE;
/*!40000 ALTER TABLE `power_readings` DISABLE KEYS */;
INSERT INTO `power_readings` VALUES (7375,3,4918,0,0,0,0,'2026-04-24 14:06:21'),(7376,3,4918,0,0,0,0,'2026-04-24 14:16:21'),(7377,3,4918,0,0,0,0,'2026-04-24 14:26:21'),(7378,3,4918,0,0,0,0,'2026-04-24 14:36:21'),(7379,3,4918,0,0,0,0,'2026-04-24 14:46:21'),(7380,3,4918,0,0,0,0,'2026-04-24 14:56:21'),(7381,3,4918,0,0,0,0,'2026-04-24 15:06:21'),(7382,3,4918,0,0,0,0,'2026-04-24 15:16:21'),(7383,3,4918,0,0,0,0,'2026-04-24 15:26:21'),(7384,3,4918,0,0,0,0,'2026-04-24 15:36:21'),(7385,3,4918,0,0,0,0,'2026-04-24 15:46:21'),(7386,3,4918,0,0,0,0,'2026-04-24 15:56:21'),(7387,3,4918,0,0,0,0,'2026-04-24 16:06:21'),(7388,3,4918,0,0,0,0,'2026-04-24 16:16:21'),(7389,3,4918,0,0,0,0,'2026-04-24 16:26:21'),(7390,3,4918,0,0,0,0,'2026-04-24 16:36:21'),(7391,3,4918,0,0,0,0,'2026-04-24 16:46:21'),(7392,3,4918,0,0,0,0,'2026-04-24 16:56:21'),(7393,3,4918,0,0,0,0,'2026-04-24 17:06:21'),(7394,3,4918,0,0,0,0,'2026-04-24 17:16:21'),(7395,3,4918,0,0,0,0,'2026-04-24 17:26:21'),(7396,3,4918,0,0,0,0,'2026-04-24 17:36:21'),(7397,3,4918,0,0,0,0,'2026-04-24 17:46:21'),(7398,3,4918,0,0,0,0,'2026-04-24 17:56:21'),(7399,3,4918,0,0,0,0,'2026-04-24 18:06:21'),(7400,3,4918,0,0,0,0,'2026-04-24 18:16:21'),(7401,3,4918,0,0,0,0,'2026-04-24 18:26:21'),(7402,3,4918,0,0,0,0,'2026-04-24 18:36:21'),(7403,3,4918,0,0,0,0,'2026-04-24 18:46:21'),(7404,3,4918,0,0,0,0,'2026-04-24 18:56:21'),(7405,3,4918,0,0,0,0,'2026-04-24 19:06:21'),(7406,3,4918,0,0,0,0,'2026-04-24 19:16:21'),(7407,3,4918,0,0,0,0,'2026-04-24 19:26:21'),(7408,3,4918,0,0,0,0,'2026-04-24 19:36:21'),(7409,3,4918,0,0,0,0,'2026-04-24 19:46:21'),(7410,3,4918,0,0,0,0,'2026-04-24 19:56:21'),(7411,3,4918,0,0,0,0,'2026-04-24 20:06:21'),(7412,3,4918,0,0,0,0,'2026-04-24 20:16:21'),(7413,3,4918,0,0,0,0,'2026-04-24 20:26:21'),(7414,3,4918,0,0,0,0,'2026-04-24 20:36:21'),(7415,3,4918,0,0,0,0,'2026-04-24 20:46:21'),(7416,3,4918,0,0,0,0,'2026-04-24 20:56:21'),(7417,3,4918,0,0,0,0,'2026-04-24 21:06:21'),(7418,3,4918,0,0,0,0,'2026-04-24 21:16:21'),(7419,3,4918,0,0,0,0,'2026-04-24 21:26:21'),(7420,3,4918,0,0,0,0,'2026-04-24 21:36:21'),(7421,3,4918,0,0,0,0,'2026-04-24 21:46:21'),(7422,3,4918,0,0,0,0,'2026-04-24 21:56:21'),(7423,3,4918,0,0,0,0,'2026-04-24 22:06:21'),(7424,3,4918,0,0,0,0,'2026-04-24 22:16:21'),(7425,3,4918,0,0,0,0,'2026-04-24 22:26:21'),(7426,3,4918,0,0,0,0,'2026-04-24 22:36:21'),(7427,3,4918,0,0,0,0,'2026-04-24 22:46:21'),(7428,3,4918,0,0,0,0,'2026-04-24 22:56:21'),(7429,3,4918,0,0,0,0,'2026-04-24 23:06:21'),(7430,3,4918,0,0,0,0,'2026-04-24 23:16:21'),(7431,3,4918,0,0,0,0,'2026-04-24 23:26:21'),(7432,3,4918,0,0,0,0,'2026-04-24 23:36:21'),(7433,3,4918,0,0,0,0,'2026-04-24 23:46:21'),(7434,3,4918,0,0,0,0,'2026-04-24 23:56:21'),(7435,3,4918,0,0,0,0,'2026-04-25 00:06:21'),(7436,3,4918,0,0,0,0,'2026-04-25 00:16:21'),(7437,3,4918,0,0,0,0,'2026-04-25 00:26:21'),(7438,3,4918,0,0,0,0,'2026-04-25 00:36:21'),(7439,3,4918,0,0,0,0,'2026-04-25 00:46:21'),(7440,3,4918,0,0,0,0,'2026-04-25 00:56:21'),(7441,3,4918,0,0,0,0,'2026-04-25 01:06:21'),(7442,3,4918,0,0,0,0,'2026-04-25 01:16:21'),(7443,3,4918,0,0,0,0,'2026-04-25 01:26:21'),(7444,3,4918,0,0,0,0,'2026-04-25 01:36:21'),(7445,3,4918,0,0,0,0,'2026-04-25 01:46:21'),(7446,3,4918,0,0,0,0,'2026-04-25 01:56:21'),(7447,3,4918,0,0,0,0,'2026-04-25 02:06:21'),(7448,3,4918,0,0,0,0,'2026-04-25 02:16:21'),(7449,3,4918,0,0,0,0,'2026-04-25 02:26:21'),(7450,3,4918,0,0,0,0,'2026-04-25 02:36:21'),(7451,3,4918,0,0,0,0,'2026-04-25 02:46:21'),(7452,3,4918,0,0,0,0,'2026-04-25 02:56:21'),(7453,3,4918,0,0,0,0,'2026-04-25 03:06:21'),(7454,3,4918,0,0,0,0,'2026-04-25 03:16:21'),(7455,3,4918,0,0,0,0,'2026-04-25 03:26:21'),(7456,3,4918,0,0,0,0,'2026-04-25 03:36:21'),(7457,3,4918,0,0,0,0,'2026-04-25 03:46:21'),(7458,3,4918,0,0,0,0,'2026-04-25 03:56:21'),(7459,3,4918,0,0,0,0,'2026-04-25 04:06:21'),(7460,3,4918,0,0,0,0,'2026-04-25 04:16:21'),(7461,3,4918,0,0,0,0,'2026-04-25 04:26:21'),(7462,3,4918,0,0,0,0,'2026-04-25 04:36:21'),(7463,3,4918,0,0,0,0,'2026-04-25 04:46:21'),(7464,3,4918,0,0,0,0,'2026-04-25 04:56:21'),(7465,3,4918,0,0,0,0,'2026-04-25 05:06:21'),(7466,3,4918,0,0,0,0,'2026-04-25 05:16:21'),(7467,3,4918,0,0,0,0,'2026-04-25 05:26:21'),(7468,3,4918,0,0,0,0,'2026-04-25 05:36:21'),(7469,3,4918,0,0,0,0,'2026-04-25 05:46:21'),(7470,3,4918,0,0,0,0,'2026-04-25 05:56:21'),(7471,3,4918,0,0,0,0,'2026-04-25 06:06:21'),(7472,3,4918,0,0,0,0,'2026-04-25 06:16:21'),(7473,3,4918,0,0,0,0,'2026-04-25 06:26:21'),(7474,3,4918,0,0,0,0,'2026-04-25 06:36:21'),(7475,3,4918,0,0,0,0,'2026-04-25 06:46:21'),(7476,3,4918,0,0,0,0,'2026-04-25 06:56:21'),(7477,3,4918,0,0,0,0,'2026-04-25 07:06:21'),(7478,3,4918,0,0,0,0,'2026-04-25 07:16:21'),(7479,3,4918,0,0,0,0,'2026-04-25 07:26:21'),(7480,3,4918,0,0,0,0,'2026-04-25 07:36:21'),(7481,3,4918,0,0,0,0,'2026-04-25 07:46:21'),(7482,3,4918,0,0,0,0,'2026-04-25 07:56:21'),(7483,3,4918,0,0,0,0,'2026-04-25 08:06:21'),(7484,3,4918,0,0,0,0,'2026-04-25 08:16:21'),(7485,3,4918,0,0,0,0,'2026-04-25 08:19:20'),(7486,3,4918,0,0,0,0,'2026-04-25 08:29:20'),(7487,3,4918,0,0,0,0,'2026-04-25 08:39:20'),(7488,3,4918,0,0,0,0,'2026-04-25 08:49:20'),(7489,3,4918,0,0,0,0,'2026-04-25 08:59:20'),(7490,3,4918,0,0,0,0,'2026-04-25 09:09:20'),(7491,3,4918,0,0,0,0,'2026-04-25 09:19:20'),(7492,3,4918,0,0,0,0,'2026-04-25 09:29:20'),(7493,3,4918,0,0,0,0,'2026-04-25 09:31:37'),(7494,3,4918,0,0,0,0,'2026-04-25 09:32:01'),(7495,3,4918,0,0,0,0,'2026-04-25 09:33:01'),(7496,3,4918,0,0,0,0,'2026-04-25 09:34:01'),(7497,3,4918,0,0,0,0,'2026-04-25 09:35:01'),(7498,3,4918,0,0,0,0,'2026-04-25 09:36:01'),(7499,3,4918,0,0,0,0,'2026-04-25 09:37:01'),(7500,3,4918,0,0,0,0,'2026-04-25 09:38:01'),(7501,3,4918,0,0,0,0,'2026-04-25 09:39:01'),(7502,3,4918,0,0,0,0,'2026-04-25 09:40:01'),(7503,3,4918,0,0,0,0,'2026-04-25 09:41:01'),(7504,3,3782.34,6.139,545.8,0.66,0.958,'2026-04-25 09:41:56'),(7505,3,3782.34,6.174,545.7,0.67,0.958,'2026-04-25 09:42:56'),(7506,3,3782.34,6.168,545.8,0.72,0.958,'2026-04-25 09:43:56'),(7507,3,3782.34,6.17,545.6,0.69,0.959,'2026-04-25 09:44:56'),(7508,3,3782.34,6.247,5,10.4,0.504,'2026-04-25 09:45:56'),(7509,3,3782.34,6.102,554,0.64,0.958,'2026-04-25 09:46:56'),(7510,3,3782.34,6.127,559.6,0.63,0.959,'2026-04-25 09:47:56'),(7511,3,3782.34,6.16,559.3,0.67,0.959,'2026-04-25 09:48:56'),(7512,3,3782.34,6.123,559,0.63,0.959,'2026-04-25 09:49:56'),(7513,3,3782.34,6.114,559.3,0.62,0.959,'2026-04-25 09:50:56'),(7514,3,3782.34,6.132,559.2,0.64,0.959,'2026-04-25 09:51:56'),(7515,3,3782.34,6.136,559.4,0.63,0.959,'2026-04-25 09:52:38'),(7516,3,3782.34,6.148,559.4,0.6,0.959,'2026-04-25 09:53:38'),(7517,3,3782.34,6.165,559.3,0.69,0.959,'2026-04-25 09:54:38'),(7518,3,3782.34,6.176,559.3,0.63,0.959,'2026-04-25 09:55:38'),(7519,3,3782.34,6.151,559.1,0.73,0.959,'2026-04-25 09:56:38'),(7520,3,3782.34,6.105,558.9,0.75,0.959,'2026-04-25 09:57:35'),(7521,3,3782.34,6.14,34.3,13.22,0.819,'2026-04-25 10:07:35'),(7522,3,3782.34,6.088,552.9,0.66,0.959,'2026-04-25 10:17:35'),(7523,3,3782.34,6.178,552.5,0.69,0.959,'2026-04-25 10:27:35'),(7524,3,3782.34,6.187,552.6,0.7,0.959,'2026-04-25 10:37:35'),(7525,3,3782.34,6.133,552.5,0.65,0.96,'2026-04-25 10:47:35'),(7526,3,3782.34,6.179,544.3,0.73,0.959,'2026-04-25 10:54:54'),(7527,3,3782.34,6.104,76.8,4.98,0.933,'2026-04-25 11:04:54'),(7528,3,3782.34,6.054,349.6,0.78,0.959,'2026-04-25 11:14:54'),(7529,3,3782.34,6.047,435.8,0.69,0.959,'2026-04-25 11:24:54'),(7530,3,3782.34,6.012,438.3,0.65,0.959,'2026-04-25 11:34:54');
/*!40000 ALTER TABLE `power_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
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
INSERT INTO `sessions` VALUES ('47Q8QzLTPmVCrT6SBqewPGw1vMZJM4SvyqPul9kE',NULL,'10.88.8.53','Mozilla/5.0 (Windows NT 6.1; rv:109.0) Gecko/20100101 Firefox/115.0','eyJfdG9rZW4iOiJoOEZaTXpkOGhlblBmWUl5WGxzaVpQRjByWEZRZlBTY3JTZjJjR1BkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEwLjg4LjguOTdcL2VuZXJneS10cmFja2VyXC9wdWJsaWNcL2luZGV4LnBocFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1775122151),('9Ie2CRUeNuIHbSeIUbHjgPvMmLyHLjVMfEWa5TNh',3,'10.88.8.97','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0','eyJfdG9rZW4iOiJTR241eWYyVHRBcjlNNGt3ZUNEMWtadU9rTjlYYThYN25vU2k4MkxJIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEwLjg4LjguOTdcL2VuZXJneS10cmFja2VyXC9wdWJsaWNcL2luZGV4LnBocFwvcmVwb3J0cz9lbmRfZGF0ZT0yMDI2LTA0LTAyJm1hY2hpbmVfaWQ9MSZzdGFydF9kYXRlPTIwMjYtMDMtMDMiLCJyb3V0ZSI6InJlcG9ydHMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6M30=',1775127136),('bvYMc97D6Z4Z028YoGjVrAAa6zisL5TKBBjpT0G4',NULL,'10.88.8.53','Mozilla/5.0 (Windows NT 6.1; rv:109.0) Gecko/20100101 Firefox/115.0','eyJfdG9rZW4iOiI0OWFONElFd2NOeFZtSXFva2RzWDdmcjVCTFRUcVJWV3RJT3ZqUUVNIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTAuODguOC45N1wvZW5lcmd5LXRyYWNrZXJcL3B1YmxpY1wvaW5kZXgucGhwIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC8xMC44OC44Ljk3XC9lbmVyZ3ktdHJhY2tlclwvcHVibGljXC9pbmRleC5waHAiLCJyb3V0ZSI6ImRhc2hib2FyZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1775122151),('eWGgsn5rp2KW1Snb9B9AIaZ00oOalcXRGrU4Wg8s',NULL,'10.88.8.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','eyJfdG9rZW4iOiJ0bERlSEFGOHJQRXkwVGJmVUZlZWtCRTRZMERnU3Z3eUFmWXpuRUlNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEwLjg4LjguOTdcL2VuZXJneS10cmFja2VyXC9wdWJsaWNcL2luZGV4LnBocFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1775120076),('LjmwHK5ZjYa7jp5SQNoNx4fuAAQrgOFHSDJIHY7H',3,'10.88.8.63','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0','eyJfdG9rZW4iOiJGMHNLRWV2dkY4OWt5VVRydzRQT2ZacVo0NXVYN0pFbnp5ZFVlejJaIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEwLjg4LjguOTdcL2VuZXJneS10cmFja2VyXC9wdWJsaWNcL2luZGV4LnBocCIsInJvdXRlIjoiZGFzaGJvYXJkIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjN9',1775121652),('LR8FlzdbxgKawLSyxsGch3p7IipWVe1ZlVQtlVEz',NULL,'10.88.8.53','Mozilla/5.0 (Windows NT 6.1; rv:109.0) Gecko/20100101 Firefox/115.0','eyJfdG9rZW4iOiJoUUNoeVB3aGN4TnhnbXJwaTVHZFlHRG9vNXRFMEh0dzM1MVFEZzJkIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTAuODguOC45N1wvZW5lcmd5LXRyYWNrZXJcL3B1YmxpY1wvaW5kZXgucGhwXC9sb2dpbiIsInJvdXRlIjoibG9naW4ifX0=',1775123069);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Direktur','direktur@peroniks.com',NULL,'$2y$12$tk2vDTOFch9YelspryGuJeYISbhX9HSE745wfrfaFw/ZrfNivJl7W',NULL,'2026-04-02 00:45:11','2026-04-02 00:45:11'),(2,'Finance','finance@peroniks.com',NULL,'$2y$12$r5QxiMDFtGIMU38YkmQt9uADCPjiSqjMZ9gNtBdUNRO5WnlX/BuiK',NULL,'2026-04-02 00:45:11','2026-04-02 00:45:11'),(3,'Management Rep','mr@peroniks.com',NULL,'$2y$12$SVbfa4tcYl1hIWcqVJtcVecdIlspRZDcfpcbgYdb7IMcn7rDFkg7m',NULL,'2026-04-02 00:45:11','2026-04-03 09:43:52'),(4,'Manager HR','managerhr@peroniks.com',NULL,'$2y$12$2Yu37pCvopGIFtFMaQpfyu8H4Uh1nTz0Oiuuuriahtn3uMq0OQlMK',NULL,'2026-04-02 00:45:11','2026-04-02 00:45:11'),(5,'Kabag Maintenance','kabagmaintenance@peroniks.com',NULL,'$2y$12$iVlFmPlJFGCS6O9OX.0kdOGv1cwamN5EXHKZLA6a5TVPjke7BNMIm','jRs8uLfGHfzxaId5AJ4ngNfgNCE43jBYpIlCzf6PI6VaN2yQHQV3JWfEgxfb','2026-04-02 00:45:11','2026-04-02 00:45:11'),(6,'Marketing Export','marketingexport@peroniks.com',NULL,'$2y$12$ZH/KDOtxrOIuTpSCjBVLrupcXD6Z20E2M3iBivB91cJjNFni3y0qS',NULL,'2026-04-02 00:45:11','2026-04-02 00:45:11'),(7,'Pajak','pajak@peroniks.com',NULL,'$2y$12$DlcQFS3DSjzYJljQWbizF.kA1MfHZsW5CGVULW4xB9k5R7rd/gzty',NULL,'2026-04-02 00:45:11','2026-04-02 00:45:11');
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

-- Dump completed on 2026-04-25  4:38:54
