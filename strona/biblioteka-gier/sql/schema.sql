-- Biblioteka gier – schemat bazy (zgodny z diagramem ERD z projektu)
-- Import: phpMyAdmin -> Import, albo: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS biblioteka_gier
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteka_gier;

DROP TABLE IF EXISTS oceny;
DROP TABLE IF EXISTS biblioteka_gra;
DROP TABLE IF EXISTS gra;
DROP TABLE IF EXISTS biblioteka;
DROP TABLE IF EXISTS gatunek;
DROP TABLE IF EXISTS administrator;

CREATE TABLE gatunek (
  id_gatunek INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nazwa VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE gra (
  id_gry INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tytul VARCHAR(150) NOT NULL,
  producent VARCHAR(100) NOT NULL,
  rok_wydania SMALLINT UNSIGNED NOT NULL,
  platforma VARCHAR(50) NOT NULL,
  ocena DECIMAL(3,1) NULL DEFAULT NULL,          -- średnia ocen (1-10)
  status ENUM('Do zagrania','W trakcie','Ukończona') NOT NULL DEFAULT 'Do zagrania',
  id_gatunek INT UNSIGNED NOT NULL,
  CONSTRAINT fk_gra_gatunek FOREIGN KEY (id_gatunek)
    REFERENCES gatunek(id_gatunek) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_gra_tytul (tytul),
  INDEX idx_gra_status (status)
) ENGINE=InnoDB;

CREATE TABLE biblioteka (
  id_biblioteka INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nazwa VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE biblioteka_gra (
  id_biblioteka INT UNSIGNED NOT NULL,
  id_gry INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_biblioteka, id_gry),
  CONSTRAINT fk_bg_biblioteka FOREIGN KEY (id_biblioteka)
    REFERENCES biblioteka(id_biblioteka) ON DELETE CASCADE,
  CONSTRAINT fk_bg_gra FOREIGN KEY (id_gry)
    REFERENCES gra(id_gry) ON DELETE CASCADE
) ENGINE=InnoDB;

-- pojedyncze oceny użytkowników (jedna ocena na gościa i grę, można ją zmienić)
CREATE TABLE oceny (
  id_gry INT UNSIGNED NOT NULL,
  uzytkownik CHAR(32) NOT NULL,
  ocena TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id_gry, uzytkownik),
  CONSTRAINT fk_oceny_gra FOREIGN KEY (id_gry)
    REFERENCES gra(id_gry) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE administrator (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(50) NOT NULL UNIQUE,
  haslo_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- dane startowe -------------------------------------------------------------
INSERT INTO gatunek (nazwa) VALUES ('FPS'),('RPG'),('Wyścigowe'),('Sportowe');

INSERT INTO biblioteka (nazwa) VALUES ('Moja biblioteka');

INSERT INTO gra (tytul, producent, rok_wydania, platforma, ocena, status, id_gatunek) VALUES
 ('Cyberpunk 2077','CD Projekt RED',2020,'PC',8.0,'W trakcie',2),
 ('The Witcher 3: Wild Hunt','CD Projekt RED',2015,'PC',9.5,'Ukończona',2),
 ('Counter-Strike 2','Valve',2023,'PC',8.5,'W trakcie',1),
 ('DOOM Eternal','id Software',2020,'PC',9.0,'Ukończona',1),
 ('Forza Horizon 5','Playground Games',2021,'Xbox',9.0,'Do zagrania',3),
 ('Need for Speed: Heat','Ghost Games',2019,'PlayStation',7.0,'Ukończona',3),
 ('FIFA 23','EA Sports',2022,'PlayStation',6.5,'Do zagrania',4),
 ('NBA 2K24','Visual Concepts',2023,'PC',7.5,'W trakcie',4);

INSERT INTO biblioteka_gra (id_biblioteka, id_gry) SELECT 1, id_gry FROM gra;

-- konto administratora: login "admin", hasło "admin123" (zmień po instalacji!)
INSERT INTO administrator (login, haslo_hash) VALUES
 ('admin','$2y$10$7rj2LoIfiad1y9iYiVtvZeykNkJnJrA89hCmuAuVvB6lWtBsLu7YC');
