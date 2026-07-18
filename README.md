# Face Register Module

A self-contained face registration & recognition system with attendance
tracking, styled for KIFFY WORPSHIPPER. Combines:

- **Python (Flask + OpenCV LBPH)** — face detection/recognition API
- **PHP** — bridges the frontend to the API and MySQL
- **HTML/CSS/JS** — the register/recognize UI and post-login dashboard

## What's included

```
face-register-module/
├── index.html            # Register / Recognize UI
├── style.css              # Auth page styling (KIFFY WORPSHIPPER theme)
├── dashboard.php           # Post-recognition dashboard (stats + attendance log)
├── dashboard.css           # Dashboard styling
├── register.php            # PHP -> Flask bridge for registration
├── recognize.php           # PHP -> Flask bridge for recognition
├── db.php                  # Shared MySQL connection (reads config.php)
├── face_api.py              # Flask API: face detection + LBPH recognition
├── config.php.example       # Copy to config.php and fill in your DB details
├── schema.sql               # MySQL schema (users + attendance tables)
├── requirements.txt          # Python dependencies
├── setup.sh                  # One-command setup helper
└── README.md
```

## Requirements

- PHP 7.4+ with `mysqli` and `curl` extensions enabled
- MySQL / MariaDB
- Python 3.9+
- A webcam
- A local PHP server (XAMPP, WAMP, MAMP, or `php -S`)

## Setup

### 1. Run the setup script
```bash
chmod +x setup.sh
./setup.sh
```
This installs Python dependencies and creates `config.php` from the template.

If you'd rather do it manually:
```bash
pip install -r requirements.txt --break-system-packages
cp config.php.example config.php
```

### 2. Configure your database
Edit `config.php`:
```php
return [
    "db_host"     => "127.0.0.1",
    "db_port"     => 3306,
    "db_user"     => "root",
    "db_password" => "your_password_here",
    "db_name"     => "face_db",
    "face_api_url" => "http://localhost:5000",
];
```

### 3. Import the schema
```bash
mysql -u root -p < schema.sql
```

### 4. Start the face recognition API
```bash
python face_api.py
```
Leave this running — it must stay active while the web app is in use.
You should see:
```
* Running on http://127.0.0.1:5000
```

### 5. Serve the PHP/HTML files
Copy the whole folder into your PHP server's web root, e.g.:
```
C:\xampp\htdocs\face-register-module\
```
or run PHP's built-in server for quick testing:
```bash
php -S localhost:8080
```

### 6. Open it in your browser
```
http://localhost/face-register-module/index.html
```
or
```
http://localhost:8080/index.html
```

## How it works

1. **Register tab** — captures a face sample via webcam, sends it + a
   name to `register.php` → forwarded to the Flask API, which detects
   the face, saves it, and retrains the recognition model. Saves the
   name to the `users` table.
2. **Recognize tab** — captures a live frame, sends it to
   `recognize.php` → Flask matches it against trained faces. A match
   logs a timestamped row in `attendance` and redirects to
   `dashboard.php`.
3. **Duplicate protection** — registering a face that already matches
   someone in the system returns "You already been register" instead
   of creating a duplicate entry.
4. **Dashboard** — shows total registered users, today's check-in
   count, and the recognized person's own attendance history.

## Notes & limitations

- LBPH (OpenCV's built-in recognizer) is lightweight but less accurate
  than deep-learning-based recognition for large user bases or
  significant lighting/angle variation. Fine for small teams/classrooms.
- The dashboard currently identifies the user via a `?name=` URL
  parameter after recognition. For anything beyond local/trusted use,
  swap this for a PHP session (`$_SESSION`) so a name can't be typed
  directly into the URL to view someone else's dashboard.
- Face images are stored in `dataset/<name>/` as grayscale JPGs — treat
  this folder as sensitive biometric data; don't commit it to version
  control or share it.
- `config.php` is excluded from anything you share/version-control if
  you set a real password — only `config.php.example` should be shared.
