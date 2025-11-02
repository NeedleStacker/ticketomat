<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upravljanje aparatima - Ticketomat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { background-color: #f0ffff; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">Admin - Ticketomat</a>
            <div>
                <a href="admin.php" class="btn btn-outline-light btn-sm">Administracija ticketa</a>
                <button class="btn btn-outline-light btn-sm" onclick="logout()">Odjava</button>
            </div>
        </div>
    </nav>

    <div class="container py-3">
        <div class="card p-3 p-sm-4">
            <h1 class="mb-4 fs-4">Upravljanje aparatima</h1>
            <div class="mb-3">
                <div class="input-group">
                    <input type="text" id="newDeviceName" class="form-control" placeholder="Unesite naziv novog aparata">
                    <button class="btn btn-primary" onclick="addDevice()">Dodaj aparat</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Naziv aparata</th>
                        </tr>
                    </thead>
                    <tbody id="devicesBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const API = "api.php/";

        function logout() {
            localStorage.removeItem("user");
            window.location = "index.php";
        }

        async function loadDevices() {
            const res = await fetch(API + "getDevices.php");
            const devices = await res.json();
            const body = document.getElementById("devicesBody");
            body.innerHTML = "";
            devices.forEach(d => {
                const row = body.insertRow();
                row.insertCell().textContent = d.id;
                row.insertCell().textContent = d.name;
            });
        }

        async function addDevice() {
            const name = document.getElementById("newDeviceName").value.trim();
            if (!name) {
                alert("Naziv aparata ne smije biti prazan.");
                return;
            }

            const res = await fetch(API + "addDevice.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ name })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById("newDeviceName").value = "";
                loadDevices();
            } else {
                alert("Greška: " + (data.error || "Došlo je do pogreške."));
            }
        }

        document.addEventListener("DOMContentLoaded", loadDevices);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
