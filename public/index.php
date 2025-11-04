<!DOCTYPE html>
<html lang="hr">
<head>
<meta charset="utf-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prijava | Ticketomat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(120deg, #e9f1f7, #f2f5f8);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}
.card {
  border-radius: 1rem;
  box-shadow: 0 0 20px rgba(0,0,0,0.1);
  max-width: 400px;
  width: 100%;
}
.logo {
  font-weight: 700;
  font-size: 1.8rem;
  color: #0d6efd;
  text-align: center;
}
</style>
<script>
const API = "../api/";

async function loginUser() {
  const username = document.getElementById("username").value.trim();
  const password = document.getElementById("password").value.trim();

  if (!username || !password) {
    showAlert("Molimo unesite korisničko ime i lozinku.", "danger");
    return;
  }

  const res = await fetch(API + "loginUser.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ username, password })
  });
  const data = await res.json();

  if (data.success) {
    localStorage.setItem("user", JSON.stringify(data.user));
    showAlert("Prijava uspješna! Preusmjeravanje...", "success");
    setTimeout(() => {
      if (data.user.role === "admin") {
        window.location = "admin.php";
      } else {
        window.location = "client.php";
      }
    }, 800);
  } else {
    showAlert(data.error || "Neispravni podaci.", "danger");
  }
}

function showAlert(msg, type) {
  document.getElementById("alert").innerHTML = `<div class="alert alert-${type}" role="alert">${msg}</div>`;
}
</script>
</head>
<body>

<div class="card p-4 bg-white">
  <div class="logo mb-3"><img src="favicon.png" width="50" height="50" type="image/png" /> Ticketomat</div>
  <h4 class="text-center mb-3">Prijava u sustav</h4>
  <div id="alert"></div>
  <form onsubmit="event.preventDefault(); loginUser();">
    <div class="mb-3">
      <label for="username" class="form-label">Korisničko ime</label>
      <input id="username" class="form-control form-control-lg" placeholder="Unesite korisničko ime" autocomplete="username">
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Lozinka</label>
      <input id="password" type="password" class="form-control form-control-lg" placeholder="Unesite lozinku" autocomplete="current-password">
    </div>
    <div class="d-grid mb-3">
      <button type="submit" class="btn btn-primary btn-lg">Prijavi se</button>
    </div>
    <div class="text-center">
      <a href="register.php" class="text-decoration-none">Nemate račun? Registrirajte se</a>
    </div>
  </form>
</div>

</body>
</html>
