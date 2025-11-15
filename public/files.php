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
    <title>Sve datoteke - Ticketomat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="bg-light">
    <?php include 'nav.php'; ?>
    <?php include '_newTicketModal.php'; ?>

    <div class="container py-3">
        <div class="card p-3 p-sm-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0 fs-4">Sve datoteke</h1>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Naziv datoteke</th>
                            <th>Ticket</th>
                            <th>Dodao</th>
                            <th>Vrijeme dodavanja</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody id="files-table-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/newTicket.js"></script>
    <script>
        const API = "../api/";

        document.addEventListener('DOMContentLoaded', loadFiles);

        function logout() {
          localStorage.removeItem("user");
          window.location = "index.php";
        }

        async function loadFiles() {
            const res = await fetch(API + 'getAllFiles.php');
            const files = await res.json();
            const tableBody = document.getElementById('files-table-body');
            tableBody.innerHTML = '';

            if (files.error) {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${files.error}</td></tr>`;
                return;
            }

            files.forEach(file => {
                const row = tableBody.insertRow();
                row.setAttribute('data-bs-toggle', 'tooltip');
                row.setAttribute('data-bs-placement', 'top');
                const tooltipTitle = `Ticket #${file.ticket_id}: ${file.ticket_title}\nUređaj: ${file.device_name || 'N/A'}\nS/N: ${file.serial_number || 'N/A'}\nKorisnik: ${file.ticket_user_first_name || ''} ${file.ticket_user_last_name || ''}`;
                row.setAttribute('title', tooltipTitle);

                row.innerHTML = `
                    <td>${file.id}</td>
                    <td><a href="${API}getAttachment.php?id=${file.id}" target="_blank">${file.attachment_name}</a></td>
                    <td>${file.ticket_id}</td>
                    <td>${file.added_by || 'N/A'}</td>
                    <td>${new Date(file.created_at).toLocaleString('hr-HR')}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${file.id})"><i class="bi bi-trash"></i></button>
                    </td>
                `;
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }


        async function deleteFile(id) {
            if (!confirm('Jeste li sigurni da želite obrisati ovu datoteku?')) return;

            const res = await fetch(API + 'deleteAttachment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attachment_id: id })
            });

            const result = await res.json();
            if (result.success) {
                loadFiles();
            } else {
                alert('Greška: ' + result.error);
            }
        }
    </script>
</body>
</html>
