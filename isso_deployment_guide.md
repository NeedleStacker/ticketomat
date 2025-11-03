
# Vodič za Postavljanje Isso Komentara na Produkcijski Server

Ovaj vodič objašnjava kako instalirati i konfigurirati Isso server za komentare na vašem Linux hostingu.

## Preduslovi

*   Linux server s pristupom terminalu (SSH).
*   Python 3.6+ i `pip` instalirani.
*   Web server kao što su Nginx ili Apache (primjer koristi Nginx).
*   Pristup za uređivanje DNS zapisa vaše domene.

---

## 1. Instalacija Isso-a

Povežite se na vaš server putem SSH-a i instalirajte Isso koristeći `pip`:

```bash
pip install isso
```

---

## 2. Konfiguracija

Nakon instalacije, potrebno je konfigurirati Isso. Konfiguracijska datoteka `isso.cfg` je već uključena u ovaj projekt.

1.  **Postavite datoteke na vaš hosting:**
    *   Prebacite sve datoteke projekta na vaš server (npr. u `/var/www/ticketomat`).

2.  **Uredite `isso.cfg`:**
    *   Otvorite datoteku `isso.cfg` koja se nalazi u root direktoriju projekta.
    *   **Pronađite `[general]` sekciju i promijenite `host` vrijednost.** Umjesto `https://your-domain-goes-here.com`, upišite punu domenu na kojoj će se nalaziti vaš Ticketomat sustav.

    ```ini
    [general]
    ; Ovdje upišite vašu stvarnu domenu
    host = https://vasa-domena.com
    ```

3.  **Provjerite putanju do baze podataka:**
    *   Putanja do baze podataka (`dbpath`) je postavljena na relativnu putanju `isso_data/comments.db`. Provjerite da direktorij `isso_data` postoji i da web server ima ovlasti za pisanje u njega.

---

## 3. Pokretanje Isso Servera

Isso server se pokreće iz naredbenog retka. Za produkcijsko okruženje, preporučuje se pokrenuti ga kao pozadinski servis koristeći `systemd`.

1.  **Kreirajte `systemd` servis datoteku:**

    ```bash
    sudo nano /etc/systemd/system/isso.service
    ```

2.  **Zalijepite sljedeći sadržaj u datoteku.** **Obavezno zamijenite `/var/www/ticketomat`** s točnom putanjom do vašeg projekta.

    ```ini
    [Unit]
    Description=Isso Commenting Server
    After=network.target

    [Service]
    User=www-data  # Korisnik pod kojim se pokreće vaš web server (može biti i `nginx` ili `apache`)
    WorkingDirectory=/var/www/ticketomat
    ExecStart=/usr/local/bin/isso -c isso.cfg run
    Restart=always

    [Install]
    WantedBy=multi-user.target
    ```

3.  **Omogućite i pokrenite servis:**

    ```bash
    sudo systemctl enable isso.service
    sudo systemctl start isso.service
    ```

4.  **Provjerite status servisa:**

    ```bash
    sudo systemctl status isso
    ```
    Ako je sve u redu, trebali biste vidjeti da je servis `active (running)`.

---

## 4. Konfiguracija Web Servera (Reverse Proxy)

Isso po zadanim postavkama radi na `localhost:8080`. Da bi bio dostupan javno, trebate konfigurirati vaš web server (Nginx ili Apache) da prosljeđuje zahtjeve na njega.

Ovo je **primjer za Nginx**. Uredite konfiguracijsku datoteku vašeg sajta:

```bash
sudo nano /etc/nginx/sites-available/vasa-domena.com
```

Dodajte sljedeći `location` blok unutar vašeg `server` bloka:

```nginx
server {
    listen 80;
    server_name vasa-domena.com;

    # ... vaša postojeća PHP konfiguracija ...

    location /isso/ {
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://localhost:8080/;
    }

    # ... ostatak konfiguracije ...
}
```

**Objašnjenje:**
*   Ova konfiguracija preusmjerava sve zahtjeve koji počinju s `/isso/` (npr. `vasa-domena.com/isso/js/embed.min.js`) na Isso server koji se vrti na `localhost:8080`.
*   HTML datoteke (`admin.php` i `client.php`) su već konfigurirane da traže skriptu na `/isso/js/embed.min.js`, tako da će ovo raditi bez dodatnih izmjena koda.

Nakon spremanja, provjerite ispravnost Nginx konfiguracije i ponovno je učitajte:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Vaš sustav za komentare bi sada trebao biti potpuno funkcionalan na vašoj domeni.
