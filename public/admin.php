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
    .card { background-color: #f0ffff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    table { background-color: #fff; border-radius: 8px; overflow: hidden; border: 1px solid rgba(0,0,0,0.5); }
    td, th { vertical-align: middle !important; border: 1px solid rgba(0,0,0,0.5); }

    .priority-low { color: #198754; font-weight: 600; }
    .priority-medium { color: #ffc107; font-weight: 600; }
    .priority-high { color: #dc3545; font-weight: 600; }

    .priority-high-row { --bs-table-bg: #f8d7da; }
    .priority-medium-row { --bs-table-bg: #fff3cd; }
    .priority-low-row { --bs-table-bg: #d1e7dd; }
    .status-otkazan-row { --bs-table-bg: #e2e3e5; }

    .modal-priority-high .modal-header { background-color: #dc3545 !important; color: #fff; }
    .modal-priority-medium .modal-header { background-color: #ffc107 !important; color: #000; }
    .modal-priority-low .modal-header { background-color: #198754 !important; color: #fff; }
    .modal-status-otkazan .modal-header { background-color: #6c757d !important; color: #fff; }

    /* Modal styling */
    .modal.fade .modal-dialog {
      margin-top: 2rem;
      margin-bottom: 2rem;
      height: calc(100% - 4rem);
    }
    .modal.fade .modal-content {
      height: 100%;
    }
    .modal.fade .modal-body {
      overflow-y: auto;
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
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        }[match];
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

        if (params.toString()) {
            url += '&' + params.toString();
        }

        const res = await fetch(url);
        const tickets = await res.json();
        const body = document.getElementById("ticketsBody");
        body.innerHTML = "";

        if (tickets.error) {
            body.innerHTML = `<tr><td colspan='7' class='text-danger text-center'>Greška: ${tickets.error}</td></tr>`;
            return;
        }
        if (tickets.length === 0) {
            body.innerHTML = `<tr><td colspan='7' class='text-center text-muted'>Nema ticketa za prikaz.</td></tr>`;
            return;
        }

        tickets.forEach(t => {
            const row = body.insertRow();
            row.className =
              t.status === 'Otkazan' ? 'status-otkazan-row' :
              t.priority === 'high' ? 'priority-high-row' :
              t.priority === 'medium' ? 'priority-medium-row' :
              'priority-low-row';

            row.insertCell().textContent = t.title;
            row.insertCell().textContent = t.username || 'N/A';
            row.insertCell().textContent = t.device_name || '';
            row.insertCell().textContent = t.serial_number || '';
            row.insertCell().textContent = t.status;
            row.insertCell().textContent = t.created_at || '';

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

        attachmentList.innerHTML = ""; // Clear loader/previous content
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
            link.className = 'btn btn-outline-secondary btn-sm me-2 mb-2';
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
      document.getElementById("ticket_created").textContent = t.created_at || '-';
      document.getElementById("ticket_canceled_at").textContent = t.canceled_at || '-';
      document.getElementById("ticket_cancel_reason").textContent = t.cancel_reason || '-';
      document.getElementById("ticket_request_creator").textContent = t.request_creator || '-';
      document.getElementById("ticket_creator_contact").textContent = t.creator_contact || '-';

      loadAttachmentsAdmin(t.id);

      const cusdisContainer = document.getElementById("cusdis-container");
      cusdisContainer.innerHTML = ''; // Clear previous instance

      const iframe = document.createElement('iframe');
      iframe.style.width = '100%';
      iframe.style.border = 'none';
      iframe.style.minHeight = '250px';
      cusdisContainer.appendChild(iframe);

      const ssoName = (user.first_name && user.last_name) ? `${user.first_name} ${user.last_name}` : user.username;
      const emailAttr = user.email ? `data-viewer-email="${escapeHTML(user.email)}"` : '';

      const iframeContent = `
        <html>
          <head>
            <base target="_parent">
          </head>
          <body style="margin: 0;">
            <script>
              window.CUSDIS_LOCALE = {
                "powered_by": "Pokreće Cusdis", "post_comment": "Pošalji poruku", "loading": "Učitavanje...",
                "nickname": "Ime", "email": "Email (opcionalno)", "reply_btn": "Odgovori",
                "reply_placeholder": "Poruka...", "COMMENT_TEXTAREA_PLACEHOLDER": "Poruka...",
                "SUBMIT_COMMENT_BUTTON": "Pošalji poruku", "mod_badge": "Admin",
                "content_is_required": "Sadržaj je obavezan.",
              }
            <\/script>
            <div id="cusdis_thread"
              data-host="https://cusdis.com"
              data-app-id="9195cf53-b951-405c-aa1a-2acccc1b57ce"
              data-page-id="${t.id}"
              data-page-url="${window.location.href.split('?')[0] + '?ticket=' + t.id}"
              data-page-title="${escapeHTML(t.title)}"
              data-viewer-name="${escapeHTML(ssoName)}"
              ${emailAttr}
            ></div>
            <script async defer src="https://cusdis.com/js/cusdis.es.js"><\/script>
            <script>
              window.addEventListener('load', () => {
                const resizeObserver = new ResizeObserver(entries => {
                  window.parent.postMessage({ height: entries[0].target.scrollHeight }, '*');
                });
                resizeObserver.observe(document.body);
              });
            <\/script>
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
      const cancel_reason = document.getElementById("cancel_reason_input").value;

      const body = { id, status, priority, description, device_name, serial_number };
      if (status === 'Otkazan') {
        body.cancel_reason = cancel_reason;
      }

      const res = await fetch(API + "updateTicket.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body)
      });

      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
        loadTickets();
      } else {
        alert("Greška: " + (data.error || JSON.stringify(data)));
      }
    }

    async function loadDevices() {
        const res = await fetch(API + "getDevices.php");
        const devices = await res.json();
        const deviceSelects = document.querySelectorAll("#ticket_device_name, #new_ticket_device");
        deviceSelects.forEach(select => {
            select.innerHTML = '<option value="">Odaberite uređaj...</option>';
            devices.forEach(d => {
                select.innerHTML += `<option>${d.name}</option>`;
            });
        });
    }

    async function loadClients() {
        const res = await fetch(API + "getClients.php");
        const clients = await res.json();
        const clientSelects = document.querySelectorAll("#clientFilter, #new_ticket_client");
        clientSelects.forEach(select => {
            select.innerHTML += `<option value="">Odaberite klijenta...</option>`;
            clients.forEach(c => {
                select.innerHTML += `<option value="${c.id}">${c.username} (${c.first_name} ${c.last_name})</option>`;
            });
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

        const res = await fetch(API + "addTicket.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            alert("✅ Ticket uspješno kreiran.");
            bootstrap.Modal.getInstance(document.getElementById('newTicketModal')).hide();
            loadTickets();
        } else {
            alert("❌ " + (data.error || "Greška prilikom kreiranja ticketa."));
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        loadTickets();
        loadDevices();
        loadClients();

        window.addEventListener('message', event => {
          if (event.data && event.data.height) {
            const cusdisIframe = document.querySelector('#cusdis-container iframe');
            if (cusdisIframe) {
              cusdisIframe.style.height = event.data.height + 'px';
            }
          }
        });

        const ticketModal = document.getElementById('ticketModal');
        ticketModal.addEventListener('hidden.bs.modal', function () {
            const fileInput = document.getElementById('attachment');
            if (fileInput) fileInput.value = '';
        });
    });
  </script>
</head>

<body class="bg-light">
  <nav class="navbar navbar-dark bg-dark mb-3">
    <div class="container-fluid">
      <a class="navbar-brand" href="admin.php">Admin - Ticketomat</a>
      <div>
        <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">Novi ticket</button>
        <a href="devices.php" class="btn btn-outline-light btn-sm">Upravljanje aparatima</a>
        <button class="btn btn-outline-light btn-sm" onclick="logout()">Odjava</button>
      </div>
    </div>
  </nav>

  <div class="container py-3">
    <div class="card p-3 p-sm-4">
      <h1 class="mb-4 fs-4 text-center text-sm-start">Administracija ticketa</h1>
      <div class="row mb-3 g-2">
        <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Pretraži..." onkeyup="loadTickets()">
        </div>
        <div class="col-md-4">
          <select id="statusFilter" class="form-select" onchange="loadTickets()">
            <option value="">Svi statusi</option>
            <option>Otvoren</option>
            <option>U tijeku</option>
            <option>Riješen</option>
            <option>Zatvoren</option>
            <option>Otkazan</option>
          </select>
        </div>
        <div class="col-md-4">
          <select id="clientFilter" class="form-select" onchange="loadTickets()">
            <option value="">Svi klijenti</option>
          </select>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mb-2">
        <small><span style="color: #f8d7da;">■</span> Visok prioritet</small>
        <small><span style="color: #fff3cd;">■</span> Srednji prioritet</small>
        <small><span style="color: #d1e7dd;">■</span> Nizak prioritet</small>
        <small><span style="color: #e2e3e5;">■</span> Otkazan</small>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>Naslov</th>
              <th>Korisnik</th>
              <th>Ime aparata</th>
              <th>Serijski broj</th>
              <th>Status</th>
              <th>Datum kreiranja</th>
              <th>Akcija</th>
            </tr>
          </thead>
          <tbody id="ticketsBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal -->
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
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select id="ticket_status" class="form-select" onchange="const cancelDiv = document.getElementById('cancel_reason_div'); if (this.value === 'Otkazan') {cancelDiv.style.display = 'block';} else {cancelDiv.style.display = 'none';}">
                <option value="Otvoren">Otvoren</option>
                <option value="U tijeku">U tijeku</option>
                <option value="Riješen">Riješen</option>
                <option value="Zatvoren">Zatvoren</option>
                <option value="Otkazan">Otkazan</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Prioritet</label>
              <select id="ticket_priority" class="form-select">
                <option value="low">Nizak</option>
                <option value="medium">Srednji</option>
                <option value="high">Visok</option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Ime aparata</label>
              <select id="ticket_device_name" class="form-select"></select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Serijski broj</label>
              <input type="text" id="ticket_serial_number" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Opis</label>
            <textarea id="ticket_description" class="form-control" rows="4" style="word-wrap: break-word;"></textarea>
          </div>
          <h6 class="mt-4">📞 Podaci o korisniku</h6>
          <div class="bg-light border rounded p-2">
            <p class="mb-1"><b>Ime:</b> <span id="ticket_user"></span></p>
            <p class="mb-1"><b>Email:</b> <span id="ticket_email"></span></p>
            <p class="mb-1"><b>Telefon:</b> <span id="ticket_phone"></span></p>
            <p class="mb-1"><b>Kreirano:</b> <span id="ticket_created"></span></p>
            <p class="mb-1"><b>Otkazano:</b> <span id="ticket_canceled_at"></span></p>
            <p class="mb-0"><b>Razlog otkazivanja:</b> <span id="ticket_cancel_reason"></span></p>
          </div>
          <h6 class="mt-4">👤 Podaci o kreatoru zahtjeva</h6>
          <div class="bg-light border rounded p-2">
            <p class="mb-1"><b>Osoba:</b> <span id="ticket_request_creator"></span></p>
            <p class="mb-0"><b>Kontakt:</b> <span id="ticket_creator_contact"></span></p>
          </div>
          <div id="attachmentSectionAdmin">
            <hr>
            <h6>Datoteke</h6>
            <div id="attachmentListAdmin" class="mb-3"></div>
          </div>
          <div id="cancel_reason_div" class="mt-3" style="display:none;">
            <label class="form-label">Razlog otkazivanja:</label>
            <textarea id="cancel_reason_input" class="form-control" rows="2"></textarea>
          </div>

          <hr>
          <h6>Pošaljite poruku vezanu za ovaj zahtjev</h6>
          <div id="cusdis-container"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
          <button class="btn btn-success" onclick="saveChanges()">Spremi promjene</button>
        </div>
      </div>
    </div>
  </div>

  <!-- New Ticket Modal -->
  <div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Kreiraj novi ticket</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="new_ticket_client" class="form-label">Klijent</label>
            <select id="new_ticket_client" class="form-select"></select>
          </div>
          <div class="mb-3">
            <label for="new_ticket_title" class="form-label">Naslov</label>
            <input type="text" id="new_ticket_title" class="form-control">
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="new_ticket_device" class="form-label">Ime aparata</label>
              <select id="new_ticket_device" class="form-select"></select>
            </div>
            <div class="col-md-6">
              <label for="new_ticket_serial" class="form-label">Serijski broj</label>
              <input type="text" id="new_ticket_serial" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label for="new_ticket_description" class="form-label">Opis</label>
            <textarea id="new_ticket_description" class="form-control" rows="4"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
          <button type="button" class="btn btn-primary" onclick="createNewTicket()">Kreiraj ticket</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
