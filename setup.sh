#!/bin/bash
# Face Register Module — one-time setup script
set -e

echo "== Face Register Module Setup =="

# 1. Python dependencies
echo "-> Installing Python dependencies..."
pip install -r requirements.txt --break-system-packages

# 2. Config file
if [ ! -f config.php ]; then
    echo "-> Creating config.php from template..."
    cp config.php.example config.php
    echo "   Edit config.php with your database credentials before continuing."
else
    echo "-> config.php already exists, skipping."
fi

echo ""
echo "Setup files are ready. Next steps:"
echo "  1. Edit config.php with your DB username/password."
echo "  2. Import the database schema:"
echo "       mysql -u root -p < schema.sql"
echo "  3. Start the face recognition API:"
echo "       python face_api.py"
echo "  4. Place this whole folder in your PHP server's web root (e.g. XAMPP htdocs/)."
echo "  5. Open index.html in your browser."
