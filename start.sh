#!/usr/bin/env bash
# Startet MariaDB (falls nicht läuft) und die Joomla-Seite auf http://localhost:8080
set -euo pipefail
cd "$(dirname "$0")"
if ! mariadb -uzmk -pzmk -e 'SELECT 1' zmk >/dev/null 2>&1; then
  echo "MariaDB starten ..."
  if command -v brew >/dev/null 2>&1; then brew services start mariadb >/dev/null; else (mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld; nohup mariadbd-safe --user=mysql >/dev/null 2>&1 &); fi
  for i in $(seq 1 30); do mariadb -uzmk -pzmk -e 'SELECT 1' zmk >/dev/null 2>&1 && break; sleep 1; done
fi
echo "Joomla läuft auf http://localhost:8080  (Backend: http://localhost:8080/administrator  Benutzer admin)"
exec php -S localhost:8080 -t joomla joomla/router.php
