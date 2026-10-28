-- Acheto : base de donnees
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS acheto
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE acheto;

-- Utilisateurs et categories
CREATE TABLE IF NOT EXISTS villes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom_fr VARCHAR(80) NOT NULL,
  nom_ar VARCHAR(80) NOT NULL,
  UNIQUE KEY uq_villes_nom_fr (nom_fr)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom_fr VARCHAR(100) NOT NULL,
  nom_ar VARCHAR(100) NOT NULL,
  icone VARCHAR(50) NOT NULL DEFAULT 'bi-tag',
  actif TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_categories_nom_fr (nom_fr)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS utilisateurs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  telephone VARCHAR(20) DEFAULT NULL,
  ville_id INT UNSIGNED DEFAULT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  role ENUM('membre','admin') NOT NULL DEFAULT 'membre',
  actif TINYINT(1) NOT NULL DEFAULT 1,
  date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_utilisateurs_email (email),
  KEY idx_utilisateurs_role (role),
  CONSTRAINT fk_utilisateurs_ville FOREIGN KEY (ville_id)
    REFERENCES villes(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Annonces
CREATE TABLE IF NOT EXISTS annonces (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id INT UNSIGNED NOT NULL,
  categorie_id INT UNSIGNED NOT NULL,
  ville_id INT UNSIGNED NOT NULL,
  titre VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  prix DECIMAL(12,2) NOT NULL DEFAULT 0,
  telephone_contact VARCHAR(20) DEFAULT NULL,
  whatsapp VARCHAR(20) DEFAULT NULL,
  statut ENUM('en_attente','approuvee','refusee','expiree') NOT NULL DEFAULT 'en_attente',
  a_la_une TINYINT(1) NOT NULL DEFAULT 0,
  motif_refus VARCHAR(255) DEFAULT NULL,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_approbation DATETIME DEFAULT NULL,
  date_expiration DATETIME DEFAULT NULL,
  KEY idx_annonces_publiques (statut, date_expiration),
  KEY idx_annonces_categorie (categorie_id),
  KEY idx_annonces_ville (ville_id),
  KEY idx_annonces_prix (prix),
  KEY idx_annonces_date (date_creation),
  KEY idx_annonces_titre (titre),
  KEY idx_annonces_utilisateur (utilisateur_id),
  CONSTRAINT fk_annonces_utilisateur FOREIGN KEY (utilisateur_id)
    REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_annonces_categorie FOREIGN KEY (categorie_id)
    REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_annonces_ville FOREIGN KEY (ville_id)
    REFERENCES villes(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS images_annonces (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id INT UNSIGNED NOT NULL,
  chemin VARCHAR(255) NOT NULL,
  est_principale TINYINT(1) NOT NULL DEFAULT 0,
  ordre TINYINT UNSIGNED NOT NULL DEFAULT 0,
  date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_images_annonce (annonce_id),
  CONSTRAINT fk_images_annonce FOREIGN KEY (annonce_id)
    REFERENCES annonces(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favoris (
  utilisateur_id INT UNSIGNED NOT NULL,
  annonce_id INT UNSIGNED NOT NULL,
  date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (utilisateur_id, annonce_id),
  KEY idx_favoris_annonce (annonce_id),
  CONSTRAINT fk_favoris_utilisateur FOREIGN KEY (utilisateur_id)
    REFERENCES utilisateurs(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_favoris_annonce FOREIGN KEY (annonce_id)
    REFERENCES annonces(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id INT UNSIGNED NOT NULL,
  expediteur_id INT UNSIGNED DEFAULT NULL,
  nom_expediteur VARCHAR(100) NOT NULL,
  email_expediteur VARCHAR(150) NOT NULL,
  contenu TEXT NOT NULL,
  lu TINYINT(1) NOT NULL DEFAULT 0,
  date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_annonce (annonce_id),
  CONSTRAINT fk_messages_annonce FOREIGN KEY (annonce_id)
    REFERENCES annonces(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_messages_expediteur FOREIGN KEY (expediteur_id)
    REFERENCES utilisateurs(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Donnees de depart
INSERT IGNORE INTO categories (id, nom_fr, nom_ar, icone) VALUES
  (1,  'Véhicules',                   'السيارات',                  'bi-car-front'),
  (2,  'Immobilier',                  'العقارات',                  'bi-building'),
  (3,  'Téléphones et tablettes',     'الهواتف واللوحات',          'bi-phone'),
  (4,  'Informatique et électronique','الإعلاميات والإلكترونيات',  'bi-laptop'),
  (5,  'Maison et meubles',           'المنزل والأثاث',            'bi-lamp'),
  (6,  'Vêtements et accessoires',    'الملابس والإكسسوارات',      'bi-handbag'),
  (7,  'Emploi et services',          'الوظائف والخدمات',          'bi-briefcase'),
  (8,  'Loisirs et sports',           'الترفيه والرياضة',          'bi-trophy'),
  (9,  'Animaux',                     'الحيوانات',                 'bi-emoji-smile'),
  (10, 'Matériel professionnel',      'المعدات المهنية',           'bi-tools'),
  (11, 'Autres',                      'أخرى',                      'bi-three-dots');

INSERT IGNORE INTO villes (nom_fr, nom_ar) VALUES
  ('Casablanca', 'الدار البيضاء'),
  ('Rabat',      'الرباط'),
  ('Marrakech',  'مراكش'),
  ('Fès',        'فاس'),
  ('Tanger',     'طنجة'),
  ('Agadir',     'أكادير'),
  ('Meknès',     'مكناس'),
  ('Oujda',      'وجدة'),
  ('Kénitra',    'القنيطرة'),
  ('Tétouan',    'تطوان'),
  ('Salé',       'سلا'),
  ('Béni Mellal','بني ملال');

  -- Messages envoyes depuis la page Contact du site
CREATE TABLE IF NOT EXISTS contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  sujet VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  lu TINYINT(1) NOT NULL DEFAULT 0,
  date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contacts_lu (lu, date_envoi)
) ENGINE=InnoDB;