# Biblioteka gier (PHP + MySQL, XAMPP)

## Instalacja
1. Skopiuj folder `biblioteka-gier` do `C:\xampp\htdocs\`.
2. W XAMPP Control Panel uruchom **Apache** i **MySQL**.
3. Otwórz http://localhost/biblioteka-gier/ – jeśli bazy nie ma, strona sama przeniesie Cię do `install.php`,
   który tworzy bazę, tabele i przykładowe dane. Jeśli masz już starszą wersję bazy z grami,
   instalator tylko ją aktualizuje (gry zostają, dochodzą konta użytkowników).

## Konta
- Administrator: `admin` / `admin123` (dodaje, edytuje i usuwa gry) – zmień hasło po instalacji.
- Przykładowi użytkownicy: `anna`, `marek`, `kasia` – hasło `haslo123`.
- Każdy może założyć własne konto na stronie **Załóż konto**. Zalogowany użytkownik ocenia gry (1–10, jedna ocena na grę, można ją zmienić) i zmienia ich status.
- Niezalogowani mogą przeglądać, wyszukiwać i filtrować.

## Podium
Na stronie głównej widać trzy gry z najwyższą średnią ocen użytkowników.
Przy remisie wyżej jest gra z większą liczbą ocen.

## Struktura
- `index.php` – podium, lista, wyszukiwanie, filtry
- `gra.php` – szczegóły, oceny użytkowników, ocenianie, zmiana statusu
- `stats.php` – statystyki
- `login.php`, `register.php`, `logout.php` – konta
- `admin/` – dodawanie/edycja (`form.php`) i usuwanie (`delete.php`)
- `sql/schema.sql` – pełny schemat; `sql/aktualizacja.sql` – aktualizacja istniejącej bazy
