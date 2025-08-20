# TanMan Plattform (PHP)

Einfaches, sicheres Dashboard für Vermieter und Mieter auf Basis von PHP und MySQL.

## Funktionen
- Admin-Menü mit Unterseiten zum Verwalten von Wohnungen und zum Versenden von Nachrichten
- Mieter-Menü mit Dokumentenverwaltung, Posteingang und bearbeitbaren Kontaktdaten
- Benutzerverwaltung zum Deaktivieren/Löschen und Hinterlegen von Mieterdokumenten
- Selbstbedienung für Mieteranfragen (Haustier, Kündigung etc.)
- Nutzungsänderungsanträge für Partner/Mitbewohner oder unerwünschte Airbnb-/Untermietwünsche
- Nachmietersuche durch den Mieter mit Kandidatenprofilen und Dokumentupload
- FAQ-Bereich mit Kategorien, pflegbar über das Admin-Panel
- Downloadbereich für Vorlagen (z.B. Übergabeprotokoll)
- "Passwort vergessen"-Funktion für den Login
- Anmeldung per E-Mail und Passwort mit rollenbasiertem Zugriff
- Zwei-Faktor-Authentifizierung via Authenticator-App
- Login-Formulare mit Google reCAPTCHA gegen Spam
- Responsive Oberfläche für Desktop und Mobilgeräte
- Umschaltbare Sprache (Deutsch/Englisch) mit automatischem Vorschlag je nach System
- Dark Mode per Schalter für Mieter und Admin
- Versionierung mit Update-Seite im Adminbereich (aktuell v0.1)
- Erinnerung, wenn länger als drei Monate kein VPI-Upload erfolgte
- Admin wählt eine individuelle Primärfarbe im Control Panel
- Allgemeine Einstellungen wie Seitentitel und Kontakt-E-Mail im Adminbereich
- Hamburger-Menü und Cookie-Hinweis für mobile und DSGVO-Konformität
- Backup- und Seitenverwaltung (Impressum/Datenschutz) im Adminbereich
- Finanzverwaltung pro Wohnung mit Erfassung von Mieten und Ausgaben inkl. Diagrammen
- Steuerübersicht (E1b) je Objekt mit Jahreswerten für Einnahmen und Ausgaben
- VPI-gekoppelte Mietanpassung mit automatischer Hinweisfunktion im Dashboard
- Automatische CPI-Berechnung durch XLSX-Upload mit sofortiger Mietanpassung und Mailbenachrichtigung
- Bankdaten pro Wohnung zur Anzeige für Mieter
- Betriebskostenübersicht pro Wohnung mit Hinweis bei Kostensteigerungen
- Optionaler Heizkostenbereich je Wohnung mit Hinweis auf vertragliche Nachforderungen
- Titelbilder und Google-Maps-Ansicht pro Wohnung
- Verwaltung von Wohnungen und Einfamilienhäusern
- Zusätzliche Wohnungsdetails wie Stockwerk, Lift, Zimmeranzahl, Außenflächen, Keller- und Hausverwaltungsangaben; bei Häusern Anzahl der Stockwerke sowie Garten- und Garagenangaben, bei Wohnungen Parkplätze und Gemeinschaftsräume (Waschküche, Fahrrad- und Kinderwagenraum)
- Inventarlisten pro Wohnung mit Änderungsdatum
- Schadenmeldungen mit Foto-Upload, Inventarbezug und grafischem Fortschrittsstatus
- Inventargegenstände mit Kaufdatum, Garantiedauer und Rechnungsablage
- Kleinreparaturen bis 150 € pro Fall (max. 600 € jährlich) werden vom Mieter dokumentiert und im Adminbereich angezeigt
- Kündigungsprozess mit To-Do-Liste, Kautionsabrechnung und Upload des Übergabeprotokolls
- Nachforderungen nach Kautionsrückzahlung werden dokumentiert und für ehemalige Mieter einsehbar
- Widgets auf Admin- und Mieter-Dashboard lassen sich per Drag & Drop neu anordnen
- Bearbeitbare Mailvorlagen für automatische Benachrichtigungen
- Konfigurierbarer Versand von Einladungen und Benachrichtigungen über eigenen SMTP-Server
- Terminverwaltung mit Kategorien, Google-/iCal-Export und beidseitigen Terminanfragen
- Übergabeprotokoll mit unveränderten Foto-Uploads aller Inventargegenstände
- Globale Ankündigungen vom Vermieter, die im Dashboard der Mieter erscheinen
- Schlüsselverwaltung: Mieter melden Verlust oder beantragen Zusatzschlüssel, Admin dokumentiert Kosten
- Versorgungsverträge: Upload von Strom- und Gasnachweisen mit Statusverwaltung
- Thermenservice: jährliche Nachweisuploads mit Erinnerung im Dashboard

## Installation
1. **Voraussetzungen installieren** – Beispiel für Debian/Ubuntu:
   ```bash
   sudo apt update
   sudo apt install apache2 mariadb-server php libapache2-mod-php \
        php-mysql php-gd php-curl php-xml php-mbstring php-zip php-intl \
        clamav
   sudo systemctl enable --now apache2 mariadb
   ```
   Apache stellt die Seite bereit, MariaDB speichert die Daten, PHP‑Module wie `php-gd` erlauben Bildverarbeitung, `clamav` prüft Uploads auf Viren.
2. **Quellcode bereitstellen** – Repository nach `/var/www/html` klonen oder kopieren:
   ```bash
   sudo git clone https://example.com/datenpapst.git /var/www/html
   ```
   Rechte setzen: `sudo chown -R www-data:www-data /var/www/html`.
3. **Datenbank vorbereiten** – in MariaDB einloggen und Schema importieren:
   ```bash
   sudo mysql -e "CREATE DATABASE mietverwaltung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   sudo mysql mietverwaltung < init.sql
   ```
4. **Admin-Benutzer anlegen**:
   ```sql
   INSERT INTO users (email, password_hash, role)
   VALUES ('admin@example.com', PASSWORD_HASH, 'admin');
   ```
   Hash erzeugen: `php -r "echo password_hash('deinpasswort', PASSWORD_DEFAULT);"`.
5. **Google reCAPTCHA Schlüssel holen** – unter <https://www.google.com/recaptcha/admin/create> Typ *v2 Checkbox* wählen und Site‑Key sowie Secret notieren.
6. **Umgebungsvariablen setzen** – z. B. in `/etc/apache2/envvars` oder per Systemd:
   ```bash
   export DB_HOST=localhost
   export DB_USER=root
   export DB_PASS=geheim
   export DB_NAME=mietverwaltung
   export RECAPTCHA_SITE_KEY=dein_site_key
   export RECAPTCHA_SECRET_KEY=dein_secret_key
   ```
   Webserver neu starten: `sudo systemctl restart apache2`.
7. **Installationsskript ausführen** – `install.php` im Browser aufrufen. Das Skript prüft abhängige Schlüssel und erinnert an die Aktivierung der Zwei‑Faktor‑Authentifizierung.
8. **Cronjobs einrichten** – für den Mailversand und Backups:
   ```bash
   crontab -e
   # E-Mails alle 5 Minuten senden
   */5 * * * * php /var/www/html/send_email_queue.php
   # Wöchentlicher Datenbank-Backup
   0 3 * * 0 php /var/www/html/admin_backups.php >/dev/null 2>&1
   ```
9. **Erster Login** – `http://localhost/` aufrufen, als Admin anmelden und unter „2FA Einstellungen“ den QR‑Code scannen.

## Sicherheit
- Datenbankzugriffe erfolgen mit vorbereiteten Statements.
- Passwörter werden gehasht gespeichert.
- Session-Cookies sind `HttpOnly` und `SameSite=Strict`.
- Datei-Uploads landen in benutzerbezogenen Verzeichnissen außerhalb des Webroots.
- Datei-Uploads werden – sofern ClamAV installiert ist – automatisch auf Schadsoftware geprüft.
