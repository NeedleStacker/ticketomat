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
    .navbar .btn { align-self: center; } /* To align buttons */

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
    #newTicketModal .modal-dialog,
    #ticketModal .modal-dialog {
        max-height: 95vh;
        margin-top: 2.5vh;
        margin-bottom: 2.5vh;
    }
    #ticketModal .modal-content {
        height: 100%;
    }
    #ticketModal .modal-body {
        overflow-y: auto;
    }
    .custom-file-upload-container {
      border: 1px solid #dee2e6;
      border-radius: .375rem;
      padding: .375rem .75rem;
      display: flex;
      align-items: center;
    }
    .custom-file-upload {
      background: #0d6efd;
      color: white;
      padding: 0.375rem 0.75rem;
      border-radius: .375rem;
      cursor: pointer;
      font-size: 1rem;
      margin-right: 10px;
      white-space: nowrap;
    }
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

      const cusdisContainer = document.getElementById("cusdis-container-admin");
      cusdisContainer.innerHTML = ''; // Clear previous instance

      const iframe = document.createElement('iframe');
      iframe.style.width = '100%';
      iframe.style.border = 'none';
      cusdisContainer.appendChild(iframe);

      const iframeContent = `
        <html>
          <head>
            <link rel="stylesheet" href="assets/css/cusdis.css">
            <base target="_parent">
          </head>
          <body style="margin: 0;">
            <script>
              window.CUSDIS_LOCALE = {
                "powered_by": "Pokreće Cusdis", "post_comment": "Pošalji poruku", "loading": "Učitavanje...",
                "nickname": "Ime", "email": "Email (opcionalno)", "reply_btn": "Odgovori",
                "reply_placeholder": "Poruka...", "COMMENT_TEXTAREA_PLACEHOLDER": "Poruka...",
                "SUBMIT_COMMENT_BUTTON": "Pošalji poruku", "mod_badge": "Admin",
                "content_is_required": "Sadržaj je obavezan.", "sending": "Slanje...",
                "comment_has_been_sent": "Vaš komentar je poslan."
              }
            <\/script>
            <div id="cusdis_thread"
              data-host="https://cusdis.com"
              data-app-id="9195cf53-b951-405c-aa1a-2acccc1b57ce"
              data-page-id="${t.id}"
              data-page-url="${window.location.href.split('?')[0] + '?ticket=' + t.id}"
              data-page-title="${escapeHTML(t.title)}"
              data-nickname="${`${user.first_name} ${user.last_name}`.trim() || user.username}"
              data-moderator="${user.role === 'admin'}"
            ></div>
            <script>
                window.addEventListener('message', (event) => {
                    if (event.origin === 'https://cusdis.com' && event.data === 'cusdis:ready') {
                        const style = document.createElement('style');
                        style.innerHTML = \`
                            .cusdis-form__meta { display: none !important; }
                            .cusdis-textarea { min-height: 100px; }
                        \`;
                        document.head.appendChild(style);
                    }
                });
            <\/script>
            <script async defer src="https://cusdis.com/js/cusdis.es.js"><\/script>
          </body>
        </html>
      `;
      iframe.srcdoc = iframeContent;

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
      const title = document.getElementById("new_ticket_title").value.trim();
      const device = document.getElementById("new_ticket_device").value;
      const serial = document.getElementById("new_ticket_serial").value.trim();
      const client = document.getElementById("new_ticket_client").value;

      if (!client || !title || !device || !serial) {
          alert("Molimo popunite sva obavezna polja: Korisnik, Naslov, Ime aparata i Serijski broj.");
          return;
      }

      const formData = new FormData();
      formData.append('title', title);
      formData.append('description', document.getElementById("new_ticket_description").value.trim());
      formData.append('device_name', document.getElementById("new_ticket_device").value);
      formData.append('serial_number', document.getElementById("new_ticket_serial").value.trim());
      formData.append('user_id', document.getElementById("new_ticket_client").value);
      formData.append('status', "Otvoren");
      formData.append('request_creator', `${user.first_name} ${user.last_name} (Admin)`);
      formData.append('creator_contact', user.email);

      const attachment = document.getElementById("new_ticket_attachment").files[0];
      if (attachment) {
          formData.append('attachment', attachment);
      }

      const res = await fetch(API + "addTicket.php", { method: "POST", body: formData });
      const data = await res.json();
      if (data.success) {
        alert("✅ Ticket uspješno kreiran.");
        bootstrap.Modal.getInstance(document.getElementById('newTicketModal')).hide();
        document.getElementById('newTicketForm').reset();
        loadTickets();
      } else alert("❌ " + (data.error || "Greška prilikom kreiranja ticketa."));
    }

    async function addAttachmentAdmin() {
        const ticket_id = document.getElementById("ticket_id").value;
        const fileInput = document.getElementById("admin_new_attachment");
        const attachment = fileInput.files[0];

        if (!attachment) {
            alert("Molimo odaberite datoteku.");
            return;
        }

        const formData = new FormData();
        formData.append('ticket_id', ticket_id);
        formData.append('attachment', attachment);

        const res = await fetch(API + "addAttachment.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            fileInput.value = '';
            document.getElementById('file-name-span-admin').textContent = 'Nije izabrana datoteka';
            loadAttachmentsAdmin(ticket_id);
        } else {
            alert("❌ " + (data.error || "Greška prilikom dodavanja datoteke."));
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadTickets();
        loadDevices();
        loadClients();

        const newTicketModal = document.getElementById('newTicketModal');
        newTicketModal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('newTicketForm').reset();
            document.getElementById('file-name-span-new').textContent = 'Nije izabrana datoteka';
        });

        const fileInput = document.getElementById('new_ticket_attachment');
        fileInput.addEventListener('change', function() {
            const fileNameSpan = document.getElementById('file-name-span-new');
            fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Nije izabrana datoteka';
        });

        const adminAttachmentInput = document.getElementById('admin_new_attachment');
        adminAttachmentInput.addEventListener('change', function() {
            const fileNameSpan = document.getElementById('file-name-span-admin');
            fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Nije izabrana datoteka';
        });
    });
  </script>
</head>

<body class="bg-light">
  <?php include 'nav.php'; ?>

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
          <form id="newTicketForm">
            <div class="mb-3"><label for="new_ticket_client" class="form-label">Korisnik</label><select id="new_ticket_client" class="form-select"></select></div>
            <div class="mb-3"><label for="new_ticket_title" class="form-label">Naslov</label><input type="text" id="new_ticket_title" class="form-control"></div>
            <div class="row mb-3">
              <div class="col-md-6"><label for="new_ticket_device" class="form-label">Ime aparata</label><select id="new_ticket_device" class="form-select"></select></div>
              <div class="col-md-6"><label for="new_ticket_serial" class="form-label">Serijski broj</label><input type="text" id="new_ticket_serial" class="form-control"></div>
            </div>
            <div class="mb-3"><label for="new_ticket_description" class="form-label">Opis</label><textarea id="new_ticket_description" class="form-control" rows="4"></textarea></div>
            <div class="mb-3">
              <label class="form-label">Dodaj datoteku</label>
              <div class="custom-file-upload-container">
                  <label for="new_ticket_attachment" class="custom-file-upload">Odaberi datoteku</label>
                  <span id="file-name-span-new" class="text-muted">Nije izabrana datoteka</span>
                  <input type="file" id="new_ticket_attachment" class="d-none">
              </div>
            </div>
          </form>
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
          <div class="mb-3">
              <label class="form-label">Prilozi</label>
              <div id="attachmentListAdmin" class="mb-2"></div>
              <div class="custom-file-upload-container">
                  <label for="admin_new_attachment" class="custom-file-upload">Odaberi datoteku</label>
                  <span id="file-name-span-admin" class="text-muted">Nije izabrana datoteka</span>
                  <input type="file" id="admin_new_attachment" class="d-none">
                  <button class="btn btn-outline-primary btn-sm ms-auto" type="button" onclick="addAttachmentAdmin()">Dodaj</button>
              </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
          <button type="button" class="btn btn-primary" onclick="saveChanges()">Spremi promjene</button>
        </div>
        <hr>
        <div class="modal-body">
            <h6 class="mb-3">Komentari</h6>
            <div id="cusdis-container-admin"></div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
