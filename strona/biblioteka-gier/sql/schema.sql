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
DROP TABLE IF EXISTS uzytkownik;

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

CREATE TABLE IF NOT EXISTS uzytkownik (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(30) NOT NULL UNIQUE,
  haslo_hash VARCHAR(255) NOT NULL,
  rola ENUM('user','admin') NOT NULL DEFAULT 'user',
  utworzono TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE oceny (
  id_gry INT UNSIGNED NOT NULL,
  id_uzytkownik INT UNSIGNED NOT NULL,
  ocena TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id_gry, id_uzytkownik),
  CONSTRAINT fk_oceny_gra FOREIGN KEY (id_gry)
    REFERENCES gra(id_gry) ON DELETE CASCADE,
  CONSTRAINT fk_oceny_uzytkownik FOREIGN KEY (id_uzytkownik)
    REFERENCES uzytkownik(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- dane startowe -------------------------------------------------------------
INSERT INTO gatunek (nazwa) VALUES ('FPS'),('RPG'),('Wyścigowe'),('Sportowe');

INSERT INTO biblioteka (nazwa) VALUES ('Moja biblioteka');

INSERT INTO gra (tytul, producent, rok_wydania, platforma, ocena, status, id_gatunek) VALUES
 ('Cyberpunk 2077','CD Projekt RED',2020,'PC',NULL,'W trakcie',2),
 ('The Witcher 3: Wild Hunt','CD Projekt RED',2015,'PC',NULL,'Ukończona',2),
 ('Counter-Strike 2','Valve',2023,'PC',NULL,'W trakcie',1),
 ('DOOM Eternal','id Software',2020,'PC',NULL,'Ukończona',1),
 ('Forza Horizon 5','Playground Games',2021,'Xbox',NULL,'Do zagrania',3),
 ('Need for Speed: Heat','Ghost Games',2019,'PlayStation',NULL,'Ukończona',3),
 ('FIFA 23','EA Sports',2022,'PlayStation',NULL,'Do zagrania',4),
 ('NBA 2K24','Visual Concepts',2023,'PC',NULL,'W trakcie',4);

INSERT INTO biblioteka_gra (id_biblioteka, id_gry) SELECT 1, id_gry FROM gra;

-- przykładowi użytkownicy (hasło każdego z nich: haslo123) i ich oceny
INSERT IGNORE INTO uzytkownik (login, haslo_hash, rola) VALUES
 ('admin','$2y$10$7rj2LoIfiad1y9iYiVtvZeykNkJnJrA89hCmuAuVvB6lWtBsLu7YC','admin'),
 ('anna','$2y$10$ylH.VlHfOcbbp2ajw8f5xuiiNsM33fCmQNGmAQ72p6oeHRAEPzIWG','user'),
 ('marek','$2y$10$ylH.VlHfOcbbp2ajw8f5xuiiNsM33fCmQNGmAQ72p6oeHRAEPzIWG','user'),
 ('kasia','$2y$10$ylH.VlHfOcbbp2ajw8f5xuiiNsM33fCmQNGmAQ72p6oeHRAEPzIWG','user');

INSERT IGNORE INTO oceny (id_gry, id_uzytkownik, ocena)
SELECT g.id_gry, u.id, v.o FROM (
  SELECT 'Cyberpunk 2077' t,'anna' l,8 o UNION ALL
  SELECT 'Cyberpunk 2077' t,'marek' l,7 o UNION ALL
  SELECT 'Cyberpunk 2077' t,'kasia' l,9 o UNION ALL
  SELECT 'The Witcher 3: Wild Hunt' t,'anna' l,10 o UNION ALL
  SELECT 'The Witcher 3: Wild Hunt' t,'marek' l,9 o UNION ALL
  SELECT 'The Witcher 3: Wild Hunt' t,'kasia' l,10 o UNION ALL
  SELECT 'Counter-Strike 2' t,'anna' l,8 o UNION ALL
  SELECT 'Counter-Strike 2' t,'marek' l,9 o UNION ALL
  SELECT 'Counter-Strike 2' t,'kasia' l,8 o UNION ALL
  SELECT 'DOOM Eternal' t,'anna' l,9 o UNION ALL
  SELECT 'DOOM Eternal' t,'marek' l,10 o UNION ALL
  SELECT 'DOOM Eternal' t,'kasia' l,9 o UNION ALL
  SELECT 'Forza Horizon 5' t,'anna' l,9 o UNION ALL
  SELECT 'Forza Horizon 5' t,'marek' l,8 o UNION ALL
  SELECT 'Need for Speed: Heat' t,'anna' l,7 o UNION ALL
  SELECT 'Need for Speed: Heat' t,'marek' l,6 o UNION ALL
  SELECT 'FIFA 23' t,'anna' l,6 o UNION ALL
  SELECT 'FIFA 23' t,'marek' l,5 o UNION ALL
  SELECT 'FIFA 23' t,'kasia' l,7 o UNION ALL
  SELECT 'NBA 2K24' t,'anna' l,7 o UNION ALL
  SELECT 'NBA 2K24' t,'marek' l,8 o
) v JOIN gra g ON g.tytul = v.t JOIN uzytkownik u ON u.login = v.l;

UPDATE gra SET ocena = (SELECT ROUND(AVG(o.ocena),1) FROM oceny o WHERE o.id_gry = gra.id_gry);
