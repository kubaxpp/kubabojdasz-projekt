# Biblioteka gier (PHP + MySQL, XAMPP)

## Instalacja
1. Skopiuj folder `biblioteka-gier` do `C:\xampp\htdocs\`.
2. W XAMPP Control Panel uruchom **Apache** i **MySQL**.
3. Otwórz http://localhost/phpmyadmin → zakładka **Import** → wybierz `sql/schema.sql` → **Wykonaj**
4. Otwórz http://localhost/biblioteka-gier/

Dane bazy są w `config.php` (domyślnie XAMPP: `root`, bez hasła).

## Panel administratora
Login `admin`, hasło `admin123` (zmień je po instalacji). Admin dodaje, edytuje i usuwa gry.
Czytelnik (każdy gość) przegląda listę, wyszukuje, filtruje, ocenia gry i zmienia ich status.

## Struktura
- `index.php` – lista, wyszukiwanie po nazwie, filtr gatunku i statusu (PU-01..03)
- `gra.php` – szczegóły, ocena 1–10, zmiana statusu (PU-04..06)
- `stats.php` – statystyki (PU-07)
- `admin/` – logowanie, dodawanie/edycja (`form.php`), usuwanie (`delete.php`) (PU-08..10)
- `sql/schema.sql` – baza zgodna z diagramem ERD (gatunek, gra, biblioteka, biblioteka_gra + oceny, administrator)

## Zmiana hasła admina
Wygeneruj hash: `php -r "echo password_hash('noweHaslo', PASSWORD_DEFAULT);"`
i zaktualizuj tabelę `administrator` w phpMyAdmin.
