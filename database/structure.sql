
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
-- Table structure for table `amounts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amounts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `originalValue` bigint(20) NOT NULL,
  `originalUnit` varchar(4) NOT NULL,
  `value` bigint(20) NOT NULL,
  `unit` varchar(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `originalValue` (`originalValue`),
  KEY `value` (`value`),
  KEY `unit` (`unit`)
) ENGINE=TokuDB AUTO_INCREMENT=48277 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_rates`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_rates` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` varchar(100) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_and_date` (`ip`(20),`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bulletins`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bulletins` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `external_id` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `fetched` datetime DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT '0',
  `fixes` int(11) NOT NULL DEFAULT '0',
  `last_error` longtext,
  `parsed` datetime DEFAULT NULL,
  `bulletin_schema` varchar(15) NOT NULL,
  `format` varchar(40) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `done` datetime DEFAULT NULL,
  `status` varchar(25) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`bulletin_schema`,`date`,`external_id`) USING BTREE,
  KEY `created` (`bulletin_schema`,`created`) USING BTREE
) ENGINE=TokuDB AUTO_INCREMENT=521256 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(400) NOT NULL,
  `cache_value` text NOT NULL,
  `expire` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`,`expire`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB AUTO_INCREMENT=634 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `entities`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entities` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) DEFAULT NULL,
  `subtype` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `fetched` datetime NOT NULL,
  `created` date DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `national_id` varchar(30) DEFAULT NULL,
  `parent_id` bigint(20) DEFAULT NULL,
  `keywords` varchar(400) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`) USING HASH,
  KEY `name` (`name`),
  KEY `last_name_2` (`first_name`),
  KEY `type` (`type`) USING HASH,
  KEY `country_and_subtype` (`country`,`subtype`) USING HASH,
  KEY `type_and_subtype` (`type`,`subtype`) USING HASH,
  KEY `country` (`country`) USING HASH
) ENGINE=TokuDB AUTO_INCREMENT=138926 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `location_cities`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_cities` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `county_id` bigint(20) NOT NULL,
  `state_id` bigint(20) NOT NULL,
  `country` varchar(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_id` (`country`,`name`(20)),
  KEY `state_id` (`state_id`),
  KEY `county_id` (`county_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `location_counties`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_counties` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `state_id` bigint(20) NOT NULL,
  `country` varchar(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_id` (`country`,`name`(20)),
  KEY `state_id` (`state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `location_states`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_states` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `country` varchar(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country`,`name`(20))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `locations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `original` varchar(200) NOT NULL,
  `label` varchar(200) DEFAULT NULL,
  `country` varchar(5) DEFAULT NULL,
  `state` bigint(20) DEFAULT NULL,
  `county` bigint(20) DEFAULT NULL,
  `city` bigint(20) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `housenumber` varchar(8) DEFAULT NULL,
  `postalcode` varchar(8) DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `relevance` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_and_label` (`country`,`label`(20))
) ENGINE=TokuDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `locks`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `target` varchar(30) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `target` (`target`),
  KEY `created` (`created`)
) ENGINE=TokuDB AUTO_INCREMENT=415 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `names`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `names` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `name` varchar(80) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `type` (`type`)
) ENGINE=TokuDB AUTO_INCREMENT=343198 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `options`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `options` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=TokuDB AUTO_INCREMENT=76089 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `precepts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `precepts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `bulletin_id` bigint(20) NOT NULL,
  `issuing_id` bigint(20) DEFAULT NULL,
  `title` longtext,
  `text` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `issuing_id` (`issuing_id`),
  KEY `bulletin_id` (`bulletin_id`)
) ENGINE=TokuDB AUTO_INCREMENT=112863 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spiders`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spiders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulletin_schema` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `pid` bigint(20) DEFAULT NULL,
  `date_back` date DEFAULT NULL,
  `workers_count` mediumint(9) NOT NULL,
  `cpu_rate` tinyint(4) NOT NULL,
  `extract` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `bulletin_schema` (`bulletin_schema`),
  KEY `status` (`status`)
) ENGINE=TokuDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `status_has_service`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_has_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) NOT NULL,
  `service_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_id` (`status_id`),
  KEY `service_id` (`service_id`)
) ENGINE=TokuDB AUTO_INCREMENT=30057 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statuses`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `precept_id` bigint(20) NOT NULL,
  `target_id` bigint(20) DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `action` varchar(30) NOT NULL,
  `start` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `amount` bigint(20) DEFAULT NULL,
  `related_id` bigint(20) DEFAULT NULL,
  `contract_type_id` bigint(6) DEFAULT NULL,
  `sector_id` bigint(6) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  KEY `related_query` (`related_id`,`type`,`action`) USING HASH,
  KEY `target_query` (`target_id`,`type`,`action`) USING HASH,
  KEY `precept_id` (`precept_id`) USING HASH,
  KEY `target_id` (`target_id`) USING HASH
) ENGINE=TokuDB AUTO_INCREMENT=148038 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `workers`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spider_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(20) NOT NULL,
  `pid` bigint(20) DEFAULT NULL,
  `started` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=TokuDB AUTO_INCREMENT=57 DEFAULT CHARSET=latin1 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

