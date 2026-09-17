# Coffee Ordering System

Lightweight standalone PHP/MySQL coffee ordering system for cPanel.

## Local development

1. Create a local MySQL database.
2. Import `database/schema.sql`.
3. Copy `config/database.example.php` to `config/database.php`.
4. Enter local database credentials in `config/database.php`.
5. Serve the project with PHP/Apache (XAMPP, Laragon, MAMP, etc.).
6. Open `/admin/login.php`.

Default admin:
- Username: `admin`
- Password: `admin123`

Change the password after first login.

## Git / GitHub Desktop

`config/database.php` is intentionally ignored by Git. The repository contains only the template:
`config/database.example.php`

Commit and push application changes with GitHub Desktop.

## cPanel deployment

Use cPanel Git Version Control with this repository. The `.cpanel.yml` file defines the production deployment.

Before the first deployment:
- Create the production MySQL database/user in cPanel.
- Copy `config/database.example.php` to `config/database.php` on the server and enter production credentials.
- Do NOT commit `config/database.php`.
- Update the `DEPLOYPATH` in `.cpanel.yml` to the correct cPanel path.
- Import `database/schema.sql` once into the production database.

The production database is not replaced during normal code deployments.

## Important

Do not run `database/schema.sql` against the production database again after the system is in use unless you understand the migration/change being made. Future database changes should be handled as migrations.

Customer ordering: `/`
Kitchen display: `/kitchen/`
Admin: `/admin/`
