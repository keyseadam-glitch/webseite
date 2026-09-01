# Schritt 1 – Prüfung: Welche Joomla-Version gibt es? (Stand 1. September 2026)

## Ergebnis in drei Zeilen

- **Aktuelle stabile Version: Joomla 6.1.3** (Stable, veröffentlicht 18. August 2026). Parallel wird die alte 5er-Reihe noch mit 5.4.8 gepflegt.
- **Joomla 6 gibt es** (6.0.0 seit Oktober 2025). **Joomla 6.2** ist noch Beta (6.2.0-beta2 getaggt).
- **Joomla 7 gibt es NICHT als Release.** Es existiert nur ein Entwicklungszweig `7.0-dev` im Status „alpha1-dev“ mit geplantem Datum 12. Oktober 2027. Keyse darf dem Kunden keine „Joomla-7-Seite“ zusagen. Korrekt ist: „Joomla 6 (aktuell 6.1.3)“.

## PHP-Anforderung

- Joomla 6.1.3 verlangt **PHP ≥ 8.3.0** (`composer.json`: `"php": "^8.3.0"`; `cli/joomla.php`: `JOOMLA_MINIMUM_PHP = '8.3.0'`). Empfohlen wird PHP 8.4; das offizielle Docker-Image gibt es für PHP 8.3 und 8.4.
- Datenbank: MySQL ≥ 8.0.13 oder MariaDB ≥ 10.4 (oder PostgreSQL). Lokal verwendet: MariaDB 10.11.14.
- Die alte Seite läuft mit PHP 7.4 – damit läuft Joomla 6 **nicht**. Der Hoster muss PHP 8.3 oder 8.4 bereitstellen. Vor Auftragsannahme prüfen, was der aktuelle Hoster anbietet.

## Belege (selbst geprüft am 1.9.2026)

| Aussage | Quelle |
|---|---|
| 6.1.3 = Stable, 18.08.2026 | `libraries/src/Version.php` im Tag `6.1.3` des offiziellen Repos github.com/joomla/joomla-cms (`DEV_STATUS = 'Stable'`, `RELDATE = '18-August-2026'`); Kopie unter `docs/belege/Version-6.1.3.php` |
| Neueste Tags im Repo | `git ls-remote --tags https://github.com/joomla/joomla-cms`: … 6.1.2, 6.1.3, 6.2.0-alpha1…beta2. Kein Tag beginnt mit 7. |
| Offizielles Docker-Image | hub.docker.com, Image `joomla`: Tags `6.1.3`, `6.1.3-php8.3-apache`, `6.1.3-php8.4-apache`, `latest` = 6.1.3; 5er-Reihe: `5.4.8` |
| Joomla 7 nur Entwicklung | Zweig `7.0-dev`: `MAJOR_VERSION = 7`, `EXTRA_VERSION = 'alpha1-dev'`, `DEV_STATUS = 'Development'`, `RELDATE = '12-October-2027'`; Kopie unter `docs/belege/Version-7.0-dev.php` |
| PHP ≥ 8.3 | `composer.json` (Tag 6.1.3): `"require": {"php": "^8.3.0"}`; Kopie `docs/belege/composer-6.1.3.json` |
| Websuche (Bestätigung) | [downloads.joomla.org/latest](https://downloads.joomla.org/latest), [endoflife.date/joomla](https://endoflife.date/joomla), [mysites.guru: Joomla 6 requirements](https://mysites.guru/blog/joomla-6-technical-requirements/), [db8.nl: Joomla 5 or 6 in 2026](https://db8.nl/en/blog/joomla-5-or-joomla-6) – alle nennen 6.1.x als aktuell, PHP 8.3+, keine Joomla-7-Version |

Hinweis: downloads.joomla.org und endoflife.date waren aus der Arbeitsumgebung selbst nicht abrufbar (Netzsperre), die Belege stammen deshalb primär aus dem Quellcode-Repository und der Docker-Registry. Beide sind die Originalquellen des Joomla-Projekts.
