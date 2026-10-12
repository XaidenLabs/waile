-- ============================================================================
-- WALE — WhatsApp AI Lead Engine
-- Database schema + starter data (phpMyAdmin fallback for `php spark migrate`).
--
-- USAGE: create an empty database, then import this file (phpMyAdmin → Import).
-- Then log in at your site with:  admin@wale.dev  /  Admin@1234
-- (you will be forced to set a new password on first login).
--
-- NOTE: This file already marks every migration as applied, so you do NOT
-- need to run `php spark migrate` afterwards.
-- ============================================================================
SET FOREIGN_KEY_CHECKS=0;


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
CREATE TABLE `ai_configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `ai_provider` varchar(20) NOT NULL DEFAULT 'openai',
  `openai_api_key` text DEFAULT NULL,
  `deepseek_api_key` text DEFAULT NULL,
  `system_prompt` text DEFAULT NULL,
  `model` varchar(100) NOT NULL DEFAULT 'gpt-4o-mini',
  `temperature` decimal(3,2) NOT NULL DEFAULT 0.70,
  `max_tokens` int(11) NOT NULL DEFAULT 500,
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `fallback_message` text DEFAULT NULL,
  `qualification_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`qualification_rules`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `ai_configs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(500) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `api_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `automation_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `rule_id` int(10) unsigned NOT NULL,
  `lead_id` int(10) unsigned NOT NULL,
  `conversation_id` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','executed','failed','skipped') NOT NULL DEFAULT 'pending',
  `trigger_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`trigger_data`)),
  `error` text DEFAULT NULL,
  `triggered_at` datetime DEFAULT NULL,
  `executed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_id_status` (`rule_id`,`status`),
  KEY `lead_id` (`lead_id`),
  KEY `user_id_created_at` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `automation_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `trigger_type` enum('new_lead','no_reply','status_change','keyword_match') NOT NULL DEFAULT 'new_lead',
  `trigger_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`trigger_config`)),
  `action_type` enum('send_message','send_template','update_status','add_note') NOT NULL DEFAULT 'send_message',
  `action_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`action_config`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `run_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `user_id_trigger_type_is_active` (`user_id`,`trigger_type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'tenant',
  `lead_id` int(10) unsigned NOT NULL,
  `wa_thread_id` varchar(255) DEFAULT NULL,
  `status` enum('active','closed','paused') NOT NULL DEFAULT 'active',
  `ai_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `lead_id` (`lead_id`),
  CONSTRAINT `conversations_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funnel_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `funnel_id` int(10) unsigned NOT NULL,
  `lead_id` int(10) unsigned NOT NULL,
  `conversation_id` int(10) unsigned DEFAULT NULL,
  `current_step_id` int(10) unsigned DEFAULT NULL,
  `status` enum('active','completed','abandoned') NOT NULL DEFAULT 'active',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id_status` (`lead_id`,`status`),
  KEY `funnel_id` (`funnel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funnel_step_conditions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `step_id` int(10) unsigned NOT NULL,
  `funnel_id` int(10) unsigned NOT NULL,
  `condition_type` enum('contains','equals','starts_with','is_number','choice_num','any') NOT NULL DEFAULT 'contains',
  `condition_value` varchar(255) DEFAULT NULL,
  `next_step_id` int(10) unsigned DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Conditions checked in this order; first match wins',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `step_id_sort_order` (`step_id`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funnel_steps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `funnel_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `step_order` int(10) unsigned NOT NULL COMMENT '1-based display order',
  `name` varchar(100) DEFAULT NULL COMMENT 'Short admin label',
  `step_type` enum('message','question','action','end') NOT NULL DEFAULT 'message',
  `message` text DEFAULT NULL COMMENT 'Text sent to the lead (supports {variables})',
  `input_type` enum('text','number','choice','yesno') DEFAULT NULL,
  `choices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`choices`)),
  `save_to` varchar(80) DEFAULT NULL,
  `action_type` enum('update_status','add_note','add_tag') DEFAULT NULL,
  `action_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`action_config`)),
  `next_step_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `funnel_id_step_order` (`funnel_id`,`step_order`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funnels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `trigger_type` enum('manual','keyword','new_lead') NOT NULL DEFAULT 'manual',
  `trigger_value` varchar(255) DEFAULT NULL COMMENT 'Keyword or condition that starts the funnel',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_is_active` (`user_id`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'tenant',
  `lead_id` int(10) unsigned NOT NULL,
  `note` text NOT NULL,
  `type` enum('general','call','email','meeting','whatsapp') NOT NULL DEFAULT 'general',
  `created_by` int(10) unsigned NOT NULL COMMENT 'user_id who wrote it',
  `author_label` varchar(120) DEFAULT NULL COMMENT 'Overrides author name for system-generated notes (e.g. "Funnel: Welcome")',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `lead_notes_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `lead_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_status_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'tenant',
  `lead_id` int(10) unsigned NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `changed_by` int(10) unsigned NOT NULL COMMENT 'user_id',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `lead_status_logs_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `lead_status_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'tenant',
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `status` enum('new','hot','warm','cold','converted','lost') NOT NULL DEFAULT 'new',
  `score` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0-100',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `notes` text DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `last_contact` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `phone` (`phone`),
  CONSTRAINT `leads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'tenant',
  `conversation_id` int(10) unsigned NOT NULL,
  `lead_id` int(10) unsigned NOT NULL,
  `direction` enum('inbound','outbound') NOT NULL DEFAULT 'inbound',
  `sender` enum('lead','ai','agent') NOT NULL DEFAULT 'lead',
  `content` text NOT NULL,
  `wa_message_id` varchar(255) DEFAULT NULL,
  `message_type` enum('text','image','audio','document','template') NOT NULL DEFAULT 'text',
  `status` enum('sent','delivered','read','failed') NOT NULL DEFAULT 'sent',
  `tokens_used` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_lead_id_foreign` (`lead_id`),
  KEY `user_id` (`user_id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `idx_messages_wa_message_id` (`wa_message_id`(191)),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `messages_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'tenant',
  `type` varchar(30) NOT NULL DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `body` text DEFAULT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_is_read` (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` int(10) unsigned NOT NULL,
  `gateway` varchar(30) NOT NULL DEFAULT 'paypal',
  `gateway_order_id` varchar(191) DEFAULT NULL,
  `buyer_name` varchar(150) NOT NULL,
  `buyer_email` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `status` enum('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gateway_order_id` (`gateway_order_id`),
  KEY `buyer_email` (`buyer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `lead_limit` int(11) NOT NULL DEFAULT 100 COMMENT '-1 = unlimited',
  `ai_replies` int(11) NOT NULL DEFAULT 500 COMMENT '-1 = unlimited',
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_group_setting_key` (`setting_group`,`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `plan_id` int(10) unsigned NOT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_plan_id_foreign` (`plan_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `timezone` varchar(100) NOT NULL DEFAULT 'UTC',
  `role` enum('superadmin','user') DEFAULT 'user',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `force_password_change` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verify_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wa_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'tenant',
  `name` varchar(200) NOT NULL,
  `language` varchar(20) NOT NULL DEFAULT 'en_US',
  `category` varchar(40) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'APPROVED',
  `header_format` varchar(20) DEFAULT NULL COMMENT 'TEXT|IMAGE|VIDEO|DOCUMENT|NONE',
  `header_text` text DEFAULT NULL,
  `header_params` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `body_text` text DEFAULT NULL,
  `body_params` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `footer_text` text DEFAULT NULL,
  `buttons` text DEFAULT NULL COMMENT 'raw buttons JSON',
  `meta_id` varchar(64) DEFAULT NULL COMMENT 'Meta template id',
  `synced_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_name_language` (`user_id`,`name`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `phone_number_id` varchar(100) NOT NULL,
  `access_token` text NOT NULL,
  `verify_token` varchar(100) DEFAULT NULL,
  `waba_id` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `webhook_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `whatsapp_configs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- ---- Migration tracking + starter plans ----

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-04-16-074838','App\\Database\\Migrations\\CreateCoreSchema','default','App',1776326075,1);
INSERT INTO `migrations` VALUES (2,'2026-04-16-094632','App\\Database\\Migrations\\AlterLeadsAddNotesTables','default','App',1776332815,2);
INSERT INTO `migrations` VALUES (3,'2026-04-16-120000','App\\Database\\Migrations\\CreateAutomationTables','default','App',1776336648,3);
INSERT INTO `migrations` VALUES (4,'2026-04-16-130000','App\\Database\\Migrations\\CreateFunnelTables','default','App',1776337166,4);
INSERT INTO `migrations` VALUES (5,'2026-04-24-080000','App\\Database\\Migrations\\AddOpenAIKeyToAiConfigs','default','App',1782293296,5);
INSERT INTO `migrations` VALUES (6,'2026-04-24-090000','App\\Database\\Migrations\\RemoveAdminRole','default','App',1782293296,5);
INSERT INTO `migrations` VALUES (8,'2026-06-25-100000','App\\Database\\Migrations\\CreateSettingsTable','default','App',1782385003,6);
INSERT INTO `migrations` VALUES (9,'2026-06-26-100000','App\\Database\\Migrations\\CreatePaymentOrdersTable','default','App',1782455838,7);
INSERT INTO `migrations` VALUES (10,'2026-06-26-110000','App\\Database\\Migrations\\IndexMessagesWaMessageId','default','App',1782477888,8);
INSERT INTO `migrations` VALUES (11,'2026-06-29-100000','App\\Database\\Migrations\\CreateNotificationsTable','default','App',1782799019,9);
INSERT INTO `migrations` VALUES (12,'2026-07-08-100000','App\\Database\\Migrations\\CreateWaTemplatesTable','default','App',1783496159,10);
INSERT INTO `migrations` VALUES (13,'2026-07-08-120000','App\\Database\\Migrations\\AddAuthorLabelToLeadNotes','default','App',1783506898,11);
INSERT INTO `migrations` VALUES (14,'2026-07-09-100000','App\\Database\\Migrations\\AddForcePasswordChangeToUsers','default','App',1784095645,12);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (1,'Free','free',0.00,50,100,'[\"whatsapp_bot\",\"lead_capture\",\"basic_dashboard\"]',1,'2026-04-16 07:54:40','2026-06-26 07:34:38');
INSERT INTO `plans` VALUES (2,'Starter','starter',29.00,500,2000,'[\"whatsapp_bot\",\"lead_capture\",\"basic_dashboard\",\"ai_qualification\",\"csv_export\"]',1,'2026-04-16 07:54:40','2026-04-16 07:54:40');
INSERT INTO `plans` VALUES (3,'Professional','professional',79.00,5000,20000,'[\"whatsapp_bot\",\"lead_capture\",\"advanced_dashboard\",\"ai_qualification\",\"csv_export\",\"api_access\",\"team_members\",\"custom_prompt\"]',1,'2026-04-16 07:54:40','2026-04-16 07:54:40');
INSERT INTO `plans` VALUES (4,'Enterprise','enterprise',199.00,-1,-1,'[\"whatsapp_bot\",\"lead_capture\",\"advanced_dashboard\",\"ai_qualification\",\"csv_export\",\"api_access\",\"team_members\",\"custom_prompt\",\"white_label\",\"priority_support\",\"custom_integrations\"]',1,'2026-04-16 07:54:40','2026-04-16 07:54:40');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- ---- Default super-admin (must change password on first login) ----
INSERT INTO `users` (`name`,`company_name`,`email`,`password`,`role`,`status`,`email_verified`,`force_password_change`,`created_at`,`updated_at`)
VALUES ('Super Admin','WALE HQ','admin@wale.dev','$2y$12$22BWSIOi46Yzxe1gjLTYDOy.0JKMmoXjBnL2g3e5hQSfKtILsGlJC','superadmin','active',1,1,NOW(),NOW());

INSERT INTO `subscriptions` (`user_id`,`plan_id`,`status`,`starts_at`,`expires_at`,`created_at`,`updated_at`)
VALUES (LAST_INSERT_ID(),1,'active',NOW(),DATE_ADD(NOW(), INTERVAL 30 DAY),NOW(),NOW());

SET FOREIGN_KEY_CHECKS=1;
