-- MySQL dump 10.13  Distrib 8.0.40, for Linux (x86_64)
--
-- Host: localhost    Database: db
-- ------------------------------------------------------
-- Server version	8.0.40

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
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `findings`
--

DROP TABLE IF EXISTS `findings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `findings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint unsigned NOT NULL,
  `check` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `findings_site_id_foreign` (`site_id`),
  CONSTRAINT `findings_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `findings`
--

LOCK TABLES `findings` WRITE;
/*!40000 ALTER TABLE `findings` DISABLE KEYS */;
INSERT INTO `findings` VALUES (1,1,'long_title','medium','open','Title is 63 chars (recommended: under 60)',NULL,'2026-03-24 12:58:29','2026-03-24 12:58:29'),(2,1,'moderate_ttfb','medium','open','TTFB is 569ms (could be faster)',NULL,'2026-03-24 13:01:55','2026-03-24 13:01:55');
/*!40000 ALTER TABLE `findings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
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
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `keywords`
--

DROP TABLE IF EXISTS `keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint unsigned NOT NULL,
  `phrase` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `volume` smallint unsigned DEFAULT NULL,
  `difficulty` tinyint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `keywords_site_id_foreign` (`site_id`),
  CONSTRAINT `keywords_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `keywords`
--

LOCK TABLES `keywords` WRITE;
/*!40000 ALTER TABLE `keywords` DISABLE KEYS */;
/*!40000 ALTER TABLE `keywords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_01_01_000001_create_sites_table',1),(5,'2025_01_01_000002_create_findings_table',1),(6,'2025_01_01_000003_create_tasks_table',1),(7,'2025_01_01_000004_create_keywords_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `sessions` VALUES ('1ciTiVxz9IhUuPAjmeHGHW5NZ0nbnHpmpOccCmJQ',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJXZEVVTFFaQ3VMUFV3ekRmaUZUMTB3NWwzalowUkFTNXhmb0JiSUN1IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356444),('3DAp3CX1pAA0re3dkDClGRS4WlX9dlKEosxJ8WLa',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiIweldtVXIza1JIdGpBdVowZ2Q1UTB5V2xvUEZaN0RXYUdTeDBwR1QzIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356458),('3NWkzr6QqatAoi5e8oV6x4sySZGTNs7ub4eIz9qm',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ4a1hkNTg1S2I3ZzdqTUYycXg4WUFLMkdDYlRCMEs0ZnNGb2N2Mk9LIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356457),('3UyuS8MgCPuNaIEZYVRjV12zbJJNuoySteYJ3ZS7',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJyaE9QSWE2UTA4dUlZWE9LVVZYaUZjSUNBVzd1Sll1VUxnTlZERUVNIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356444),('3Y8RhH74COyKxzppVdfO0FuXqk1HjlgGk6NQhUSP',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJhVUdsQ1NXa0lNMHR0RFZNNjhGTXRiYlQ4eEpoVkd5RkVrQlZRemJOIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356451),('64fBuj1CmRYTBpyhkEXoVA8QgTNpkPpPAoZaZd0q',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJRTmxkME9EZ1Z3VUppTjdZQVhXeGFxcDUyZ1U2Z1VjdTlvUlpreTkzIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356448),('8nv8JlHSxPZ8UgC2gj5B9g6flkBV334q2i3dQu5X',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJzM2lZWE8wOGlhWjloaXR5REFZVTBwbUhiTnNjNkoyNlhVV0lCRXlGIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356449),('9uO7WVBM9vFaiOd4CKY3kVoaL0mJzmQZLCNp8EJU',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJqWWpBMWUxNVZpNThFSjBvNTRNTmZxRVZaWFdKZFJYeFVlNEhhOWpFIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356445),('ag0ADilxmle4bFcz4monyvSDPzYUFDfBOMC44kg7',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI4ZUZlMzh5QWh0ZFpVT1RYWlpZbFI2UGNnd0RXMjVpWGtrWGg3bXVwIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356454),('Ak5pjltfcowgTmMUx4NswLSJ2OsjylX22tzNeBkF',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJDdlVLbTA5NkJRTUhZTmRyakZmU0Fma28wcEo3MERHcUh5ak5FekpHIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356453),('AxD5P9ASCjGfKqpInCdIfFlCD7Dd9kUHaAdyNLWr',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJraE9hbmtwcEhCNzYzRGZ5Y1dKRFVGTmhTdFJ5ZTNyTHRPR1NqMFAzIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356443),('bA7gtOdYbABO1sea3GEu8VixEvcMg6ohUw3Xdl6L',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJzaGsyRGJZa1NlSDVNb0lETmVlWjRRQTQ3dXc4VkYzdzJBeW53cUZVIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356454),('bmPEwIBn14sio5UeWNwkEp4MHEgQA8KEmV8iA2DS',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJkWVZlRGg4TkNjd2ZMeUg1Nm9DNmtrUUZEeUpWZ0tiVnhNcG1CVVRGIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356447),('BvMCtqudNYjGKJS18Zq7UkvsXgdIGD4QTfXZkeIM',NULL,'127.0.0.1','Symfony','eyJfdG9rZW4iOiJxRVBVQ2F2dVB3TE1jUnhxTlNjZTJWeWFpR1JOOFE4SERKR2NLWmpLIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356405),('C45aFPfuCEaprAC8nDmYFUHQzJ5BeXyjpRPQSL0O',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJDU3MweWx6WDJTU3lzeGVtYkliU2RHMGRDWW1FUk5ldzNONWhRMm5kIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356455),('DCp8HJ69pNxAMqwS4ujUoJ6FYe34tqyGEGEhY4L9',NULL,'192.168.97.5','curl/8.7.1','eyJfdG9rZW4iOiJ4VzVxNUxtY05VUnVWRUZpSGh2OXNjZVpaNk5zT1d1R3pUTHZ3MnpsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC8yM3A0Y2hlY2suZGRldi5zaXRlXC9zYW5jdHVtXC9jc3JmLWNvb2tpZSIsInJvdXRlIjoic2FuY3R1bS5jc3JmLWNvb2tpZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1774356100),('dMvyazYNcSyyJCnlRmbEQ0lYpzA1pMwR3u3yGgho',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI5aWNDR3BRTDk0YlRmNVhkWFJVQmZzME5yemJrWGU0V0F0RUlrMnJtIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356452),('DPl9xfsQ8032inVZBdUgD1KWEJfgafZp5VJjyO5a',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ5UmQ5SjZES1lreFhtNjFMRnZyenkxR3hibmhqYjdWbGk0bjk1bEdBIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356457),('E4KL7KoYudAvQDbIZ5b5LjRZTNl3R2Y5OogC790u',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJjRGJWZVE0Y3paZk9uUmd6cU1GMUJOZDVTSXZVWWxBN3pmVmxtb0ZJIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356457),('EV0DeGKSdzlL2ACp7Y6XxX76TCd5fyEeFSFBVBZg',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJvQ0xyN2lRZmxJQmRwV1BYclhXd0RGMzdRMzFuM0hxWE5tSFNGbmpYIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356453),('goNRS9CqbMziJB71b6vK52rBXfFD9u3o2GN86xw3',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ0ZUpwZDNsaGN5VWJhMGNrZjhGSm43V005eDlrbzlMWkRjeEs3MU52IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356446),('ighXG6NInsY08VcN3rMJZJQ17NuowHTH4Liefx5h',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJEZFo1Q3VvdTREaHFXWHBLUGo1NGNVRnBadmpLVjg1ZWE0clpoQW83IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356442),('IjIpYd2toiyO8EFLX4752ZKmGBixtyrVPJGbUfqC',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJlNFR4MHJPdGNkOVNpWWY2QktScmlsMkNPOGRPTlF6QUFFYTIxb0ZBIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356458),('IldwXj3hhM3OCRBqdN4AJjfMbkB2Es1SeVSQprGO',1,'192.168.97.5','curl/8.7.1','eyJfdG9rZW4iOiJKZkFGdEF3MUFJTE00VlVHbHhnV1c0T1NpVmJiRk44enZYT1FvWDlFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC8yM3A0Y2hlY2suZGRldi5zaXRlXC9zYW5jdHVtXC9jc3JmLWNvb2tpZSIsInJvdXRlIjoic2FuY3R1bS5jc3JmLWNvb2tpZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1774356249),('JMfUudNDEFoYxA74lb0LsvBWp8KCChA7qkVfLqQJ',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJGdGYxSGU0VHdoNGZIODdUTTBtVEloR3FobUJMUzQwVUxYblpoTEVWIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356452),('KanhDJHqYSyDR2m8igr3XkX5goZLPnHS1PZVSH2x',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI3eHM4Qm50bTl1Nk42ZGFnekJTTlpMbjlwYTl5YUFZZXlRRFY5akRnIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356453),('KKxA98PQD97SU3yGWOMXo1qxG3mAtyexAtQ4a7kW',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJrOXROSG5UMXJrckV6SDhCZlE4QThqdTdZTW1Ra1lFcmRzaE5GZmZHIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356442),('kUuiQSnWMAbTF8gyMBy8VsiS3NGPTsum2BRqElnZ',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJVUGlEaHNxSzhBZmhwVmZWRjRDYllYeFdOZHdSbnlHdHJFczVRRzY4IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356450),('lHeeXaM6hgZVF49y6BldiLQDCu7NyAPhmrNDU2GU',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI2eG5Td3llRzdFMkpDTjVpZlFJMXlUUE5aTExwSlQxaFoyUWxxbzJaIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356456),('LskNV1umwhULFH18G5NYskmPkKyMJfTQfuZfg2K1',NULL,'192.168.97.5','curl/8.7.1','eyJfdG9rZW4iOiJXV0YyUkNZdFVaQmZiaE9JS0RBMlpQTjhRNjdCSXJDUFpGM2ZmdzV2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC8yM3A0Y2hlY2suZGRldi5zaXRlXC9zYW5jdHVtXC9jc3JmLWNvb2tpZSIsInJvdXRlIjoic2FuY3R1bS5jc3JmLWNvb2tpZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1774356119),('m4HjbxbfBk54puiUbFRLhoRFSaNFKd7meM9MAcOn',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJLSEc0cWhpZ3pQckd5U0FQenUyT3FxdGVlNVp5dTlaOWg0Mm5XdVdlIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356455),('MqlpI4vC2XNwM3QMqfFGazFlXppFbg5ZXw3Uq4wu',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ3bE1HVDgyYnRDR0lVS0ZGaWg0UVNNdVhXSjhYTDBZSDJrZWhJQlJFIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356449),('NuARPhw1q6oPBMLhSI0i710BqUNXcEuEQwP0bG10',1,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJPSkdHRTREWWRMaXBQbDZPNnY0bVBZeXRnODJkR1NWUzZSa3ZiNkFwIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cLzIzcDRjaGVjay5kZGV2LnNpdGVcL3NhbmN0dW1cL2NzcmYtY29va2llIiwicm91dGUiOiJzYW5jdHVtLmNzcmYtY29va2llIn0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjVhNWFjNzM2MWY3NTVkZDI4Y2FlM2M5NGFmODJjOTJjZjJlZDBjNmM4OGM3MGRmM2E2NWU5NGY0NzA1YTIxMWUifQ==',1774359529),('o1J6PXpMIUOtZSLSxLSJ685pM7cksThKxgeCGNet',NULL,'192.168.97.5','curl/8.7.1','eyJfdG9rZW4iOiJRT0FqenU1Zm5OQUJTMFdpU2Q4cXR1VE4xd3VzOEN5cjRHR0lGakxXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC8yM3A0Y2hlY2suZGRldi5zaXRlXC9zYW5jdHVtXC9jc3JmLWNvb2tpZSIsInJvdXRlIjoic2FuY3R1bS5jc3JmLWNvb2tpZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1774356079),('OiNOXwxXx0cTSRDJTlxJEJScPvO93DH3eiiUJOxu',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJIdmhhZzZCV0xZZVN1dXRVREdnQWRpVlc2cXdPWHJCN2d2ckRXaTRMIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356456),('P5JZ37Z4beoHrgFjGqxwHHcwyrgu2U5Pr0kJzClX',1,'192.168.97.5','curl/8.7.1','eyJfdG9rZW4iOiJhMEFpUkFIR1QzVGhSM3dqcDFDbXRhV1JJYVJvNGRueEtmdHJaNE8xIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC8yM3A0Y2hlY2suZGRldi5zaXRlXC9zYW5jdHVtXC9jc3JmLWNvb2tpZSIsInJvdXRlIjoic2FuY3R1bS5jc3JmLWNvb2tpZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1774356335),('Psm6PfYR09SEDj3aOUMXNAAzuDcImBpy3Af6aIP5',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJJUnlYNzc5NG1TR3hrZlNwcVhLMFNNZGFDSmlpdTNBSzB2OGlsSEZwIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356449),('q11c5KDdqIFPxEnu5MlKSatbjTAnTuAn3UmQCiVj',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJjSkpzeFVDYk9zQm1sZTJ0Y3lnMnRIcHVkbnNRdjFtZHNnazZJb1cxIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356451),('q3pGVqwlitVLK0P2atPKhiv8t9sHnsUYX0WXtAXA',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJVTmZXOXpoWHRRUmFCV2RDTEZTTDIxa2xiQzFsNlhBbVVodk5BSnBmIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356443),('QfuZfgEOuuv3tHbFox8c1Ens7EVrE5GFUxhbP1mw',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJZSEE1b2JPSW14Vm1ZUFU5MHp1YndzZ1U5aVM1bElOYUR6Nnp4NDlNIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356450),('QkrCVrPy1wLylsXTkdqWj5BdukEyTjOJsp4kaMSQ',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiIyc2gzck5aWDdueHRMUFZ2Z3hEeEVDc1I0WE5IT2hBM0pGd1dHbFFlIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356452),('qNZUkFHYaU55bd2qiAFfJ8B5FqWu9PfjClhcpets',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJBcTc3VTNhRnRIQmFHbzEydFRBZjJxYVhFSnlEN0pTUUx5TWs4eU1FIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356458),('qvn2Igkr8SZNMQTuDCFHYBrpXHHKKqffrusKgWrJ',NULL,'127.0.0.1','curl/8.14.1','eyJfdG9rZW4iOiJhUndkOE85SFFiOWx5UlplQlJGekxQOHNkTFY3elc4M05US2pGU1BjIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356359),('qXgJwIr7iYelaKbsa1nZXAyMyFx6CGAyrRcy1afp',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJBQ0w2ZlNmT1l4b1hGREtJc3Jib2FiSGVMdzNINzdjQVEyUW9Ic2I3IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356442),('RHBEmJPxdHWt3K92VRp9noCnLjt4ZsVoXMSHnzTD',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI3eUFNTXljUUZOWmtmRzhjRHVPZ05taEV3a0FCQ25RVXRaVWFQZGJvIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356447),('saiwScps0Fqi9oRVl4gzcmx8q2zPoPiJhYHgu9cJ',NULL,'192.168.97.5','curl/8.7.1','eyJfdG9rZW4iOiI4NXU5Y05XYm01QlJOMFYxWmxRczZ3SHkwdUt6R0NrZVBMeTE3WlNzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC8yM3A0Y2hlY2suZGRldi5zaXRlXC9zYW5jdHVtXC9jc3JmLWNvb2tpZSIsInJvdXRlIjoic2FuY3R1bS5jc3JmLWNvb2tpZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1774356100),('sxXi2LJOXcjC3VK2YLLx72C9b8iS8AfKvpZgZ7Z2',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI4S01DS0FzSDMwcWdhTkxGRmJSWUluMlJGdFlWWGRURlRKTmZUTkJCIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356448),('vNPw9fFnaVL8WZys8hF7UFnJOU4QhRh0766MZNuj',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJpWWxWV1JpZHZQTjBVSnNZU0x5OFdBUWd4c1VPV0pwZ1k0RVYyVUg2IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356446),('vtM5qTpvsHADwuhzBHcSuK82yLSKSGSeJfjTiEt6',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJqRXUyakZDM0hUNlhsdkNhUUhBRmlMc1EyYjYxaGdaNDNLVzZlbHhxIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356442),('wDbyNxGDe8w2NymKqcY0i9XbAq2Z9KGf9xiZNQMV',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJBU1Ywb0JkZ05oVmpaeW9zS0FEc3U5WmU1YU9lUnJLbTZZbk10Y3RlIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356450),('Wys6hnkeN6woBsJaAx2CGybOa5TRshCu19crMvW7',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJQeE5rT1d2WWtOanM5TUpicUdyb3BscGZDeFRWQjBKZlduMXhMVXJaIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356446),('xb7dxnc6rJB9FwPiSFK5lq5x8Dytd5nhIjBhJafR',1,'192.168.97.5','curl/8.7.1','eyJfdG9rZW4iOiI4Qk9FYlNxdFQwbXBmb05rZGNnb3UxamUyQm1uaXdHajladjR1MGduIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC8yM3A0Y2hlY2suZGRldi5zaXRlXC9zYW5jdHVtXC9jc3JmLWNvb2tpZSIsInJvdXRlIjoic2FuY3R1bS5jc3JmLWNvb2tpZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxfQ==',1774356320),('XelqLp7HckE7rmCR8OERHRcGgFqSY9Iexv7YP25q',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJSMzBqVUZIZ3l3eXo5VGQyZVk5UmIxUkJDQXhzSjlKcFFRZmdydjJDIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356445),('XygB64VBEhqNCOISaPXFjpRiv2akiY4MfwGlqlY3',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJIT05Db2VCUGpRbUhJSUtBQk5qZWpDMUVxRXJVaVhjeURPNDFSbmdNIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356448),('y3gQmfzPLN5Qb2yeyMwEXJVQYN8oeFHvHLt7a3jO',NULL,'127.0.0.1','curl/8.14.1','eyJfdG9rZW4iOiJtblFkaDVTM1hvcVU0SjBZUWVDRzA2YjNjaXJtUE5UU3d0TldSSWk4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvc2FuY3R1bVwvY3NyZi1jb29raWUiLCJyb3V0ZSI6InNhbmN0dW0uY3NyZi1jb29raWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356359),('Y8ExCWNeDRmFu5nk2S1Pu3QYeMDjuJa3u7mxcQUq',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJmOUdRc1A1bkh2dVJzeVBOQUgxQVRCV0ExZ25NVEdmUU82TTBBS29HIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356454),('ZTRr3eHhIEKjbkvaunfzF5wKf1DOY9tBgbJOgvus',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJLUHNIVnhPcFJ6QnFqOVp6bDNNa0pSQ3ZxSkd2b2o3SnFDNTBsMUtBIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356456),('zXFN1bCKL3W7nlz1zIP6SzoRc2Zk8ZywQUuYTOuL',NULL,'192.168.97.5','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ2RVN5SWF5Mmd1WWFPSWNGZXFqTmRYd2ZDQWlteGxSeUN5WEtYRXpEIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1774356455);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_scanned_at` timestamp NULL DEFAULT NULL,
  `score` smallint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sites_user_id_foreign` (`user_id`),
  CONSTRAINT `sites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES (1,1,'https://altiuslifts.co.uk',NULL,'2026-03-24 13:01:55',NULL,'2026-03-24 12:52:46','2026-03-24 13:01:55');
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `finding_id` bigint unsigned NOT NULL,
  `sort` tinyint unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_finding_id_foreign` (`finding_id`),
  CONSTRAINT `tasks_finding_id_foreign` FOREIGN KEY (`finding_id`) REFERENCES `findings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,2,0,'Consider optimising server response time',0,'2026-03-24 13:01:55','2026-03-24 13:01:55');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@23p4.local',NULL,'$2y$12$55UzRd3bB7.AeqKIlW1Yz.TKyCdCJtgCpHymcztXeAjOJRENDU/Wu',NULL,'2026-03-24 12:38:44','2026-03-24 12:38:44');
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

-- Dump completed on 2026-03-24 13:39:48
