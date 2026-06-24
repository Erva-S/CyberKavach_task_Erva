# CyberKavach Todo List

This file is the working checklist for the current build. Update it as implementation progresses and refer to it before starting any new task.

## Current state
- Built scaffold: `src/Core/` classes, `src/Modules/Auth/`, `src/Modules/Approvals/`, `src/Modules/Notifications/`, `public/index.php`, `database/schema.sql`, `.env.example`, `.htaccess`.
- Implemented this session: config-driven RBAC permission map, audit query helpers, file-based public/auth/dashboard layout shells, shared environment loader, base design-system stylesheet, public homepage shell, database-backed JWT session revocation, institutional email validation, OTP issue/verify/reset endpoints, OTP-gated registration, browser login/dashboard routes, Router helpers and middleware support, core event/team/registration/content/notification/certificate/reward tables.
- Partial or needing follow-up: feature modules, design system components, production styling.
- Immediate manual setup: `.env`, Composer install, MySQL import, Apache DocumentRoot, role/permission seeding, cron jobs, `INSTITUTIONAL_EMAIL_DOMAINS`.

## Already present
- Core scaffold exists in `src/Core/` with Router, Auth, RBAC, Audit, Request, Response, Database, View, and Mailer classes.
- Initial approval flow classes exist in `src/Modules/Approvals/`.
- Basic auth module scaffold exists in `src/Modules/Auth/`.
- Notifications scaffold exists in `src/Modules/Notifications/`.
- Partial database schema already exists in `database/schema.sql`.

## Manual setup still required
- Copy environment settings into `.env` and fill database, mail, JWT, and storage credentials.
- Install Composer dependencies.
- Import `database/schema.sql` into MySQL.
- Configure Apache to point the document root to `public/`.
- Verify `.htaccess` and security headers in the Apache environment.
- Configure cron jobs for approval escalation, email digests, certificate queue, and cleanup tasks.
- Seed roles, permissions, and the first admin account with `database/seeds/run_seeds.php`.

## Build backlog

### Phase 1 - Foundation
- [ ] Finish the database schema for all required tables and indexes.
- [x] Harden `config/database.php` with the final PDO singleton and environment loading.
- [x] Expand `src/Core/Router.php` to support the full app routing structure.
- [x] Complete `src/Core/Auth.php` for sessions, JWT refresh, logout, and role redirects.
- [x] Complete `src/Core/RBAC.php` with permission map loading and middleware helpers.
- [x] Complete `src/Core/Audit.php` with write helpers and query support.
- [x] Add base layouts for public, auth, and dashboard shells.
- [ ] Finish the CSS design system and shared UI components.

### Phase 2 - Auth System
- [x] Registration with institutional email validation.
- [x] Login with bcrypt verification.
- [x] OTP flow for registration and password reset.
- [ ] Device/session tracking UI and backend.
- [ ] Suspicious login detection alerts.
- [x] Logout and session destruction hardening.

### Phase 3 - Core Modules
- [ ] Event CRUD with drafts, publish, archive states.
- [ ] Quill.js rich text editing for content fields.
- [ ] Cloudinary banner upload support.
- [ ] Multi-step approval engine end to end.
- [ ] Approval SLA cron and escalation flow.
- [ ] Team creation, invitations, and conflict detection.
- [ ] Event registration with waitlist logic.
- [ ] Approval timeline and remarks UI.

### Phase 4 - Operational Modules
- [ ] QR generation for events and teams.
- [ ] QR attendance scanner and manual check-in dashboard.
- [ ] Certificate template manager and field mapping.
- [ ] Bulk certificate generation pipeline.
- [ ] Certificate QR verification page.
- [ ] Reward points, badges, and leaderboard.
- [ ] Notification center and email delivery.
- [ ] Content publishing workflow.
- [ ] Social media campaign planner and approvals.

### Phase 5 - Dashboards
- [ ] Faculty coordinator dashboard.
- [ ] Student coordinator dashboard.
- [ ] Tech coordinator dashboard.
- [ ] Content coordinator dashboard.
- [ ] Social media coordinator dashboard.
- [ ] Club member dashboard.
- [ ] Guest dashboard.
- [ ] Chart.js analytics and command palette.

### Phase 6 - Public Pages
- [ ] Landing page with animated metrics.
- [ ] Public events listing and filtering.
- [ ] Event detail and registration flow.
- [ ] Certificate verification page.
- [ ] Auth pages with production design.

### Phase 7 - Security Hardening
- [ ] CSRF on all state-changing actions.
- [ ] Rate limiting for login, OTP, and API endpoints.
- [ ] Input sanitization layer.
- [ ] File upload validation.
- [ ] XSS and clickjacking prevention headers.
- [ ] Audit log viewer for admins.
- [ ] Session fixation prevention and secure cookies.

### Phase 8 - Deployment
- [ ] Environment variable documentation.
- [ ] Database backup cron job.
- [ ] Error logging and monitoring.
- [ ] Mobile responsiveness pass.
- [ ] Performance optimization pass.
- [ ] Security checklist and onboarding guide.

## Notes
- Keep this file updated whenever a milestone lands.
- Before starting work, check the highest-priority unchecked items here.
