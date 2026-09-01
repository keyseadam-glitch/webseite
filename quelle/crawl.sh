#!/usr/bin/env bash
# Reiner LESE-Zugriff auf die alte Seite. Kein Login, keine Zugangsdaten, kein Schreibzugriff.
# Spiegelt http://zmk-gruenenplan.de komplett (HTML, Bilder, CSS, JS, Logo) in ./quelle/mirror
# und schreibt eine Seiten-/Bildliste. Auf dem Mac ausführen:
#   cd /Users/keys/zmk-klon && bash quelle/crawl.sh
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p mirror
wget \
  --mirror --page-requisites --convert-links --adjust-extension --no-parent \
  --wait=1 --random-wait --limit-rate=300k \
  --user-agent="Mozilla/5.0 (Macintosh) zmk-klon-crawler (nur lesen)" \
  --reject-regex='(\?|/)(format=feed|type=rss|type=atom|task=|view=login|component/users)' \
  --directory-prefix=mirror \
  --output-file=crawl.log \
  http://zmk-gruenenplan.de/ || true   # wget liefert Exit 8 bei einzelnen 404, das ist ok
echo "== HTML-Seiten"; find mirror -name '*.html' | sort | tee seiten.txt
echo "== Bilder";      find mirror -type f \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' -o -iname '*.gif' -o -iname '*.svg' \) | sort | tee bilder.txt
echo "== Fertig. Danach: php ../joomla-tools/import.php  (siehe docs/README.md)"
