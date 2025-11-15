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
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/devices.css">
</head>
<body class="bg-light">
    <?php include 'nav.php'; ?>
    <?php include '_newTicketModal.php'; ?>

    <div class="container py-3">
        <div class="card p-3 p-sm-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0 fs-4">Upravljanje aparatima</h1>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editDeviceModal" onclick="prepareNewDeviceModal()">Dodaj aparat</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Slika</th>
                            <th>Naziv aparata</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody id="devicesBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Device Modal -->
    <div class="modal fade" id="editDeviceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Uredi aparat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editDeviceForm">
                        <input type="hidden" id="edit_device_id">
                        <div class="mb-3">
                            <label for="edit_device_name" class="form-label">Naziv aparata</label>
                            <input type="text" class="form-control" id="edit_device_name">
                        </div>
                        <div class="mb-3">
                            <label for="edit_device_image" class="form-label">Promijeni sliku (opcionalno)</label>
                            <input class="form-control" type="file" id="edit_device_image" accept="image/jpeg,image/png,image/jpg">
                            <img id="edit_image_preview" src="" alt="Pregled slike" class="device-img-preview d-none">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
                    <button type="button" class="btn btn-primary" onclick="updateDevice()">Spremi promjene</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/newTicket.js"></script>
    <script>
        const API = "../api/";
        let editDeviceModal;

        document.addEventListener("DOMContentLoaded", function() {
            editDeviceModal = new bootstrap.Modal(document.getElementById('editDeviceModal'));
            loadDevices();

            document.getElementById('edit_device_image').addEventListener('change', function(event) {
                const [file] = event.target.files;
                if (file) {
                    const preview = document.getElementById('edit_image_preview');
                    preview.src = URL.createObjectURL(file);
                    preview.classList.remove('d-none');
                }
            });
        });

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

                const imgCell = row.insertCell();
                const img = document.createElement('img');
                img.src = d.image_path ? `../${d.image_path}?t=${new Date().getTime()}` : '../img/placeholder.jpg';
                img.alt = d.name;
                img.className = 'device-img-thumb';
                imgCell.appendChild(img);

                row.insertCell().textContent = d.name;

                const actionCell = row.insertCell();
                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-outline-primary me-2';
                editBtn.textContent = 'Uredi';
                editBtn.onclick = () => openEditModal(d);
                actionCell.appendChild(editBtn);

                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-outline-danger';
                deleteBtn.textContent = 'Obriši';
                deleteBtn.onclick = () => deleteDevice(d.id);
                actionCell.appendChild(deleteBtn);
            });
        }

        function prepareNewDeviceModal() {
            document.getElementById('editDeviceModal').querySelector('.modal-title').textContent = 'Dodaj aparat';
            document.getElementById('editDeviceForm').reset();
            document.getElementById('edit_device_id').value = '';
            document.getElementById('edit_image_preview').classList.add('d-none');
        }

        function openEditModal(device) {
            document.getElementById('editDeviceModal').querySelector('.modal-title').textContent = 'Uredi aparat';
            document.getElementById('edit_device_id').value = device.id;
            document.getElementById('edit_device_name').value = device.name;
            const preview = document.getElementById('edit_image_preview');
            if (device.image_path) {
                preview.src = `../${device.image_path}?t=${new Date().getTime()}`;
                preview.classList.remove('d-none');
            } else {
                preview.classList.add('d-none');
            }
            document.getElementById('edit_device_image').value = ''; // Clear file input
            editDeviceModal.show();
        }

        async function updateDevice() {
            const id = document.getElementById('edit_device_id').value;
            const name = document.getElementById('edit_device_name').value.trim();
            const imageFile = document.getElementById('edit_device_image').files[0];
            const isNew = !id;

            if (!name) {
                alert("Naziv aparata ne smije biti prazan.");
                return;
            }

            const formData = new FormData();
            if (id) formData.append('id', id);
            formData.append('name', name);
            if (imageFile) {
                formData.append('image', imageFile);
            }

            const url = isNew ? "addDevice.php" : "updateDevice.php";

            const res = await fetch(API + url, {
                method: "POST",
                body: formData
            });

            const data = await res.json();
            if (data.success) {
                editDeviceModal.hide();
                loadDevices();
            } else {
                alert("Greška: " + (data.error || "Došlo je do pogreške."));
            }
        }

        async function deleteDevice(id) {
            if (!confirm('Jeste li sigurni da želite obrisati ovaj aparat?')) return;

            const res = await fetch(API + 'deleteDevice.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            const result = await res.json();
            if (result.success) {
                loadDevices();
            } else {
                alert('Greška: ' + result.error);
            }
        }

    </script>
</body>
</html>
