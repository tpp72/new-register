# Repository Guidelines

## Project Structure

[spec.md](spec.md) defines the Training Registration System and is the only project file at this stage. Follow its planned layout as the application takes shape.

- `public/` holds the course listing, course details, registration, confirmation, and privacy pages. Put CSS, JavaScript, and images in `public/assets/`.
- `admin/` holds authentication, course and applicant management, and CSV export.
- `includes/` holds shared validation, escaping, authentication, and optional mail helpers. Put PDO setup and local settings in `config/` and the database schema in `sql/schema.sql`.

## Local Development

- Once the application files exist, start Apache and the database service in XAMPP. Use PHP 8.1+ with PDO and MySQL 8.0 or MariaDB 10.5+. Serve `public/` as the document root and map `/admin/` separately.
- From a database client started in the project root, run `SOURCE sql/schema.sql` to initialize a local `training_reg` database. Set the database credentials and base URL in `config/config.php`.
- Run `php -l public/register.php` to check PHP syntax. Use `C:\xampp\php\php.exe` if PHP is outside your PATH. The planned stack uses Bootstrap 5 via CDN and has no frontend build step.

## Coding Style and Configuration

Use four spaces for PHP indentation and lowercase snake_case for multiword page names and database columns, such as `course_form.php` and `reg_deadline`. Use PDO prepared statements, escape HTML output, and validate CSRF tokens on form submissions. Keep credentials out of version control and `config/` inaccessible over HTTP. Preserve UTF-8 Thai text, `utf8mb4` database encoding, and the `Asia/Bangkok` timezone.

## Testing

The project has no automated test suite or coverage target yet. Use the acceptance checklist in `spec.md` with synthetic applicant data. Check successful registration, duplicate email and phone rejection, closed courses, concurrent submissions for the last seat, admin access controls, mobile layouts, and Thai CSV output in Excel.

## Commits and Pull Requests

Use short, imperative commit subjects such as `Prevent overbooking on concurrent registration`. In pull requests, summarize the change, reference relevant issue or spec IDs, report verification steps, and include screenshots for visible changes.
