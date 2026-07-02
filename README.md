# WU-P-A-FRAMS

Facial Recognition Attendance Management System built with PHP, MySQL, and a local Python Flask recognition service.

FRAMS helps manage students, teachers, attendance events, manual attendance records, reports, and webcam-based face enrollment/recognition for attendance workflows.

## Features

- Session-based authentication
- Admin and teacher role access
- Student create, edit, soft delete, and profile photo upload
- Multiple face image enrollment per student
- Live webcam attendance recognition
- Manual attendance marking and editing
- Attendance event scheduling
- Dashboard summaries and charts
- Date, student, course, and status report filters
- CSV report export
- PHP API relay for Python recognition endpoints
- Offline/local Python recognition API using Flask and OpenCV

## Tech Stack

- PHP 8+
- MySQL or MariaDB
- Apache / XAMPP
- Bootstrap 5
- Chart.js
- Python 3
- Flask
- OpenCV
- Pillow
- NumPy

## Project Structure

```text
FRAMS/
|-- assets/                 # CSS, JavaScript, and images
|-- config/                 # App and database configuration
|-- controllers/            # PHP controller classes
|-- database/               # MySQL schema and seed data
|-- models/                 # PHP model classes
|-- python_api_offline/     # Local Flask face recognition API
|-- views/                  # PHP views/templates
|-- .htaccess               # Apache rewrite rules
|-- index.php               # Front controller
|-- README.md
```

## Requirements

- XAMPP or equivalent Apache/MySQL/PHP environment
- PHP 8 or newer
- MySQL or MariaDB
- Python 3.10 or newer
- Git

## Setup

1. Clone or download the project into your XAMPP web root:

```bash
c:/xampp/htdocs/FRAMS
```

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Import the database schema:

```sql
SOURCE database/schema.sql;
```

You can also import `database/schema.sql` through phpMyAdmin.

4. Check the database settings in `config/database.php`.

Default local settings:

```text
Host: localhost
Database: frams_db
Username: root
Password: empty
```

5. Open the web app:

```text
http://localhost/FRAMS/
```

## Demo Accounts

The database seed includes demo users:

```text
Admin:   admin@frams.com / password
Teacher: teacher@frams.com / password
```

Change these credentials before using the system outside local development.

## Offline Python API

The project includes a local Flask API in `python_api_offline/`. It provides these endpoints:

- `GET /health`
- `POST /enroll`
- `POST /unenroll`
- `POST /recognize`

The PHP app expects the API at:

```text
http://127.0.0.1:5000
```

This value is configured in `config/config.php` as `PYTHON_API_URL`.

### Install Python Dependencies

From the project root:

```bash
python -m venv .venv
.venv\Scripts\activate
pip install -r python_api_offline/requirements.txt
```

### Run the Python API

```bash
python python_api_offline/app.py
```

Keep this service running while using webcam enrollment or recognition features.

## Important GitHub Notes

The repository is configured to ignore local/private runtime files, including:

- `.venv/`
- `.vscode/`
- `uploads/`
- `python_api_offline/data/`
- Python cache files
- `.env` files
- logs and temporary files

These ignored folders may contain local dependencies, uploaded face images, and generated face enrollment data. Do not commit them to a public repository.

## Security Notes

- Uses PDO prepared statements for database queries
- Uses CSRF tokens on POST forms
- Regenerates session IDs on login
- Applies role checks in controller actions
- Validates uploaded image type and size
- Stores demo passwords as hashes in the seed schema

For production use, move database credentials into environment variables, use HTTPS, replace demo accounts, and review upload storage permissions.

## Route Overview

Routes are handled through query parameters:

```text
/?page={page}&action={action}
```

Main pages:

- `auth`
- `dashboard`
- `students`
- `teachers`
- `attendance`
- `reports`
- `api`

## License

This project is licensed under the MIT License. See the LICENSE file for details.
