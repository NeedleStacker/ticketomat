<nav class="navbar navbar-dark bg-dark mb-3 navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin.php">Admin - Ticketomat</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#newTicketModal">Novi ticket</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin.php">Ticketi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="company.php">Tvrtka</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="devices.php">Aparati</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">Korisnici</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="logout()">Odjava</a>
                </li>
            </ul>
        </div>
    </div>
</nav>