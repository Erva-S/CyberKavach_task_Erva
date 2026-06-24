-- CyberKavach initial schema (partial)

CREATE DATABASE IF NOT EXISTS `cyberkavach` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cyberkavach`;

-- users
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ulid` CHAR(26) NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_url` VARCHAR(255) DEFAULT NULL,
  `enrollment_no` VARCHAR(64) DEFAULT NULL,
  `department` VARCHAR(128) DEFAULT NULL,
  `batch_year` SMALLINT DEFAULT NULL,
  `bio` TEXT,
  `status` VARCHAR(32) DEFAULT 'active',
  `email_verified_at` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- user_sessions
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `device_fingerprint` VARCHAR(191) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `is_revoked` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_sessions_token_hash` (`token_hash`),
  KEY `idx_user_sessions_user` (`user_id`),
  KEY `idx_user_sessions_expires` (`expires_at`),
  KEY `idx_user_sessions_revoked` (`is_revoked`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- auth_otps
CREATE TABLE IF NOT EXISTS `auth_otps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(191) NOT NULL,
  `purpose` VARCHAR(50) NOT NULL,
  `code_hash` CHAR(64) NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `consumed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_auth_otps_email_purpose` (`email`, `purpose`),
  KEY `idx_auth_otps_user` (`user_id`),
  KEY `idx_auth_otps_expires` (`expires_at`),
  CONSTRAINT `fk_auth_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `level` INT NOT NULL DEFAULT 1,
  `is_system_role` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `assigned_by` BIGINT UNSIGNED DEFAULT NULL,
  `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_user_roles_user` (`user_id`),
  KEY `idx_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(100) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permissions_module_action` (`module`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- role_permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_role_permissions_role` (`role_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(191) NOT NULL,
  `module` VARCHAR(100) NOT NULL,
  `record_type` VARCHAR(100) DEFAULT NULL,
  `record_id` VARCHAR(100) DEFAULT NULL,
  `old_value_json` JSON DEFAULT NULL,
  `new_value_json` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_module_created` (`module`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- approval_requests and approval_steps for multi-level approval engine
CREATE TABLE IF NOT EXISTS `approval_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ulid` CHAR(26) NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `requested_by` BIGINT UNSIGNED NOT NULL,
  `current_level` INT NOT NULL DEFAULT 1,
  `max_level` INT NOT NULL DEFAULT 3,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `priority` VARCHAR(32) DEFAULT 'normal',
  `sla_deadline` DATETIME DEFAULT NULL,
  `related_id` VARCHAR(100) DEFAULT NULL,
  `related_type` VARCHAR(100) DEFAULT NULL,
  `metadata_json` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_approval_ulid` (`ulid`),
  KEY `idx_approval_status` (`status`),
  KEY `idx_approval_requested_by` (`requested_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `approval_steps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` BIGINT UNSIGNED NOT NULL,
  `level` INT NOT NULL,
  `approver_role` VARCHAR(100) DEFAULT NULL,
  `assigned_to` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(50) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `attachment_url` VARCHAR(255) DEFAULT NULL,
  `acted_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_steps_request` (`request_id`),
  CONSTRAINT `fk_steps_request` FOREIGN KEY (`request_id`) REFERENCES `approval_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- event_categories
CREATE TABLE IF NOT EXISTS `event_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `color` VARCHAR(32) DEFAULT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- events
CREATE TABLE IF NOT EXISTS `events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ulid` CHAR(26) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description_html` LONGTEXT,
  `banner_url` VARCHAR(255) DEFAULT NULL,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `venue` VARCHAR(255) DEFAULT NULL,
  `capacity` INT UNSIGNED DEFAULT NULL,
  `team_min` INT UNSIGNED DEFAULT NULL,
  `team_max` INT UNSIGNED DEFAULT NULL,
  `registration_start` DATETIME DEFAULT NULL,
  `registration_end` DATETIME DEFAULT NULL,
  `event_start` DATETIME DEFAULT NULL,
  `event_end` DATETIME DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
  `visibility` VARCHAR(32) NOT NULL DEFAULT 'public',
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `approval_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `approved_by` BIGINT UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `is_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `fee_amount` DECIMAL(10,2) DEFAULT NULL,
  `tags` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_events_ulid` (`ulid`),
  UNIQUE KEY `uk_events_slug` (`slug`),
  KEY `idx_events_status` (`status`),
  KEY `idx_events_approval_status` (`approval_status`),
  KEY `idx_events_event_start` (`event_start`),
  KEY `idx_events_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_events_category` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_events_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- event_coordinators
CREATE TABLE IF NOT EXISTS `event_coordinators` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_label` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_coordinators_event_user_role` (`event_id`, `user_id`, `role_label`),
  KEY `idx_event_coordinators_user` (`user_id`),
  CONSTRAINT `fk_event_coordinators_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_coordinators_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- event_faqs
CREATE TABLE IF NOT EXISTS `event_faqs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `question` VARCHAR(500) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_faqs_event` (`event_id`),
  KEY `idx_event_faqs_sort` (`sort_order`),
  CONSTRAINT `fk_event_faqs_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- event_schedule
CREATE TABLE IF NOT EXISTS `event_schedule` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_event_schedule_event` (`event_id`),
  KEY `idx_event_schedule_starts_at` (`starts_at`),
  CONSTRAINT `fk_event_schedule_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- teams
CREATE TABLE IF NOT EXISTS `teams` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ulid` CHAR(26) NOT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `leader_id` BIGINT UNSIGNED DEFAULT NULL,
  `join_code` VARCHAR(32) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_teams_ulid` (`ulid`),
  UNIQUE KEY `uk_teams_join_code` (`join_code`),
  KEY `idx_teams_event` (`event_id`),
  KEY `idx_teams_leader` (`leader_id`),
  KEY `idx_teams_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_teams_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_teams_leader` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- team_members
CREATE TABLE IF NOT EXISTS `team_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `team_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `joined_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `role_in_team` VARCHAR(100) DEFAULT NULL,
  `invited_by` BIGINT UNSIGNED DEFAULT NULL,
  `invitation_status` VARCHAR(32) NOT NULL DEFAULT 'accepted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_team_members_team_user` (`team_id`, `user_id`),
  KEY `idx_team_members_user` (`user_id`),
  KEY `idx_team_members_invited_by` (`invited_by`),
  CONSTRAINT `fk_team_members_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_team_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_team_members_invited_by` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- registrations
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ulid` CHAR(26) NOT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `team_id` BIGINT UNSIGNED DEFAULT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `registered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(32) NOT NULL DEFAULT 'registered',
  `payment_status` VARCHAR(32) NOT NULL DEFAULT 'not_required',
  `payment_reference` VARCHAR(191) DEFAULT NULL,
  `waitlist_position` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_registrations_ulid` (`ulid`),
  UNIQUE KEY `uk_registrations_event_user` (`event_id`, `user_id`),
  KEY `idx_registrations_team` (`team_id`),
  KEY `idx_registrations_waitlist` (`waitlist_position`),
  CONSTRAINT `fk_registrations_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_registrations_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `action_url` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `sent_via` VARCHAR(32) NOT NULL DEFAULT 'in_app',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`, `is_read`),
  KEY `idx_notifications_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- notification_preferences
CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `in_app_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `email_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notification_preferences_user_type` (`user_id`, `type`),
  CONSTRAINT `fk_notification_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- content_posts
CREATE TABLE IF NOT EXISTS `content_posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ulid` CHAR(26) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `body_html` LONGTEXT,
  `excerpt` TEXT DEFAULT NULL,
  `cover_url` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `tags` JSON DEFAULT NULL,
  `author_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
  `scheduled_at` DATETIME DEFAULT NULL,
  `published_at` DATETIME DEFAULT NULL,
  `approval_request_id` BIGINT UNSIGNED DEFAULT NULL,
  `seo_title` VARCHAR(255) DEFAULT NULL,
  `seo_description` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_posts_ulid` (`ulid`),
  UNIQUE KEY `uk_content_posts_slug` (`slug`),
  KEY `idx_content_posts_status` (`status`),
  KEY `idx_content_posts_author` (`author_id`),
  KEY `idx_content_posts_approval_request` (`approval_request_id`),
  CONSTRAINT `fk_content_posts_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_content_posts_approval_request` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- social_campaigns
CREATE TABLE IF NOT EXISTS `social_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED DEFAULT NULL,
  `platform` VARCHAR(100) NOT NULL,
  `caption` TEXT DEFAULT NULL,
  `hashtags` VARCHAR(500) DEFAULT NULL,
  `media_urls_json` JSON DEFAULT NULL,
  `scheduled_at` DATETIME DEFAULT NULL,
  `posted_at` DATETIME DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `approved_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_social_campaigns_event` (`event_id`),
  KEY `idx_social_campaigns_status` (`status`),
  KEY `idx_social_campaigns_created_by` (`created_by`),
  CONSTRAINT `fk_social_campaigns_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_social_campaigns_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_social_campaigns_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- certificate_templates
CREATE TABLE IF NOT EXISTS `certificate_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `event_id` BIGINT UNSIGNED DEFAULT NULL,
  `template_url` VARCHAR(255) NOT NULL,
  `width_px` INT UNSIGNED NOT NULL,
  `height_px` INT UNSIGNED NOT NULL,
  `orientation` VARCHAR(32) NOT NULL DEFAULT 'landscape',
  `fields_json` JSON DEFAULT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_certificate_templates_event` (`event_id`),
  KEY `idx_certificate_templates_created_by` (`created_by`),
  CONSTRAINT `fk_certificate_templates_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_certificate_templates_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- certificate_field_map
CREATE TABLE IF NOT EXISTS `certificate_field_map` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_id` BIGINT UNSIGNED NOT NULL,
  `field_key` VARCHAR(100) NOT NULL,
  `x_percent` DECIMAL(5,2) NOT NULL,
  `y_percent` DECIMAL(5,2) NOT NULL,
  `font_family` VARCHAR(100) DEFAULT NULL,
  `font_size` INT UNSIGNED DEFAULT NULL,
  `font_weight` VARCHAR(50) DEFAULT NULL,
  `color` VARCHAR(32) DEFAULT NULL,
  `align` VARCHAR(16) DEFAULT 'left',
  `max_width_percent` DECIMAL(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_certificate_field_map_template_key` (`template_id`, `field_key`),
  CONSTRAINT `fk_certificate_field_map_template` FOREIGN KEY (`template_id`) REFERENCES `certificate_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- certificates
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ulid` CHAR(26) NOT NULL,
  `certificate_number` VARCHAR(191) NOT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `template_id` BIGINT UNSIGNED DEFAULT NULL,
  `file_url` VARCHAR(255) NOT NULL,
  `qr_code_url` VARCHAR(255) DEFAULT NULL,
  `issued_at` DATETIME DEFAULT NULL,
  `issued_by` BIGINT UNSIGNED DEFAULT NULL,
  `verification_hash` CHAR(64) NOT NULL,
  `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
  `generation_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `generated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_certificates_ulid` (`ulid`),
  UNIQUE KEY `uk_certificates_number` (`certificate_number`),
  UNIQUE KEY `uk_certificates_verification_hash` (`verification_hash`),
  KEY `idx_certificates_event` (`event_id`),
  KEY `idx_certificates_user` (`user_id`),
  KEY `idx_certificates_template` (`template_id`),
  KEY `idx_certificates_revoked` (`is_revoked`),
  KEY `idx_certificates_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_certificates_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_certificates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_certificates_template` FOREIGN KEY (`template_id`) REFERENCES `certificate_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_certificates_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- reward_points
CREATE TABLE IF NOT EXISTS `reward_points` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `points` INT NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `reference_id` VARCHAR(100) DEFAULT NULL,
  `reference_type` VARCHAR(100) DEFAULT NULL,
  `awarded_by` BIGINT UNSIGNED DEFAULT NULL,
  `awarded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reward_points_user` (`user_id`),
  KEY `idx_reward_points_category` (`category`),
  KEY `idx_reward_points_awarded_by` (`awarded_by`),
  KEY `idx_reward_points_reference` (`reference_type`, `reference_id`),
  CONSTRAINT `fk_reward_points_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reward_points_awarded_by` FOREIGN KEY (`awarded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- badges
CREATE TABLE IF NOT EXISTS `badges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `icon` VARCHAR(255) DEFAULT NULL,
  `condition_type` VARCHAR(100) NOT NULL,
  `condition_value` VARCHAR(191) NOT NULL,
  `xp_required` INT UNSIGNED NOT NULL DEFAULT 0,
  `tier` VARCHAR(50) NOT NULL DEFAULT 'bronze',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_badges_name` (`name`),
  KEY `idx_badges_tier` (`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- user_badges
CREATE TABLE IF NOT EXISTS `user_badges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `badge_id` BIGINT UNSIGNED NOT NULL,
  `earned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `awarded_for_event_id` BIGINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_badges_user_badge` (`user_id`, `badge_id`),
  KEY `idx_user_badges_badge` (`badge_id`),
  KEY `idx_user_badges_event` (`awarded_for_event_id`),
  CONSTRAINT `fk_user_badges_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_badges_badge` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_badges_event` FOREIGN KEY (`awarded_for_event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

