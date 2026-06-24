-- One-click SQL to assign the `faculty_coordinator` role to a user by email.
-- Import this file in phpMyAdmin or run with: mysql -u root -p cyberkavach < assign_faculty_coordinator.sql

USE `cyberkavach`;

-- EDIT THIS VALUE if you want to target a different email
SET @email = 'admin@cyberkavach.local';
SET @role_slug = 'faculty_coordinator';

-- Resolve ids
SET @uid := (SELECT id FROM users WHERE email = @email LIMIT 1);
SET @rid := (SELECT id FROM roles WHERE slug = @role_slug LIMIT 1);

-- Show resolved ids (visible in phpMyAdmin results)
SELECT @email AS target_email, @uid AS user_id, @role_slug AS target_role, @rid AS role_id;

-- Insert mapping only if both exist and mapping doesn't already exist
INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at, is_active)
SELECT @uid, @rid, NULL, NOW(), 1
FROM (SELECT 1) AS tmp
WHERE @uid IS NOT NULL
  AND @rid IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM user_roles WHERE user_id = @uid AND role_id = @rid
  );

-- Status summary
SELECT
  IF(@uid IS NULL, 'USER_NOT_FOUND', 'USER_EXISTS') AS user_status,
  IF(@rid IS NULL, 'ROLE_NOT_FOUND', 'ROLE_EXISTS') AS role_status,
  (SELECT COUNT(*) FROM user_roles WHERE user_id = @uid AND role_id = @rid) AS mapping_count;

-- Show assignments for the user (if any)
SELECT ur.id, r.slug, r.name, ur.assigned_at, ur.is_active
FROM user_roles ur
JOIN roles r ON r.id = ur.role_id
WHERE ur.user_id = @uid;

-- Helpful note for admins: if user is missing, run `php database/seeds/seed_admin.php` to create an admin user.
