-- Aktualizacja istniejącej bazy: konta użytkowników + oceny przypisane do użytkowników.
-- Nie usuwa gier. Stare oceny (anonimowe) są zastąpione przykładowymi ocenami użytkowników.
USE biblioteka_gier;

CREATE TABLE IF NOT EXISTS uzytkownik (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(30) NOT NULL UNIQUE,
  haslo_hash VARCHAR(255) NOT NULL,
  rola ENUM('user','admin') NOT NULL DEFAULT 'user',
  utworzono TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SELECT 'migracja_administratora';

DROP TABLE IF EXISTS oceny;
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
