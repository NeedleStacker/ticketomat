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
  attachmentList.className = "d-flex flex-wrap"; // Set container to wrap items

  if (attachments.error) {
      attachmentList.innerHTML = `<div class="text-danger small">${attachments.error}</div>`;
      attachmentSection.style.display = 'block';
      return;
  }

  attachmentSection.style.display = 'block';

  if (attachments.length > 0) {
      attachments.forEach(file => {
          const fileContainer = document.createElement('div');
          fileContainer.className = 'd-flex align-items-center me-2 mb-2';

          const link = document.createElement('a');
          link.href = `${API}getAttachment.php?id=${file.id}`;
          link.textContent = file.attachment_name;
          link.className = 'btn btn-outline-secondary btn-sm attachment-link';
          link.target = '_blank';

          const deleteBtn = document.createElement('button');
          deleteBtn.className = 'btn btn-outline-danger btn-sm ms-1';
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

    // Accessibility fix for modal focus
    const ticketModal = document.getElementById('ticketModal');
    const cancelModal = document.getElementById('adminCancelModal');

    const modals = [ticketModal, cancelModal];

    modals.forEach(modal => {
        if(modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                document.body.focus();
            });
        }
    });

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
