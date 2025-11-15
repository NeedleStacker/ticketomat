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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/comments.css">
  <link rel="stylesheet" href="assets/css/admin.css">

  <script>
    const API = "../api/";
    const user = JSON.parse(localStorage.getItem("user") || "null");
    if (!user) window.location = "index.php";

    function setFormDisabledState(disabled) {
        const formElements = document.querySelectorAll(
            '#ticketModal .modal-body input, #ticketModal .modal-body select, #ticketModal .modal-body textarea, #ticketModal .modal-footer .btn'
        );
        const attachmentButton = document.querySelector('#adminAttachmentSection button');

        formElements.forEach(el => {
            if (!el.matches('[data-bs-dismiss="modal"]') && !el.matches('.btn-close') && el.id !== 'lockButton') {
                el.disabled = disabled;
            }
        });

        if (attachmentButton) attachmentButton.disabled = disabled;
    }

    async function lockTicket() {
        const ticket_id = document.getElementById("ticket_id").value;
        if (!confirm('Jeste li sigurni da želite zaključati ovaj ticket? Akcija je nepovratna bez otključavanja od strane administratora.')) return;

        try {
            const res = await fetch(API + 'lockTicket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id })
            });
            const data = await res.json();
            if (data.success) {
                alert('✅ Ticket uspješno zaključan.');
                const currentModal = bootstrap.Modal.getInstance(document.getElementById('ticketModal'));
                currentModal.hide();
                showTicketDetails(ticket_id);
            } else {
                alert('❌ ' + (data.error || 'Greška prilikom zaključavanja.'));
            }
        } catch (error) {
            console.error('Lock error:', error);
            alert('Došlo je do greške na strani servera.');
        }
    }

    async function deleteAttachment(attachmentId, ticketId) {
        if (!confirm('Jeste li sigurni da želite obrisati ovu datoteku?')) return;

        try {
            const res = await fetch(API + 'deleteAttachment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attachment_id: attachmentId })
            });
            const data = await res.json();
            if (data.success) {
                loadAttachmentsAdmin(ticketId);
            } else {
                alert('❌ ' + (data.error || 'Greška prilikom brisanja datoteke.'));
            }
        } catch (error) {
            console.error('Delete attachment error:', error);
            alert('Došlo je do greške na strani servera.');
        }
    }

    async function unlockTicket() {
        const ticket_id = document.getElementById("ticket_id").value;
        const password = prompt("Za otključavanje unesite svoju lozinku:");
        if (password === null) return;

        const unlock_reason = prompt("Molimo unesite razlog otključavanja:");
        if (unlock_reason === null || unlock_reason.trim() === '') {
            alert("Razlog otključavanja je obavezan.");
            return;
        }

        try {
            const res = await fetch(API + 'unlockTicket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id, password, unlock_reason })
            });

            const data = await res.json();
            if (data.success) {
                alert('✅ Ticket uspješno otključan.');
                // Re-fetch details to show unlocked state
                const currentModal = bootstrap.Modal.getInstance(document.getElementById('ticketModal'));
                currentModal.hide();
                showTicketDetails(ticket_id);
            } else {
                alert('❌ ' + (data.error || 'Greška prilikom otključavanja.'));
            }
        } catch (error) {
            console.error('Unlock error:', error);
            alert('Došlo je do greške na strani servera.');
        }
    }

    function logout() {
      localStorage.removeItem("user");
      window.location = "index.php";
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
      const attachmentSection = document.getElementById("adminAttachmentSection");
      const attachmentList = document.getElementById("attachmentListAdmin");
      attachmentList.innerHTML = '<div class="text-muted">Učitavanje...</div>';

      const res = await fetch(API + `getAttachments.php?ticket_id=${ticketId}`);
      const attachments = await res.json();
      attachmentList.innerHTML = ""; // Clear loading message

      if (attachments.error) {
          attachmentList.innerHTML = `<div class="text-danger small">${attachments.error}</div>`;
          attachmentSection.style.display = 'block';
          return;
      }

      if (attachments.length === 0) {
          attachmentSection.style.display = 'none';
      } else {
          attachmentSection.style.display = 'block';
          attachments.forEach(file => {
              const fileContainer = document.createElement('div');
              fileContainer.className = 'd-flex align-items-center mb-2';

              const link = document.createElement('a');
              link.href = `${API}getAttachment.php?id=${file.id}`;
              link.textContent = file.attachment_name;
              link.className = 'btn btn-outline-secondary btn-sm me-2 attachment-link flex-grow-1';
              link.target = '_blank';

              const deleteBtn = document.createElement('button');
              deleteBtn.className = 'btn btn-outline-danger btn-sm';
              deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
              deleteBtn.onclick = () => deleteAttachment(file.id, ticketId);

              fileContainer.appendChild(link);
              fileContainer.appendChild(deleteBtn);
              attachmentList.appendChild(fileContainer);
          });
      }
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

      // Handle lock state
      const isLocked = t.is_locked == 1;
      const lockButton = document.getElementById('lockButton');
      const icon = lockButton.querySelector('i');
      const unlockReasonContainer = document.getElementById('unlockReasonContainer');
      const unlockReasonText = document.getElementById('unlockReasonText');

      setFormDisabledState(isLocked);

      if (isLocked) {
        icon.classList.remove('bi-unlock-fill');
        icon.classList.add('bi-lock-fill');
        lockButton.title = "Otključaj ovaj ticket";
        lockButton.onclick = unlockTicket;
        unlockReasonContainer.style.display = 'none';
        lockButton.style.display = 'inline-block';
      } else {
        icon.classList.remove('bi-lock-fill');
        icon.classList.add('bi-unlock-fill');
        lockButton.title = "Zaključaj ovaj ticket";
        lockButton.onclick = lockTicket;

        if (t.status === 'Riješen' || t.status === 'Otkazan') {
            lockButton.style.display = 'inline-block';
        } else {
            lockButton.style.display = 'none';
        }

        if(t.unlock_reason) {
            unlockReasonContainer.style.display = 'block';
            unlockReasonText.textContent = t.unlock_reason;
        } else {
            unlockReasonContainer.style.display = 'none';
        }
      }

      const btnCancel = document.getElementById("cancelTicketBtnAdmin");
      if (t.status === 'Otkazan' || t.status === 'Zatvoren' || t.status === 'Riješen') {
        btnCancel.style.display = 'none';
      } else {
        btnCancel.style.display = 'inline-block';
      }

      const commentsContainer = document.getElementById('comments-section-container');
      commentsContainer.style.display = 'block';
      renderCommentUI(commentsContainer, t.id, true, isLocked); // true for isAdmin

      new bootstrap.Modal(modal).show();
    }

    async function cancelTicketAdmin() {
      const id = document.getElementById("ticket_id").value;
      const reason = document.getElementById("admin_cancel_reason").value.trim();

      const res = await fetch(API + "cancelTicket.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id, reason, user_id: user.id })
      });
      const data = await res.json();

      if (data.success) {
        alert("✅ Ticket uspješno otkazan.");
        bootstrap.Modal.getInstance(document.getElementById('adminCancelModal')).hide();
        bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
        loadTickets();
      } else alert("❌ " + (data.error || "Greška prilikom otkazivanja."));
    }

    async function saveChanges() {
      const id = document.getElementById("ticket_id").value;
      const status = document.getElementById("ticket_status").value;
      const priority = document.getElementById("ticket_priority").value;

      if (status === 'Otkazan') {
        const cancelModal = new bootstrap.Modal(document.getElementById('adminCancelModal'));
        cancelModal.show();
        return;
      }
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
            <option value="">Svi korisnici</option>
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
            <div class="ms-auto d-flex align-items-center">
                <button type="button" id="lockButton" class="btn btn-sm me-2"><i class="bi bi-lock-fill fs-5"></i></button>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
        </div>
        <div class="modal-body">
          <input type="hidden" id="ticket_id">
          <div id="unlockReasonContainer" class="alert alert-warning" style="display: none;">
              <strong>Ticket Otključan:</strong> <span id="unlockReasonText"></span>
          </div>
          <div class="row">
            <div class="col-lg-8">
              <div class="row mb-3">
                <div class="col-md-6"><label for="ticket_device_name" class="form-label">Uređaj</label><input type="text" id="ticket_device_name" class="form-control"></div>
                <div class="col-md-6"><label for="ticket_serial_number" class="form-label">Serijski broj</label><input type="text" id="ticket_serial_number" class="form-control"></div>
              </div>
              <div class="mb-3"><label for="ticket_description" class="form-label">Opis</label><textarea id="ticket_description" class="form-control" rows="4"></textarea></div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-light p-3">
                    <h6 class="fs-6 fw-bold">Korisnik</h6>
                    <p id="ticket_user" class="mb-1"></p>
                    <p id="ticket_email" class="mb-1"></p>
                    <p id="ticket_phone" class="mb-0"></p>
                </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4"><label for="ticket_status" class="form-label">Status</label>
              <select id="ticket_status" class="form-select"><option>Otvoren</option><option>U tijeku</option><option>Riješen</option><option>Zatvoren</option><option>Otkazan</option></select>
            </div>
            <div class="col-md-4"><label for="ticket_priority" class="form-label">Prioritet</label>
              <select id="ticket_priority" class="form-select"><option value="low">Nizak</option><option value="medium">Srednji</option><option value="high">Visok</option></select>
            </div>
          </div>
          <div class="mb-3" id="adminAttachmentSection">
              <label class="form-label" for="admin_new_attachment">Datoteke</label>
              <div id="attachmentListAdmin" class="mb-2"></div>
              <div class="custom-file-upload-container">
                  <label for="admin_new_attachment" class="custom-file-upload">Odaberi datoteku</label>
                  <span id="file-name-span-admin" class="text-muted">Nije izabrana datoteka</span>
                  <input type="file" id="admin_new_attachment" class="d-none">
                  <button class="btn btn-outline-primary btn-sm ms-auto" type="button" onclick="addAttachmentAdmin()">Dodaj</button>
              </div>
          </div>
          <hr>
          <div id="comments-section-container"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger me-auto" id="cancelTicketBtnAdmin" data-bs-toggle="modal" data-bs-target="#adminCancelModal">Otkaži Ticket</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
          <button type="button" class="btn btn-primary" onclick="saveChanges()">Spremi promjene</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Otkazivanje -->
  <div class="modal fade" id="adminCancelModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Otkazivanje ticketa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Molimo unesite razlog otkazivanja (opcionalno):</p>
          <textarea id="admin_cancel_reason" class="form-control" rows="3"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
          <button type="button" class="btn btn-danger" onclick="cancelTicketAdmin()">Potvrdi otkazivanje</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/newTicket.js"></script>
  <script src="assets/js/comments.js"></script>
</body>
</html>
