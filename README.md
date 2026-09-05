# KuTaWerk-Website

Neuaufbau der öffentlichen Website der Kultur- und Tanzwerkstatt e.V. mit Symfony 8.1 und PHP 8.4.1 oder neuer.

## Technische Anforderungen

- PHP >= 8.4.1
- Composer 2
- MySQL 8.0 oder neuer
- Apache mit `mod_rewrite` oder ein vergleichbar konfigurierter Webserver
- Document-Root muss auf `public/` zeigen
- `var/cache` und `var/log` müssen für den Webserver beschreibbar sein

## Vollständig mit Docker Compose starten

Der Stack enthält nginx, PHP-FPM 8.4, MySQL 8.4 und einen automatisch laufenden Migrationsdienst:

```bash
docker compose up -d --build
```

Die Website ist anschließend unter `http://localhost:8098` verfügbar. Der Port lässt sich mit `HTTP_PORT` ändern. MySQL-Daten bleiben im benannten Volume `kutawerk_database_data` erhalten.

Nach den Schema-Migrationen importiert der Startvorgang automatisch den versionierten Altbestand aus `content/pages/` und `content/legacy/`. Der Import ist idempotent: Er ergänzt nur noch nicht importierte Legacy-Datensätze und überschreibt keine redaktionellen Änderungen.

Für einen produktiven Betrieb müssen `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `APP_SECRET`, `MAILER_DSN`, `MAILER_FROM` und `KUTAWERK_DEFAULT_URI` über die Serverumgebung gesetzt werden.

## Ohne Docker lokal starten

```bash
composer install
php -S 127.0.0.1:8000 -t public public/router.php
```

Die Website einschließlich aller Unterseiten ist danach unter `http://127.0.0.1:8000/` erreichbar.

## Redaktion einrichten

Die Verwaltung liegt unter `/editor`. Der erste Administrator wird einmalig über die Konsole angelegt:

```bash
docker compose exec php php bin/console app:user:create-admin admin@kutawerk.de Vorname Nachname --password='MINDESTENS-12-ZEICHEN'
```

Weitere Benutzer werden anschließend durch einen Administrator angelegt und erhalten einen 48 Stunden gültigen Einladungslink. Passwörter werden ausschließlich gehasht gespeichert und sind für Administratoren nicht sichtbar.

Auf dem Server in `.env.local` eintragen (diese Datei wird nicht versioniert):

```dotenv
APP_ENV=prod
APP_SECRET=EIN_LANGER_ZUFAELLIGER_WERT
MAILER_DSN='smtp://SMTP-BENUTZER:SMTP-PASSWORT@SMTP-HOST:587?encryption=tls'
MAILER_FROM='info@kutawerk.de'
```

Termine werden in der MySQL-Tabelle `events` gespeichert. Die MySQL-Datenbank muss deshalb in die Datensicherung einbezogen werden.

Administratoren pflegen Benutzer, Trainer, Bereiche, Locations und Kurse. Berechtigte Trainer beziehungsweise Kursleiter pflegen ausschließlich die ihnen zugeordneten Trainingszeiten und Bereichstermine. Importierte Inhalte sind reguläre MySQL-Datensätze und können danach bearbeitet oder über ihren Veröffentlichungsstatus aus der öffentlichen Ansicht entfernt werden. Die öffentliche Wochenübersicht liegt unter `/areas/dance/schedule`, die jeweils aktuell generierte PDF unter `/areas/dance/schedule.pdf`.

## Inhalte

- `content/pages/`: lokal übernommene öffentliche Inhalte der bisherigen Website
- `public/media/`: lokal gesicherte öffentlich ausgelieferte Bilder
- `public/styles/site.css`: eigenständig implementierte Gestaltung der Vereinswebsite
- `templates/pages/`: eigenständige Twig-Seitentemplates
- `src/Entity/Event.php`: Doctrine-Entität für Veranstaltungen und Termine
- `src/Repository/EventRepository.php`: Datenbankabfragen für Veranstaltungen
- `migrations/`: versionierte MySQL-Schemaänderungen
- `variants/modern/`: gesicherter, nicht eingebundener Redesign-Entwurf

Die alten Website-Baukasten-Formulare wurden bewusst deaktiviert, weil sie ohne das bisherige Website-Baukasten-Konto keine Nachrichten zustellen könnten. Bis SMTP/Mailer eingerichtet ist, verweist die Seite transparent auf die veröffentlichten E-Mail-Adressen.

## Prüfung und Produktion

```bash
composer validate --strict --no-check-publish
php bin/console lint:twig templates
php bin/console lint:container
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

Auf Alfahosting wird `DATABASE_URL` mit den dort bereitgestellten MySQL-Zugangsdaten gesetzt, beispielsweise `mysql://BENUTZER:PASSWORT@HOST:3306/DATENBANK?serverVersion=8.0.0&charset=utf8mb4`. Zugangsdaten gehören ausschließlich in `.env.local` oder die Serverumgebung.

Nach dem Upload muss die Domain auf das Verzeichnis `public/` zeigen. Erst nach erfolgreicher Prüfung der Vorschau sollten die DNS- beziehungsweise Weiterleitungsregeln für `kutawerk.de` umgestellt werden.
