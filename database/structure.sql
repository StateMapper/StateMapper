
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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `original_value` bigint(20) NOT NULL,
  `original_unit` varchar(4) NOT NULL,
  `value` bigint(20) NOT NULL,
  `unit` varchar(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `originalValue` (`original_value`),
  KEY `value` (`value`),
  KEY `unit` (`unit`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_rates` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` varchar(100) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_and_date` (`ip`(20),`date`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bulletins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `fetched` datetime DEFAULT NULL,
  `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
  `fixes` smallint(5) unsigned NOT NULL DEFAULT '0',
  `last_error` longtext,
  `parsed` datetime DEFAULT NULL,
  `bulletin_schema` varchar(15) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `format` varchar(5) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `done` datetime DEFAULT NULL,
  `status` varchar(25) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `bulletin_hash` varchar(256) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`bulletin_schema`,`date`,`external_id`) USING BTREE,
  KEY `created` (`bulletin_schema`,`created`) USING BTREE
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(400) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cache_value` mediumtext NOT NULL,
  `expire` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `expire` (`expire`),
  KEY `cache_key` (`cache_key`(100),`expire`) USING HASH
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `subtype` varchar(20) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `name` varchar(150) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `slug` varchar(200) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `fetched` datetime NOT NULL,
  `country` varchar(3) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `national_id` varchar(30) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `keywords` varchar(400) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `normalized` varchar(150) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`) USING HASH,
  KEY `name` (`name`),
  KEY `last_name_2` (`first_name`),
  KEY `type` (`type`) USING HASH,
  KEY `country_and_subtype` (`country`,`subtype`) USING HASH,
  KEY `type_and_subtype` (`type`,`subtype`) USING HASH,
  KEY `country` (`country`) USING HASH
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `entities_by_name` (
  `id` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `subtype` tinyint NOT NULL,
  `name` tinyint NOT NULL,
  `slug` tinyint NOT NULL,
  `first_name` tinyint NOT NULL,
  `fetched` tinyint NOT NULL,
  `country` tinyint NOT NULL,
  `national_id` tinyint NOT NULL,
  `keywords` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entity_uses_name` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned NOT NULL,
  `used_name` varchar(150) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `precept_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entity_id` (`entity_id`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `list_has_entity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `list_id` int(10) unsigned NOT NULL,
  `entity_id` int(10) unsigned NOT NULL,
  `added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `list_id` (`list_id`) USING HASH,
  KEY `entity_id` (`entity_id`) USING HASH,
  KEY `added` (`added`) USING HASH
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(400) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `owner_id` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`) USING HASH,
  KEY `owner_id` (`owner_id`) USING HASH
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_cities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8 NOT NULL,
  `slug` varchar(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `county_id` int(10) unsigned NOT NULL,
  `state_id` int(10) unsigned NOT NULL,
  `country` varchar(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_id` (`country`,`name`(20)),
  KEY `state_id` (`state_id`),
  KEY `county_id` (`county_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_counties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8 NOT NULL,
  `slug` varchar(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `state_id` int(11) NOT NULL,
  `country` varchar(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_id` (`country`,`name`(20)),
  KEY `state_id` (`state_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8 NOT NULL,
  `slug` varchar(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `country` varchar(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country`,`name`(20))
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `original` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `label` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `country` varchar(5) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `state` int(10) unsigned DEFAULT NULL,
  `county` int(10) unsigned DEFAULT NULL,
  `city` int(10) unsigned DEFAULT NULL,
  `street` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `housenumber` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `postalcode` varchar(8) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `relevance` smallint(6) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_and_label` (`country`,`label`(20))
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `target` varchar(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `target` (`target`),
  KEY `created` (`created`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `names` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `name` varchar(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `type` (`type`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `precepts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bulletin_id` int(10) unsigned NOT NULL,
  `issuing_id` int(10) unsigned DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `issuing_id` (`issuing_id`),
  KEY `bulletin_id` (`bulletin_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spiders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulletin_schema` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `pid` bigint(20) DEFAULT NULL,
  `date_back` date DEFAULT NULL,
  `max_workers` mediumint(9) NOT NULL,
  `max_cpu_rate` tinyint(4) NOT NULL,
  `extract` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `bulletin_schema` (`bulletin_schema`),
  KEY `status` (`status`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_has_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) NOT NULL,
  `service_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_id` (`status_id`),
  KEY `service_id` (`service_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `precept_id` int(10) unsigned NOT NULL,
  `target_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `action` varchar(15) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `start` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `amount` bigint(20) unsigned DEFAULT NULL,
  `related_id` int(10) unsigned DEFAULT NULL,
  `contract_type_id` int(10) unsigned DEFAULT NULL,
  `sector_id` int(10) unsigned DEFAULT NULL,
  `note` text CHARACTER SET utf8 COLLATE utf8_bin,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `related_query` (`related_id`,`type`,`action`) USING HASH,
  KEY `target_query` (`target_id`,`type`,`action`) USING HASH,
  KEY `precept_id` (`precept_id`) USING HASH,
  KEY `target_id` (`target_id`) USING HASH,
  KEY `location_id` (`location_id`) USING HASH
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_login` varchar(50) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `user_pass` varchar(200) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `created` datetime NOT NULL,
  `last_seen` datetime DEFAULT NULL,
  `user_email` varchar(200) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `status` varchar(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`) USING HASH,
  UNIQUE KEY `user_login` (`user_login`) USING HASH,
  UNIQUE KEY `user_email` (`user_email`) USING HASH,
  KEY `created` (`created`),
  KEY `last_seen` (`last_seen`) USING HASH
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=TokuDB DEFAULT CHARSET=utf8mb4 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP TABLE IF EXISTS `entities_by_name`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 */
/*!50001 VIEW `entities_by_name` AS select `entities`.`id` AS `id`,`entities`.`type` AS `type`,`entities`.`subtype` AS `subtype`,`entities`.`name` AS `name`,`entities`.`slug` AS `slug`,`entities`.`first_name` AS `first_name`,`entities`.`fetched` AS `fetched`,`entities`.`country` AS `country`,`entities`.`national_id` AS `national_id`,`entities`.`keywords` AS `keywords` from `entities` order by `entities`.`name`,`entities`.`first_name` limit 500 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

