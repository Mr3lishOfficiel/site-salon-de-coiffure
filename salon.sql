-- MariaDB dump 10.19  Distrib 10.11.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: salon
-- ------------------------------------------------------
-- Server version	10.11.6-MariaDB-0+deb12u1

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
-- Table structure for table `Clients`
--

DROP TABLE IF EXISTS `Clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Clients` (
  `id_client` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `genre` enum('homme','femme','autres') DEFAULT NULL,
  PRIMARY KEY (`id_client`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Clients`
--

LOCK TABLES `Clients` WRITE;
/*!40000 ALTER TABLE `Clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `Clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Factures`
--

DROP TABLE IF EXISTS `Factures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Factures` (
  `id_facture` int(11) NOT NULL AUTO_INCREMENT,
  `id_rendez_vous` int(11) DEFAULT NULL,
  `montant_total` decimal(10,2) DEFAULT NULL,
  `date_facturation` date DEFAULT NULL,
  `prestations_details` text DEFAULT NULL,
  `prenom_coiffeur` varchar(255) DEFAULT NULL,
  `etat` enum('non attribué','annulée','terminé','en cours') DEFAULT 'non attribué',
  PRIMARY KEY (`id_facture`),
  KEY `id_rendez_vous` (`id_rendez_vous`),
  CONSTRAINT `Factures_ibfk_1` FOREIGN KEY (`id_rendez_vous`) REFERENCES `Rendez_vous` (`id_rendez_vous`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Factures`
--

LOCK TABLES `Factures` WRITE;
/*!40000 ALTER TABLE `Factures` DISABLE KEYS */;
INSERT INTO `Factures` VALUES
(23,35,20.00,'2024-06-03','Coupe homme (20.00 €)','Ilyes','terminé'),
(24,34,20.00,'2024-06-03','Coupe homme (20.00 €)','Ilyes','terminé'),
(25,32,20.00,'2024-06-03','Coupe homme (20.00 €)','Ilyes','terminé'),
(26,42,45.00,'2024-06-03','Coupe femme (45.00 €)','Ilyes','terminé'),
(27,39,45.00,'2024-06-03','Coupe femme (45.00 €)','Ilyes','terminé'),
(28,37,45.00,'2024-06-13','Coupe femme (45.00 €)','Hammad','terminé'),
(29,50,45.00,'2024-06-13','Coupe femme (45.00 €)','Hammad','terminé'),
(30,38,90.00,'2024-06-13','Décoloration (90.00 €)','Hammad','terminé');
/*!40000 ALTER TABLE `Factures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Prestations`
--

DROP TABLE IF EXISTS `Prestations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Prestations` (
  `id_prestation` int(11) NOT NULL AUTO_INCREMENT,
  `nom_prestation` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tarif` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_prestation`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Prestations`
--

LOCK TABLES `Prestations` WRITE;
/*!40000 ALTER TABLE `Prestations` DISABLE KEYS */;
INSERT INTO `Prestations` VALUES
(2,'Coupe femme','Coupe de cheveux pour femme',45.00),
(5,'Coupe homme','Coupe de cheveux pour homme',20.00),
(7,'BuzzCut','Une coupe tendance',15.00),
(9,'Décoloration','on enlève la couleur !',90.00);
/*!40000 ALTER TABLE `Prestations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Rendez_vous`
--

DROP TABLE IF EXISTS `Rendez_vous`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Rendez_vous` (
  `id_rendez_vous` int(11) NOT NULL AUTO_INCREMENT,
  `date_heure` datetime DEFAULT NULL,
  `id_coiffeur` int(11) DEFAULT NULL,
  `id_prestation` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `etat` enum('non attribué','annulée','terminé','en cours') DEFAULT 'non attribué',
  PRIMARY KEY (`id_rendez_vous`),
  KEY `id_coiffeur` (`id_coiffeur`),
  KEY `id_prestation` (`id_prestation`),
  KEY `fk_utilisateur` (`id_utilisateur`),
  CONSTRAINT `fk_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `Utilisateurs` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Rendez_vous`
--

LOCK TABLES `Rendez_vous` WRITE;
/*!40000 ALTER TABLE `Rendez_vous` DISABLE KEYS */;
INSERT INTO `Rendez_vous` VALUES
(22,'2024-05-01 12:00:00',NULL,1,2,'non attribué'),
(29,'2024-05-16 09:00:00',NULL,5,2,'terminé'),
(30,'2024-05-07 09:00:00',13,5,2,'terminé'),
(31,'2024-05-09 12:00:00',NULL,5,2,'terminé'),
(32,'2024-05-14 16:00:00',13,5,2,'terminé'),
(33,'2024-05-14 15:00:00',NULL,5,2,'terminé'),
(34,'2024-05-22 13:00:00',13,7,2,'terminé'),
(35,'2024-05-24 11:00:00',13,2,2,'terminé'),
(36,'2024-05-24 14:00:00',NULL,8,2,'non attribué'),
(37,'2024-06-25 09:00:00',19,2,2,'terminé'),
(38,'2024-05-29 11:00:00',19,9,2,'terminé'),
(39,'2024-05-24 14:00:00',13,2,2,'terminé'),
(40,'2024-05-24 11:00:00',NULL,9,2,'terminé'),
(41,'2024-05-24 11:00:00',NULL,7,2,'annulée'),
(42,'2024-05-31 12:00:00',13,2,2,'terminé'),
(46,'2024-07-12 09:00:00',NULL,7,21,'non attribué'),
(47,'2024-06-06 17:00:00',NULL,9,22,'terminé'),
(48,'2024-06-07 09:00:00',NULL,9,22,'en cours'),
(49,'2024-06-07 10:00:00',NULL,5,23,'terminé'),
(50,'2024-06-10 09:00:00',19,2,21,'terminé'),
(51,'2024-06-09 09:00:00',NULL,7,21,'en cours'),
(52,'2024-06-11 09:00:00',NULL,2,21,'terminé'),
(53,'2024-06-15 09:00:00',NULL,5,21,'en cours'),
(54,'2024-06-27 09:00:00',NULL,2,21,'non attribué'),
(55,'2024-06-30 09:00:00',NULL,2,2,'non attribué'),
(56,'2024-06-30 10:00:00',NULL,2,35,'non attribué'),
(57,'2024-06-29 09:00:00',NULL,2,35,'non attribué'),
(58,'2024-06-29 10:00:00',NULL,7,35,'non attribué'),
(59,'2024-06-29 11:00:00',NULL,9,35,'non attribué');
/*!40000 ALTER TABLE `Rendez_vous` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Rendez_vous_Prestations`
--

DROP TABLE IF EXISTS `Rendez_vous_Prestations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Rendez_vous_Prestations` (
  `id_rdv_prestation` int(11) NOT NULL AUTO_INCREMENT,
  `id_rendez_vous` int(11) DEFAULT NULL,
  `id_prestation` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_rdv_prestation`),
  KEY `id_rendez_vous` (`id_rendez_vous`),
  KEY `id_prestation` (`id_prestation`),
  CONSTRAINT `Rendez_vous_Prestations_ibfk_1` FOREIGN KEY (`id_rendez_vous`) REFERENCES `Rendez_vous` (`id_rendez_vous`),
  CONSTRAINT `Rendez_vous_Prestations_ibfk_2` FOREIGN KEY (`id_prestation`) REFERENCES `Prestations` (`id_prestation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Rendez_vous_Prestations`
--

LOCK TABLES `Rendez_vous_Prestations` WRITE;
/*!40000 ALTER TABLE `Rendez_vous_Prestations` DISABLE KEYS */;
/*!40000 ALTER TABLE `Rendez_vous_Prestations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Utilisateurs`
--

DROP TABLE IF EXISTS `Utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Utilisateurs` (
  `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mot_de_passe_hashé` varchar(255) DEFAULT NULL,
  `role` enum('coiffeur','comptable','gerant','client') DEFAULT NULL,
  `etat` varchar(20) DEFAULT 'non vérifié',
  PRIMARY KEY (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Utilisateurs`
--

LOCK TABLES `Utilisateurs` WRITE;
/*!40000 ALTER TABLE `Utilisateurs` DISABLE KEYS */;
INSERT INTO `Utilisateurs` VALUES
(2,'Clement','clement@gmail.com','$2y$10$ZYkPdzrg3bwsmFfZiMK7Uu3XL.2nYZLWgxmRjWpcGXp4ATD5qGsxW','client','verifie'),
(19,'Hammad','HammadLeCoiffeur@gmail.com','$2y$10$zwc4H0GOLuaVRML0XOhLauwmaqL/PDgN3iY.xV1O5lO6boX.PzFHG','coiffeur','non_verif'),
(20,'Talal','Talal@gmail.com','$2y$10$HFeG0v9wWLmZeTq8lRYO9e/kET2jXBoT5t4PcZcmRl/bg1xLSHmEW','comptable','non_verif'),
(21,'Leo','Leo@gmail.com','$2y$10$K7Yg8xcFMUwBhgIN87pN0.OQwT8rDRwEVodLfsgXokjpmU4mvH0ie','client','verifie'),
(22,'Jacques','jacques.raulo@cyu.fr','$2y$10$r/M9WixKjb5XoF/A8tbIB.FZhxC5CVniNzNnkmcCJ/IkKF5E3O.3e','client','non_verif'),
(23,'Quentin','dugas.quentin78@gmail.com','$2y$10$ljJfI0MDOi/JWcwsgJHN2OKBws3.kQK2VDX8tQbTfa7/2OuReWTLG','client','non_verif'),
(24,'Lukmane','LukmaneLeCoiffeur@gmail.com','$2y$10$.weyliHiokM/mSZx/sFfGuoqEHKk4KgCRfFlmsul0TlGXONy39ldW','coiffeur','non_verif'),
(25,'Quentin','dugas.quentin78@gmail.com','$2y$10$.NONIBJBxgZ5FTaKB/maO.DbZS7AFtRdRCgxjUfQSKh8fCvmm8Heu','coiffeur','non_verif'),
(28,'Gerant','Gerant@gmail.com','$2y$10$NvVx1/Xh/1x2vh.56SftpOo2hjt08TkwzlL6jYEMRMwwOmpbddSB2','gerant','non_verif'),
(31,'Azerty','clementproxys@gmail.com','$2y$10$YFbJfexlxBG8GbqaTamUBeDX/vdmTcqzxhQmg2yEdvAjbq7ay3ngG','client','verifie'),
(32,'Albert','Alber@gmail.com','$2y$10$WPLFXhhGVnaLHPtVMy3Eh.2cA5la4je5WBS4KA0dkq2ileGMasXtS','client','verifie'),
(33,'test','test@gmail.com','$2y$10$lshG10N9E2XHBS/Yl57Uau4nFzFJpXQH9L8lCe9zZtnclaey8oxJ6','client','verifie'),
(34,'test2','test2@gmail.com','$2y$10$n860NQRauJgj/QP8kFN3uuTe4Csc7Z4PSumRxaTqREGj8xBLz.nwW','client','verifie'),
(35,'test3','test3@gmail.com','$2y$10$SZdKGgjK3gUrCn8KDuntqOEOenYuinJdoS5AGFCw3SfCSOYhJADHm','client','verifie'),
(36,'test4','test4@gmail.com','$2y$10$fnQZIjM0C8gO4XwqaDOJPu8JlLOr1ZTDUziJMd92Ua3mo1kP39aoi','client','verifie');
/*!40000 ALTER TABLE `Utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-06-27 11:52:07
