# Ticketomat - Sustav za Prijavu Problema

## 1. O Projektu

Ticketomat je web-bazirani sustav za podršku korisnicima (ticketing) koji omogućuje klijentima jednostavnu prijavu problema, a administratorima nudi centraliziranu platformu za pregled, upravljanje i rješavanje prijavljenih ticketa.

Sustav je dizajniran s ciljem automatizacije i optimizacije procesa korisničke podrške, smanjujući vrijeme potrebno za rješavanje problema i poboljšavajući komunikaciju između klijenata i tima za podršku.

---

## 2. Ključne Značajke

### Za Klijente
- **Sigurna Prijava:** Korisnici se mogu prijaviti u sustav sa svojim jedinstvenim vjerodajnicama.
- **Kreiranje Ticketa:** Jednostavan obrazac za prijavu problema s poljima za naslov, detaljan opis, odabir uređaja, serijski broj i mogućnost dodavanja datoteke (privitka).
- **Pregled Ticketa:** Korisnici mogu vidjeti popis svih svojih prijavljenih ticketa s jasno naznačenim statusom (Otvoren, U tijeku, Riješen, Otkazan).
- **Detalji Ticketa:** Klikom na ticket otvara se detaljan prikaz s porukama i svim relevantnim informacijama.
- **Slanje Poruka:** Mogućnost ostavljanja poruka na ticketu za direktnu komunikaciju s administratorom.
- **Dodavanje Privitaka:** Korisnici mogu dodavati datoteke na otvorene tickete.
- **Otkazivanje Ticketa:** Korisnici mogu samostalno otkazati svoje tickete ako problem više nije relevantan.

### Za Administratore
- **Administratorska Ploča:** Centralizirani pregled svih ticketa od svih korisnika.
- **Filtriranje i Pretraga:** Napredne mogućnosti filtriranja ticketa po statusu, klijentu te pretraga po ključnim riječima (naslov, opis, serijski broj).
- **Upravljanje Ticketima:** Administratori mogu mijenjati status ticketa, prioritet, i dodavati interne bilješke.
- **Upravljanje Korisnicima:** Mogućnost dodavanja, uređivanja i brisanja korisničkih računa.
- **Upravljanje Uređajima:** Administratori mogu dodavati i uređivati listu uređaja dostupnih za odabir prilikom kreiranja ticketa.
- **Kreiranje Ticketa u Ime Klijenata:** Mogućnost otvaranja novog ticketa u ime postojećeg klijenta.
- **Pregled Poruka:** Administratori vide sve poruke i mogu odgovarati na njih.

---

## 3. Tehnologije

- **Backend:** PHP
- **Baza Podataka:** MySQL / MariaDB
- **Frontend:** HTML, CSS, JavaScript
- **Frameworks/Libraries:** Bootstrap 5

---

## 4. Postavljanje Lokalnog Okruženja

Pratite ove korake kako biste postavili projekt na svom lokalnom računalu.

### Preduvjeti
- Web poslužitelj s podrškom za PHP (npr. XAMPP, WAMP, Laragon, ili ugrađeni PHP server)
- MySQL ili MariaDB baza podataka
- `php-mysql` ekstenzija za PHP

### Instalacija

1.  **Klonirajte Repozitorij:**
    ```sh
    git clone <URL_repozitorija>
    cd <ime_direktorija>
    ```

2.  **Postavljanje Baze Podataka:**
    - Kreirajte novu bazu podataka u svom MySQL/MariaDB sustavu.
    - Uvezite shemu baze i osnovne podatke iz `database.sql` datoteke koja se nalazi u root direktoriju projekta.

3.  **Konfiguracija Konekcije:**
    - U `api/` direktoriju, preimenujte datoteku `config.php.example` u `config.php`.
    - Otvorite `api/config.php` i unesite točne podatke za spajanje na vašu lokalnu bazu podataka (host, korisničko ime, lozinka, ime baze).

4.  **Pokretanje Aplikacije:**
    - Najjednostavniji način za pokretanje je korištenjem ugrađenog PHP web servera. Otvorite terminal u root direktoriju projekta i pokrenite sljedeću naredbu:
      ```sh
      php -S localhost:8000 -t public
      ```
    - Aplikacija će biti dostupna na adresi `http://localhost:8000` u vašem pregledniku.

### Vjerodajnice za Prijavu
Nakon uvoza `database.sql` datoteke, dostupni su sljedeći korisnici za testiranje:

- **Administrator:**
  - **Korisničko ime:** `admin`
  - **Lozinka:** `admin`

- **Klijent (Korisnik):**
  - **Korisničko ime:** `korisnik`
  - **Lozinka:** `korisnik`

---

## 5. Struktura Projekta

- **`/api`**: Sadrži svu backend PHP logiku (skripte za rad s bazom, autentikaciju, itd.).
- **`/public`**: Glavni direktorij dostupan korisnicima. Sadrži sve frontend datoteke (`.php`, `.js`, `.css`).
  - `index.php`: Stranica za prijavu.
  - `client.php`: Korisnički panel za klijente.
  - `admin.php`: Administratorska ploča.
- **`/img`**: Slike korištene u aplikaciji.
- **`database.sql`**: Datoteka sa shemom baze podataka i početnim podacima.
- **`README.md`**: Ova datoteka.
