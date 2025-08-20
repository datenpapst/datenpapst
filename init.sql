CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    otp_secret VARCHAR(32),
    profile_image VARCHAR(255),
    role ENUM('admin','tenant') NOT NULL DEFAULT 'tenant',
    status ENUM('active','disabled','blocked') NOT NULL DEFAULT 'active'
);

CREATE TABLE apartments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address VARCHAR(255) NOT NULL,
    property_type ENUM('apartment','house') DEFAULT 'apartment',
    size INT,
    floor INT,
    has_lift TINYINT(1) DEFAULT 0,
    rooms INT,
    outdoor_space VARCHAR(20),
    energy_info TEXT,
    cellar TINYINT(1) DEFAULT 0,
    cellar_size INT,
    cellar_unit VARCHAR(50),
    unit_number VARCHAR(50),
    manager_contact VARCHAR(255),
    notes TEXT,
    bank_name VARCHAR(255),
    bank_iban VARCHAR(34),
    bank_bic VARCHAR(11),
    rent_base DECIMAL(10,2),
    cpi_base DECIMAL(10,2),
    cpi_threshold DECIMAL(5,2) DEFAULT 5,
    cpi_index VARCHAR(20) DEFAULT 'VPI2020',
    parking VARCHAR(50),
    laundry_room TINYINT(1) DEFAULT 0,
    bike_room TINYINT(1) DEFAULT 0,
    stroller_room TINYINT(1) DEFAULT 0,
    floors_total INT,
    has_garden TINYINT(1) DEFAULT 0,
    has_garage TINYINT(1) DEFAULT 0,
    title_image VARCHAR(255),
    heating_included BOOLEAN DEFAULT FALSE
);

CREATE TABLE tenant_apartment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT DEFAULT NULL,
    filename VARCHAR(255) NOT NULL,
    visibility ENUM('tenant','admin') NOT NULL DEFAULT 'tenant',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (sender_id) REFERENCES users(id)
);

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    visible_until DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE usage_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(20) NOT NULL,
    person_name VARCHAR(255),
    details TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE replacement_candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT NOT NULL,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (apartment_id) REFERENCES apartments(id),
    FOREIGN KEY (tenant_id) REFERENCES users(id)
);

CREATE TABLE replacement_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES replacement_candidates(id)
);

CREATE TABLE faq_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES faq_categories(id)
);

CREATE TABLE templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` VARCHAR(255) NOT NULL
);
INSERT INTO settings (`key`,`value`) VALUES
 ('primary_color','#0d6efd'),
 ('cpi_threshold_default','5'),
 ('cpi_current_VPI2020','100'),
 ('cpi_current_VPI2015','100'),
 ('site_title','TanMan Plattform'),
 ('contact_email','owner@example.com'),
 ('version','0.1');

CREATE TABLE rent_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT NOT NULL,
    payment_date DATE NOT NULL,
    rent DECIMAL(10,2) NOT NULL,
    op_costs_umlegbar DECIMAL(10,2) DEFAULT 0,
    op_costs_nonumlegbar DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE operating_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    effective_date DATE NOT NULL,
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE heating_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    effective_date DATE NOT NULL,
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE apartment_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'general',
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    invoice VARCHAR(255),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE page_contents (
    page VARCHAR(50) PRIMARY KEY,
    content TEXT NOT NULL
);

CREATE TABLE deletion_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE key_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT NOT NULL,
    request_type ENUM('loss','additional') NOT NULL,
    description TEXT,
    status ENUM('open','approved','rejected','replaced') DEFAULT 'open',
    cost DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE supply_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT NOT NULL,
    contract_type ENUM('electricity','gas') NOT NULL,
    provider VARCHAR(255),
    contract_start DATE,
    contract_end DATE,
    proof VARCHAR(255),
    status ENUM('active','terminated') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE therme_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT NOT NULL,
    service_date DATE NOT NULL,
    proof VARCHAR(255),
    status ENUM('submitted','approved','rejected') DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE apartment_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT NOT NULL,
    item VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    purchase_date DATE,
    warranty_months INT,
    invoice_path VARCHAR(255),
    included TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE damage_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT NOT NULL,
    inventory_id INT DEFAULT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255),
    discovered_at DATETIME NOT NULL,
    is_wear TINYINT(1) DEFAULT 0,
    late_report TINYINT(1) DEFAULT 0,
    status ENUM('reported','in_progress','needs_info','resolved') DEFAULT 'reported',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME DEFAULT NULL,
    insurance_claim VARCHAR(100),
    needs_pm TINYINT(1) DEFAULT 0,
    pm_notified_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id),
    FOREIGN KEY (inventory_id) REFERENCES apartment_inventory(id)
);
CREATE TABLE viewing_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    moveout_id INT NOT NULL,
    requested_time DATETIME NOT NULL,
    status ENUM('pending','approved','declined') DEFAULT 'pending',
    FOREIGN KEY (moveout_id) REFERENCES moveouts(id)
);
CREATE TABLE small_repairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    repair_date DATE NOT NULL,
    invoice VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE cpi_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    index_name VARCHAR(20) NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rent_increases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT NOT NULL,
    old_rent DECIMAL(10,2) NOT NULL,
    new_rent DECIMAL(10,2) NOT NULL,
    increase_amount DECIMAL(10,2) NOT NULL,
    old_cpi DECIMAL(10,2) NOT NULL,
    new_cpi DECIMAL(10,2) NOT NULL,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE mail_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(50) NOT NULL,
    lang CHAR(2) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    UNIQUE KEY(template_key, lang)
);

INSERT INTO mail_templates (template_key, lang, subject, body) VALUES
 ('rent_increase','de','Mietanpassung','Ihre Miete für {{address}} steigt auf {{new_rent}} EUR. Nachzahlung: {{increase}} EUR.'),
 ('rent_increase','en','Rent adjustment','Your rent for {{address}} increases to {{new_rent}} EUR. Additional amount: {{increase}} EUR.');

CREATE TABLE translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    de TEXT,
    en TEXT
);

INSERT INTO translations (key_name, de, en) VALUES
('widget_apartment','Wohnungsinfo','Apartment Info'),
('widget_messages','Nachrichten','Messages'),
('widget_announcements','Ankündigungen','Announcements'),
('widget_stats','Statistiken','Stats'),
('widget_requests','Anfragen','Requests'),
('translations_manage','Übersetzungen','Translations'),
('dashboard_layout','Dashboard-Layout','Dashboard Layout'),
('admin_layout','Admin-Layout','Admin Layout'),
('requests','Anfragen','Requests'),
 ('manage','Verwalten','Manage'),
 ('users','Benutzer','Users');

CREATE TABLE dashboard_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget VARCHAR(50) UNIQUE NOT NULL,
    position INT NOT NULL
);

INSERT INTO dashboard_widgets (widget, position) VALUES
 ('apartment',1),
 ('messages',2),
 ('announcements',3),
 ('moveout',4),
 ('service',5),
 ('events',6);

CREATE TABLE admin_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget VARCHAR(50) UNIQUE NOT NULL,
    position INT NOT NULL
);

INSERT INTO admin_widgets (widget, position) VALUES
 ('stats',1),
 ('requests',2);

CREATE TABLE user_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    widget VARCHAR(50) NOT NULL,
    position INT NOT NULL,
    UNIQUE KEY user_widget (user_id, widget),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO settings (`key`,`value`) VALUES
 ('smtp_host',''),('smtp_port',''),('smtp_user',''),('smtp_pass',''),('smtp_from','');

INSERT INTO translations (key_name, de, en) VALUES
('calendar','Kalender','Calendar'),
('calendar_manage','Kalender verwalten','Manage Calendar'),
('events','Termine','Events'),
('event_title','Titel','Title'),
('start','Beginn','Start'),
('end','Ende','End'),
('category','Kategorie','Category'),
('add_event','Termin hinzufügen','Add Event'),
('add_category','Kategorie hinzufügen','Add Category'),
('calendar_export','Kalender exportieren','Export Calendar'),
('no_events','Keine Termine','No Events'),
('categories','Kategorien','Categories'),
('mail_settings','Mail Einstellungen','Mail Settings'),
('smtp_host','SMTP Host','SMTP Host'),
('smtp_port','SMTP Port','SMTP Port'),
('smtp_user','SMTP Benutzer','SMTP User'),
('smtp_pass','SMTP Passwort','SMTP Password'),
('smtp_from','Absenderadresse','From Address'),
('add_user','Benutzer hinzufügen','Add User'),
('announcements','Ankündigungen','Announcements'),
('announcements_manage','Ankündigungen verwalten','Manage announcements'),
('visible_until','Sichtbar bis','Visible until'),
('existing_announcements','Bestehende Ankündigungen','Existing announcements'),
('delete','Löschen','Delete'),
('deleted','Gelöscht','Deleted'),
('fill_all_fields','Alle Felder ausfüllen','Fill in all fields'),
('back','Zurück','Back');

INSERT INTO translations (key_name, de, en) VALUES
('updates','Updates','Updates'),
('current_version','Aktuelle Version','Current version'),
('upload_update','Update hochladen','Upload update'),
('apply_update','Update anwenden','Apply update'),
('update_applied','Update installiert','Update applied'),
('update_failed','Update fehlgeschlagen','Update failed'),
('invalid_file','Ungültige Datei','Invalid file'),
('cpi_reminder','VPI-Daten älter als 3 Monate - bitte neue Datei hochladen','CPI data older than 3 months - please upload a new file'),
('insurance_claim','Schadenfallnummer','Insurance claim number'),
('notify_manager','Hausverwaltung informieren','Notify property manager'),
('send_to_manager','An Hausverwaltung senden','Send to property manager');

INSERT INTO translations (key_name, de, en) VALUES
('sent_to_manager','an Hausverwaltung gesendet','sent to property manager');

INSERT INTO translations (key_name, de, en) VALUES
('yes','Ja','Yes'),('no','Nein','No');

CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    sent_at DATETIME DEFAULT NULL
);

CREATE TABLE event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

INSERT INTO event_categories (name) VALUES ('Besichtigung'), ('Kontrolle');

CREATE TABLE calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    apartment_id INT NULL,
    category_id INT NULL,
    title VARCHAR(255) NOT NULL,
    start DATETIME NOT NULL,
    end DATETIME DEFAULT NULL,
    visible_to_tenants BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id),
    FOREIGN KEY (category_id) REFERENCES event_categories(id)
);

CREATE TABLE calendar_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    apartment_id INT NULL,
    category_id INT NULL,
    title VARCHAR(255) NOT NULL,
    start DATETIME NOT NULL,
    end DATETIME DEFAULT NULL,
    status ENUM('pending','approved','declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id),
    FOREIGN KEY (category_id) REFERENCES event_categories(id)
);

CREATE TABLE moveout_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE moveouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT NOT NULL,
    move_out_date DATE NOT NULL,
    deposit_amount DECIMAL(10,2) DEFAULT 0,
    deposit_deduction DECIMAL(10,2) DEFAULT 0,
    bank_account VARCHAR(255),
    handover_report VARCHAR(255),
    reason VARCHAR(50) DEFAULT 'tenant_notice',
    initiated_by ENUM('tenant','admin') DEFAULT 'admin',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (apartment_id) REFERENCES apartments(id)
);

CREATE TABLE moveout_task_status (
    moveout_id INT NOT NULL,
    task_id INT NOT NULL,
    tenant_done BOOLEAN DEFAULT FALSE,
    admin_confirmed BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (moveout_id, task_id),
    FOREIGN KEY (moveout_id) REFERENCES moveouts(id),
    FOREIGN KEY (task_id) REFERENCES moveout_tasks(id)
);

CREATE TABLE moveout_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    moveout_id INT NOT NULL,
    inventory_id INT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (moveout_id) REFERENCES moveouts(id),
    FOREIGN KEY (inventory_id) REFERENCES apartment_inventory(id)
);

CREATE TABLE deposit_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    moveout_id INT NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (moveout_id) REFERENCES moveouts(id)
);

INSERT INTO translations (key_name, de, en) VALUES
 ('moveout','Auszug','Move-out'),
 ('moveout_manage','Auszugsprozess','Move-out process'),
 ('days_until_moveout','Tage bis zum Auszug:','Days until move-out:'),
 ('moveout_tasks','To-Do-Liste','Checklist'),
 ('deposit','Kaution','Deposit'),
 ('deposit_amount','Kautionsbetrag','Deposit amount'),
 ('deposit_deduction','Abzüge','Deductions'),
 ('deposit_return','Rückzahlung','Refund'),
 ('bank_account','Bankverbindung','Bank account'),
 ('handover_report','Übergabeprotokoll','Handover report'),
 ('upload_handover','Protokoll hochladen','Upload report'),
 ('mark_done','Erledigt','Done'),
 ('confirm','Bestätigen','Confirm'),
 ('details','Details','Details'),
 ('save','Speichern','Save');

INSERT INTO translations (key_name, de, en) VALUES
 ('heating_costs','Heizkosten','Heating costs'),
 ('heating_notice','Nachforderungen aus BK/HK treffen den aktuellen Mieter; Gutschriften verbleiben beim Vermieter','Additional charges from operating or heating costs go to the current tenant; refunds stay with the landlord'),
 ('keys','Schlüssel','Keys'),
 ('report_key_loss','Schlüsselverlust melden','Report key loss'),
 ('request_new_key','Zusatzschlüssel anfordern','Request additional key'),
 ('supply_contracts','Versorgungsverträge','Supply contracts'),
 ('provider','Anbieter','Provider'),
 ('contract_start','Vertragsbeginn','Contract start'),
 ('contract_end','Vertragsende','Contract end'),
 ('upload_proof','Nachweis hochladen','Upload proof');

INSERT INTO translations (key_name, de, en) VALUES
 ('usage_change','Nutzungsänderung','Usage change'),
 ('usage_requests','Nutzungsänderungen','Usage requests'),
 ('usage_notice','Vermietung nur zu Wohnzwecken; andere Nutzungen nur mit Zustimmung des Vermieters','Use is residential only; other uses require landlord approval'),
 ('partner','Partner','Partner'),
 ('roommate','Mitbewohner','Roommate'),
 ('airbnb','Airbnb','Airbnb'),
 ('sublet','Untermiete','Sublet'),
 ('person_name','Name der Person','Person name'),
 ('pending','Offen','Pending'),
 ('approved','Genehmigt','Approved'),
 ('rejected','Abgelehnt','Rejected'),
 ('replacement_candidates','Nachmieterkandidaten','Replacement candidates'),
 ('add_candidate','Kandidaten hinzufügen','Add candidate'),
 ('candidate','Kandidat','Candidate'),
 ('tenant','Mieter','Tenant'),
 ('contact','Kontakt','Contact'),
 ('phone','Telefon','Phone'),
 ('shortlist','Engere Auswahl','Shortlist'),
 ('viewing','Besichtigung','Viewing'),
 ('accepted','Angenommen','Accepted'),
 ('upload','Hochladen','Upload'),
 ('title','Titel','Title');
INSERT INTO translations (key_name, de, en) VALUES
 ('small_repairs','Kleinreparaturen','Small repairs'),
 ('add_repair','Reparatur hinzufügen','Add repair'),
 ('repair_date','Reparaturdatum','Repair date'),
 ('year_total','Jahressumme','Year total'),
 ('max_repair_notice','Bis 150€ pro Fall, maximal 600€ pro Jahr','Repairs up to €150 each, max €600 per year'),
 ('cost_limit_exceeded','Betrag über 150€','Cost exceeds 150€'),
 ('year_limit_exceeded','Jahreslimit überschritten','Year limit exceeded'),
 ('invoice','Rechnung','Invoice'),
('invalid_file','Ungültige Datei','Invalid file'),
('file_rejected','Datei abgelehnt','File rejected');
INSERT INTO translations (key_name, de, en) VALUES
 ('approve','Bestätigen','Approve'),
 ('decline','Ablehnen','Decline'),
 ('request_event','Termin anfragen','Request appointment'),
 ('pending_requests','Offene Terminanfragen','Pending appointment requests'),
 ('photo_documentation','Fotodokumentation','Photo documentation'),
 ('upload_photo','Foto hochladen','Upload photo'),
 ('inventory_item','Inventargegenstand','Inventory item');

INSERT INTO translations (key_name, de, en) VALUES
 ('post_deposit_claims','Nachforderungen','Additional claims'),
 ('claim_description','Beschreibung','Description'),
 ('claim_amount','Betrag','Amount'),
 ('add_claim','Nachforderung hinzufügen','Add claim'),
 ('missing_photos','Fehlende Fotodokumentation','Missing photo documentation');

INSERT INTO translations (key_name, de, en) VALUES
('widget_events','Termine','Events'),
('upcoming_events','Bevorstehende Termine','Upcoming events'),
('view_calendar','Kalender ansehen','View calendar');

INSERT INTO translations (key_name, de, en) VALUES
 ('damage_type','Art','Type'),
 ('normal_wear','Normale Abnutzung','Normal wear'),
 ('damage','Beschädigung','Damage'),
 ('discovered_at','Festgestellt am','Discovered on'),
 ('late_report','Verspätete Meldung','Late report'),
 ('no_offset','Forderungen dürfen nicht mit dem Mietzins verrechnet oder einbehalten werden.','Claims cannot be offset against rent or withheld.'),
 ('moveout_reason','Beendigungsgrund','Termination reason'),
 ('tenant_notice','Kündigung durch Mieter','Tenant notice'),
 ('landlord_termination','Außerordentliche Kündigung','Landlord termination'),
 ('contract_breach','Vertragsverletzung','Contract breach'),
 ('structural_change','Bauliche Veränderung','Structural change'),
 ('cpi_history','VPI-Verlauf','CPI history'),
 ('value','Wert','Value'),
 ('recorded_at','Erfasst am','Recorded at');
