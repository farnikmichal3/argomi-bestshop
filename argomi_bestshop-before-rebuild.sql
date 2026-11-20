-- MySQL dump 10.13  Distrib 8.4.13, for Win64 (x86_64)
--
-- Host: localhost    Database: argomi_bestshop
-- ------------------------------------------------------
-- Server version	8.4.13

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
-- Current Database: `argomi_bestshop`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `argomi_bestshop` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `argomi_bestshop`;

--
-- Table structure for table `attribute`
--

DROP TABLE IF EXISTS `attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `type` enum('text','number','enum','bool') NOT NULL,
  `unit_id` bigint unsigned DEFAULT NULL,
  `is_filterable` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attribute_code` (`code`),
  KEY `idx_attribute_unit` (`unit_id`),
  CONSTRAINT `fk_attribute_unit` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute`
--

LOCK TABLES `attribute` WRITE;
/*!40000 ALTER TABLE `attribute` DISABLE KEYS */;
INSERT INTO `attribute` VALUES (1,'color','enum',NULL,1,1),(2,'size','enum',NULL,1,2),(3,'length_m','number',4,1,3),(4,'c_connectors','number',NULL,1,4),(5,'slots','number',NULL,1,5),(6,'power_w','number',6,1,6),(7,'gan','bool',NULL,1,7);
/*!40000 ALTER TABLE `attribute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attribute_translation`
--

DROP TABLE IF EXISTS `attribute_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attribute_translation` (`attribute_id`,`locale`),
  KEY `idx_attribute_translation_locale` (`locale`),
  CONSTRAINT `fk_attribute_translation_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attribute` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attribute_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute_translation`
--

LOCK TABLES `attribute_translation` WRITE;
/*!40000 ALTER TABLE `attribute_translation` DISABLE KEYS */;
INSERT INTO `attribute_translation` VALUES (1,1,'cs','Barva'),(2,1,'en','Color'),(3,2,'cs','Velikost'),(4,2,'en','Size'),(5,3,'cs','Délka'),(6,3,'en','Length'),(7,4,'cs','Počet konektorů USB-C'),(8,4,'en','Number of USB-C connectors'),(9,5,'cs','Počet portů'),(10,5,'en','Number of ports'),(11,6,'cs','Výkon'),(12,6,'en','Power'),(13,7,'cs','Technologie GaN'),(14,7,'en','GaN technology');
/*!40000 ALTER TABLE `attribute_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attribute_value`
--

DROP TABLE IF EXISTS `attribute_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_value` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` bigint unsigned NOT NULL,
  `code` varchar(100) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `meta` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attribute_value` (`attribute_id`,`code`),
  UNIQUE KEY `uq_attribute_value_composite` (`id`,`attribute_id`),
  CONSTRAINT `fk_attribute_value_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attribute` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute_value`
--

LOCK TABLES `attribute_value` WRITE;
/*!40000 ALTER TABLE `attribute_value` DISABLE KEYS */;
INSERT INTO `attribute_value` VALUES (1,1,'black',1,'{\"hex\": \"#111111\"}'),(2,1,'white',2,'{\"hex\": \"#ffffff\"}'),(3,2,'s',1,NULL),(4,2,'m',2,NULL),(5,2,'l',3,NULL);
/*!40000 ALTER TABLE `attribute_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attribute_value_translation`
--

DROP TABLE IF EXISTS `attribute_value_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_value_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attribute_value_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attribute_value_translation` (`attribute_value_id`,`locale`),
  KEY `idx_attribute_value_translation_locale` (`locale`),
  CONSTRAINT `fk_attribute_value_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_attribute_value_translation_value` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_value` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attribute_value_translation`
--

LOCK TABLES `attribute_value_translation` WRITE;
/*!40000 ALTER TABLE `attribute_value_translation` DISABLE KEYS */;
INSERT INTO `attribute_value_translation` VALUES (1,1,'cs','Černá'),(2,1,'en','Black'),(3,2,'cs','Bílá'),(4,2,'en','White'),(5,3,'cs','S'),(6,3,'en','S'),(7,4,'cs','M'),(8,4,'en','M'),(9,5,'cs','L'),(10,5,'en','L');
/*!40000 ALTER TABLE `attribute_value_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(26) NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `currency` char(3) NOT NULL,
  `locale` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_public_id` (`public_id`),
  KEY `idx_cart_customer` (`customer_id`),
  KEY `idx_cart_expires` (`expires_at`),
  KEY `fk_cart_currency` (`currency`),
  KEY `fk_cart_language` (`locale`),
  CONSTRAINT `fk_cart_currency` FOREIGN KEY (`currency`) REFERENCES `currency` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cart_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_item`
--

DROP TABLE IF EXISTS `cart_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `configuration_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_item_configuration` (`cart_id`,`configuration_hash`),
  UNIQUE KEY `uq_cart_item_id_product` (`id`,`product_id`),
  KEY `idx_cart_item_cart` (`cart_id`),
  KEY `fk_cart_item_variant_product` (`product_variant_id`,`product_id`),
  CONSTRAINT `fk_cart_item_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_item_variant_product` FOREIGN KEY (`product_variant_id`, `product_id`) REFERENCES `product_variant` (`id`, `product_id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_cart_item_quantity` CHECK ((`quantity` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_item`
--

LOCK TABLES `cart_item` WRITE;
/*!40000 ALTER TABLE `cart_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_item_option`
--

DROP TABLE IF EXISTS `cart_item_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_item_option` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_option_id` bigint unsigned NOT NULL,
  `product_option_value_id` bigint unsigned DEFAULT NULL,
  `text_value` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_item_option` (`cart_item_id`,`product_option_id`),
  KEY `fk_cart_item_option_item` (`cart_item_id`,`product_id`),
  KEY `fk_cart_item_option_product_option` (`product_option_id`,`product_id`),
  KEY `fk_cart_item_option_value` (`product_option_value_id`,`product_option_id`),
  CONSTRAINT `fk_cart_item_option_item` FOREIGN KEY (`cart_item_id`, `product_id`) REFERENCES `cart_item` (`id`, `product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_item_option_product_option` FOREIGN KEY (`product_option_id`, `product_id`) REFERENCES `product_option` (`id`, `product_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cart_item_option_value` FOREIGN KEY (`product_option_value_id`, `product_option_id`) REFERENCES `product_option_value` (`id`, `product_option_id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_cart_item_option_one` CHECK ((((`product_option_value_id` is not null) + (`text_value` is not null)) = 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_item_option`
--

LOCK TABLES `cart_item_option` WRITE;
/*!40000 ALTER TABLE `cart_item_option` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_item_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_code` (`code`),
  KEY `idx_category_parent` (`parent_id`,`sort_order`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `category` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,NULL,'clothing',1,1),(2,NULL,'electronics',2,1),(3,2,'cables',1,1),(4,2,'chargers',2,1);
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_translation`
--

DROP TABLE IF EXISTS `category_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_translation` (`category_id`,`locale`),
  UNIQUE KEY `uq_category_translation_slug` (`locale`,`slug`),
  KEY `idx_category_translation_locale` (`locale`),
  CONSTRAINT `fk_category_translation_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_category_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_translation`
--

LOCK TABLES `category_translation` WRITE;
/*!40000 ALTER TABLE `category_translation` DISABLE KEYS */;
INSERT INTO `category_translation` VALUES (1,1,'cs','Oblečení','obleceni',NULL),(2,1,'en','Clothing','clothing',NULL),(3,2,'cs','Elektronika','elektronika',NULL),(4,2,'en','Electronics','electronics',NULL),(5,3,'cs','Kabely','kabely',NULL),(6,3,'en','Cables','cables',NULL),(7,4,'cs','Nabíječky','nabijecky',NULL),(8,4,'en','Chargers','chargers',NULL);
/*!40000 ALTER TABLE `category_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currency`
--

DROP TABLE IF EXISTS `currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` char(3) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(8) NOT NULL,
  `minor_units` tinyint unsigned NOT NULL DEFAULT '2',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `default_flag` tinyint(1) GENERATED ALWAYS AS (if((`is_default` = 1),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_currency_code` (`code`),
  UNIQUE KEY `uq_currency_default` (`default_flag`),
  KEY `fk_currency_iso4217_code_minor` (`code`,`minor_units`),
  CONSTRAINT `fk_currency_iso4217_code_minor` FOREIGN KEY (`code`, `minor_units`) REFERENCES `iso4217_currency` (`code`, `minor_units`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_currency_default_is_active` CHECK (((`is_default` = 0) or (`is_active` = 1))),
  CONSTRAINT `ck_currency_minor_units` CHECK ((`minor_units` <= 4))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currency`
--

LOCK TABLES `currency` WRITE;
/*!40000 ALTER TABLE `currency` DISABLE KEYS */;
INSERT INTO `currency` (`id`, `code`, `name`, `symbol`, `minor_units`, `is_default`, `is_active`, `sort_order`) VALUES (1,'CZK','Czech koruna','Kč',2,1,1,1),(2,'EUR','Euro','€',2,0,1,2);
/*!40000 ALTER TABLE `currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(26) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `locale` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customer_public_id` (`public_id`),
  UNIQUE KEY `uq_customer_email` (`email`),
  KEY `idx_customer_locale` (`locale`),
  CONSTRAINT `fk_customer_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer`
--

LOCK TABLES `customer` WRITE;
/*!40000 ALTER TABLE `customer` DISABLE KEYS */;
INSERT INTO `customer` VALUES (1,'01J9ZK3Q8M5N7V2R4T6X8B0C1D','jana.novakova@example.com',NULL,'Jana','Nováková',NULL,'cs',1,'2026-11-15 00:50:39','2026-11-15 00:50:39',NULL),(2,'01J9ZK3Q8M5N7V2R4T6X8B0C2E','john.smith@example.com',NULL,'John','Smith',NULL,'en',1,'2026-11-15 00:50:39','2026-11-15 00:50:39',NULL);
/*!40000 ALTER TABLE `customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_address`
--

DROP TABLE IF EXISTS `customer_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_address` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `type` enum('billing','shipping') NOT NULL,
  `name` varchar(200) NOT NULL,
  `company` varchar(200) DEFAULT NULL,
  `street` varchar(200) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country_code` char(2) NOT NULL,
  `company_id` varchar(20) DEFAULT NULL,
  `vat_id` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_customer_address_customer` (`customer_id`,`type`),
  CONSTRAINT `fk_customer_address_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_address`
--

LOCK TABLES `customer_address` WRITE;
/*!40000 ALTER TABLE `customer_address` DISABLE KEYS */;
INSERT INTO `customer_address` VALUES (1,1,'billing','Jana Nováková',NULL,'Příkladná 12','Ostrava','702 00','CZ',NULL,NULL,1),(2,1,'shipping','Jana Nováková',NULL,'Příkladná 12','Ostrava','702 00','CZ',NULL,NULL,1),(3,2,'billing','John Smith',NULL,'Example Street 5','Praha','110 00','CZ',NULL,NULL,1),(4,2,'shipping','John Smith',NULL,'Example Street 5','Praha','110 00','CZ',NULL,NULL,1);
/*!40000 ALTER TABLE `customer_address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `iso4217_catalog_release`
--

DROP TABLE IF EXISTS `iso4217_catalog_release`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `iso4217_catalog_release` (
  `id` smallint unsigned NOT NULL,
  `version` varchar(32) NOT NULL,
  `published_at` date NOT NULL,
  `source_name` varchar(190) NOT NULL,
  `source_url` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_iso4217_catalog_release_version` (`version`),
  CONSTRAINT `ck_iso4217_catalog_release_singleton` CHECK ((`id` = 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `iso4217_catalog_release`
--

LOCK TABLES `iso4217_catalog_release` WRITE;
/*!40000 ALTER TABLE `iso4217_catalog_release` DISABLE KEYS */;
INSERT INTO `iso4217_catalog_release` VALUES (1,'2026-09-17','2026-09-17','SIX Interbank Clearing Ltd – ISO 4217 Maintenance Agency','https://www.six-group.com/dam/download/financial-information/data-center/iso-currrency/lists/list-one.xml');
/*!40000 ALTER TABLE `iso4217_catalog_release` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `iso4217_currency`
--

DROP TABLE IF EXISTS `iso4217_currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `iso4217_currency` (
  `code` char(3) NOT NULL,
  `numeric_code` char(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `minor_units` tinyint unsigned NOT NULL,
  `iso4217_catalog_release_id` smallint unsigned NOT NULL,
  PRIMARY KEY (`code`),
  UNIQUE KEY `uq_iso4217_currency_numeric` (`numeric_code`),
  UNIQUE KEY `uq_iso4217_currency_code_minor` (`code`,`minor_units`),
  KEY `idx_iso4217_currency_release` (`iso4217_catalog_release_id`),
  CONSTRAINT `fk_iso4217_currency_release` FOREIGN KEY (`iso4217_catalog_release_id`) REFERENCES `iso4217_catalog_release` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_iso4217_currency_minor_units` CHECK ((`minor_units` <= 4))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `iso4217_currency`
--

LOCK TABLES `iso4217_currency` WRITE;
/*!40000 ALTER TABLE `iso4217_currency` DISABLE KEYS */;
INSERT INTO `iso4217_currency` VALUES ('AED','784',2,1),('AFN','971',2,1),('ALL','008',2,1),('AMD','051',2,1),('AOA','973',2,1),('ARS','032',2,1),('AUD','036',2,1),('AWG','533',2,1),('AZN','944',2,1),('BAM','977',2,1),('BBD','052',2,1),('BDT','050',2,1),('BHD','048',3,1),('BIF','108',0,1),('BMD','060',2,1),('BND','096',2,1),('BOB','068',2,1),('BRL','986',2,1),('BSD','044',2,1),('BTN','064',2,1),('BWP','072',2,1),('BYN','933',2,1),('BZD','084',2,1),('CAD','124',2,1),('CDF','976',2,1),('CHF','756',2,1),('CLP','152',0,1),('CNY','156',2,1),('COP','170',2,1),('CRC','188',2,1),('CUP','192',2,1),('CVE','132',2,1),('CZK','203',2,1),('DJF','262',0,1),('DKK','208',2,1),('DOP','214',2,1),('DZD','012',2,1),('EGP','818',2,1),('ERN','232',2,1),('ETB','230',2,1),('EUR','978',2,1),('FJD','242',2,1),('FKP','238',2,1),('GBP','826',2,1),('GEL','981',2,1),('GHS','936',2,1),('GIP','292',2,1),('GMD','270',2,1),('GNF','324',0,1),('GTQ','320',2,1),('GYD','328',2,1),('HKD','344',2,1),('HNL','340',2,1),('HTG','332',2,1),('HUF','348',2,1),('IDR','360',2,1),('ILS','376',2,1),('INR','356',2,1),('IQD','368',3,1),('IRR','364',2,1),('ISK','352',0,1),('JMD','388',2,1),('JOD','400',3,1),('JPY','392',0,1),('KES','404',2,1),('KGS','417',2,1),('KHR','116',2,1),('KMF','174',0,1),('KPW','408',2,1),('KRW','410',0,1),('KWD','414',3,1),('KYD','136',2,1),('KZT','398',2,1),('LAK','418',2,1),('LBP','422',2,1),('LKR','144',2,1),('LRD','430',2,1),('LSL','426',2,1),('LYD','434',3,1),('MAD','504',2,1),('MDL','498',2,1),('MGA','969',2,1),('MKD','807',2,1),('MMK','104',2,1),('MNT','496',2,1),('MOP','446',2,1),('MRU','929',2,1),('MUR','480',2,1),('MVR','462',2,1),('MWK','454',2,1),('MXN','484',2,1),('MYR','458',2,1),('MZN','943',2,1),('NAD','516',2,1),('NGN','566',2,1),('NIO','558',2,1),('NOK','578',2,1),('NPR','524',2,1),('NZD','554',2,1),('OMR','512',3,1),('PAB','590',2,1),('PEN','604',2,1),('PGK','598',2,1),('PHP','608',2,1),('PKR','586',2,1),('PLN','985',2,1),('PYG','600',0,1),('QAR','634',2,1),('RON','946',2,1),('RSD','941',2,1),('RUB','643',2,1),('RWF','646',0,1),('SAR','682',2,1),('SBD','090',2,1),('SCR','690',2,1),('SDG','938',2,1),('SEK','752',2,1),('SGD','702',2,1),('SHP','654',2,1),('SLE','925',2,1),('SOS','706',2,1),('SRD','968',2,1),('SSP','728',2,1),('STN','930',2,1),('SVC','222',2,1),('SYP','760',2,1),('SZL','748',2,1),('THB','764',2,1),('TJS','972',2,1),('TMT','934',2,1),('TND','788',3,1),('TOP','776',2,1),('TRY','949',2,1),('TTD','780',2,1),('TWD','901',2,1),('TZS','834',2,1),('UAH','980',2,1),('UGX','800',0,1),('USD','840',2,1),('UYU','858',2,1),('UZS','860',2,1),('VED','926',2,1),('VES','928',2,1),('VND','704',0,1),('VUV','548',0,1),('WST','882',2,1),('XAF','950',0,1),('XCD','951',2,1),('XCG','532',2,1),('XOF','952',0,1),('XPF','953',0,1),('YER','886',2,1),('ZAR','710',2,1),('ZMW','967',2,1),('ZWG','924',2,1);
/*!40000 ALTER TABLE `iso4217_currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `language`
--

DROP TABLE IF EXISTS `language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `default_flag` tinyint(1) GENERATED ALWAYS AS (if((`is_default` = 1),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_language_code` (`code`),
  UNIQUE KEY `uq_language_default` (`default_flag`),
  CONSTRAINT `ck_language_default_is_active` CHECK (((`is_default` = 0) or (`is_active` = 1)))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language`
--

LOCK TABLES `language` WRITE;
/*!40000 ALTER TABLE `language` DISABLE KEYS */;
INSERT INTO `language` (`id`, `code`, `name`, `is_default`, `is_active`, `sort_order`) VALUES (1,'cs','Čeština',1,1,1),(2,'en','English',0,1,2);
/*!40000 ALTER TABLE `language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(100) NOT NULL,
  `file` varchar(100) NOT NULL,
  `checksum` char(32) NOT NULL,
  `executed` datetime NOT NULL,
  `ready` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_file` (`group`,`file`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'structures','2026-11-04-200000-init.sql','86b7c61d2d796d7424627729fd1cd894','2026-11-14 23:50:37',1),(2,'basic-data','2026-11-04-201000-basic-data.sql','17ec184d011791a25ccee55340b2bd2d','2026-11-14 23:50:39',1),(3,'dummy-data','2026-11-04-202000-demo-products.sql','fb9a1d3862b2b958997aec56c8d763f6','2026-11-14 23:50:39',1),(4,'structures','2026-11-15-120000-transactional-outbox.sql','122744743fe6b62a5c7cb58bab733f95','2026-11-15 05:22:20',1),(5,'structures','2026-11-15-130000-iso4217-currency-catalog.sql','5662472997226bc9a1f0627769dc45f6','2026-11-18 11:50:41',1),(6,'structures','2026-11-15-140000-normalize-iso4217-release-fk.sql','ad65dfa02d9223abb5c1b637a4291ea2','2026-11-18 11:50:42',1),(7,'structures','2026-11-15-150000-protect-variant-attribute-values.sql','2b1f4e6cb47b4c20193d275bfd23a4fc','2026-11-18 11:50:42',1),(8,'structures','2026-11-15-160000-add-language-foreign-keys-to-locales.sql','5bae8ac88b5752ba93f7cbc94b742f1e','2026-11-18 11:50:42',1),(9,'structures','2026-11-15-170000-order-accounting-invariants.sql','e511396333faa9b684160ee91c9809cd','2026-11-18 11:50:43',0);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `option_value_price`
--

DROP TABLE IF EXISTS `option_value_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `option_value_price` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_option_value_id` bigint unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `delta_minor` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_option_value_price` (`product_option_value_id`,`currency`),
  KEY `fk_option_value_price_currency` (`currency`),
  CONSTRAINT `fk_option_value_price_currency` FOREIGN KEY (`currency`) REFERENCES `currency` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_option_value_price_value` FOREIGN KEY (`product_option_value_id`) REFERENCES `product_option_value` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `option_value_price`
--

LOCK TABLES `option_value_price` WRITE;
/*!40000 ALTER TABLE `option_value_price` DISABLE KEYS */;
INSERT INTO `option_value_price` VALUES (1,1,'CZK',0),(2,1,'EUR',0),(3,2,'CZK',12000),(4,2,'EUR',490);
/*!40000 ALTER TABLE `option_value_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outbox_message`
--

DROP TABLE IF EXISTS `outbox_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outbox_message` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `event_name` varchar(190) NOT NULL,
  `payload` json NOT NULL,
  `status` enum('pending','processing','delivered','dead') NOT NULL DEFAULT 'pending',
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `available_at` datetime(6) NOT NULL,
  `locked_until` datetime(6) DEFAULT NULL,
  `lock_token` binary(16) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL,
  `delivered_at` datetime(6) DEFAULT NULL,
  `dead_at` datetime(6) DEFAULT NULL,
  `last_error` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_outbox_message_id` (`message_id`),
  UNIQUE KEY `uq_outbox_lock_token` (`lock_token`),
  KEY `idx_outbox_available` (`status`,`available_at`,`id`),
  KEY `idx_outbox_expired_lease` (`status`,`locked_until`,`id`),
  CONSTRAINT `ck_outbox_event_name_not_empty` CHECK ((char_length(trim(`event_name`)) > 0)),
  CONSTRAINT `ck_outbox_lock_state` CHECK ((((`status` = _utf8mb4'processing') and (`locked_until` is not null) and (`lock_token` is not null)) or ((`status` <> _utf8mb4'processing') and (`locked_until` is null) and (`lock_token` is null)))),
  CONSTRAINT `ck_outbox_payload_object` CHECK ((json_type(`payload`) = _utf8mb4'OBJECT')),
  CONSTRAINT `ck_outbox_processing_attempt` CHECK (((`status` <> _utf8mb4'processing') or (`attempts` > 0))),
  CONSTRAINT `ck_outbox_terminal_state` CHECK ((((`status` = _utf8mb4'delivered') and (`delivered_at` is not null) and (`dead_at` is null)) or ((`status` = _utf8mb4'dead') and (`delivered_at` is null) and (`dead_at` is not null)) or ((`status` in (_utf8mb4'pending',_utf8mb4'processing')) and (`delivered_at` is null) and (`dead_at` is null))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outbox_message`
--

LOCK TABLES `outbox_message` WRITE;
/*!40000 ALTER TABLE `outbox_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `outbox_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_method`
--

DROP TABLE IF EXISTS `payment_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_method` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_method_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_method`
--

LOCK TABLES `payment_method` WRITE;
/*!40000 ALTER TABLE `payment_method` DISABLE KEYS */;
INSERT INTO `payment_method` VALUES (1,'card',1,1),(2,'bank_transfer',1,2),(3,'cod',1,3);
/*!40000 ALTER TABLE `payment_method` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_method_translation`
--

DROP TABLE IF EXISTS `payment_method_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_method_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_method_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_method_translation` (`payment_method_id`,`locale`),
  KEY `idx_payment_method_translation_locale` (`locale`),
  CONSTRAINT `fk_payment_method_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_payment_method_translation_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_method_translation`
--

LOCK TABLES `payment_method_translation` WRITE;
/*!40000 ALTER TABLE `payment_method_translation` DISABLE KEYS */;
INSERT INTO `payment_method_translation` VALUES (1,1,'cs','Platba kartou'),(2,1,'en','Card payment'),(3,2,'cs','Bankovní převod'),(4,2,'en','Bank transfer'),(5,3,'cs','Dobírka'),(6,3,'en','Cash on delivery');
/*!40000 ALTER TABLE `payment_method_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `tax_class_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_code` (`code`),
  KEY `idx_product_status` (`status`),
  KEY `idx_product_tax_class` (`tax_class_id`),
  CONSTRAINT `fk_product_tax_class` FOREIGN KEY (`tax_class_id`) REFERENCES `tax_class` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,'tshirt-printed','active',1,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(2,'usb-c-cable','active',1,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(3,'gan-charger','active',1,'2026-11-15 00:50:39','2026-11-15 00:50:39');
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_attribute_value`
--

DROP TABLE IF EXISTS `product_attribute_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attribute_value` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `attribute_id` bigint unsigned NOT NULL,
  `attribute_value_id` bigint unsigned DEFAULT NULL,
  `value_number` decimal(20,6) DEFAULT NULL,
  `value_text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_attribute_value` (`product_id`,`attribute_id`),
  KEY `idx_product_attribute_value_filter_number` (`attribute_id`,`value_number`),
  KEY `idx_product_attribute_value_filter_enum` (`attribute_id`,`attribute_value_id`),
  KEY `fk_product_attribute_value_composite` (`attribute_value_id`,`attribute_id`),
  CONSTRAINT `fk_product_attribute_value_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attribute` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_product_attribute_value_composite` FOREIGN KEY (`attribute_value_id`, `attribute_id`) REFERENCES `attribute_value` (`id`, `attribute_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_product_attribute_value_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ck_product_attribute_value_exactly_one` CHECK (((((`attribute_value_id` is not null) + (`value_number` is not null)) + (`value_text` is not null)) = 1))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_attribute_value`
--

LOCK TABLES `product_attribute_value` WRITE;
/*!40000 ALTER TABLE `product_attribute_value` DISABLE KEYS */;
INSERT INTO `product_attribute_value` VALUES (1,3,7,NULL,1.000000,NULL);
/*!40000 ALTER TABLE `product_attribute_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_category`
--

DROP TABLE IF EXISTS `product_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_category` (`product_id`,`category_id`),
  KEY `idx_product_category_category` (`category_id`,`sort_order`),
  CONSTRAINT `fk_product_category_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_category_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_category`
--

LOCK TABLES `product_category` WRITE;
/*!40000 ALTER TABLE `product_category` DISABLE KEYS */;
INSERT INTO `product_category` VALUES (1,1,1,0),(2,2,3,0),(3,3,4,0);
/*!40000 ALTER TABLE `product_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_image`
--

DROP TABLE IF EXISTS `product_image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_image` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_product_image_product` (`product_id`,`sort_order`),
  KEY `fk_product_image_variant_composite` (`product_variant_id`,`product_id`),
  CONSTRAINT `fk_product_image_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_image_variant_composite` FOREIGN KEY (`product_variant_id`, `product_id`) REFERENCES `product_variant` (`id`, `product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_image`
--

LOCK TABLES `product_image` WRITE;
/*!40000 ALTER TABLE `product_image` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_option`
--

DROP TABLE IF EXISTS `product_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_option` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `code` varchar(100) NOT NULL,
  `type` enum('choice','text') NOT NULL DEFAULT 'choice',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_option` (`product_id`,`code`),
  UNIQUE KEY `uq_product_option_composite` (`id`,`product_id`),
  CONSTRAINT `fk_product_option_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_option`
--

LOCK TABLES `product_option` WRITE;
/*!40000 ALTER TABLE `product_option` DISABLE KEYS */;
INSERT INTO `product_option` VALUES (1,1,'print','choice',1,1),(2,1,'print_text','text',0,2);
/*!40000 ALTER TABLE `product_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_option_translation`
--

DROP TABLE IF EXISTS `product_option_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_option_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_option_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_option_translation` (`product_option_id`,`locale`),
  KEY `idx_product_option_translation_locale` (`locale`),
  CONSTRAINT `fk_product_option_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_product_option_translation_option` FOREIGN KEY (`product_option_id`) REFERENCES `product_option` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_option_translation`
--

LOCK TABLES `product_option_translation` WRITE;
/*!40000 ALTER TABLE `product_option_translation` DISABLE KEYS */;
INSERT INTO `product_option_translation` VALUES (1,1,'cs','Potisk'),(2,1,'en','Print'),(3,2,'cs','Text potisku'),(4,2,'en','Print text');
/*!40000 ALTER TABLE `product_option_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_option_value`
--

DROP TABLE IF EXISTS `product_option_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_option_value` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_option_id` bigint unsigned NOT NULL,
  `code` varchar(100) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_option_value` (`product_option_id`,`code`),
  UNIQUE KEY `uq_product_option_value_composite` (`id`,`product_option_id`),
  CONSTRAINT `fk_product_option_value_option` FOREIGN KEY (`product_option_id`) REFERENCES `product_option` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_option_value`
--

LOCK TABLES `product_option_value` WRITE;
/*!40000 ALTER TABLE `product_option_value` DISABLE KEYS */;
INSERT INTO `product_option_value` VALUES (1,1,'none',1),(2,1,'custom',2);
/*!40000 ALTER TABLE `product_option_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_option_value_translation`
--

DROP TABLE IF EXISTS `product_option_value_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_option_value_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_option_value_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `label` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_option_value_translation` (`product_option_value_id`,`locale`),
  KEY `idx_product_option_value_translation_locale` (`locale`),
  CONSTRAINT `fk_product_option_value_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_product_option_value_translation_value` FOREIGN KEY (`product_option_value_id`) REFERENCES `product_option_value` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_option_value_translation`
--

LOCK TABLES `product_option_value_translation` WRITE;
/*!40000 ALTER TABLE `product_option_value_translation` DISABLE KEYS */;
INSERT INTO `product_option_value_translation` VALUES (1,1,'cs','Bez potisku'),(2,1,'en','No print'),(3,2,'cs','Vlastní potisk'),(4,2,'en','Custom print');
/*!40000 ALTER TABLE `product_option_value_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_relation`
--

DROP TABLE IF EXISTS `product_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_relation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `related_product_id` bigint unsigned NOT NULL,
  `type` enum('accessory','alternative','compatible') NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_relation` (`product_id`,`related_product_id`,`type`),
  KEY `fk_product_relation_related` (`related_product_id`),
  CONSTRAINT `fk_product_relation_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_relation_related` FOREIGN KEY (`related_product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_relation`
--

LOCK TABLES `product_relation` WRITE;
/*!40000 ALTER TABLE `product_relation` DISABLE KEYS */;
INSERT INTO `product_relation` VALUES (1,3,2,'accessory',1),(2,2,3,'accessory',1);
/*!40000 ALTER TABLE `product_relation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_translation`
--

DROP TABLE IF EXISTS `product_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_description` text,
  `description` mediumtext,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_translation` (`product_id`,`locale`),
  UNIQUE KEY `uq_product_translation_slug` (`locale`,`slug`),
  KEY `idx_product_translation_locale` (`locale`),
  CONSTRAINT `fk_product_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_product_translation_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_translation`
--

LOCK TABLES `product_translation` WRITE;
/*!40000 ALTER TABLE `product_translation` DISABLE KEYS */;
INSERT INTO `product_translation` VALUES (1,1,'cs','Tričko s potiskem','tricko-s-potiskem','Bavlněné tričko.','<p>Pohodlné tričko.</p>','Tričko','Popis'),(2,1,'en','Printed T-shirt','printed-t-shirt','Cotton T-shirt.','<p>Comfortable t-shirt.</p>','T-shirt','Desc'),(3,2,'cs','USB-C kabel','usb-c-kabel','Odolný kabel.','<p>Kabel.</p>','Kabel','Popis'),(4,2,'en','USB-C cable','usb-c-cable','Durable cable.','<p>Cable.</p>','Cable','Desc'),(5,3,'cs','GaN nabíječka','gan-nabijecka','Nabíječka GaN.','<p>GaN.</p>','Nabíječka','Popis'),(6,3,'en','GaN charger','gan-charger','GaN charger.','<p>GaN.</p>','Charger','Desc');
/*!40000 ALTER TABLE `product_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variant`
--

DROP TABLE IF EXISTS `product_variant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variant` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `sku` varchar(64) NOT NULL,
  `combination_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `weight_g` int unsigned DEFAULT NULL,
  `length_mm` int unsigned DEFAULT NULL,
  `width_mm` int unsigned DEFAULT NULL,
  `height_mm` int unsigned DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `default_flag` tinyint(1) GENERATED ALWAYS AS (if((`is_default` = 1),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_variant_sku` (`sku`),
  UNIQUE KEY `uq_product_variant_combination` (`product_id`,`combination_hash`),
  UNIQUE KEY `uq_product_variant_composite` (`id`,`product_id`),
  UNIQUE KEY `uq_product_variant_single_default` (`product_id`,`default_flag`),
  CONSTRAINT `fk_product_variant_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variant`
--

LOCK TABLES `product_variant` WRITE;
/*!40000 ALTER TABLE `product_variant` DISABLE KEYS */;
INSERT INTO `product_variant` (`id`, `product_id`, `sku`, `combination_hash`, `is_default`, `is_active`, `weight_g`, `length_mm`, `width_mm`, `height_mm`, `sort_order`, `created_at`, `updated_at`) VALUES (1,1,'TSHIRT-BK-S','a5c64cc0bbcaaef2681de05a91f2dc6fa69ae6493fde60cfc5e5194e6efa5b44',1,1,180,NULL,NULL,NULL,1,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(2,1,'TSHIRT-BK-M','4e0448c778d6f3b04a682acaf59a113f3bca8a272f2d38e51ed9538a638af7d2',0,1,190,NULL,NULL,NULL,2,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(3,1,'TSHIRT-BK-L','981ce4c17535f0e722be3f78f957c8959e791da10419b3498dc13ccc9c12ee58',0,1,200,NULL,NULL,NULL,3,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(4,1,'TSHIRT-WH-S','5ba01a475a4a6a7458642578be85e037fa9666da767c9e3882eea3370cfefee7',0,1,180,NULL,NULL,NULL,4,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(5,1,'TSHIRT-WH-M','be0748e6652e2611bcf55d3aa529c3b7d0ea570c83b97b15dec9f00f1d66d552',0,1,190,NULL,NULL,NULL,5,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(6,1,'TSHIRT-WH-L','96062d2c0a935ee78153803200e1aec6ae68566a193e56f6962981510b82f0dc',0,1,200,NULL,NULL,NULL,6,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(7,2,'CABLE-CC-1M-BK','e8bf8cb412a296a803724543bd4a8e758f132051a3a81492206d2a263b504812',1,1,30,NULL,NULL,NULL,1,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(8,2,'CABLE-CC-2M-BK','89666202dc7aaee8af4875bbbc8721b8bfc30a9d0fb813d7e6a59bac97149e1b',0,1,45,NULL,NULL,NULL,2,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(9,2,'CABLE-CC-2M-WH','87373876aafa4dc02529a9af3e84f927142d14740c9a1da1b5c81152d6817e80',0,1,45,NULL,NULL,NULL,3,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(10,2,'CABLE-CA-1M-BK','5052e212d91440e379177c07ff851634dda4c0a13a1fb4ed746cab1878334d62',0,1,30,NULL,NULL,NULL,4,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(11,2,'CABLE-CA-2M-BK','b5a8a7526742e7184e84da7bca2f13578339232b7403fe34b284ec4df28fac78',0,1,45,NULL,NULL,NULL,5,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(12,3,'GAN-1X65-WH','322725b15f2390c5b39b4b2e7d9df553fc9275e30dd80cc28015b0d77d07f289',1,1,90,NULL,NULL,NULL,1,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(13,3,'GAN-2X65-BK','2c5401550f6d3623478bf6c883c6bc2b18d9efc29ea1aee9ceee1941220f860c',0,1,120,NULL,NULL,NULL,2,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(14,3,'GAN-2X100-BK','e7d8c8ed3ce02826fcac6dee96a38501894fc0dc0173a18e8c8594d0e2f03dc1',0,1,180,NULL,NULL,NULL,3,'2026-11-15 00:50:39','2026-11-15 00:50:39'),(15,3,'GAN-3X100-BK','aeeb1adfd44b79c32136b7f3b7ff4ace877f52c17ea991545545d601b5b088e5',0,1,220,NULL,NULL,NULL,4,'2026-11-15 00:50:39','2026-11-15 00:50:39');
/*!40000 ALTER TABLE `product_variant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variant_attribute_value`
--

DROP TABLE IF EXISTS `product_variant_attribute_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variant_attribute_value` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `attribute_id` bigint unsigned NOT NULL,
  `attribute_value_id` bigint unsigned DEFAULT NULL,
  `value_number` decimal(20,6) DEFAULT NULL,
  `value_text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_variant_attribute_value` (`product_variant_id`,`attribute_id`),
  KEY `idx_product_variant_attribute_value_filter_number` (`attribute_id`,`value_number`),
  KEY `idx_product_variant_attribute_value_filter_enum` (`attribute_id`,`attribute_value_id`),
  KEY `fk_product_variant_attribute_value_product_variant` (`product_variant_id`,`product_id`),
  KEY `fk_pvav_axis_restrict` (`product_id`,`attribute_id`),
  KEY `fk_pvav_value_restrict` (`attribute_value_id`,`attribute_id`),
  CONSTRAINT `fk_product_variant_attribute_value_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attribute` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_product_variant_attribute_value_product_variant` FOREIGN KEY (`product_variant_id`, `product_id`) REFERENCES `product_variant` (`id`, `product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pvav_axis_restrict` FOREIGN KEY (`product_id`, `attribute_id`) REFERENCES `product_variant_axis` (`product_id`, `attribute_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pvav_value_restrict` FOREIGN KEY (`attribute_value_id`, `attribute_id`) REFERENCES `attribute_value` (`id`, `attribute_id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_product_variant_attribute_value_exactly_one` CHECK (((((`attribute_value_id` is not null) + (`value_number` is not null)) + (`value_text` is not null)) = 1))
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variant_attribute_value`
--

LOCK TABLES `product_variant_attribute_value` WRITE;
/*!40000 ALTER TABLE `product_variant_attribute_value` DISABLE KEYS */;
INSERT INTO `product_variant_attribute_value` VALUES (1,1,1,1,1,NULL,NULL),(2,1,1,2,3,NULL,NULL),(3,2,1,1,1,NULL,NULL),(4,2,1,2,4,NULL,NULL),(5,3,1,1,1,NULL,NULL),(6,3,1,2,5,NULL,NULL),(7,4,1,1,2,NULL,NULL),(8,4,1,2,3,NULL,NULL),(9,5,1,1,2,NULL,NULL),(10,5,1,2,4,NULL,NULL),(11,6,1,1,2,NULL,NULL),(12,6,1,2,5,NULL,NULL),(13,7,2,3,NULL,1.000000,NULL),(14,7,2,4,NULL,2.000000,NULL),(15,7,2,1,1,NULL,NULL),(16,8,2,3,NULL,2.000000,NULL),(17,8,2,4,NULL,2.000000,NULL),(18,8,2,1,1,NULL,NULL),(19,9,2,3,NULL,2.000000,NULL),(20,9,2,4,NULL,2.000000,NULL),(21,9,2,1,2,NULL,NULL),(22,10,2,3,NULL,1.000000,NULL),(23,10,2,4,NULL,1.000000,NULL),(24,10,2,1,1,NULL,NULL),(25,11,2,3,NULL,2.000000,NULL),(26,11,2,4,NULL,1.000000,NULL),(27,11,2,1,1,NULL,NULL),(28,12,3,5,NULL,1.000000,NULL),(29,12,3,6,NULL,65.000000,NULL),(30,12,3,1,2,NULL,NULL),(31,13,3,5,NULL,2.000000,NULL),(32,13,3,6,NULL,65.000000,NULL),(33,13,3,1,1,NULL,NULL),(34,14,3,5,NULL,2.000000,NULL),(35,14,3,6,NULL,100.000000,NULL),(36,14,3,1,1,NULL,NULL),(37,15,3,5,NULL,3.000000,NULL),(38,15,3,6,NULL,100.000000,NULL),(39,15,3,1,1,NULL,NULL);
/*!40000 ALTER TABLE `product_variant_attribute_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variant_axis`
--

DROP TABLE IF EXISTS `product_variant_axis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variant_axis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `attribute_id` bigint unsigned NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_variant_axis` (`product_id`,`attribute_id`),
  KEY `fk_product_variant_axis_attribute` (`attribute_id`),
  CONSTRAINT `fk_product_variant_axis_attribute` FOREIGN KEY (`attribute_id`) REFERENCES `attribute` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_product_variant_axis_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variant_axis`
--

LOCK TABLES `product_variant_axis` WRITE;
/*!40000 ALTER TABLE `product_variant_axis` DISABLE KEYS */;
INSERT INTO `product_variant_axis` VALUES (1,1,1,1),(2,1,2,2),(3,2,3,1),(4,2,4,2),(5,2,1,3),(6,3,5,1),(7,3,6,2),(8,3,1,3);
/*!40000 ALTER TABLE `product_variant_axis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_method`
--

DROP TABLE IF EXISTS `shipping_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_method` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `tax_class_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shipping_method_code` (`code`),
  KEY `fk_shipping_method_tax_class` (`tax_class_id`),
  CONSTRAINT `fk_shipping_method_tax_class` FOREIGN KEY (`tax_class_id`) REFERENCES `tax_class` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_method`
--

LOCK TABLES `shipping_method` WRITE;
/*!40000 ALTER TABLE `shipping_method` DISABLE KEYS */;
INSERT INTO `shipping_method` VALUES (1,'personal_pickup',1,1,1),(2,'pickup_point',1,1,2),(3,'courier',1,1,3);
/*!40000 ALTER TABLE `shipping_method` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_method_price`
--

DROP TABLE IF EXISTS `shipping_method_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_method_price` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipping_method_id` bigint unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `amount_minor` bigint NOT NULL,
  `tax_included` tinyint(1) NOT NULL DEFAULT '1',
  `free_from_minor` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shipping_method_price` (`shipping_method_id`,`currency`),
  KEY `fk_shipping_method_price_currency` (`currency`),
  CONSTRAINT `fk_shipping_method_price_currency` FOREIGN KEY (`currency`) REFERENCES `currency` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_shipping_method_price_shipping_method` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ck_shipping_method_price_amount` CHECK ((`amount_minor` >= 0)),
  CONSTRAINT `ck_shipping_method_price_free_from` CHECK (((`free_from_minor` is null) or (`free_from_minor` >= 0)))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_method_price`
--

LOCK TABLES `shipping_method_price` WRITE;
/*!40000 ALTER TABLE `shipping_method_price` DISABLE KEYS */;
INSERT INTO `shipping_method_price` VALUES (1,1,'CZK',0,1,NULL),(2,1,'EUR',0,1,NULL),(3,2,'CZK',6900,1,150000),(4,2,'EUR',290,1,6000),(5,3,'CZK',11900,1,250000),(6,3,'EUR',490,1,10000);
/*!40000 ALTER TABLE `shipping_method_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_method_translation`
--

DROP TABLE IF EXISTS `shipping_method_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_method_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipping_method_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shipping_method_translation` (`shipping_method_id`,`locale`),
  KEY `idx_shipping_method_translation_locale` (`locale`),
  CONSTRAINT `fk_shipping_method_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_shipping_method_translation_shipping_method` FOREIGN KEY (`shipping_method_id`) REFERENCES `shipping_method` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_method_translation`
--

LOCK TABLES `shipping_method_translation` WRITE;
/*!40000 ALTER TABLE `shipping_method_translation` DISABLE KEYS */;
INSERT INTO `shipping_method_translation` VALUES (1,1,'cs','Osobní odběr'),(2,1,'en','Personal pickup'),(3,2,'cs','Výdejní místo'),(4,2,'en','Pickup point'),(5,3,'cs','Kurýr'),(6,3,'en','Courier');
/*!40000 ALTER TABLE `shipping_method_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_order`
--

DROP TABLE IF EXISTS `shop_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_order` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(26) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `status` varchar(32) NOT NULL,
  `locale` varchar(10) NOT NULL,
  `currency` char(3) NOT NULL,
  `prices_include_tax` tinyint(1) NOT NULL DEFAULT '1',
  `subtotal_net_minor` bigint NOT NULL,
  `adjustments_net_minor` bigint NOT NULL DEFAULT '0',
  `tax_total_minor` bigint NOT NULL,
  `grand_total_minor` bigint NOT NULL,
  `billing_address` json NOT NULL,
  `shipping_address` json NOT NULL,
  `customer_note` varchar(1000) DEFAULT NULL,
  `placed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shop_order_public_id` (`public_id`),
  UNIQUE KEY `uq_shop_order_number` (`order_number`),
  KEY `idx_shop_order_customer` (`customer_id`),
  KEY `idx_shop_order_status` (`status`,`placed_at`),
  KEY `fk_shop_order_currency` (`currency`),
  KEY `fk_shop_order_language` (`locale`),
  CONSTRAINT `fk_shop_order_currency` FOREIGN KEY (`currency`) REFERENCES `currency` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_shop_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_shop_order_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_order`
--

LOCK TABLES `shop_order` WRITE;
/*!40000 ALTER TABLE `shop_order` DISABLE KEYS */;
INSERT INTO `shop_order` VALUES (1,'01J9ZK3Q8M5N7V2R4T6X8B0D3F','2026-000001',1,'jana.novakova@example.com','paid','cs','CZK',1,42893,9835,11072,63800,'{\"name\": \"Jana\"}','{\"name\": \"Jana\"}',NULL,'2026-11-02 10:15:00','2026-11-02 10:12:00','2026-11-18 12:50:43'),(2,'01J9ZK3Q8M5N7V2R4T6X8B0E4G','2026-000002',2,'john.smith@example.com','new','en','EUR',1,4288,240,950,5478,'{\"name\": \"John\"}','{\"name\": \"John\"}',NULL,'2026-11-03 18:40:00','2026-11-03 18:36:00','2026-11-18 12:50:43');
/*!40000 ALTER TABLE `shop_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_order_adjustment`
--

DROP TABLE IF EXISTS `shop_order_adjustment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_order_adjustment` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_order_id` bigint unsigned NOT NULL,
  `type` enum('shipping','payment_fee','discount','surcharge') NOT NULL,
  `code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount_net_minor` bigint NOT NULL,
  `amount_tax_minor` bigint NOT NULL,
  `amount_gross_minor` bigint NOT NULL,
  `tax_rate_bp` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shop_order_adjustment_order` (`shop_order_id`),
  CONSTRAINT `fk_shop_order_adjustment_order` FOREIGN KEY (`shop_order_id`) REFERENCES `shop_order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ck_shop_order_adjustment_amounts` CHECK ((`amount_gross_minor` = (`amount_net_minor` + `amount_tax_minor`))),
  CONSTRAINT `ck_shop_order_adjustment_sign` CHECK ((((`type` = _utf8mb4'discount') and (`amount_gross_minor` <= 0)) or ((`type` in (_utf8mb4'shipping',_utf8mb4'payment_fee',_utf8mb4'surcharge')) and (`amount_gross_minor` >= 0))))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_order_adjustment`
--

LOCK TABLES `shop_order_adjustment` WRITE;
/*!40000 ALTER TABLE `shop_order_adjustment` DISABLE KEYS */;
INSERT INTO `shop_order_adjustment` VALUES (1,1,'shipping','courier','Kurýr',9835,2065,11900,2100),(2,2,'shipping','pickup_point','Pickup point',240,50,290,2100);
/*!40000 ALTER TABLE `shop_order_adjustment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_order_item`
--

DROP TABLE IF EXISTS `shop_order_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_order_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `sku` varchar(64) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_label` varchar(255) DEFAULT NULL,
  `attributes_snapshot` json DEFAULT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `base_unit_price_minor` bigint NOT NULL,
  `options_unit_total_minor` bigint NOT NULL DEFAULT '0',
  `tax_rate_bp` int unsigned NOT NULL,
  `line_net_minor` bigint NOT NULL,
  `line_tax_minor` bigint NOT NULL,
  `line_gross_minor` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shop_order_item_order` (`shop_order_id`),
  KEY `idx_shop_order_item_variant` (`product_variant_id`),
  CONSTRAINT `fk_shop_order_item_order` FOREIGN KEY (`shop_order_id`) REFERENCES `shop_order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ck_shop_order_item_quantity` CHECK ((`quantity` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_order_item`
--

LOCK TABLES `shop_order_item` WRITE;
/*!40000 ALTER TABLE `shop_order_item` DISABLE KEYS */;
INSERT INTO `shop_order_item` VALUES (1,1,1,2,'TSHIRT-BK-M','Tričko s potiskem','Černá / M','{\"color\": \"black\"}',1.000,39900,12000,2100,42893,9007,51900),(2,2,2,8,'CABLE-CC-2M-BK','USB-C cable','Black','{\"color\": \"black\"}',2.000,799,0,2100,1321,277,1598),(3,2,3,13,'GAN-2X65-BK','GaN charger','Black','{\"color\": \"black\"}',1.000,3590,0,2100,2967,623,3590);
/*!40000 ALTER TABLE `shop_order_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_order_item_option`
--

DROP TABLE IF EXISTS `shop_order_item_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_order_item_option` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_order_item_id` bigint unsigned NOT NULL,
  `option_name` varchar(255) NOT NULL,
  `value_label` varchar(500) NOT NULL,
  `price_delta_minor` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_shop_order_item_option_item` (`shop_order_item_id`),
  CONSTRAINT `fk_shop_order_item_option_item` FOREIGN KEY (`shop_order_item_id`) REFERENCES `shop_order_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_order_item_option`
--

LOCK TABLES `shop_order_item_option` WRITE;
/*!40000 ALTER TABLE `shop_order_item_option` DISABLE KEYS */;
INSERT INTO `shop_order_item_option` VALUES (1,1,'Potisk','Vlastní potisk',12000);
/*!40000 ALTER TABLE `shop_order_item_option` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_order_status_history`
--

DROP TABLE IF EXISTS `shop_order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_order_status_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_order_id` bigint unsigned NOT NULL,
  `from_status` varchar(32) DEFAULT NULL,
  `to_status` varchar(32) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shop_order_status_history_order` (`shop_order_id`,`created_at`),
  CONSTRAINT `fk_shop_order_status_history_order` FOREIGN KEY (`shop_order_id`) REFERENCES `shop_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_order_status_history`
--

LOCK TABLES `shop_order_status_history` WRITE;
/*!40000 ALTER TABLE `shop_order_status_history` DISABLE KEYS */;
INSERT INTO `shop_order_status_history` VALUES (1,1,NULL,'new','Vytvořeno','2026-11-02 10:15:00'),(2,1,'new','paid','Platba potvrzena','2026-11-02 10:16:00'),(3,2,NULL,'new','Created','2026-11-03 18:40:00');
/*!40000 ALTER TABLE `shop_order_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_item`
--

DROP TABLE IF EXISTS `stock_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint unsigned NOT NULL,
  `track_stock` tinyint(1) NOT NULL DEFAULT '1',
  `allow_backorder` tinyint(1) NOT NULL DEFAULT '0',
  `quantity_on_hand` decimal(12,3) NOT NULL DEFAULT '0.000',
  `quantity_reserved` decimal(12,3) NOT NULL DEFAULT '0.000',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_item_variant` (`product_variant_id`),
  CONSTRAINT `fk_stock_item_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variant` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_stock_item_integrity` CHECK (((`allow_backorder` = 1) or ((`quantity_on_hand` >= 0) and (`quantity_on_hand` >= `quantity_reserved`)))),
  CONSTRAINT `ck_stock_item_reserved` CHECK ((`quantity_reserved` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_item`
--

LOCK TABLES `stock_item` WRITE;
/*!40000 ALTER TABLE `stock_item` DISABLE KEYS */;
INSERT INTO `stock_item` VALUES (1,1,1,0,25.000,0.000,'2026-11-15 00:50:39'),(2,2,1,0,40.000,0.000,'2026-11-15 00:50:39'),(3,3,1,0,30.000,0.000,'2026-11-15 00:50:39'),(4,4,1,0,20.000,0.000,'2026-11-15 00:50:39'),(5,5,1,0,0.000,0.000,'2026-11-15 00:50:39'),(6,6,1,0,15.000,0.000,'2026-11-15 00:50:39'),(7,7,1,0,100.000,0.000,'2026-11-15 00:50:39'),(8,8,1,0,80.000,0.000,'2026-11-15 00:50:39'),(9,9,1,0,60.000,0.000,'2026-11-15 00:50:39'),(10,10,1,0,120.000,0.000,'2026-11-15 00:50:39'),(11,11,1,0,90.000,0.000,'2026-11-15 00:50:39'),(12,12,1,0,35.000,0.000,'2026-11-15 00:50:39'),(13,13,1,0,50.000,0.000,'2026-11-15 00:50:39'),(14,14,1,1,0.000,0.000,'2026-11-15 00:50:39'),(15,15,1,0,12.000,0.000,'2026-11-15 00:50:39');
/*!40000 ALTER TABLE `stock_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movement`
--

DROP TABLE IF EXISTS `stock_movement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movement` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint unsigned NOT NULL,
  `delta` decimal(12,3) NOT NULL,
  `reason` enum('receipt','reservation','release','sale','return','adjustment') NOT NULL,
  `reference_type` varchar(32) DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_movement_variant` (`product_variant_id`,`created_at`),
  KEY `idx_stock_movement_reference` (`reference_type`,`reference_id`),
  CONSTRAINT `fk_stock_movement_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variant` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movement`
--

LOCK TABLES `stock_movement` WRITE;
/*!40000 ALTER TABLE `stock_movement` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_class`
--

DROP TABLE IF EXISTS `tax_class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_class` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tax_class_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_class`
--

LOCK TABLES `tax_class` WRITE;
/*!40000 ALTER TABLE `tax_class` DISABLE KEYS */;
INSERT INTO `tax_class` VALUES (1,'standard','Základní sazba'),(2,'reduced','Snížená sazba');
/*!40000 ALTER TABLE `tax_class` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rate`
--

DROP TABLE IF EXISTS `tax_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_rate` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tax_class_id` bigint unsigned NOT NULL,
  `country_code` char(2) NOT NULL,
  `rate_bp` int unsigned NOT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tax_rate` (`tax_class_id`,`country_code`,`valid_from`),
  CONSTRAINT `fk_tax_rate_class` FOREIGN KEY (`tax_class_id`) REFERENCES `tax_class` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_tax_rate_dates` CHECK (((`valid_to` is null) or (`valid_to` >= `valid_from`)))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rate`
--

LOCK TABLES `tax_rate` WRITE;
/*!40000 ALTER TABLE `tax_rate` DISABLE KEYS */;
INSERT INTO `tax_rate` VALUES (1,1,'CZ',2100,'2024-01-01',NULL),(2,2,'CZ',1200,'2024-01-01',NULL);
/*!40000 ALTER TABLE `tax_rate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation_message`
--

DROP TABLE IF EXISTS `translation_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translation_message` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(50) NOT NULL,
  `message_key` varchar(190) NOT NULL,
  `locale` varchar(10) NOT NULL,
  `message_text` text NOT NULL,
  `source` enum('core','custom') NOT NULL DEFAULT 'core',
  `is_customized` tinyint(1) NOT NULL DEFAULT '0',
  `default_hash` char(40) DEFAULT NULL,
  `is_outdated` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_translation_message` (`domain`,`locale`,`message_key`),
  KEY `idx_translation_message_review` (`is_outdated`,`domain`),
  KEY `idx_translation_message_locale` (`locale`),
  CONSTRAINT `fk_translation_message_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_message`
--

LOCK TABLES `translation_message` WRITE;
/*!40000 ALTER TABLE `translation_message` DISABLE KEYS */;
INSERT INTO `translation_message` VALUES (1,'catalog','button.add_to_cart','cs','Přidat do košíku','core',0,NULL,0,'2026-11-15 00:50:39',NULL),(2,'catalog','button.add_to_cart','en','Add to cart','core',0,NULL,0,'2026-11-15 00:50:39',NULL);
/*!40000 ALTER TABLE `translation_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit`
--

DROP TABLE IF EXISTS `unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unit_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit`
--

LOCK TABLES `unit` WRITE;
/*!40000 ALTER TABLE `unit` DISABLE KEYS */;
INSERT INTO `unit` VALUES (1,'pcs',1,1),(2,'g',1,2),(3,'kg',1,3),(4,'m',1,4),(5,'m2',1,5),(6,'W',1,6);
/*!40000 ALTER TABLE `unit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_translation`
--

DROP TABLE IF EXISTS `unit_translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_translation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unit_translation` (`unit_id`,`locale`),
  KEY `idx_unit_translation_locale` (`locale`),
  CONSTRAINT `fk_unit_translation_language` FOREIGN KEY (`locale`) REFERENCES `language` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_unit_translation_unit` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_translation`
--

LOCK TABLES `unit_translation` WRITE;
/*!40000 ALTER TABLE `unit_translation` DISABLE KEYS */;
INSERT INTO `unit_translation` VALUES (1,1,'cs','ks','{count, plural, one {kus} few {kusy} many {kusu} other {kusů}}'),(2,1,'en','pcs','{count, plural, one {piece} other {pieces}}'),(3,2,'cs','g','{count, plural, one {gram} few {gramy} many {gramu} other {gramů}}'),(4,2,'en','g','{count, plural, one {gram} other {grams}}'),(5,3,'cs','kg','{count, plural, one {kilogram} few {kilogramy} many {kilogramu} other {kilogramů}}'),(6,3,'en','kg','{count, plural, one {kilogram} other {kilograms}}'),(7,4,'cs','m','{count, plural, one {metr} few {metry} many {metru} other {metrů}}'),(8,4,'en','m','{count, plural, one {meter} other {meters}}'),(9,5,'cs','m²','{count, plural, one {metr čtvereční} few {metry čtvereční} many {metru čtverečního} other {metrů čtverečních}}'),(10,5,'en','m²','{count, plural, one {square meter} other {square meters}}'),(11,6,'cs','W','{count, plural, one {watt} few {watty} many {wattu} other {wattů}}'),(12,6,'en','W','{count, plural, one {watt} other {watts}}');
/*!40000 ALTER TABLE `unit_translation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variant_price`
--

DROP TABLE IF EXISTS `variant_price`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_price` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `amount_minor` bigint NOT NULL,
  `tax_included` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_variant_price` (`product_variant_id`,`currency`),
  KEY `fk_variant_price_currency` (`currency`),
  CONSTRAINT `fk_variant_price_currency` FOREIGN KEY (`currency`) REFERENCES `currency` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_variant_price_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variant` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_variant_price_nonneg` CHECK ((`amount_minor` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variant_price`
--

LOCK TABLES `variant_price` WRITE;
/*!40000 ALTER TABLE `variant_price` DISABLE KEYS */;
INSERT INTO `variant_price` VALUES (1,1,'CZK',39900,1),(2,1,'EUR',1690,1),(3,2,'CZK',39900,1),(4,2,'EUR',1690,1),(5,3,'CZK',39900,1),(6,3,'EUR',1690,1),(7,4,'CZK',39900,1),(8,4,'EUR',1690,1),(9,5,'CZK',39900,1),(10,5,'EUR',1690,1),(11,6,'CZK',39900,1),(12,6,'EUR',1690,1),(13,7,'CZK',14900,1),(14,7,'EUR',599,1),(15,8,'CZK',19900,1),(16,8,'EUR',799,1),(17,9,'CZK',19900,1),(18,9,'EUR',799,1),(19,10,'CZK',9900,1),(20,10,'EUR',399,1),(21,11,'CZK',12900,1),(22,11,'EUR',499,1),(23,12,'CZK',69900,1),(24,12,'EUR',2790,1),(25,13,'CZK',89900,1),(26,13,'EUR',3590,1),(27,14,'CZK',119900,1),(28,14,'EUR',4790,1),(29,15,'CZK',149900,1),(30,15,'EUR',5990,1);
/*!40000 ALTER TABLE `variant_price` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variant_price_history`
--

DROP TABLE IF EXISTS `variant_price_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_price_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_variant_id` bigint unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `amount_minor` bigint NOT NULL,
  `tax_included` tinyint(1) NOT NULL DEFAULT '1',
  `effective_at` datetime(6) NOT NULL,
  `change_source` enum('initial','manual','promotion','import','system') NOT NULL DEFAULT 'manual',
  `note` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_price_history_point` (`product_variant_id`,`currency`,`effective_at`),
  KEY `fk_variant_price_history_currency` (`currency`),
  CONSTRAINT `fk_variant_price_history_currency` FOREIGN KEY (`currency`) REFERENCES `currency` (`code`) ON DELETE RESTRICT,
  CONSTRAINT `fk_variant_price_history_variant` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variant` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ck_variant_price_history_amount` CHECK ((`amount_minor` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variant_price_history`
--

LOCK TABLES `variant_price_history` WRITE;
/*!40000 ALTER TABLE `variant_price_history` DISABLE KEYS */;
INSERT INTO `variant_price_history` VALUES (1,1,'CZK',39900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(2,1,'EUR',1690,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(3,2,'CZK',39900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(4,2,'EUR',1690,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(5,3,'CZK',39900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(6,3,'EUR',1690,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(7,4,'CZK',39900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(8,4,'EUR',1690,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(9,5,'CZK',39900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(10,5,'EUR',1690,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(11,6,'CZK',39900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(12,6,'EUR',1690,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(13,7,'CZK',14900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(14,7,'EUR',599,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(15,8,'CZK',19900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(16,8,'EUR',799,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(17,9,'CZK',19900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(18,9,'EUR',799,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(19,10,'CZK',9900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(20,10,'EUR',399,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(21,11,'CZK',12900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(22,11,'EUR',499,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(23,12,'CZK',69900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(24,12,'EUR',2790,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(25,13,'CZK',89900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(26,13,'EUR',3590,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(27,14,'CZK',119900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(28,14,'EUR',4790,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(29,15,'CZK',149900,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39'),(30,15,'EUR',5990,1,'2026-11-01 00:00:00.000000','initial','Počáteční demo cena','2026-11-15 00:50:39');
/*!40000 ALTER TABLE `variant_price_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'argomi_bestshop'
--

--
-- Dumping routines for database 'argomi_bestshop'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-18 12:57:47
