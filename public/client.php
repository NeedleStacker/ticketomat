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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/comments.css">
  <link rel="stylesheet" href="assets/css/client.css">
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
      <h1 class="mb-3 fs-4 text-center text-sm-start">Podaci o korisniku</h1>
      <p class="mb-1"><b>Ime i prezime:</b> <span id="clientName"></span></p>
      <p class="mb-1"><b>Korisničko ime:</b> <span id="clientUsername"></span></p>
    </div>

    <div class="card p-3 p-sm-4 mb-4">
      <h1 class="mb-3 fs-4 text-center text-sm-start">Prijava problema</h1>
      <div id="alertBox" class="mb-2"></div>

      <div class="mb-3">
        <input id="title" class="form-control mb-2" placeholder="Naslov ticketa (kratko opisati problem)" />
        <input id="request_creator" class="form-control mb-2" placeholder="Osoba koja otvara ticket" />
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
            <label class="form-label" for="attachment">Datoteka (Max 10MB)</label>
            <div class="custom-file-upload-container">
                <label for="attachment" class="custom-file-upload">Odaberi datoteku</label>
                <span id="file-name-span-new-ticket" class="text-muted">Nije izabrana datoteka</span>
                <input type="file" id="attachment" class="d-none">
            </div>
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
        <div class="modal-header bg-primary text-white d-flex justify-content-between">
            <h5 class="modal-title" id="modalTitle">Detalji ticketa</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="ticket_id">
          <p><b>Uređaj:</b> <span id="modalDevice"></span></p>
          <p><b>Serijski broj:</b> <span id="modalSerial"></span></p>
          <p><b>Opis:</b> <span id="modalDesc" style="word-wrap: break-word;"></span></p>
          <p><b>Status:</b> <span id="modalStatus"></span></p>
          <p><b>Otvoreno:</b> <span id="modalDate"></span></p>
          <p><b>Ticket otvorio:</b> <span id="modalRequestCreator"></span></p>
          <p id="cancelDateRow"><b>Otkazano:</b> <span id="modalCanceledAt"></span></p>
          <p id="cancelReasonRow"><b>Razlog otkazivanja:</b> <span id="modalCancelReason"></span></p>

          <div id="attachmentSection">
              <hr>
              <h6>Datoteke</h6>
              <div id="attachmentList" class="mb-3"></div>
              <div id="addAttachmentSection" style="display:none;">
                  <h6 class="fs-6">Dodaj novu datoteku</h6>
                  <div class="custom-file-upload-container">
                      <label for="new_attachment" class="custom-file-upload">Odaberi datoteku</label>
                      <span id="file-name-span">Nije izabran fajl</span>
                      <input type="file" id="new_attachment" class="d-none">
                      <button class="btn btn-outline-primary btn-sm ms-auto" type="button" onclick="addAttachment()">Dodaj</button>
                  </div>
              </div>
          </div>
          <div id="comments-section-container"></div>
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
  <script src="assets/js/comments.js"></script>
  <script src="assets/js/client.js"></script>
</body>
</html>
