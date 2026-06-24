CyberKavach — Scaffold

This repository contains an initial scaffold for the CyberKavach Smart Club Management System.

Next steps:
- Run `composer install` to install PHP dependencies.
- Copy `.env.example` to `.env` and populate credentials.
- Import `database/schema.sql` into MySQL to create the initial schema.
- Point your webserver DocumentRoot to `public/`.
- Seed roles, permissions, and the first admin with `php database/seeds/run_seeds.php`.
- Run `composer test` to execute the PHPUnit suite.
- Run `vendor/bin/phpunit --testsuite unit` once dev dependencies are installed.

Optional admin seed variables:
- `SEED_ADMIN_EMAIL`
- `SEED_ADMIN_PASSWORD`
- `SEED_ADMIN_NAME`

Planned work:
- Implement Router, Auth, RBAC, Audit, Approval engine, Events, Attendance, Certificates.

CI:
- GitHub Actions workflow lives in `.github/workflows/ci.yml` and runs PHPUnit on push and pull request.
