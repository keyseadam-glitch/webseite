#!/usr/bin/env bash
# Frische Joomla-Installation (aktuelle Stable 6.1.3) + Inhalte der alten Seite importieren.
# Voraussetzungen auf dem Mac: php >= 8.3 (brew install php), mariadb (brew install mariadb), unzip.
# Ein Aufruf reicht:   bash setup.sh      danach:   bash start.sh
set -euo pipefail
cd "$(dirname "$0")"
JV=6.1.3
if [ ! -f joomla/index.php ]; then
  echo "Lade Joomla $JV (offizielles Vollpaket) ..."
  curl -fL -o /tmp/joomla-$JV.zip "https://downloads.joomla.org/cms/joomla6/$(echo $JV | tr . -)/Joomla_$JV-Stable-Full_Package.zip?format=zip"
  mkdir -p joomla && unzip -q -o /tmp/joomla-$JV.zip -d joomla
fi
grep -q "PATCH_VERSION = 3" joomla/libraries/src/Version.php && grep -q "MINOR_VERSION = 1" joomla/libraries/src/Version.php || { echo "Unerwartete Joomla-Version in joomla/"; exit 1; }
if ! mariadb -uroot -e 'SELECT 1' >/dev/null 2>&1; then
  command -v brew >/dev/null && brew services start mariadb || true
  for i in $(seq 1 30); do mariadb -uroot -e 'SELECT 1' >/dev/null 2>&1 && break; sleep 1; done
fi
mariadb -uroot -e "CREATE DATABASE IF NOT EXISTS zmk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'zmk'@'localhost' IDENTIFIED BY 'zmk'; GRANT ALL ON zmk.* TO 'zmk'@'localhost'; FLUSH PRIVILEGES;"
if [ ! -f joomla/configuration.php ]; then
  (cd joomla && php installation/joomla.php install --site-name="ZMK Grünenplan" --admin-user="Administrator" --admin-username=admin --admin-password='ZmkKlon-2026!' --admin-email=admin@zmk-klon.local --db-type=mysqli --db-host=localhost --db-user=zmk --db-pass=zmk --db-name=zmk --db-prefix=zmk_ --no-interaction)
fi
(cd joomla && php cli/joomla.php extension:discover >/dev/null
  EID=$(mariadb -uzmk -pzmk zmk -N -e "SELECT extension_id FROM zmk_extensions WHERE name='zmk2700' AND state=-1" | head -1)
  [ -n "$EID" ] && php cli/joomla.php extension:discover:install --eid="$EID" >/dev/null)
[ -d quelle/mirror ] && php joomla-tools/extract.php
# PHP-Built-in-Server meldet sich als SAPI 'cli-server'; Joomla behandelt das wie CLI und braucht deshalb live_site.
(cd joomla && php cli/joomla.php config:set sef_rewrite=1 live_site=http://localhost:8080 >/dev/null)
php joomla-tools/import.php
echo "Fertig. Start:  bash start.sh"
