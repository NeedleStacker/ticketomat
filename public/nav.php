<nav class="navbar navbar-dark bg-dark mb-3 navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin.php">Admin - Ticketomat</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <div class="navbar-nav ms-auto">
                <a href="admin.php" class="btn btn-outline-light btn-sm me-2">Ticketi</a>
                <a href="company.php" class="btn btn-outline-light btn-sm me-2">Tvrtka</a>
                <a href="devices.php" class="btn btn-outline-light btn-sm me-2">Aparati</a>
                <a href="users.php" class="btn btn-outline-light btn-sm me-2">Korisnici</a>
                <button class="btn btn-outline-light btn-sm" onclick="logout()">Odjava</button>
            </div>
        </div>
    </div>
</nav>