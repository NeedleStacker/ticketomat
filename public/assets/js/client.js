const API = "../api/";
const user = JSON.parse(localStorage.getItem("user") || "null");
if (!user) window.location = "index.php";

let devicesList = [];
let hideTooltipTimeout;

function logout() {
  localStorage.removeItem("user");
  window.location = "index.php";
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
          <b>#${t.id}</b> - ${t.title}
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
    const attachmentSection = document.getElementById("attachmentSection");
    const attachmentList = document.getElementById("attachmentList");
    attachmentList.innerHTML = '<div class="text-muted">Učitavanje...</div>';

    const res = await fetch(API + `getAttachments.php?ticket_id=${ticketId}`);
    const attachments = await res.json();
    attachmentList.innerHTML = ""; // Clear loading message
  attachmentList.className = "d-flex flex-wrap";

    if (attachments.error) {
         attachmentList.innerHTML = `<div class="text-danger small">${attachments.error}</div>`;
         attachmentSection.style.display = 'block';
         return;
    }

  attachmentSection.style.display = 'block';

  if (attachments.length > 0) {
        attachments.forEach(file => {
            const link = document.createElement('a');
            link.href = `${API}getAttachment.php?id=${file.id}`;
            link.textContent = file.attachment_name;
            link.className = 'btn btn-outline-secondary btn-sm me-2 mb-2';
            link.target = '_blank';
            attachmentList.appendChild(link);
        });
    }
}

async function openDetails(id) {
  const res = await fetch(API + `getTicketDetails.php?id=${id}`);
  const t = await res.json();
  if (t.error) { alert(t.error); return; }

  const modal = document.getElementById('ticketModal');
  const modalTitle = document.getElementById("modalTitle");

  let titleHTML = `Ticket #${t.id} – ${t.title}`;
  if (t.is_locked == 1) {
      titleHTML += ' <i class="bi bi-lock-fill" title="Ovaj ticket je zaključen"></i>';
  }
  modalTitle.innerHTML = titleHTML;

  document.getElementById("modalDevice").textContent = t.device_name || "-";
  document.getElementById("modalSerial").textContent = t.serial_number || "-";
  document.getElementById("modalDesc").textContent = t.description || "-";
  document.getElementById("modalStatus").textContent = t.status;
  document.getElementById("modalDate").textContent = new Date(t.created_at).toLocaleString('hr-HR');
  document.getElementById("modalRequestCreator").textContent = t.request_creator || "N/A";
  document.getElementById("modalCanceledAt").textContent = t.canceled_at ? new Date(t.canceled_at).toLocaleString('hr-HR') : "-";
  document.getElementById("modalCancelReason").textContent = t.cancel_reason || "-";
  document.getElementById("ticket_id").value = t.id;

  if (t.status === 'Otkazan') {
    document.getElementById('cancelDateRow').style.display = 'block';
    document.getElementById('cancelReasonRow').style.display = 'block';
  } else {
    document.getElementById('cancelDateRow').style.display = 'none';
    document.getElementById('cancelReasonRow').style.display = 'none';
  }

  loadAttachments(t.id);

  document.getElementById("addAttachmentSection").style.display = 'block';

  const btnCancel = document.getElementById("cancelTicketBtn");
  const addAttachmentSection = document.getElementById("addAttachmentSection");
  const commentsContainer = document.getElementById('comments-section-container');
  const isLocked = t.is_locked == 1;

  commentsContainer.style.display = 'block';
  renderCommentUI(commentsContainer, t.id, false, isLocked); // false for isAdmin

  if (isLocked) {
      btnCancel.style.display = 'none';
      addAttachmentSection.style.display = 'none';
  } else {
      addAttachmentSection.style.display = 'block';
      if (t.status === 'Otkazan' || t.status === 'Zatvoren' || t.status === 'Riješen') {
          btnCancel.style.display = 'none';
          addAttachmentSection.style.display = 'none';
      } else {
          btnCancel.style.display = 'inline-block';
          addAttachmentSection.style.display = 'block';
      }
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
        imgSrc = `../${device.image_path}?v=${new Date().getTime()}`;
    } else {
        imgSrc = "../img/serial_location.jpg?v=" + new Date().getTime();
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
      document.getElementById('file-name-span').textContent = 'Nije izabrana datoteka';

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
    fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Nije odabrana datoteka';
  });

  const newTicketAttachment = document.getElementById('attachment');
    newTicketAttachment.addEventListener('change', function() {
        const fileNameSpan = document.getElementById('file-name-span-new-ticket');
        fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Nije izabrana datoteka';
    });
});
