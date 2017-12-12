-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 24, 2017 at 04:18 AM
-- Server version: 5.7.20-0ubuntu0.16.04.1
-- PHP Version: 7.0.22-0ubuntu0.16.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `boe`
--

-- --------------------------------------------------------

--
-- Table structure for table `amounts`
--

CREATE TABLE `amounts` (
  `id` bigint(20) NOT NULL,
  `originalValue` bigint(20) NOT NULL,
  `originalUnit` varchar(4) NOT NULL,
  `value` bigint(20) NOT NULL,
  `unit` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bulletins`
--

CREATE TABLE `bulletins` (
  `id` bigint(11) NOT NULL,
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
  `status` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bulletin_uses_bulletin`
--

CREATE TABLE `bulletin_uses_bulletin` (
  `id` bigint(11) NOT NULL,
  `bulletin_id` bigint(11) NOT NULL,
  `bulletin_in` bigint(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `entities`
--

CREATE TABLE `entities` (
  `id` bigint(11) NOT NULL,
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
  `keywords` varchar(400) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` bigint(20) NOT NULL,
  `original` varchar(200) NOT NULL,
  `label` varchar(200) DEFAULT NULL,
  `country` varchar(5) DEFAULT NULL,
  `state` bigint(20) DEFAULT NULL,
  `county` bigint(20) DEFAULT NULL,
  `city` bigint(20) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `housenumber` varchar(8) DEFAULT NULL,
  `postalcode` varchar(8) DEFAULT NULL,
  `updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `locks`
--

CREATE TABLE `locks` (
  `id` bigint(20) NOT NULL,
  `target` varchar(30) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `names`
--

CREATE TABLE `names` (
  `id` bigint(20) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `name` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `precepts`
--

CREATE TABLE `precepts` (
  `id` bigint(20) NOT NULL,
  `bulletin_id` bigint(20) NOT NULL,
  `issuing_id` bigint(20) DEFAULT NULL,
  `title` longtext,
  `text` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `spiders`
--

CREATE TABLE `spiders` (
  `id` int(11) NOT NULL,
  `bulletin_schema` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `pid` bigint(20) DEFAULT NULL,
  `date_back` date DEFAULT NULL,
  `workers_count` mediumint(9) NOT NULL,
  `cpu_rate` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `statuses`
--

CREATE TABLE `statuses` (
  `id` int(11) NOT NULL,
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
  `note` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `status_has_service`
--

CREATE TABLE `status_has_service` (
  `id` int(11) NOT NULL,
  `status_id` bigint(20) NOT NULL,
  `service_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `id` int(11) NOT NULL,
  `spider_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(20) NOT NULL,
  `pid` bigint(20) DEFAULT NULL,
  `started` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amounts`
--
ALTER TABLE `amounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `originalValue` (`originalValue`),
  ADD KEY `value` (`value`),
  ADD KEY `unit` (`unit`);

--
-- Indexes for table `bulletins`
--
ALTER TABLE `bulletins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bulletin_schema` (`bulletin_schema`),
  ADD KEY `date` (`date`),
  ADD KEY `external_id` (`external_id`);

--
-- Indexes for table `bulletin_uses_bulletin`
--
ALTER TABLE `bulletin_uses_bulletin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bulletin_id` (`bulletin_id`),
  ADD KEY `bulletin_in` (`bulletin_in`);

--
-- Indexes for table `entities`
--
ALTER TABLE `entities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `type` (`type`),
  ADD KEY `name` (`name`),
  ADD KEY `last_name` (`first_name`),
  ADD KEY `name_2` (`name`),
  ADD KEY `last_name_2` (`first_name`),
  ADD KEY `country` (`country`),
  ADD KEY `type_2` (`type`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locks`
--
ALTER TABLE `locks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `target` (`target`);

--
-- Indexes for table `names`
--
ALTER TABLE `names`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `precepts`
--
ALTER TABLE `precepts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issuing_id` (`issuing_id`),
  ADD KEY `bulletin_id` (`bulletin_id`);

--
-- Indexes for table `spiders`
--
ALTER TABLE `spiders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bulletin_schema` (`bulletin_schema`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `statuses`
--
ALTER TABLE `statuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `precept_id` (`precept_id`),
  ADD KEY `target_id` (`target_id`),
  ADD KEY `contract_type_id` (`contract_type_id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indexes for table `status_has_service`
--
ALTER TABLE `status_has_service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amounts`
--
ALTER TABLE `amounts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=564;
--
-- AUTO_INCREMENT for table `bulletins`
--
ALTER TABLE `bulletins`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=269447;
--
-- AUTO_INCREMENT for table `bulletin_uses_bulletin`
--
ALTER TABLE `bulletin_uses_bulletin`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=266057;
--
-- AUTO_INCREMENT for table `entities`
--
ALTER TABLE `entities`
  MODIFY `id` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2633;
--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `locks`
--
ALTER TABLE `locks`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65683;
--
-- AUTO_INCREMENT for table `names`
--
ALTER TABLE `names`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251288;
--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75887;
--
-- AUTO_INCREMENT for table `precepts`
--
ALTER TABLE `precepts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2478;
--
-- AUTO_INCREMENT for table `spiders`
--
ALTER TABLE `spiders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9291;
--
-- AUTO_INCREMENT for table `status_has_service`
--
ALTER TABLE `status_has_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3802;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
