<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Klijent - Ticketomat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .navbar-brand { font-weight: 600; letter-spacing: 0.5px; }
    .card { border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    textarea { resize: vertical; }
    .tooltip-inner img { width: 300px; height: auto; }
    .ticket-item:hover { background-color: #f1f1f1; cursor: pointer; transition: background 0.2s; }
    .status-badge { font-size: 0.85rem; }
    .status-otkazan { background-color: #6c757d !important; } /* sivo */
    @media (max-width: 576px) {
      h1, h2 { font-size: 1.4rem; }
      .navbar-brand { font-size: 1rem; }
      .btn { font-size: 0.9rem; }
    }
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

    async function getTickets() {
      const res = await fetch(API + `getTickets.php?user_id=${user.id}&role=${user.role}`, {
        headers: { "X-API-KEY": API_KEY }
      });
      const data = await res.json();
      const out = document.getElementById("tickets");
      out.innerHTML = "";

      if (data.error) {
        out.innerHTML = `<div class='alert alert-danger'>${data.error}</div>`;
        return;
      }
      if (data.length === 0) {
        out.innerHTML = `<div class="text-muted text-center py-3">Nema ticketa za prikaz.</div>`;
        return;
      }

      data.forEach(t => {
        const created = t.created_at ? new Date(t.created_at).toLocaleString('hr-HR') : '-';
        const isCanceled = t.status === 'Otkazan';
        const badgeClass = isCanceled ? 'status-otkazan' : 'bg-secondary';
        const rowStyle = isCanceled ? 'background-color:#e2e3e5;color:#6c757d;' : '';

        out.innerHTML += `
          <li class='list-group-item ticket-item d-flex justify-content-between align-items-start flex-wrap'
              style="${rowStyle}" onclick="openDetails(${t.id})">
            <div>
              <b>#${t.id}</b> - ${t.title}<br>
              <small class="text-muted">${t.device_name || ''} (${t.serial_number || '-'})</small><br>
              <small class="text-muted">${t.description || ""}</small><br>
              <small class="text-secondary">📅 Kreirano: ${created}</small>
            </div>
            <div class="text-end">
              <span class='badge ${badgeClass} status-badge mt-2 mt-sm-0'>${t.status}</span>
            </div>
          </li>`;
      });
    }

    async function openDetails(id) {
      const res = await fetch(API + `getTicketDetails.php?id=${id}`, {
        headers: { "X-API-KEY": API_KEY }
      });
      const t = await res.json();
      if (t.error) { alert(t.error); return; }

      const modal = document.getElementById('ticketModal');
      document.getElementById("modalTitle").textContent = `Ticket #${t.id} – ${t.title}`;
      document.getElementById("modalDevice").textContent = t.device_name || "-";
      document.getElementById("modalSerial").textContent = t.serial_number || "-";
      document.getElementById("modalDesc").textContent = t.description || "-";
      document.getElementById("modalStatus").textContent = t.status;
      document.getElementById("modalDate").textContent = new Date(t.created_at).toLocaleString('hr-HR');
      document.getElementById("modalCanceledAt").textContent = t.canceled_at ? new Date(t.canceled_at).toLocaleString('hr-HR') : "-";
      document.getElementById("modalCancelReason").textContent = t.cancel_reason || "-";
      document.getElementById("ticket_id").value = t.id;

      const btnCancel = document.getElementById("cancelTicketBtn");
      if (t.status === 'Otkazan' || t.status === 'Zatvoren' || t.status === 'Riješen') {
        btnCancel.style.display = 'none';
      } else {
        btnCancel.style.display = 'inline-block';
      }

      if (t.status === 'Otkazan') {
        modal.querySelector('.modal-header').classList.remove('bg-primary');
        modal.querySelector('.modal-header').classList.add('bg-secondary');
      } else {
        modal.querySelector('.modal-header').classList.remove('bg-secondary');
        modal.querySelector('.modal-header').classList.add('bg-primary');
      }

      new bootstrap.Modal(modal).show();
    }

    async function cancelTicket() {
      const id = document.getElementById("ticket_id").value;
      const reason = document.getElementById("cancel_reason").value.trim();

      const res = await fetch(API + "cancelTicket.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-API-KEY": API_KEY },
        body: JSON.stringify({ id, reason, user_id: user.id })
      });
      const data = await res.json();

      if (data.success) {
        alert("✅ Ticket uspješno otkazan.");
        bootstrap.Modal.getInstance(document.getElementById('cancelModal')).hide();
        bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
        getTickets();
      } else alert("❌ " + (data.error || "Greška prilikom otkazivanja."));
    }

    async function addTicket() {
      const title = document.getElementById("title").value.trim();
      const description = document.getElementById("desc").value.trim();
      const device_name = document.getElementById("device_name").value;
      const serial_number = document.getElementById("serial_number").value.trim();
      const request_creator = document.getElementById("request_creator").value.trim();
      const creator_contact = document.getElementById("creator_contact").value.trim();

      const res = await fetch(API + "addTicket.php", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-API-KEY": API_KEY },
        body: JSON.stringify({
          title,
          description,
          device_name,
          serial_number,
          user_id: user.id,
          request_creator,
          creator_contact,
          status: "Otvoren"
        })
      });
      const data = await res.json();
      if (data.success) {
        alert("✅ Ticket uspješno dodan.");
        getTickets();
      } else {
        alert("❌ " + (data.error || "Greška prilikom dodavanja ticketa."));
      }
    }

    // Tooltip logika (ostaje ista)
    function onDeviceChange() {
      const select = document.getElementById("device_name");
      const tooltipSpan = document.getElementById("serialTooltip");
      if (select.value === "") { disableTooltip(); return; }

      const imgMap = {
        "Ulrich CT Motion": "../img/serial_ctmotion.jpg",
        "Ulrich MAX2/3": "../img/serial_max.jpg",
        "Vernacare Vortex AIR": "../img/serial_vortex.jpg",
        "Vernacare Vortex+": "../img/serial_vortex.jpg",
		"ACIST CVi": "../img/serial_acist.jpg",
        "Eurosets ECMOLIFE": "../img/serial_ecmolife.jpg"
      };
      const imgSrc = imgMap[select.value] || "../img/serial_location.jpg";
      tooltipSpan.setAttribute("data-bs-toggle", "tooltip");
      tooltipSpan.setAttribute("data-bs-placement", "top");
      tooltipSpan.setAttribute("title", `<img src='${imgSrc}' alt='Gdje pronaći serijski broj'>`);
      tooltipSpan.style.opacity = "1";

      var tooltip = bootstrap.Tooltip.getInstance(tooltipSpan);
      if (tooltip) {
        tooltip.dispose();
      }
      new bootstrap.Tooltip(tooltipSpan, { html: true });
    }

    function disableTooltip() {
      const tooltipSpan = document.getElementById("serialTooltip");
      if (tooltipSpan._tooltipInstance) {
        tooltipSpan._tooltipInstance.dispose();
        tooltipSpan._tooltipInstance = null;
      }
      tooltipSpan.removeAttribute("data-bs-toggle");
      tooltipSpan.removeAttribute("title");
      tooltipSpan.style.opacity = "0.5";
    }

    function populateClientInfo() {
      if (user) {
        document.getElementById("clientName").textContent = `${user.first_name} ${user.last_name}`;
        document.getElementById("clientUsername").textContent = user.username;
      }
    }

    document.addEventListener("DOMContentLoaded", function() {
      disableTooltip();
      getTickets();
      populateClientInfo();
    });
  </script>
</head>

<body class="bg-light">
  <nav class="navbar navbar-dark bg-dark mb-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <span class="navbar-brand mb-0"><img src="favicon.png" width="50" height="50" type="image/png" /> Ticketomat Klijent</span>
      <button class="btn btn-outline-light btn-sm" onclick="logout()">Odjava</button>
    </div>
  </nav>

  <div class="container py-3">
    <div class="card p-3 p-sm-4 mb-4">
      <h1 class="mb-3 fs-4 text-center text-sm-start">Podaci o klijentu</h1>
      <p class="mb-1"><b>Ime i prezime:</b> <span id="clientName"></span></p>
      <p class="mb-1"><b>Korisničko ime:</b> <span id="clientUsername"></span></p>
    </div>

    <div class="card p-3 p-sm-4 mb-4">
      <h1 class="mb-3 fs-4 text-center text-sm-start">Prijava problema</h1>
      <div id="alertBox" class="mb-2"></div>

      <div class="mb-3">
        <input id="title" class="form-control mb-2" placeholder="Naslov ticketa (kratko opisati problem)" />
        <input id="request_creator" class="form-control mb-2" placeholder="Osoba koja kreira zahtjev" />
        <input id="creator_contact" class="form-control mb-2" placeholder="Kontakt (telefon ili email)" />
        <select id="device_name" class="form-select mb-2" onchange="onDeviceChange()">
          <option value="">Odaberite uređaj...</option>
          <option>Ulrich CT Motion</option>
          <option>Ulrich MAX2/3</option>
          <option>Vernacare Vortex AIR</option>
          <option>Vernacare Vortex+</option>
          <option>ACIST CVi</option>
          <option>Eurosets ECMOLIFE</option>
        </select>

        <div class="input-group mb-2">
          <input id="serial_number" class="form-control" placeholder="Serijski broj uređaja (obavezno)" />
          <span id="serialTooltip" class="input-group-text" style="opacity:0.5;">Gdje ga pronaći?</span>
        </div>

        <textarea id="desc" class="form-control mb-2" rows="3" placeholder="Detaljan opis problema (opcionalno)"></textarea>
        <div class="text-end">
          <button class="btn btn-primary px-4" onclick="addTicket()">Pošalji</button>
        </div>
      </div>
    </div>

    <div class="card p-3 p-sm-4">
      <h2 class="mb-3 fs-5 text-center text-sm-start">Popis vaših ticketa</h2>
      <ul id="tickets" class="list-group"></ul>
    </div>
  </div>

  <!-- Modal Detalji -->
  <div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalTitle">Detalji ticketa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="ticket_id">
          <p><b>Uređaj:</b> <span id="modalDevice"></span></p>
          <p><b>Serijski broj:</b> <span id="modalSerial"></span></p>
          <p><b>Opis:</b> <span id="modalDesc"></span></p>
          <p><b>Status:</b> <span id="modalStatus"></span></p>
          <p><b>Kreirano:</b> <span id="modalDate"></span></p>
          <p><b>Otkazano:</b> <span id="modalCanceledAt"></span></p>
          <p><b>Razlog otkazivanja:</b> <span id="modalCancelReason"></span></p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-danger me-auto" id="cancelTicketBtn" data-bs-toggle="modal" data-bs-target="#cancelModal">Otkaži zahtjev</button>
          <button class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Otkazivanje -->
  <div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Otkazivanje ticketa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Molimo unesite razlog otkazivanja (opcionalno):</p>
          <textarea id="cancel_reason" class="form-control" rows="3"></textarea>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
          <button class="btn btn-danger" onclick="cancelTicket()">Potvrdi otkazivanje</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
