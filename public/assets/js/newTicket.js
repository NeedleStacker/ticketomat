// public/assets/js/newTicket.js

async function loadDevices() {
  const res = await fetch(API + "getDevices.php");
  const devices = await res.json();
  const selects = document.querySelectorAll("#new_ticket_device");
  selects.forEach(select => {
    select.innerHTML = '<option value="">Odaberite uređaj...</option>';
    devices.forEach(d => select.innerHTML += `<option>${d.name}</option>`);
  });
}

async function loadClients() {
  const res = await fetch(API + "getKorisnici.php");
  const clients = await res.json();
  const selects = document.querySelectorAll("#new_ticket_client");
  selects.forEach(select => {
    select.innerHTML = '<option value="">Odaberite korisnika...</option>';
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

  // Hardcoding admin user for now, since this is only available to admins.
  // A better solution would be to get this from the session on the backend.
  formData.append('request_creator', `Admin`);
  formData.append('creator_contact', 'admin@example.com');


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
    if (typeof loadTickets === "function") {
        loadTickets();
    }
  } else alert("❌ " + (data.error || "Greška prilikom kreiranja ticketa."));
}

document.addEventListener("DOMContentLoaded", () => {
    if (document.getElementById('newTicketModal')) {
        loadDevices();
        loadClients();

        const newTicketModal = document.getElementById('newTicketModal');
        newTicketModal.addEventListener('hidden.bs.modal', function () {
            document.getElementById('newTicketForm').reset();
            const fileNameSpan = document.getElementById('file-name-span-new');
            if(fileNameSpan) fileNameSpan.textContent = 'Nije izabrana datoteka';
        });

        const fileInput = document.getElementById('new_ticket_attachment');
        if(fileInput) {
            fileInput.addEventListener('change', function() {
                const fileNameSpan = document.getElementById('file-name-span-new');
                if(fileNameSpan) fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Nije izabrana datoteka';
            });
        }
    }
});
