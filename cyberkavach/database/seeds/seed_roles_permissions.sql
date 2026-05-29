-- Seed roles and permissions for CyberKavach

-- Roles
INSERT INTO roles (name, slug, description, level, is_system_role) VALUES
('Faculty Coordinator', 'faculty_coordinator', 'Top-level approver and system coordinator', 7, 1),
('Student Coordinator', 'student_coordinator', 'Operational coordinator for students', 6, 1),
('Tech Coordinator', 'tech_coordinator', 'Technical resource coordinator', 5, 1),
('Content Coordinator', 'content_coordinator', 'Content and editorial coordinator', 5, 1),
('Social Coordinator', 'social_coordinator', 'Social media coordinator', 5, 1),
('Club Member', 'club_member', 'Regular club member', 2, 0),
('Guest', 'guest', 'Unregistered guest', 1, 0);

-- Permissions (from config/permissions.php)
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
('attendance','scan','Scan attendance'),
('attendance','mark_manual','Mark attendance manually'),
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
('content','draft','Draft content'),
('content','submit','Submit for review'),
('content','approve','Approve content'),
('content','publish','Publish content'),
('content','delete','Delete content'),

('social','view','View social campaigns'),
('social','draft','Draft social posts'),
('social','submit','Submit social posts'),
('social','approve','Approve social posts'),
('social','schedule','Schedule social posts'),

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

('audit','view','View audit logs');

-- Grant all permissions to Faculty Coordinator
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.slug = 'faculty_coordinator';

-- Student Coordinator: most permissions except full system management
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON 1=1
WHERE r.slug = 'student_coordinator' AND NOT (p.module = 'settings' AND p.action = 'manage_system');

-- Tech/Content/Social Coordinators: grant module-relevant permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module IN ('events','teams','registrations','approvals','attendance','certificates','analytics','users')
WHERE r.slug = 'tech_coordinator';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module IN ('content','events','approvals','analytics')
WHERE r.slug = 'content_coordinator';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module IN ('social','events','approvals','analytics')
WHERE r.slug = 'social_coordinator';

-- Club Member: limited permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module IN ('events','teams','registrations','content','certificates','rewards')
WHERE r.slug = 'club_member' AND p.action IN ('view','create','join','create','view_own','generate','view');

-- Guest: read-only event/content access
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module IN ('events','content')
WHERE r.slug = 'guest' AND p.action = 'view';

-- Note: tweak role_permissions after reviewing mapping.
