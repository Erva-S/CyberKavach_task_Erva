<?php
return [
  'events' => ['view', 'create', 'edit', 'delete', 'approve', 'clone'],
  'teams' => ['view', 'create', 'join', 'manage', 'delete'],
  'registrations' => ['view', 'create', 'cancel', 'export'],
  'approvals' => ['view_own', 'view_all', 'approve', 'reject', 'escalate'],
  'attendance' => ['view', 'scan', 'mark_manual', 'export'],
  'certificates' => ['view_own', 'view_all', 'generate', 'revoke', 'send'],
  'rewards' => ['view', 'award', 'revoke', 'manage_badges'],
  'content' => ['view', 'draft', 'submit', 'approve', 'publish', 'delete'],
  'social' => ['view', 'draft', 'submit', 'approve', 'schedule'],
  'users' => ['view', 'invite', 'manage_roles', 'deactivate'],
  'analytics' => ['view_own', 'view_club', 'view_global', 'export'],
  'settings' => ['view', 'manage_branding', 'manage_system'],
  'audit' => ['view'],
];
