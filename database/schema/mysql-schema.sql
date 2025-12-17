/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `club_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `followers_user_id_foreign` (`user_id`),
  KEY `followers_club_id_foreign` (`club_id`),
  CONSTRAINT `followers_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `followers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clubs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stripe_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pm_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pm_last_four` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `subscription_exempt` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `clubs_user_id_foreign` (`user_id`),
  KEY `clubs_stripe_id_index` (`stripe_id`),
  CONSTRAINT `clubs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contestants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contestants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `league_session_id` bigint unsigned NOT NULL,
  `division_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  `index` int unsigned NOT NULL DEFAULT '0',
  `overall_rank` int unsigned DEFAULT NULL,
  `division_rank` int unsigned DEFAULT NULL,
  `notified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contestants_division_id_foreign` (`division_id`),
  KEY `contestants_member_id_foreign` (`member_id`),
  KEY `contestants_league_session_id_foreign` (`league_session_id`),
  CONSTRAINT `contestants_division_id_foreign` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `contestants_league_session_id_foreign` FOREIGN KEY (`league_session_id`) REFERENCES `league_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contestants_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `code` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `divisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `league_session_id` bigint unsigned NOT NULL,
  `tier_id` bigint unsigned NOT NULL,
  `index` int NOT NULL DEFAULT '0',
  `contestant_count` int unsigned NOT NULL DEFAULT '0',
  `promote_count` int unsigned NOT NULL,
  `relegate_count` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `divisions_tier_id_foreign` (`tier_id`),
  KEY `divisions_league_session_id_foreign` (`league_session_id`),
  CONSTRAINT `divisions_league_session_id_foreign` FOREIGN KEY (`league_session_id`) REFERENCES `league_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `divisions_tier_id_foreign` FOREIGN KEY (`tier_id`) REFERENCES `tiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entrants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entrants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `league_session_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  `index` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entrants_league_session_id_foreign` (`league_session_id`),
  KEY `entrants_member_id_foreign` (`member_id`),
  CONSTRAINT `entrants_league_session_id_foreign` FOREIGN KEY (`league_session_id`) REFERENCES `league_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entrants_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint unsigned NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invitations_member_id_foreign` (`member_id`),
  CONSTRAINT `invitations_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `league_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `league_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `league_id` bigint unsigned NOT NULL,
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `pts_win` tinyint NOT NULL DEFAULT '0',
  `pts_draw` tinyint NOT NULL DEFAULT '0',
  `pts_per_set` tinyint NOT NULL DEFAULT '0',
  `pts_play` tinyint NOT NULL DEFAULT '0',
  `built_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `has_published` tinyint(1) NOT NULL DEFAULT '0',
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seasons_league_id_foreign` (`league_id`),
  CONSTRAINT `league_sessions_league_id_foreign` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leagues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leagues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_league_id` int unsigned NOT NULL,
  `club_id` bigint unsigned NOT NULL,
  `sport_id` bigint unsigned NOT NULL,
  `template` json NOT NULL DEFAULT (json_array()),
  `tally_unit_id` bigint unsigned DEFAULT NULL,
  `best_of` tinyint NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leagues_sport_id_foreign` (`sport_id`),
  KEY `leagues_club_id_foreign` (`club_id`),
  KEY `leagues_tally_unit_id_fk` (`tally_unit_id`),
  CONSTRAINT `leagues_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leagues_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leagues_tally_unit_id_fk` FOREIGN KEY (`tally_unit_id`) REFERENCES `tally_units` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_member_id` int unsigned NOT NULL,
  `club_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `members_club_id_foreign` (`club_id`),
  KEY `members_user_id_foreign` (`user_id`),
  CONSTRAINT `members_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`),
  CONSTRAINT `members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ranking_squash_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ranking_squash_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `es_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `county` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `match_at` datetime DEFAULT NULL,
  `club_id` bigint unsigned NOT NULL,
  `league_id` bigint unsigned NOT NULL,
  `league_session_id` bigint unsigned NOT NULL,
  `division_id` bigint unsigned NOT NULL,
  `home_contestant_id` bigint unsigned NOT NULL,
  `away_contestant_id` bigint unsigned NOT NULL,
  `home_score` smallint unsigned NOT NULL,
  `away_score` smallint unsigned NOT NULL,
  `home_attended` tinyint(1) NOT NULL DEFAULT '1',
  `away_attended` tinyint(1) NOT NULL DEFAULT '1',
  `submitted_by` bigint unsigned NOT NULL,
  `submitted_by_admin` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `results_league_id_foreign` (`league_id`),
  KEY `results_league_session_id_foreign` (`league_session_id`),
  KEY `results_club_id_foreign` (`club_id`),
  KEY `results_division_id_foreign` (`division_id`),
  KEY `results_submitted_by_foreign` (`submitted_by`),
  KEY `results_home_contestant_id_foreign` (`home_contestant_id`),
  KEY `results_away_contestant_id_foreign` (`away_contestant_id`),
  CONSTRAINT `results_away_contestant_id_foreign` FOREIGN KEY (`away_contestant_id`) REFERENCES `contestants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `results_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `results_division_id_foreign` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `results_home_contestant_id_foreign` FOREIGN KEY (`home_contestant_id`) REFERENCES `contestants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `results_league_id_foreign` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_league_session_id_foreign` FOREIGN KEY (`league_session_id`) REFERENCES `league_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `sport_tally_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sport_tally_unit` (
  `sport_id` bigint unsigned NOT NULL,
  `tally_unit_id` bigint unsigned NOT NULL,
  `max_best_of` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sport_id`,`tally_unit_id`),
  KEY `sport_tally_unit_tally_unit_id_foreign` (`tally_unit_id`),
  CONSTRAINT `sport_tally_unit_sport_id_foreign` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sport_tally_unit_tally_unit_id_foreign` FOREIGN KEY (`tally_unit_id`) REFERENCES `tally_units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint unsigned NOT NULL,
  `stripe_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_product` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_items_stripe_id_unique` (`stripe_id`),
  KEY `subscription_items_subscription_id_stripe_price_index` (`subscription_id`,`stripe_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_id` bigint unsigned NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_stripe_id_unique` (`stripe_id`),
  KEY `subscriptions_club_id_stripe_status_index` (`club_id`,`stripe_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tally_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tally_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tally_units_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `league_session_id` bigint unsigned NOT NULL,
  `index` int NOT NULL DEFAULT '0',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tiers_league_session_id_foreign` (`league_session_id`),
  CONSTRAINT `tiers_league_session_id_foreign` FOREIGN KEY (`league_session_id`) REFERENCES `league_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tel_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `profile_photo_path` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_team_id` bigint unsigned DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2019_03_01_085123_create_leagues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2019_11_07_093243_create_pages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2020_10_13_114749_create_seasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2020_10_16_142122_create_divisions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2020_11_19_084200_create_fixtures_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2021_02_07_112246_create_players_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2021_02_07_114333_create_competitors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2021_02_09_092411_create_league_competitors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2021_02_10_151644_create_contestants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2021_02_11_151644_create_fixture_contestants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2021_02_16_160134_create_teams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2021_02_17_134605_create_followers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2021_03_17_094908_create_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2021_03_31_000455_create_invitations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2021_05_17_075437_create_sports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2021_05_18_093616_create_sport_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2021_05_18_094901_add_sport_detail_id_to_leagues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2021_05_24_084931_add_turned_up_to_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2021_06_03_101040_remove_processed_at_from_seasons_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2021_07_22_150145_update_user_id_from_competitors_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2021_07_23_132356_update_league_id_from_seasons_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2021_07_23_133113_update_season_id_from_divisions',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2021_07_23_133414_update_division_id_from_contestants',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2021_07_23_134245_update_division_id_from_fixtures',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2021_07_23_134500_update_contestant_id_from_fixture_contestants',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2021_07_26_073350_add_league_id_to_competitors_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2021_07_26_073830_transfer_league_id_data_from_league_competitors_to_competitors',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2021_07_26_074333_remove_nullable_in_league_id_from_competitors_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2021_07_26_080221_drop_league_competitors_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2021_08_08_175344_add_competitorable_type_to_leagues_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2021_08_09_071818_transfer_competitorable_type_data_from_sport_details_to_leagues_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2021_08_09_072511_remove_nullable_in_competitorable_type_from_leagues_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2021_08_09_072831_remove_sport_detail_id_from_leagues_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2021_08_09_080128_drop_sports_table_and_sport_details_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2021_08_10_070548_create_tallies_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2021_08_10_073539_add_tally_id_to_leagues_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2021_08_11_091125_update_competitor_id_with_on_delete_cascade_in_contestants_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2021_08_12_174610_remove_gender_from_users_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2021_08_12_174841_remove_gender_from_users_tableclear',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2021_08_13_124452_add_deleted_at_column_to_contestants_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2021_09_16_094118_create_rankings_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2021_09_17_120212_add_diff_w_d_l_columns_to_results_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2021_09_17_130223_transfer_diff_w_d_l_columns_to_results_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2021_09_22_153521_add_rating_column_to_results_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2021_10_21_090641_add_processed_at_column_to_seasons_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2021_10_22_153210_add_previous_rating_column_to_rankings_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2021_10_25_104231_update_season_id_with_on_delete_cascade_in_rankings_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2021_10_27_104117_add_previous_rank_column_to_rankings_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2021_11_27_183455_add_fixture_at_column_to_results_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2021_11_27_184949_duplicate_fixture_at_data_from_fixtures_to_results_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2021_12_08_100628_create_new_sports_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2021_12_08_131037_add_sport_id_to_leagues_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2021_12_08_135511_create_sport_tally_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2019_05_03_000001_create_customers_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2019_05_03_000002_create_subscriptions_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2019_05_03_000003_create_receipts_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2022_02_03_122855_add_require_subscription_to_leagues_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2022_02_24_142703_add_has_fixtures_to_leagues_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2022_03_03_135456_add_has_published_to_seasons_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2022_03_07_105035_update_round_can_be_null_to_fixtures_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2022_03_11_095907_change_has_fixtures_to_is_box_field_in_leagues_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2022_03_11_154547_remove_is_fixtures_from_seasons_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2022_03_16_110758_add_processed_at_copy_to_seasons_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2022_03_16_111227_duplicate_processed_at_to_processed_at_copy_in_seasons_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2022_03_16_111601_update_new_rankings_data',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2022_03_16_203304_add_actual_score_column_to_results_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2022_03_17_095349_add_expected_score_column_to_results_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2022_03_22_100715_update_actual_and_expected_scores_in_results_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2022_03_22_110001_add_season_id_to_results_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2022_03_22_110850_update_season_id_in_results_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2022_03_22_111422_remove_season_id_is_nullable_in_results_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2019_12_14_000001_create_personal_access_tokens_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2023_01_27_131159_remove_scam_users',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2023_01_27_133937_remove_columns_users_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2023_01_27_135251_add_columns_users_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2023_01_27_140807_add_two_factor_columns_to_users_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2023_01_27_140917_create_sessions_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2023_01_27_141750_create_clubs_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2023_01_27_141755_create_members_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2023_01_27_142844_seed_clubs_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2023_01_27_144053_seed_members_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2023_01_27_152140_remove_columns_leagues_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'2023_01_27_153458_add_columns_leagues_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2023_01_27_161636_seed_leagues_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2023_01_27_163943_remove_columns_competitors_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2023_01_27_164346_add_columns_competitors_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2023_01_27_164650_seed_competitors_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2023_01_27_175903_update_columns_seasons_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2023_01_27_182237_create_tiers_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2023_01_27_182506_seed_tiers_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2023_01_27_183222_remove_columns_divisions_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2023_01_27_183440_add_columns_divisions_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (321,'2023_01_27_183632_seed_divisions_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (322,'2023_01_27_185955_rename_players_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2023_01_27_190635_rename_teams_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (324,'2023_01_27_190746_create_jobs_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (325,'2023_01_28_085927_remove_columns_fixtures_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2023_01_28_090346_remove_columns_fixture_contestants_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2023_01_28_090547_add_columns_fixture_contestants_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2023_01_28_091455_seed_fixture_contestants_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (329,'2023_01_28_100007_remove_columns_results_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (330,'2023_01_28_100209_add_columns_results_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (331,'2023_01_28_100338_seed_results_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2023_01_28_150933_update_competitors_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2023_01_28_162111_remove_redundant_tables',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2023_01_28_181442_remove_nullable_foreign_ids',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2023_02_01_160855_update_billable_model_customers_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2023_02_01_161116_update_billable_model_receipts_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2023_02_01_161201_update_billable_model_subscriptions_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2023_02_01_182031_update_paid_at_receipts_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2023_02_02_101808_soft_delete_clubs',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (340,'2023_02_02_102715_update_data_clubs_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2023_02_02_103705_update_data_leagues_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2023_02_07_112137_add_column_name_to_users_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2023_02_07_112409_update_name_in_users_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2023_02_07_112710_remove_columns_users_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2023_02_09_203435_remove_slug_from_clubs_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2023_02_13_095653_add_reckify_key_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2023_02_13_095747_update_reckify_key_in_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2023_02_16_112724_create_failed_jobs_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2023_02_19_095641_add_column_responsible_member_id_to_competitors_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2023_02_19_095921_update_responsible_member_id_in_competitors_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2023_02_19_100127_remove_member_id_column_in_competitors_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2023_02_20_083736_add_column_member_id_to_competitors_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2023_02_20_084014_update_member_id_in_competitors_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2023_02_21_093742_delete_competitors_not_in_a_division',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2023_02_21_100113_update_jonny_gaukrodger_fixture_contestant',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (356,'2023_02_21_100355_update_jonny_gaukrodger_contestant',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (357,'2023_02_21_101934_update_jonny_gaukrodger_results',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (358,'2023_02_21_102558_update_jonny_gaukrodger_competing_individuals',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (359,'2023_02_21_103653_update_members_with_competitors_that_are_not_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (360,'2023_02_22_070902_update_responsible_member_when_null_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (361,'2023_02_22_110556_update_terry_smart_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (362,'2023_02_22_111052_delete_terry_smart_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (363,'2023_02_22_111442_update_conor_macleod_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (364,'2023_02_22_111614_delete_conor_mcleod_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (365,'2023_02_22_112015_update_chris_steven_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (366,'2023_02_22_112150_delete_chris_stevens_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (367,'2023_02_22_112505_update_gordon_taylor_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (368,'2023_02_22_112529_delete_gordon_taylor_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (369,'2023_02_22_112806_update_louise_tupling_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (370,'2023_02_22_112828_delete_louise_tupling_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (371,'2023_02_22_113111_update_kevin_vallely_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (372,'2023_02_22_113136_delete_kevin_vallely_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (373,'2023_02_22_113533_update_david_wood_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (374,'2023_02_22_113550_delete_david_wood_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (375,'2023_02_22_124346_update_dan_baron_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (376,'2023_02_22_124411_delete_dan_baron_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (377,'2023_02_22_124835_update_geoff_carter_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (378,'2023_02_22_124851_delete_geoff_carter_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (379,'2023_02_22_125227_update_john_freke_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (380,'2023_02_22_125243_delete_john_freke_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (381,'2023_02_22_125530_update_alexander_jones_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (382,'2023_02_22_125545_delete_alexander_jones_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (383,'2023_02_22_125822_update_andrew_jukes_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (384,'2023_02_22_125839_delete_andrew_jukes_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (385,'2023_02_22_130136_update_james_ratliff_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (386,'2023_02_22_130152_delete_james_ratliff_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (387,'2023_02_22_130427_update_dan_rogers_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (388,'2023_02_22_130441_delete_dan_rogers_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (389,'2023_02_22_130743_update_heather_sherriff_member_id_in_competitors',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (390,'2023_02_22_130808_delete_heather_sheriff_members',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (391,'2022_11_21_094409_create_invitations_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (392,'2023_06_05_101525_remove_columns_name_from_leagues_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (393,'2023_06_05_121832_add_columns_user_id_and_name_and_slug_and_timezone_to_leagues_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (394,'2023_06_05_122818_seed_leagues_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (395,'2023_06_05_124813_update_column_user_id_remove_nullable_in_leagues_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (396,'2023_06_05_125119_delete_column_club_id_from_leagues_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (397,'2023_06_05_125313_delete_league_id_24_from_leagues_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (398,'2023_06_05_130615_create_contacts_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (399,'2023_06_05_132602_add_columns_user_id_and_contact_id_to_competitors_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (400,'2023_06_05_133755_seed_user_id_of_competitors_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (401,'2023_06_05_175730_delete_columns_member_id_and_is_member_and_responsible_member_id_from_competitors_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (402,'2023_06_05_212630_remove_clubs_and_members_tables',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (403,'2023_06_09_080300_delete_columns_reckify_key_and_tel_no_from_users_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (404,'2023_06_23_093243_create_followers_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (405,'2023_07_04_143339_add_soft_deletes_to_competitors_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (406,'2023_08_17_054349_create_sports_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (407,'2023_08_17_054748_create_tally_units_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (408,'2023_08_17_054805_seed_sports_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (409,'2023_08_17_055609_add_sport_id_to_leagues_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (410,'2023_08_17_055719_seed_leagues_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (411,'2023_08_17_060148_remove_nullable_from_sport_id_in_leagues',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (412,'2023_08_17_060345_delete_column_tally_units_from_leagues',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (413,'2023_08_17_060559_add_column_fixture_at_to_fixtures',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (414,'2023_08_17_061004_add_column_tally_unit_id_to_seasons',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (415,'2023_08_17_061124_seed_seasons_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (416,'2023_08_17_061720_remove_nullable_from_tally_unit_id_from_seasons',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (417,'2023_08_24_131816_update_the_lord_family_chess_leagues_sport_from_squash_to_chess',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (418,'2023_08_24_134903_create_ranking_squash_levels_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (419,'2023_08_24_135206_add_columns_morphs_rankable_to_competitors_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (420,'2023_08_24_141159_seed_competitors_and_ranking_squash_levels_tables',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (421,'2023_08_24_150822_delete_some_leagues_in_leagues_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (422,'2023_09_18_082414_create_clubs_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (423,'2023_09_18_093827_add_column_club_to_leagues_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (424,'2023_09_22_093157_add_column_country_code_to_ranking_squash_levels_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (425,'2023_09_23_093157_add_column_dob_to_ranking_squash_levels_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (426,'2023_09_24_102357_add_column_county_to_ranking_squash_levels_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (427,'2024_10_01_081715_delete_record_id_23_from_leagues',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (428,'2024_10_02_082134_update_fixture_at_in_fixtures_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (429,'2024_10_03_071946_delete_clubs_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (430,'2024_10_03_073207_create_clubs_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (431,'2024_10_03_073612_transfer_leagues_data_to_clubs_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (432,'2024_10_03_081309_create_columns_in_leagues_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (433,'2024_10_03_081915_update_leagues_columns_club_league_id_and_members_per_competitor',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (434,'2024_10_03_082041_delete_columns_from_leagues_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (435,'2024_10_03_085825_add_new_column_club_id_in_followers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (436,'2024_10_03_090503_transfer_column_data_league_id_to_club_id_in_followers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (437,'2024_10_03_102240_delete_column_league_id_from_followers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (438,'2024_10_03_102759_remove_nullable_from_club_id_in_followers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (439,'2024_10_03_104703_create_club_sport_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (440,'2024_10_03_105102_update_column_data_club_id_and_sport_id_in_club_sport_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (441,'2024_10_03_112832_create_members_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (442,'2024_10_03_113300_update_column_data_in_members_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (443,'2024_10_03_123714_delete_competitors_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (444,'2024_10_03_123942_create_competitors_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (445,'2024_10_03_124102_update_column_data_in_competitors_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (446,'2024_10_03_130120_create_competitor_member_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (447,'2024_10_03_130744_add_new_column_member_id_in_invitations_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (448,'2024_10_03_130855_transfer_column_data_competitor_id_to_member_id_in_invitations_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (449,'2024_10_03_131006_delete_column_competitor_id_from_invitations_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (450,'2024_10_03_131046_remove_nullable_from_member_id_in_invitations_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (451,'2024_10_03_133932_rename_column_is_competitor_to_is_member_in_contacts_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (452,'2024_10_03_134105_update_contact_id_that_are_null_in_members_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (453,'2024_10_03_140058_update_column_data_in_competitor_member_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (454,'2024_10_03_190105_add_new_column_notified_at_in_contestants_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (455,'2024_10_03_211034_add_new_columns_to_results_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (456,'2024_10_03_213913_update_column_data_in_results_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (457,'2024_10_04_153251_remove_nullable_from_club_id_in_leagues_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (458,'2024_10_16_125341_delete_column_email_in_contacts_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (459,'2024_10_17_114515_add_column_tel_no_to_users_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (460,'2024_10_17_124331_transfer_contact_tel_no_to_user_tel_no_in_users_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (461,'2024_10_18_071141_delete_column_contact_id_from_members_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (462,'2024_10_18_071357_drop_contacts_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (463,'2024_10_21_084243_create_duplicate_seasons_table_called_league_sessions_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (464,'2024_10_21_084833_create_column_league_session_id_in_divisions_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (465,'2024_10_21_085730_transfer_season_id_into_league_session_id_in_divisions_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (466,'2024_10_21_085954_remove_nullable_on_league_session_id_in_divisions_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (467,'2024_10_21_090256_delete_season_id_in_divisions_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (468,'2024_10_21_090536_create_column_league_session_id_in_results_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (469,'2024_10_21_090708_transfer_season_id_into_league_session_id_in_results_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (470,'2024_10_21_090909_remove_nullable_on_league_session_id_in_results_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (471,'2024_10_21_091149_delete_season_id_in_results_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (472,'2024_10_21_091356_create_league_session_id_in_tiers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (473,'2024_10_21_091555_transfer_season_id_to_league_session_id_in_tiers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (474,'2024_10_21_091737_remove_nullable_on_league_session_id_in_tiers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (475,'2024_10_21_091837_delete_season_id_from_tiers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (476,'2024_10_21_091934_drops_seasons_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (477,'2024_10_30_110100_remove_western_isle_doubles_league_from_clubs',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (478,'2024_11_01_113140_add_foreign_key_to_league_id_and_tally_unit_id_in_league_sessions',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (479,'2018_11_20_145226_drop_customers_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (480,'2018_11_20_145523_drop_receipts_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (481,'2018_11_20_145623_drop_subscriptions_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (482,'2019_05_04_000001_create_customer_columns',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (483,'2019_05_04_000002_create_subscriptions_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (484,'2019_05_04_000003_create_subscription_items_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (485,'2025_02_11_110751_add_country_code_to_clubs_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (486,'2025_10_24_092842_rename_followers_table_to_new_club_user_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (487,'2025_10_24_093416_drop_club_sport_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (488,'2025_10_24_094131_drop_competing_individuals_table_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (489,'2025_10_24_094240_drop_competing_teams_table_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (490,'2025_10_24_094906_add_league_session_id_to_contestants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (491,'2025_10_24_102221_change_key_name_for_division_id_in_contestants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (492,'2025_10_24_104228_add_member_id_to_contestants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (493,'2025_10_24_104659_drop_competitor_id_in_contestants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (494,'2025_10_24_105216_add_ranking_columns_to_contestants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (495,'2025_10_24_105846_change_fixture_at_column_to_match_at_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (496,'2025_10_24_105957_add_club_id_column_to_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (497,'2025_10_24_110726_drop_sport_id_in_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (498,'2025_10_24_111142_move_league_id_in_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (499,'2025_10_24_111940_add_division_id_in_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (500,'2025_10_24_113312_add_home_contestant_id_in_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (501,'2025_10_24_113646_add_away_contestant_id_in_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (502,'2025_10_24_114643_dedupe_inverse_home_away_in_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (503,'2025_10_24_115037_rename_f_to_home_score_in_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (504,'2025_10_24_115823_rename_a_to_away_score_in_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (505,'2025_10_24_120400_add_home_attended_to_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (506,'2025_10_24_120619_add_away_attended_to_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (507,'2025_10_24_121302_update_guy_eniona_result_match_at_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (508,'2025_10_24_121304_update_guy_eniona_result_fixture_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (509,'2025_10_24_121306_add_submitted_by_to_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (510,'2025_10_24_125227_add_submitted_by_admin_to_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (511,'2025_10_24_130553_update_league_id_to_not_nullable_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (512,'2025_10_24_130931_delete_fixture_contestant_id_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (513,'2025_10_24_131232_delete_competitor_id_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (514,'2025_10_24_131418_delete_opp_competitor_id_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (515,'2025_10_24_131605_delete_diff_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (516,'2025_10_24_131937_delete_w_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (517,'2025_10_24_132004_delete_d_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (518,'2025_10_24_132010_delete_l_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (519,'2025_10_24_132017_delete_pts_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (520,'2025_10_24_132052_delete_turned_up_on_results_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (521,'2025_10_24_140234_delete_competitor_member_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (522,'2025_10_24_140346_delete_competitors_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (523,'2025_10_24_140826_change_qty_promoted_to_promote_count_on_divisions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (524,'2025_10_24_140933_change_qty_relegated_to_relegate_count_on_divisions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (525,'2025_10_24_141226_update_index_to_not_unsigned_on_divisions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (526,'2025_10_24_141640_add_contestant_count_on_divisions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (527,'2025_10_24_142211_drop_fixture_contestants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (528,'2025_10_24_142315_drop_fixtures_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (529,'2025_10_24_143544_move_club_id_in_leagues_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (530,'2025_10_24_144006_add_template_in_leagues_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (531,'2025_10_24_144721_update_record_9_sports_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (532,'2025_10_24_145000_add_record_10_in_sports_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (533,'2025_10_24_145531_create_sport_tally_unit_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (534,'2025_10_25_141400_create_entrants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (535,'2025_10_25_141716_delete_name_column_in_league_sessions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (536,'2025_10_25_142117_add_timezone_to_league_sessions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (537,'2025_10_25_142717_change_starting_at_column_name_to_starts_at_in_league_sessions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (538,'2025_10_25_142859_change_ending_at_column_name_to_ends_at_in_league_sessions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (539,'2025_10_25_145404_modify_index_column_in_tiers_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (540,'2025_10_25_172432_add_best_of_in_leagues_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (541,'2025_10_25_172651_delete_members_per_competitor_in_leagues_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (542,'2025_10_25_173559_delete_best_of_in_league_sessions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (543,'2025_10_25_173854_change_validated_at_to_built_at_in_league_sessions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (544,'2025_10_25_175345_add_tally_unit_id_in_league_sessions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (545,'2025_10_25_180353_update_tally_unit_id_in_leagues_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (546,'2025_10_25_180714_delete_tally_unit_id_in_league_sessions_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (547,'2025_10_25_180824_drop_sport_tally_unit_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (548,'2025_10_25_180917_drop_tally_units_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (549,'2025_10_25_182408_create_tally_units_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (550,'2025_10_25_182530_create_sport_tally_unit_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (551,'2025_10_25_182749_seed_data_in_tally_units_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (552,'2025_10_25_182813_seed_data_in_sport_tally_unit_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (553,'2025_10_25_184748_update_tally_unit_id_in_leagues_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (554,'2025_10_26_181209_create_cache_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (555,'2025_10_27_074520_populate_rankings_in_contestants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (556,'2025_10_27_082842_populate_entrants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (557,'2025_11_06_092107_update_contestants_foreign_key_constraint_on_contestants_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (558,'2025_11_06_093130_update_results_foreign_keys_to_cascade_to_results_table',37);
