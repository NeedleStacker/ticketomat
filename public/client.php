<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Korisnik - Ticketomat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .navbar-brand { font-weight: 600; letter-spacing: 0.5px; }
    
    /* Card styling - removed light blue background */
    .card { 
      background-color: #ffffff; 
      border-radius: 12px; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border: 1px solid #e0e0e0;
    }
    
    textarea { resize: vertical; }
    .ticket-item { position: relative; padding: 15px !important; }
    .ticket-item:hover { 
      background-color: #f1f3f5; 
      cursor: pointer; 
      transition: all 0.2s ease;
      transform: translateY(-2px);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .status-badge { font-size: 0.85rem; font-weight: 600; }
    .status-otvoren { background-color: #cfe2ff !important; color: #0d6efd !important; }
    .status-u-tijeku { background-color: #fff3cd !important; color: #664d03 !important; }
    .status-rijesen { background-color: #d1e7dd !important; color: #0f5132 !important; }
    .status-zatvoren { background-color: #e9ecef !important; color: #495057 !important; }
    .status-otkazan { background-color: #6c757d !important; color: #fff !important; }
    
    .ticket-description-excerpt {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
    }
    
    #modalDesc {
      white-space: pre-wrap;
      word-wrap: break-word;
    }
    
    @media (min-width: 992px) {
      .modal-lg { max-width: 800px; }
    }
    
    @media (max-width: 576px) {
      h1, h2 { font-size: 1.4rem; }
      .navbar-brand { font-size: 1rem; }
      .btn { font-size: 0.9rem; }
    }
    
    /* Tooltip for serial number image */
    #serialImageContainer {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 10px;
        z-index: 1080;
        background-color: white;
        border: 2px solid #0d6efd;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        border-radius: .5rem;
        padding: 10px;
        max-width: 350px;
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    #serialImageContainer img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: .3rem;
    }
    
    #serialImageContainer .btn-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background-color: rgba(255, 255, 255, 0.95);
        border-radius: 50%;
        padding: 8px;
        width: 30px;
        height: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 10;
        cursor: pointer;
    }
    
    #serialImageContainer .btn-close:hover {
        background-color: #dc3545;
        color: white;
    }
    
    .input-group {
        position: relative;
    }
    
    @media (max-width: 576px) {
        #serialImageContainer {
            right: auto;
            left: 50%;
            transform: translateX(-50%);
            max-width: 90vw;
        }
    }

    /* Modal styling with better Cusdis height */
    .modal.fade .modal-dialog {
      margin-top: 2rem;
      margin-bottom: 2rem;
      max-height: calc(100vh - 4rem);
    }
    
    .modal.fade .modal-content {
      max-height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .modal.fade .modal-body {
      overflow-y: auto;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
      flex-shrink: 1;
      min-height: 0;
    }
    
    /* Cusdis container takes remaining space */
    #cusdis-container-client {
      flex-grow: 1;
      flex-shrink: 1;
      min-height: 300px;
      display: flex;
      flex-direction: column;
    }
    
    #cusdis-container-client iframe {
      flex-grow: 1;
      height: 100%;
      min-height: 300px;
    }
    
    /* Fix aria-hidden focus issue */
    .modal.show {
      overflow-y: auto;
    }
    
    .modal-footer {
      flex-shrink: 0;
      z-index: 1;
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
    
    #file-name-span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      color: #6c757d;
    }
  </style>

  <script>
    const API = "../api/";
    const user = JSON.parse(localStorage.getItem("user") || "null");
    if (!user) window.location = "index.php";

    let devicesList = [];
    let hideTooltipTimeout;

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

    async function getTickets() {
      const res = await fetch(API + `getTickets.php?user_id=${user.id}&role=${user.role}`);
      const data = await res.json();
      const out = document.getElementById("tickets");
      out.innerHTML = "";

      if (data.error) {
        out.innerHTML = `<div class='alert alert-danger'>${data.error}</div>`;
        return;
      }
      if (data.length === 0) {
        out.innerHTML = `<div class="text-muted text-center py-4">Nema ticketa za prikaz.</div>`;
        return;
      }

      data.forEach(t => {
        const created = t.created_at ? new Date(t.created_at).toLocaleString('hr-HR') : '-';

        let badgeClass = 'bg-secondary';
        switch (t.status) {
            case 'Otvoren': badgeClass = 'status-otvoren'; break;
            case 'U tijeku': badgeClass = 'status-u-tijeku'; break;
            case 'Riješen': badgeClass = 'status-rijesen'; break;
            case 'Zatvoren': badgeClass = 'status-zatvoren'; break;
            case 'Otkazan': badgeClass = 'status-otkazan'; break;
        }

        const li = document.createElement('li');
        li.className = 'list-group-item ticket-item';
        li.onclick = () => openDetails(t.id);

        const div = document.createElement('div');
        div.innerHTML = `
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <b>#${t.id}</b> - ${escapeHTML(t.title)}
            </div>
            <span class='badge ${badgeClass} status-badge'>${t.status}</span>
          </div>
          <small class="text-muted d-block mb-1">
            <strong>${t.device_name || ''}</strong> ${t.serial_number ? '(S/N: ' + t.serial_number + ')' : ''}
          </small>
        `;

        if (t.description) {
          const desc = document.createElement('small');
          desc.className = 'text-muted ticket-description-excerpt d-block mb-2';
          desc.textContent = t.description;
          div.appendChild(desc);
        }

        div.innerHTML += `<small class="text-secondary"><i class="bi bi-calendar3"></i> 📅 ${created}</small>`;
        li.appendChild(div);
        out.appendChild(li);
      });
    }

    async function loadAttachments(ticketId) {
        const attachmentList = document.getElementById("attachmentList");
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
            link.className = 'btn btn-outline-secondary btn-sm me-2 mb-2';
            link.target = '_blank';
            attachmentList.appendChild(link);
        });
    }

    async function openDetails(id) {
      const res = await fetch(API + `getTicketDetails.php?id=${id}`);
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

      loadAttachments(t.id);

      const addAttachmentSection = document.getElementById("addAttachmentSection");
      if (t.status === 'Otvoren' || t.status === 'U tijeku') {
        addAttachmentSection.style.display = 'block';
      } else {
        addAttachmentSection.style.display = 'none';
      }

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

      const cusdisContainer = document.getElementById("cusdis-container-client");
      cusdisContainer.innerHTML = '';

      const iframe = document.createElement('iframe');
      iframe.style.width = '100%';
      iframe.style.border = 'none';
      cusdisContainer.appendChild(iframe);

      const viewportHeight = window.innerHeight;

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
                "content_is_required": "Sadržaj je obavezan.",
                "sending": "Slanje...",
                "comment_has_been_sent": "Vaš komentar je poslan. Molimo pričekajte odobrenje."
              }
            <\/script>
            <div id="cusdis_thread"
              data-host="https://cusdis.com"
              data-app-id="9195cf53-b951-405c-aa1a-2acccc1b57ce"
              data-page-id="${t.id}"
              data-page-url="${window.location.href.split('?')[0] + '?ticket=' + t.id}"
              data-page-title="${escapeHTML(t.title)}"
              data-nickname="${`${user.first_name} ${user.last_name}`.trim() || user.username}"
            ></div>
            <script async defer src="https://cusdis.com/js/cusdis.es.js"><\/script>
            <script>
              const parentViewportHeight = ${viewportHeight};
              const observer = new MutationObserver((mutationsList, observer) => {
                const cusdisIframe = document.querySelector('#cusdis_thread > iframe');
                if (cusdisIframe) {
                  const dynamicHeight = Math.max(400, parentViewportHeight * 0.4);
                  cusdisIframe.style.minHeight = \`\${dynamicHeight}px\`;
                  observer.disconnect();
                }
              });
              const targetNode = document.getElementById('cusdis_thread');
              if (targetNode) {
                observer.observe(targetNode, { childList: true });
              }

              window.addEventListener('message', event => {
                if (event.origin === 'https://cusdis.com' && event.data && Array.isArray(event.data) && event.data[0] === 'comment:sent') {
                  const cusdisContainer = document.getElementById('cusdis_thread');
                  if(cusdisContainer) {
                    cusdisContainer.innerHTML = '<div style="text-align: center; padding: 2rem; color: #0f5132; background-color: #d1e7dd; border: 1px solid #badbcc; border-radius: .375rem;">Hvala! Vaš komentar je poslan i čeka na odobrenje.</div>';
                  }
                }
              });
            <\/script>
          </body>
        </html>
      `;

      iframe.srcdoc = iframeContent;

      new bootstrap.Modal(modal).show();
    }

    async function cancelTicket() {
      const id = document.getElementById("ticket_id").value;
      const reason = document.getElementById("cancel_reason").value.trim();

      const res = await fetch(API + "cancelTicket.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
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
        const device_id = document.getElementById("device_name").value;
        const serial_number = document.getElementById("serial_number").value.trim();

        if (!title || !device_id || !serial_number) {
            alert("Molimo popunite sva obavezna polja: Naslov, Ime aparata i Serijski broj.");
            return;
        }

        const device = devicesList.find(d => d.id == device_id);
        const device_name = device ? device.name : '';

        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', document.getElementById("desc").value.trim());
        formData.append('device_name', device_name);
        formData.append('serial_number', serial_number);
        formData.append('request_creator', document.getElementById("request_creator").value.trim());
        formData.append('creator_contact', document.getElementById("creator_contact").value.trim());
        formData.append('user_id', user.id);
        formData.append('status', "Otvoren");

        const attachment = document.getElementById("attachment").files[0];
        if (attachment) {
            formData.append('attachment', attachment);
        }

        const res = await fetch(API + "addTicket.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            alert("✅ Ticket uspješno dodan.");
            document.getElementById("title").value = '';
            document.getElementById("desc").value = '';
            document.getElementById("device_name").value = '';
            document.getElementById("serial_number").value = '';
            document.getElementById("request_creator").value = '';
            document.getElementById("creator_contact").value = '';
            document.getElementById("attachment").value = '';
            hideTooltip();
            await getTickets();
        } else {
            alert("❌ " + (data.error || "Greška prilikom dodavanja ticketa."));
        }
    }

    async function addAttachment() {
        const ticket_id = document.getElementById("ticket_id").value;
        const fileInput = document.getElementById("new_attachment");
        const attachment = fileInput.files[0];

        if (!attachment) {
            alert("Molimo odaberite datoteku.");
            return;
        }

        const formData = new FormData();
        formData.append('ticket_id', ticket_id);
        formData.append('attachment', attachment);

        const addButton = document.querySelector("#addAttachmentSection button");
        const originalButtonText = addButton.innerHTML;
        addButton.disabled = true;
        addButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Dodavanje...';

        const res = await fetch(API + "addAttachment.php", {
            method: "POST",
            body: formData
        });

        addButton.disabled = false;
        addButton.innerHTML = originalButtonText;

        const data = await res.json();
        if (data.success) {
            fileInput.value = '';
            loadAttachments(ticket_id);
        } else {
            alert("❌ " + (data.error || "Greška prilikom dodavanja datoteke."));
        }
    }

    function onDeviceChange() {
        const select = document.getElementById("device_name");
        const tooltipBtn = document.getElementById("serialTooltipBtn");
        tooltipBtn.disabled = select.value === "";
        
        const container = document.getElementById("serialImageContainer");
        if (!container.classList.contains("d-none")) {
            container.classList.add("d-none");
        }
    }

    function showTooltip() {
        const select = document.getElementById("device_name");
        const deviceId = select.value;
        
        console.log("Device ID selected:", deviceId);
        
        if (!deviceId) {
            alert("Molimo prvo odaberite uređaj.");
            return;
        }

        const device = devicesList.find(d => d.id == deviceId);
        console.log("Device found:", device);
        
        if (!device) {
            alert("Uređaj nije pronađen u listi.");
            return;
        }

        let imgSrc;
        if (device.image_path) {
            const cleanPath = device.image_path.replace(/^\.\.\//, '');
            imgSrc = cleanPath + "?v=" + new Date().getTime();
        } else {
            imgSrc = "img/serial_location.jpg?v=" + new Date().getTime();
        }
        
        console.log("Image source:", imgSrc);

        const container = document.getElementById("serialImageContainer");
        const img = document.getElementById("serialImage");

        img.src = imgSrc;
        img.alt = "Lokacija serijskog broja za " + device.name;
        
        container.classList.remove("d-none");
        
        if (hideTooltipTimeout) {
            clearTimeout(hideTooltipTimeout);
        }
    }

    function hideTooltip() {
        const container = document.getElementById("serialImageContainer");
        container.classList.add("d-none");
        if (hideTooltipTimeout) {
            clearTimeout(hideTooltipTimeout);
        }
    }

    function populateClientInfo() {
      if (user) {
        document.getElementById("clientName").textContent = `${user.first_name} ${user.last_name}`;
        document.getElementById("clientUsername").textContent = user.username;
      }
    }

    async function loadDevices() {
        const res = await fetch(API + "getDevices.php");
        devicesList = await res.json();
        const select = document.getElementById("device_name");
        select.innerHTML = '<option value="">Odaberite uređaj...</option>';
        devicesList.forEach(d => {
            select.innerHTML += `<option value="${d.id}">${d.name}</option>`;
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
      getTickets();
      populateClientInfo();
      loadDevices();

      const ticketModal = document.getElementById('ticketModal');
      
      ticketModal.addEventListener('show.bs.modal', function (e) {
          if (document.activeElement && document.activeElement.blur) {
              document.activeElement.blur();
          }
      });

      ticketModal.addEventListener('hidden.bs.modal', function () {
          const fileInput = document.getElementById('new_attachment');
          if (fileInput) fileInput.value = '';
          document.getElementById('file-name-span').textContent = 'Nije izabran fajl';
          
          if (document.activeElement && document.activeElement.blur) {
            document.activeElement.blur();
          }
          
          const backdrop = document.querySelector('.modal-backdrop');
          if (backdrop) backdrop.remove();
          
          document.body.style.overflow = '';
          document.body.classList.remove('modal-open');
      });

      const cancelModal = document.getElementById('cancelModal');
      if (cancelModal) {
          cancelModal.addEventListener('show.bs.modal', function (e) {
              if (document.activeElement && document.activeElement.blur) {
                  document.activeElement.blur();
              }
          });
      }

      const fileInput = document.getElementById('new_attachment');
      fileInput.addEventListener('change', function() {
        const fileNameSpan = document.getElementById('file-name-span');
        fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Nije izabran fajl';
      });
    });
  </script>
</head>

<body class="bg-light">
  <nav class="navbar navbar-dark bg-dark mb-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <span class="navbar-brand mb-0"><img src="favicon.png" width="50" height="50" type="image/png" /> Ticketomat</span>
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
        <input id="request_creator" class="form-control mb-2" placeholder="Osoba" />
        <input id="creator_contact" class="form-control mb-2" placeholder="Kontakt (telefon ili email)" />
        <select id="device_name" class="form-select mb-2" onchange="onDeviceChange()">
          <option value="">Odaberite uređaj...</option>
        </select>

        <div class="input-group mb-2 position-relative">
          <input id="serial_number" class="form-control" placeholder="Serijski broj uređaja (obavezno)" />
          <button id="serialTooltipBtn" class="btn btn-outline-secondary" type="button" onclick="showTooltip()" disabled>
            Gdje ga pronaći?
          </button>
          
          <div id="serialImageContainer" class="d-none">
            <button type="button" class="btn-close" onclick="hideTooltip()" aria-label="Zatvori"></button>
            <img id="serialImage" src="" alt="Lokacija serijskog broja">
          </div>
        </div>

        <div class="mb-3">
            <label for="attachment" class="form-label">Datoteka (Max 5MB)</label>
            <input class="form-control" type="file" id="attachment">
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
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalTitle">Detalji ticketa</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="ticket_id">
          <p><b>Uređaj:</b> <span id="modalDevice"></span></p>
          <p><b>Serijski broj:</b> <span id="modalSerial"></span></p>
          <p><b>Opis:</b> <span id="modalDesc" style="word-wrap: break-word;"></span></p>
          <p><b>Status:</b> <span id="modalStatus"></span></p>
          <p><b>Kreirano:</b> <span id="modalDate"></span></p>
          <p><b>Otkazano:</b> <span id="modalCanceledAt"></span></p>
          <p><b>Razlog otkazivanja:</b> <span id="modalCancelReason"></span></p>

          <div id="attachmentSection">
              <hr>
              <h6>Datoteke</h6>
              <div id="attachmentList" class="mb-3"></div>
              <div id="addAttachmentSection" style="display:none;">
                  <h6 class="fs-6">Dodaj novu datoteku</h6>
                  <div class="custom-file-upload-container">
                      <label for="new_attachment" class="custom-file-upload">Odaberi fajl</label>
                      <span id="file-name-span">Nije izabran fajl</span>
                      <input type="file" id="new_attachment" class="d-none">
                      <button class="btn btn-outline-primary btn-sm ms-auto" type="button" onclick="addAttachment()">Dodaj</button>
                  </div>
              </div>
          </div>

          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Pošaljite poruku vezanu za ovaj zahtjev</h6>
          </div>
          <div id="cusdis-container-client" class="mt-2"></div>
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
