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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podaci o tvrtki - Ticketomat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-3">
        <div class="container-fluid">
            <div>
                <a class="navbar-brand" href="admin.php">Admin - Ticketomat</a>
            </div>
            <div>
                <a href="admin.php" class="btn btn-outline-light btn-sm">Nazad na tickete</a>
            </div>
        </div>
    </nav>

    <div class="container-lg py-3">
        <div class="card p-3 p-sm-4">
            <h1 class="mb-4 fs-4">Podaci o tvrtki</h1>
            <form id="company-form" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="company_name" class="form-label">Naziv tvrtke</label>
                    <input type="text" class="form-control" id="company_name" name="company_name">
                </div>
                <div class="mb-3">
                    <label for="company_address" class="form-label">Adresa</label>
                    <input type="text" class="form-control" id="company_address" name="company_address">
                </div>
                <div class="mb-3">
                    <label for="company_oib" class="form-label">OIB</label>
                    <input type="text" class="form-control" id="company_oib" name="company_oib">
                </div>
                <div class="mb-3">
                    <label for="company_phone" class="form-label">Telefon</label>
                    <input type="text" class="form-control" id="company_phone" name="company_phone">
                </div>
                <div class="mb-3">
                    <label for="company_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="company_email" name="company_email">
                </div>
                <div class="mb-3">
                    <label for="company_logo" class="form-label">Logo (JPG format)</label>
                    <input class="form-control" type="file" id="company_logo" name="company_logo" accept="image/jpeg">
                    <div class="mt-2">
                        <img id="logo-preview" src="" alt="Logo preview" style="max-height: 100px; display: none;">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Spremi promjene</button>
            </form>
        </div>
    </div>

    <script>
        const API = "../api/";

        async function loadCompanyInfo() {
            const res = await fetch(API + "getCompanyInfo.php");
            const data = await res.json();
            if (!data.error) {
                document.getElementById('company_name').value = data.name || '';
                document.getElementById('company_address').value = data.address || '';
                document.getElementById('company_oib').value = data.oib || '';
                document.getElementById('company_phone').value = data.phone || '';
                document.getElementById('company_email').value = data.email || '';
                if (data.logo) {
                    const preview = document.getElementById('logo-preview');
                    preview.src = `data:image/jpeg;base64,${data.logo}`;
                    preview.style.display = 'block';
                }
            }
        }

        document.getElementById('company-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            const res = await fetch(API + "updateCompanyInfo.php", {
                method: "POST",
                body: formData
            });

            const result = await res.json();
            if (result.success) {
                alert('Podaci o tvrtki su uspješno spremljeni.');
                loadCompanyInfo();
            } else {
                alert('Greška: ' + result.error);
            }
        });

        document.getElementById('company_logo').addEventListener('change', function(event) {
            const [file] = event.target.files;
            if (file) {
                const preview = document.getElementById('logo-preview');
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            }
        });

        document.addEventListener('DOMContentLoaded', loadCompanyInfo);
    </script>
</body>
</html>
