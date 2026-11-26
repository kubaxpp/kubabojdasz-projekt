-- Aktualizacja 2: opis i okładka gry, komentarz przy ocenie.
USE biblioteka_gier;

ALTER TABLE gra ADD COLUMN opis TEXT NULL, ADD COLUMN okladka VARCHAR(255) NULL;
ALTER TABLE oceny ADD COLUMN komentarz VARCHAR(500) NULL;

UPDATE gra SET opis = 'Otwarty świat nocnego miasta przyszłości. Jako najemnik V szukasz sposobu na przetrwanie.' WHERE tytul = 'Cyberpunk 2077' AND opis IS NULL;
UPDATE gra SET opis = 'Wiedźmin Geralt szuka przybranej córki w ogromnym, mrocznym świecie pełnym potworów i trudnych wyborów.' WHERE tytul = 'The Witcher 3: Wild Hunt' AND opis IS NULL;
UPDATE gra SET opis = 'Drużynowa strzelanka, w której liczą się celność, współpraca i ekonomia rund.' WHERE tytul = 'Counter-Strike 2' AND opis IS NULL;
UPDATE gra SET opis = 'Szybka strzelanina przeciwko hordom demonów. Stanie w miejscu oznacza przegraną.' WHERE tytul = 'DOOM Eternal' AND opis IS NULL;
UPDATE gra SET opis = 'Wyścigi w otwartym świecie inspirowanym Meksykiem, z setkami samochodów do odblokowania.' WHERE tytul = 'Forza Horizon 5' AND opis IS NULL;
UPDATE gra SET opis = 'W dzień legalne wyścigi, w nocy ucieczki przed policją w mieście Palm City.' WHERE tytul = 'Need for Speed: Heat' AND opis IS NULL;
UPDATE gra SET opis = 'Piłka nożna z licencjonowanymi klubami i ligami oraz trybem kariery.' WHERE tytul = 'FIFA 23' AND opis IS NULL;
UPDATE gra SET opis = 'Symulacja koszykówki NBA z trybem kariery i budowaniem własnej drużyny.' WHERE tytul = 'NBA 2K24' AND opis IS NULL;
