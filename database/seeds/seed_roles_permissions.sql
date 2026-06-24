-- Seed roles, permissions, and role_permissions for CyberKavach
USE `cyberkavach`;

-- Roles (idempotent)
INSERT INTO roles (name, slug, description, level, is_system_role, created_at)
VALUES
  ('Faculty Coordinator','faculty_coordinator','Faculty level coordinator',7,1,NOW()),
  ('Student Coordinator','student_coordinator','Student operations coordinator',6,1,NOW()),
  ('Tech Coordinator','tech_coordinator','Technical coordinator',5,1,NOW()),
  ('Content Coordinator','content_coordinator','Content coordinator',5,1,NOW()),
  ('Social Coordinator','social_coordinator','Social media coordinator',5,1,NOW()),
  ('Club Member','club_member','Regular club member',2,1,NOW()),
  ('Guest','guest','Unauthenticated guest role',1,1,NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), level=VALUES(level), is_system_role=VALUES(is_system_role);

-- Permissions list (idempotent)
INSERT INTO permissions (module, action, description) VALUES
  ('events','view','View events'),
  ('events','create','Create events'),
  ('events','edit','Edit events'),
  ('events','delete','Delete events'),
  ('events','approve','Approve events'),
  ('events','clone','Clone events'),

  ('teams','view','View teams'),
  ('teams','create','Create teams'),
  ('teams','join','Join teams'),
  ('teams','manage','Manage teams'),
  ('teams','delete','Delete teams'),

  ('registrations','view','View registrations'),
  ('registrations','create','Create registrations'),
  ('registrations','cancel','Cancel registrations'),
  ('registrations','export','Export registrations'),

  ('approvals','view_own','View own approvals'),
  ('approvals','view_all','View all approvals'),
  ('approvals','approve','Approve requests'),
  ('approvals','reject','Reject requests'),
  ('approvals','escalate','Escalate requests'),

  ('attendance','view','View attendance'),
  ('attendance','scan','Scan QR / mark attendance'),
  ('attendance','mark_manual','Mark manual attendance'),
  ('attendance','export','Export attendance'),

  ('certificates','view_own','View own certificates'),
  ('certificates','view_all','View all certificates'),
  ('certificates','generate','Generate certificates'),
  ('certificates','revoke','Revoke certificates'),
  ('certificates','send','Send certificates'),

  ('rewards','view','View rewards'),
  ('rewards','award','Award points'),
  ('rewards','revoke','Revoke points'),
  ('rewards','manage_badges','Manage badges'),

  ('content','view','View content'),
  ('content','draft','Create draft'),
  ('content','submit','Submit for review'),
  ('content','approve','Approve content'),
  ('content','publish','Publish content'),
  ('content','delete','Delete content'),

  ('social','view','View social campaigns'),
  ('social','draft','Draft social post'),
  ('social','submit','Submit social post'),
  ('social','approve','Approve social post'),
  ('social','schedule','Schedule social post'),

  ('users','view','View users'),
  ('users','invite','Invite users'),
  ('users','manage_roles','Manage user roles'),
  ('users','deactivate','Deactivate users'),

  ('analytics','view_own','View own analytics'),
  ('analytics','view_club','View club analytics'),
  ('analytics','view_global','View global analytics'),
  ('analytics','export','Export analytics'),

  ('settings','view','View settings'),
  ('settings','manage_branding','Manage branding'),
  ('settings','manage_system','Manage system'),

  ('audit','view','View audit logs')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Map permissions to roles
-- Faculty Coordinator: full access (all permissions)
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.slug = 'faculty_coordinator';
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.slug = 'faculty_coordinator';

-- Student Coordinator: broad operational rights
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.slug = 'student_coordinator';
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON (
  (p.module = 'events') OR
  (p.module = 'registrations') OR
  (p.module = 'approvals') OR
  (p.module = 'attendance') OR
  (p.module = 'certificates') OR
  (p.module = 'rewards') OR
  (p.module = 'content') OR
  (p.module = 'social') OR
  (p.module = 'users') OR
  (p.module = 'analytics')
) WHERE r.slug = 'student_coordinator';

-- Tech Coordinator: technical + attendance + events
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.slug = 'tech_coordinator';
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON (
  (p.module = 'events') OR (p.module = 'attendance') OR (p.module = 'users') OR (p.module = 'settings')
) WHERE r.slug = 'tech_coordinator';

-- Content Coordinator: content and editorial rights
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.slug = 'content_coordinator';
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON (
  (p.module = 'content') OR (p.module = 'events') OR (p.module = 'analytics')
) WHERE r.slug = 'content_coordinator';

-- Social Coordinator: social campaigns and scheduling
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.slug = 'social_coordinator';
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON (
  (p.module = 'social') OR (p.module = 'content') OR (p.module = 'analytics')
) WHERE r.slug = 'social_coordinator';

-- Club Member: basic interaction
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.slug = 'club_member';
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON (
  (p.module = 'events' AND p.action IN ('view','create','join','edit')) OR
  (p.module = 'teams' AND p.action IN ('view','create','join')) OR
  (p.module = 'registrations') OR
  (p.module = 'certificates' AND p.action = 'view_own') OR
  (p.module = 'rewards' AND p.action = 'view') OR
  (p.module = 'content' AND p.action = 'view') OR
  (p.module = 'social' AND p.action = 'view')
) WHERE r.slug = 'club_member';

-- Guest: read-only public access
DELETE rp FROM role_permissions rp JOIN roles r ON r.id = rp.role_id WHERE r.slug = 'guest';
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON (
  (p.module = 'events' AND p.action = 'view') OR
  (p.module = 'content' AND p.action = 'view') OR
  (p.module = 'social' AND p.action = 'view')
) WHERE r.slug = 'guest';

-- Done
SELECT 'roles_permissions_seeded' as status;
