<!DOCTYPE html>
<html lang="hr">
<head>
<meta charset="utf-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registracija korisnika | Ticketomat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(120deg, #f0f4f8, #e8ecf1);
  min-height: 100vh;
}
.card {
  border-radius: 1rem;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
</style>
<script>
const API = "../api/";
const API_KEY = "ZQjjWaAXsPbKFuahw3TK8LCRE";

async function registerUser() {
  const payload = {
    first_name: document.getElementById("first_name").value.trim(),
    last_name: document.getElementById("last_name").value.trim(),
    company: document.getElementById("company").value.trim(),
    company_oib: document.getElementById("company_oib").value.trim(),
    city: document.getElementById("city").value.trim(),
    address: document.getElementById("address").value.trim(),
    postal_code: document.getElementById("postal_code").value.trim(),
    email: document.getElementById("email").value.trim(),
    phone: document.getElementById("phone").value.trim(),
    note: document.getElementById("note").value.trim(),
    username: document.getElementById("username").value.trim(),
    password: document.getElementById("password").value
  };

  // osnovna provjera
  if (!payload.username || !payload.password || !payload.email) {
    showAlert("Molimo ispunite obavezna polja (korisničko ime, lozinka, email).", "danger");
    return;
  }

  const res = await fetch(API + "registerUser.php", {
    method: "POST",
    headers: {"Content-Type": "application/json", "X-API-KEY": API_KEY},
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.success) {
    showAlert("Registracija uspješna! Možete se prijaviti.", "success");
    setTimeout(() => window.location = "index.php", 2000);
  } else {
    showAlert(data.error || "Dogodila se greška.", "danger");
    console.log(data);
  }
}

function showAlert(msg, type) {
  document.getElementById("alert").innerHTML = `<div class="alert alert-${type}" role="alert">${msg}</div>`;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card p-4 bg-white">
        <h2 class="text-center mb-4"><img src="favicon.png" width="50" height="50" type="image/png" /> Registracija korisnika</h2>
        <div id="alert"></div>
        <form onsubmit="event.preventDefault(); registerUser();">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Ime *</label>
              <input id="first_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Prezime *</label>
              <input id="last_name" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Ustanova / Tvrtka</label>
              <input id="company" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">OIB ustanove</label>
              <input id="company_oib" class="form-control">
            </div>
			
			<div class="col-md-2">
              <label class="form-label">PTT</label>
              <input id="postal_code" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Grad</label>
              <input id="city" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Adresa</label>
              <input id="address" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input id="email" type="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon</label>
              <input id="phone" class="form-control">
            </div>

            <div class="col-12">
              <label class="form-label">Napomena</label>
              <textarea id="note" class="form-control" rows="2"></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">Korisničko ime *</label>
              <input id="username" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Lozinka *</label>
              <input id="password" type="password" class="form-control" required>
            </div>

            <div class="col-12 mt-3 text-center">
              <button type="submit" class="btn btn-success px-5">Registriraj se</button>
              <a href="index.php" class="btn btn-link">Već imaš račun?</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
