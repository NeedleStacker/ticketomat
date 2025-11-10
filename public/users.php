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
    <title>Upravljanje korisnicima - Ticketomat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">Admin - Ticketomat</a>
            <div>
                <a href="admin.php" class="btn btn-outline-light btn-sm">Nazad na tickete</a>
            </div>
        </div>
    </nav>

    <div class="container-lg py-3">
        <div class="card p-3 p-sm-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0 fs-4">Upravljanje korisnicima</h1>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="prepareNewUserModal()">Dodaj korisnika</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Korisničko ime</th>
                            <th>Ime</th>
                            <th>Prezime</th>
                            <th>Email</th>
                            <th>Uloga</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <!-- Users will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Dodaj korisnika</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="user-form">
                        <input type="hidden" id="user_id" name="id">
                        <div class="mb-3">
                            <label for="username" class="form-label">Korisničko ime</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Ime</label>
                            <input type="text" class="form-control" id="first_name" name="first_name">
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Prezime</label>
                            <input type="text" class="form-control" id="last_name" name="last_name">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Lozinka</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="form-text text-muted">Ostavite prazno ako ne želite mijenjati lozinku.</small>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Uloga</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="client">Korisnik</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Spremi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API = "../api/";
        const userModal = new bootstrap.Modal(document.getElementById('userModal'));

        async function loadUsers() {
            const res = await fetch(API + "getUsers.php");
            const users = await res.json();
            const tableBody = document.getElementById('users-table-body');
            tableBody.innerHTML = '';

            if (users.error) {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${users.error}</td></tr>`;
                return;
            }

            users.forEach(user => {
                const row = tableBody.insertRow();
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td>${user.first_name}</td>
                    <td>${user.last_name}</td>
                    <td>${user.email}</td>
                    <td>${user.role}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editUser(${JSON.stringify(user)})'>Uredi</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})">Obriši</button>
                    </td>
                `;
            });
        }

        function prepareNewUserModal() {
            document.getElementById('userModalLabel').textContent = 'Dodaj korisnika';
            document.getElementById('user-form').reset();
            document.getElementById('user_id').value = '';
            document.getElementById('password').required = true;
        }

        function editUser(user) {
            document.getElementById('userModalLabel').textContent = 'Uredi korisnika';
            document.getElementById('user_id').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('first_name').value = user.first_name;
            document.getElementById('last_name').value = user.last_name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            userModal.show();
        }

        document.getElementById('user-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const id = formData.get('id');
            const url = id ? 'updateUser.php' : 'addUser.php';

            const res = await fetch(API + url, {
                method: 'POST',
                body: new URLSearchParams(formData)
            });

            const result = await res.json();
            if (result.success) {
                userModal.hide();
                loadUsers();
            } else {
                alert('Greška: ' + result.error);
            }
        });

        async function deleteUser(id) {
            if (!confirm('Jeste li sigurni da želite obrisati ovog korisnika?')) return;

            const res = await fetch(API + 'deleteUser.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            });

            const result = await res.json();
            if (result.success) {
                loadUsers();
            } else {
                alert('Greška: ' + result.error);
            }
        }

        document.addEventListener('DOMContentLoaded', loadUsers);
    </script>
</body>
</html>
