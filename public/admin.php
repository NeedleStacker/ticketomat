<?php
// Provjera je li korisnik prijavljen kao administrator
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
  <title>Admin - Ticketomat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background-color: #f8f9fa; }
    .navbar-brand { font-weight: 600; }

    .card { 
      background-color: #ffffff; 
      border-radius: 12px; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border: 1px solid #e0e0e0;
    }
    .table-container {
      border: 2px solid #dee2e6;
      border-radius: 8px;
      overflow: hidden;
      background-color: #fff;
    }
    td, th { 
      vertical-align: middle !important; 
      border: 1px solid #dee2e6;
      padding: 12px 8px;
    }
    thead th {
      background-color: #343a40 !important;
      color: white !important;
      font-weight: 600;
      border-color: #454d55 !important;
      position: sticky;
      top: 0;
      z-index: 10;
    }
    tbody tr:hover {
      background-color: rgba(255, 255, 255, 0.5);
      transition: background-color 0.2s ease;
    }
	.table-hover tbody tr.priority-high-row:hover,
	.table-hover tbody tr.priority-medium-row:hover,
	.table-hover tbody tr.priority-low-row:hover,
	.table-hover tbody tr.status-otkazan-row:hover {
	  filter: brightness(0.95);
	}

	.table-hover tbody tr.priority-high-row td,
	.table-hover tbody tr.priority-medium-row td,
	.table-hover tbody tr.priority-low-row td,
	.table-hover tbody tr.status-otkazan-row td {
	  background-color: inherit !important;
	}
	.priority-high-row { background-color: #f8d7da; }
    .priority-medium-row { background-color: #fff3cd; }
    .priority-low-row { background-color: #d1e7dd; }
    .status-otkazan-row { background-color: #e2e3e5; }

    .modal-priority-high .modal-header { background-color: #dc3545 !important; color: #fff; }
    .modal-priority-medium .modal-header { background-color: #ffc107 !important; color: #000; }
    .modal-priority-low .modal-header { background-color: #198754 !important; color: #fff; }
    .modal-status-otkazan .modal-header { background-color: #6c757d !important; color: #fff; }

    .filter-section { background-color: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }

    .priority-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: center;
      font-size: 0.875rem;
    }
    .priority-dot { width: 18px; height: 18px; border-radius: 3px; display: inline-block; }
  </style>

  <script>
    const API = "../api/";
    const user = JSON.parse(localStorage.getItem("user") || "null");
    if (!user) window.location = "index.php";

    function logout() {
      localStorage.removeItem("user");
      window.location = "index.php";
    }

    function escapeHTML(str) {
      if (typeof str !== 'string') return '';
      return str.replace(/[&<>"']/g, function(match) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match];
      });
    }

    async function loadTickets() {
      const statusFilter = document.getElementById("statusFilter").value;
      const clientFilter = document.getElementById("clientFilter").value;
      const searchTerm = document.getElementById("searchInput").value;

      let url = API + `getTickets.php?role=admin`;
      const params = new URLSearchParams();
      if (statusFilter) params.append('status', statusFilter);
      if (clientFilter) params.append('user_id', clientFilter);
      if (searchTerm) params.append('search', searchTerm);
      if (params.toString()) url += '&' + params.toString();

      const res = await fetch(url);
      const tickets = await res.json();
      const body = document.getElementById("ticketsBody");
      body.innerHTML = "";

      if (tickets.error) {
        body.innerHTML = `<tr><td colspan='8' class='text-danger text-center'>Greška: ${tickets.error}</td></tr>`;
        return;
      }
      if (tickets.length === 0) {
        body.innerHTML = `<tr><td colspan='8' class='text-center text-muted py-4'>Nema ticketa za prikaz.</td></tr>`;
        return;
      }

      tickets.forEach(t => {
        const row = body.insertRow();
        row.className =
          t.status === 'Otkazan' ? 'status-otkazan-row' :
          t.priority === 'high' ? 'priority-high-row' :
          t.priority === 'medium' ? 'priority-medium-row' : 'priority-low-row';

        row.insertCell().textContent = t.id;
        row.insertCell().textContent = t.title;
        row.insertCell().textContent = t.username || 'N/A';
        row.insertCell().textContent = t.device_name || '-';
        row.insertCell().textContent = t.serial_number || '-';
        row.insertCell().textContent = t.status;
        const dateCell = row.insertCell();
        dateCell.textContent = t.created_at ? new Date(t.created_at).toLocaleDateString('hr-HR') : '-';
        const cell = row.insertCell();
        const btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-primary';
        btn.textContent = 'Detalji';
        btn.onclick = () => showTicketDetails(t.id);
        cell.appendChild(btn);
      });
    }

    async function loadAttachmentsAdmin(ticketId) {
      const attachmentList = document.getElementById("attachmentListAdmin");
      attachmentList.innerHTML = '<div class="text-muted">Učitavanje...</div>';
      const res = await fetch(API + `getAttachments.php?ticket_id=${ticketId}`);
      const attachments = await res.json();
      attachmentList.innerHTML = "";
      if (attachments.error) {
        attachmentList.innerHTML = `<div class="text-danger">${attachments.error}</div>`;
        return;
      }
      if (attachments.length === 0) {
        attachmentList.innerHTML = `<div class="text-muted small">Nema priloženih datoteka.</div>`;
        return;
      }
      attachments.forEach(file => {
        const link = document.createElement('a');
        link.href = `${API}getAttachment.php?id=${file.id}`;
        link.textContent = file.attachment_name;
        link.className = 'btn btn-outline-secondary btn-sm me-2 mb-2 attachment-link';
        link.target = '_blank';
        attachmentList.appendChild(link);
      });
    }

    async function showTicketDetails(id) {
      const res = await fetch(API + `getTicketDetails.php?id=${id}`);
      const t = await res.json();
      if (t.error) { alert(t.error); return; }

      const modal = document.getElementById('ticketModal');
      modal.className = 'modal fade';
      if (t.status === 'Otkazan') modal.classList.add('modal-status-otkazan');
      else if (t.priority === 'high') modal.classList.add('modal-priority-high');
      else if (t.priority === 'medium') modal.classList.add('modal-priority-medium');
      else modal.classList.add('modal-priority-low');

      document.getElementById("modalTitle").textContent = "Ticket #" + t.id + " – " + t.title;
      document.getElementById("ticket_id").value = t.id;
      document.getElementById("ticket_status").value = t.status;
      document.getElementById("ticket_priority").value = t.priority || "medium";
      document.getElementById("ticket_description").value = t.description || "";
      document.getElementById("ticket_device_name").value = t.device_name || "";
      document.getElementById("ticket_serial_number").value = t.serial_number || "";
      document.getElementById("ticket_user").textContent = `${t.first_name || ''} ${t.last_name || ''}`;
      document.getElementById("ticket_email").textContent = t.email || '';
      document.getElementById("ticket_phone").textContent = t.phone || '';
      loadAttachmentsAdmin(t.id);

      new bootstrap.Modal(modal).show();
    }

    async function saveChanges() {
      const id = document.getElementById("ticket_id").value;
      const status = document.getElementById("ticket_status").value;
      const priority = document.getElementById("ticket_priority").value;
      const description = document.getElementById("ticket_description").value;
      const device_name = document.getElementById("ticket_device_name").value;
      const serial_number = document.getElementById("ticket_serial_number").value;
      const body = { id, status, priority, description, device_name, serial_number };
      const res = await fetch(API + "updateTicket.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body)
      });
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
        loadTickets();
      } else alert("Greška: " + (data.error || JSON.stringify(data)));
    }

    async function loadDevices() {
      const res = await fetch(API + "getDevices.php");
      const devices = await res.json();
      const selects = document.querySelectorAll("#ticket_device_name, #new_ticket_device");
      selects.forEach(select => {
        select.innerHTML = '<option value="">Odaberite uređaj...</option>';
        devices.forEach(d => select.innerHTML += `<option>${d.name}</option>`);
      });
    }

    async function loadClients() {
      const res = await fetch(API + "getKorisnici.php");
      const clients = await res.json();
      const selects = document.querySelectorAll("#clientFilter, #new_ticket_client");
      selects.forEach(select => {
        select.innerHTML = '<option value="">Svi korisnici</option>';
        clients.forEach(c => select.innerHTML += `<option value="${c.id}">${c.username} (${c.first_name} ${c.last_name})</option>`);
      });
    }

    async function createNewTicket() {
      const formData = new FormData();
      formData.append('title', document.getElementById("new_ticket_title").value.trim());
      formData.append('description', document.getElementById("new_ticket_description").value.trim());
      formData.append('device_name', document.getElementById("new_ticket_device").value);
      formData.append('serial_number', document.getElementById("new_ticket_serial").value.trim());
      formData.append('user_id', document.getElementById("new_ticket_client").value);
      formData.append('status', "Otvoren");
      formData.append('request_creator', `${user.first_name} ${user.last_name} (Admin)`);
      formData.append('creator_contact', user.email);
      const res = await fetch(API + "addTicket.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        alert("✅ Ticket uspješno kreiran.");
        bootstrap.Modal.getInstance(document.getElementById('newTicketModal')).hide();
        loadTickets();
      } else alert("❌ " + (data.error || "Greška prilikom kreiranja ticketa."));
    }

    document.addEventListener("DOMContentLoaded", () => { loadTickets(); loadDevices(); loadClients(); });
  </script>
</head>

<body class="bg-light">
  <nav class="navbar navbar-dark bg-dark mb-3">
    <div class="container-fluid">
      <div>
        <a class="navbar-brand" href="admin.php">Admin - Ticketomat</a>
        <a href="company.php" class="btn btn-outline-light btn-sm d-none d-md-inline">Tvrtka</a>
      </div>
      <div class="navbar-actions">
        <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">Novi ticket</button>
        <a href="devices.php" class="btn btn-outline-light btn-sm">Aparati</a>
        <a href="users.php" class="btn btn-outline-light btn-sm">Korisnici</a>
        <button class="btn btn-outline-light btn-sm" onclick="logout()">Odjava</button>
      </div>
    </div>
  </nav>

  <div class="container-lg py-3">
    <div class="card p-3 p-sm-4">
      <h1 class="mb-3 fs-4 text-center text-sm-start">Administracija ticketa</h1>

      <div class="filter-section row g-2">
        <div class="col-md-4">
          <input type="text" id="searchInput" class="form-control" placeholder="🔍 Pretraži..." onkeyup="loadTickets()">
        </div>
        <div class="col-md-4">
          <select id="statusFilter" class="form-select" onchange="loadTickets()">
            <option value="">Svi statusi</option><option>Otvoren</option><option>U tijeku</option>
            <option>Riješen</option><option>Zatvoren</option><option>Otkazan</option>
          </select>
        </div>
        <div class="col-md-4">
          <select id="clientFilter" class="form-select" onchange="loadTickets()">
            <option value="">Svi klijenti</option>
          </select>
        </div>
      </div>

      <div class="priority-legend mb-3">
        <span class="fw-bold me-2">Legenda:</span>
        <span class="priority-dot" style="background-color:#f8d7da"></span>Visok
        <span class="priority-dot" style="background-color:#fff3cd"></span>Srednji
        <span class="priority-dot" style="background-color:#d1e7dd"></span>Nizak
        <span class="priority-dot" style="background-color:#e2e3e5"></span>Otkazan
      </div>

      <div class="table-container">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th>#</th><th>Naslov</th><th>Korisnik</th><th>Aparat</th><th>Serijski br.</th><th>Status</th><th>Datum</th><th>Akcija</th></tr></thead>
            <tbody id="ticketsBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Novi ticket -->
  <div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Kreiraj novi ticket</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label for="new_ticket_client" class="form-label">Korisnik</label><select id="new_ticket_client" class="form-select"></select></div>
          <div class="mb-3"><label for="new_ticket_title" class="form-label">Naslov</label><input type="text" id="new_ticket_title" class="form-control"></div>
          <div class="row mb-3">
            <div class="col-md-6"><label for="new_ticket_device" class="form-label">Ime aparata</label><select id="new_ticket_device" class="form-select"></select></div>
            <div class="col-md-6"><label for="new_ticket_serial" class="form-label">Serijski broj</label><input type="text" id="new_ticket_serial" class="form-control"></div>
          </div>
          <div class="mb-3"><label for="new_ticket_description" class="form-label">Opis</label><textarea id="new_ticket_description" class="form-control" rows="4"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
          <button type="button" class="btn btn-primary" onclick="createNewTicket()">Kreiraj ticket</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Detalji ticket -->
  <div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalTitle">Detalji ticketa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="ticket_id">
          <div class="row mb-3">
            <div class="col-md-4"><label class="form-label">Korisnik</label><p id="ticket_user" class="form-control-plaintext"></p></div>
            <div class="col-md-4"><label class="form-label">Email</label><p id="ticket_email" class="form-control-plaintext"></p></div>
            <div class="col-md-4"><label class="form-label">Telefon</label><p id="ticket_phone" class="form-control-plaintext"></p></div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6"><label for="ticket_device_name" class="form-label">Uređaj</label><input type="text" id="ticket_device_name" class="form-control"></div>
            <div class="col-md-6"><label for="ticket_serial_number" class="form-label">Serijski broj</label><input type="text" id="ticket_serial_number" class="form-control"></div>
          </div>
          <div class="mb-3"><label for="ticket_description" class="form-label">Opis</label><textarea id="ticket_description" class="form-control" rows="4"></textarea></div>
          <div class="row mb-3">
            <div class="col-md-4"><label for="ticket_status" class="form-label">Status</label>
              <select id="ticket_status" class="form-select"><option>Otvoren</option><option>U tijeku</option><option>Riješen</option><option>Zatvoren</option><option>Otkazan</option></select>
            </div>
            <div class="col-md-4"><label for="ticket_priority" class="form-label">Prioritet</label>
              <select id="ticket_priority" class="form-select"><option value="low">Nizak</option><option value="medium">Srednji</option><option value="high">Visok</option></select>
            </div>
          </div>
          <div class="mb-3"><label class="form-label">Prilozi</label><div id="attachmentListAdmin"></div></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
          <button type="button" class="btn btn-primary" onclick="saveChanges()">Spremi promjene</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
