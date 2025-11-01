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
    .card { border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    table { background-color: #fff; border-radius: 8px; overflow: hidden; }
    td, th { vertical-align: middle !important; }

    .priority-low { color: #198754; font-weight: 600; }
    .priority-medium { color: #ffc107; font-weight: 600; }
    .priority-high { color: #dc3545; font-weight: 600; }

    .priority-high-row { background-color: #f8d7da !important; }
    .priority-medium-row { background-color: #fff3cd !important; }
    .priority-low-row { background-color: #d1e7dd !important; }
    .status-otkazan-row { background-color: #e2e3e5 !important; }

    .modal-priority-high .modal-header { background-color: #dc3545 !important; color: #fff; }
    .modal-priority-medium .modal-header { background-color: #ffc107 !important; color: #000; }
    .modal-priority-low .modal-header { background-color: #198754 !important; color: #fff; }
    .modal-status-otkazan .modal-header { background-color: #6c757d !important; color: #fff; }
  </style>

  <script>
    const API = "../api/";
    const API_KEY = "ZQjjWaAXsPbKFuahw3TK8LCRE";
    const user = JSON.parse(localStorage.getItem("user") || "null");
    if (!user) window.location = "index.php";

    function logout() {
      localStorage.removeItem("user");
      window.location = "index.php";
    }

    // Prevod statusa
    function translateStatus(status) {
      const map = {
        "open": "Otvoren",
        "in_progress": "U tijeku",
        "resolved": "Riješen",
        "closed": "Zatvoren",
        "otkazan": "Otkazan"
      };
      return map[status] || "Nepoznato";
    }

    async function loadTickets() {
      if (!user) return;
      const res = await fetch(API + `getTickets.php?role=${user.role}&user_id=${user.id}`, {
        headers: { "X-API-KEY": API_KEY }
      });
      const tickets = await res.json();
      const body = document.getElementById("ticketsBody");
      body.innerHTML = "";

      if (tickets.error) {
        body.innerHTML = `<tr><td colspan='8' class='text-danger text-center'>Greška: ${tickets.error}</td></tr>`;
        return;
      }
      if (tickets.length === 0) {
        body.innerHTML = `<tr><td colspan='8' class='text-center text-muted'>Nema ticketa za prikaz.</td></tr>`;
        return;
      }

      tickets.forEach(t => {
        const prioClass = t.priority === 'high' ? 'priority-high' : (t.priority === 'medium' ? 'priority-medium' : 'priority-low');
        const rowClass =
          t.status === 'otkazan' ? 'status-otkazan-row' :
          t.priority === 'high' ? 'priority-high-row' :
          t.priority === 'medium' ? 'priority-medium-row' :
          'priority-low-row';

        body.innerHTML += `
          <tr class="${rowClass}">
            <td>${t.id}</td>
            <td>${t.title}</td>
            <td>${t.username || 'N/A'}</td>
            <td><span class="${prioClass}">${t.priority || 'medium'}</span></td>
            <td>${translateStatus(t.status)}</td>
            <td>${t.created_at || ''}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary" onclick="openDetails(${t.id})">Detalji</button>
            </td>
          </tr>`;
      });
    }

    async function openDetails(id) {
      const res = await fetch(API + `getTicketDetails.php?id=${id}`, { headers: { "X-API-KEY": API_KEY } });
      const t = await res.json();
      if (t.error) { alert(t.error); return; }

      const modal = document.getElementById('ticketModal');
      modal.className = 'modal fade';

      if (t.status === 'otkazan') modal.classList.add('modal-status-otkazan');
      else if (t.priority === 'high') modal.classList.add('modal-priority-high');
      else if (t.priority === 'medium') modal.classList.add('modal-priority-medium');
      else modal.classList.add('modal-priority-low');

      document.getElementById("modalTitle").textContent = "Ticket #" + t.id + " – " + t.title;
      document.getElementById("ticket_id").value = t.id;
      document.getElementById("ticket_status").value = t.status;
      document.getElementById("ticket_priority").value = t.priority || "medium";
      document.getElementById("ticket_description").value = t.description || "";
      document.getElementById("ticket_user").textContent = `${t.first_name || ''} ${t.last_name || ''}`;
      document.getElementById("ticket_email").textContent = t.email || '';
      document.getElementById("ticket_phone").textContent = t.phone || '';
      document.getElementById("ticket_created").textContent = t.created_at || '-';
      document.getElementById("ticket_canceled_at").textContent = t.canceled_at || '-';
      document.getElementById("ticket_cancel_reason").textContent = t.cancel_reason || '-';

      new bootstrap.Modal(modal).show();
    }

    async function saveChanges() {
      const id = document.getElementById("ticket_id").value;
      const status = document.getElementById("ticket_status").value;
      const priority = document.getElementById("ticket_priority").value;
      const description = document.getElementById("ticket_description").value;
      const cancel_reason = document.getElementById("cancel_reason_input").value;

      const body = { id, status, priority, description };
      if (status === 'otkazan') {
        body.cancel_reason = cancel_reason;
      }

      const res = await fetch(API + "updateTicket.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-API-KEY": API_KEY },
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
  </script>
</head>

<body class="bg-light" onload="loadTickets()">
  <nav class="navbar navbar-dark bg-dark mb-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <span class="navbar-brand"><img src="favicon.png" width="50" height="50" type="image/png" /> Admin - Ticketomat</span>
      <button class="btn btn-outline-light btn-sm" onclick="logout()">Odjava</button>
    </div>
  </nav>

  <div class="container py-3">
    <div class="card p-3 p-sm-4">
      <h1 class="mb-4 fs-4 text-center text-sm-start">Administracija ticketa</h1>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Naslov</th>
              <th>Korisnik</th>
              <th>Prioritet</th>
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
    <div class="modal-dialog modal-lg">
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
              <select id="ticket_status" class="form-select" onchange="const cancelDiv = document.getElementById('cancel_reason_div'); if (this.value === 'otkazan') {cancelDiv.style.display = 'block';} else {cancelDiv.style.display = 'none';}">
                <option value="open">Otvoren</option>
                <option value="in_progress">U tijeku</option>
                <option value="resolved">Riješen</option>
                <option value="closed">Zatvoren</option>
                <option value="otkazan">Otkazan</option>
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
          <div class="mb-3">
            <label class="form-label">Opis</label>
            <textarea id="ticket_description" class="form-control" rows="4"></textarea>
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
          <div id="cancel_reason_div" class="mt-3" style="display:none;">
            <label class="form-label">Razlog otkazivanja:</label>
            <textarea id="cancel_reason_input" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
          <button class="btn btn-success" onclick="saveChanges()">Spremi promjene</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
