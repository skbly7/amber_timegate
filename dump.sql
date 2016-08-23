-- MySQL dump 10.13  Distrib 5.5.49, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: amber_timegate
-- ------------------------------------------------------
-- Server version	5.5.49-0ubuntu0.14.04.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `amber_delete_request`
--

DROP TABLE IF EXISTS `amber_delete_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amber_delete_request` (
  `node_id` int(11) DEFAULT NULL,
  `hash` varchar(32) DEFAULT NULL,
  `timestamp` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amber_delete_request`
--

LOCK TABLES `amber_delete_request` WRITE;
/*!40000 ALTER TABLE `amber_delete_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `amber_delete_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `amber_node`
--

DROP TABLE IF EXISTS `amber_node`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amber_node` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL DEFAULT 'NULL',
  `email` varchar(100) NOT NULL DEFAULT 'NULL',
  `key` varchar(64) NOT NULL,
  `is_verified` int(11) DEFAULT NULL,
  `alive` int(11) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1 COMMENT='This table contain information of all the Nodes.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amber_node`
--

LOCK TABLES `amber_node` WRITE;
/*!40000 ALTER TABLE `amber_node` DISABLE KEYS */;
/*!40000 ALTER TABLE `amber_node` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `amber_reputation`
--

DROP TABLE IF EXISTS `amber_reputation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amber_reputation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_id` int(11) DEFAULT NULL,
  `points` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amber_reputation`
--

LOCK TABLES `amber_reputation` WRITE;
/*!40000 ALTER TABLE `amber_reputation` DISABLE KEYS */;
/*!40000 ALTER TABLE `amber_reputation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `amber_urim`
--

DROP TABLE IF EXISTS `amber_urim`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amber_urim` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_id` int(11) DEFAULT NULL,
  `timestamp` int(11) DEFAULT NULL,
  `cache_id` varchar(50) DEFAULT NULL,
  `urir_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=394 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amber_urim`
--

LOCK TABLES `amber_urim` WRITE;
/*!40000 ALTER TABLE `amber_urim` DISABLE KEYS */;
/*!40000 ALTER TABLE `amber_urim` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `amber_urir`
--

DROP TABLE IF EXISTS `amber_urir`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amber_urir` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `amber_urir`
--

LOCK TABLES `amber_urir` WRITE;
/*!40000 ALTER TABLE `amber_urir` DISABLE KEYS */;
/*!40000 ALTER TABLE `amber_urir` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

