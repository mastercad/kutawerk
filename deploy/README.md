# Deployment für kutawerk.byte-artist.de

Das Deployment folgt dem Aufbau der Byte-Artist-Projektseite: GitHub Actions
prüft die Anwendung, veröffentlicht PHP- und Web-Images in GHCR und aktualisiert
den privaten Server anschließend per SSH.

## GitHub-Secrets

- `DEPLOY_SSH_HOST`: Hostname oder IP des Servers
- `DEPLOY_SSH_USER`: SSH-Benutzer
- `DEPLOY_SSH_KEY`: privater SSH-Schlüssel
- `DEPLOY_SSH_PORT`: SSH-Port, normalerweise `22`
- `DEPLOY_PATH`: Zielverzeichnis, beispielsweise `/opt/kutawerk`
- `APP_SECRET`: mindestens 32 zufällige Zeichen
- `MYSQL_PASSWORD`: URL-sicheres Passwort für den Anwendungsbenutzer
- `MYSQL_ROOT_PASSWORD`: separates URL-sicheres Root-Passwort
- `MAILER_DSN`: produktiver Symfony-Mailer-DSN
- `GHCR_TOKEN`: Token mit `read:packages` und `write:packages`

## Einmalige Servereinrichtung

1. DNS für `kutawerk.byte-artist.de` auf den Server zeigen lassen.
2. `deploy/nginx-kutawerk.conf` in die Konfiguration des zentralen nginx
   übernehmen und nginx neu laden.
3. Das Wildcard-Zertifikat für `*.byte-artist.de` muss unter
   `/etc/letsencrypt/live/byte-artist.de/` vorhanden sein.
4. Sicherstellen, dass Port `8098` auf dem Server noch nicht lokal belegt ist.
5. Die genannten GitHub-Secrets hinterlegen.

Der Anwendungscontainer ist nicht direkt öffentlich erreichbar. Er bindet nur
an `127.0.0.1:8098`; HTTPS endet am zentralen nginx.

## Ablauf

Ein Push auf `main` startet nacheinander:

1. `Quality Assurance`
2. `Build & Push Docker Images`
3. `Deploy Application`

Vor jedem Anwendungsstart werden Datenbankmigrationen und der idempotente
Initialimport ausgeführt. Datenbank und Uploads liegen in persistenten
Docker-Volumes.
