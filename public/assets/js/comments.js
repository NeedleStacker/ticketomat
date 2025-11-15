// comments.js

/**
 * Renders the comment section UI into a container.
 * @param {HTMLElement} container - The element to render the UI into.
 * @param {number} ticketId - The ID of the current ticket.
 * @param {boolean} isAdmin - Flag to determine if admin controls should be shown.
 * @param {boolean} isLocked - Flag to determine if the ticket is locked.
 */
function renderCommentUI(container, ticketId, isAdmin, isLocked) {
    let formHtml = `
        <form id="comment-form" class="comment-form">
            <div class="form-group">
                <textarea id="comment_text" class="form-control" placeholder="Napišite poruku..." required></textarea>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Pošalji poruku</button>
            </div>
        </form>
    `;

    if (isLocked) {
        formHtml = `<div class="alert alert-secondary text-center small"><i class="bi bi-lock-fill"></i> Ticket je zaključen. Nije moguće dodavati nove poruke.</div>`;
    }

    container.innerHTML = `
        <div class="comments-container">
            <h6>Poruke</h6>
            <div id="comments-list-container">
                <div class="comments-loader">Učitavanje...</div>
                <ul id="comments-list"></ul>
            </div>
            ${formHtml}
        </div>
    `;

    loadComments(ticketId, isAdmin);

    if (!isLocked) {
        const form = container.querySelector('#comment-form');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            addComment(ticketId, isAdmin);
        });
    }
}


/**
 * Fetches and displays comments for a given ticket.
 * @param {number} ticketId - The ID of the ticket.
 * @param {boolean} isAdmin - Flag to show/hide admin controls.
 */
async function loadComments(ticketId, isAdmin) {
    const list = document.getElementById('comments-list');
    const loader = document.querySelector('.comments-loader');
    if (!list || !loader) return;

    list.innerHTML = '';
    loader.style.display = 'block';

    try {
        const res = await fetch(`${API}getComments.php?ticket_id=${ticketId}`);
        const comments = await res.json();

        loader.style.display = 'none';

        if (comments.error) {
            list.innerHTML = `<li class="text-danger">${comments.error}</li>`;
            return;
        }

        if (comments.length === 0) {
            list.innerHTML = `<li class="text-muted small">Nema poruka za ovaj ticket.</li>`;
        } else {
            comments.forEach(comment => {
                const li = document.createElement('li');
                li.className = 'comment-item';
                li.dataset.commentId = comment.id;

                const initials = (comment.author_name || '?').charAt(0);

                li.innerHTML = `
                    <div class="comment-avatar">${initials}</div>
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-author">${escapeHTML(comment.author_name)}</span>
                            <span class="comment-date">${new Date(comment.created_at).toLocaleString('hr-HR')}</span>
                            ${isAdmin ? `<div class="comment-actions"><button class="btn-delete-comment" onclick="deleteComment(${comment.id}, ${ticketId})">&times; Obriši</button></div>` : ''}
                        </div>
                        <div class="comment-text">${escapeHTML(comment.comment_text)}</div>
                    </div>
                `;
                list.appendChild(li);
            });
        }
    } catch (error) {
        loader.style.display = 'none';
        list.innerHTML = `<li class="text-danger">Greška pri učitavanju poruka.</li>`;
        console.error('Error loading comments:', error);
    }
}

/**
 * Submits a new comment.
 * @param {number} ticketId - The ID of the ticket to comment on.
 * @param {boolean} isAdmin - To reload comments with admin view after posting.
 */
async function addComment(ticketId, isAdmin) {
    const comment_text = document.getElementById('comment_text').value.trim();

    if (!comment_text) {
        alert("Poruka ne može biti prazna.");
        return;
    }

    const submitBtn = document.querySelector('#comment-form button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Slanje...';

    try {
        const res = await fetch(`${API}addComment.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ticket_id: ticketId, comment_text })
        });
        const result = await res.json();

        if (result.success) {
            document.getElementById('comment_text').value = ''; // Clear textarea only
            loadComments(ticketId, isAdmin);
        } else {
            alert('Greška: ' + (result.error || 'Nepoznata greška.'));
        }
    } catch (error) {
        alert('Došlo je do mrežne greške.');
        console.error('Error adding comment:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Pošalji poruku';
    }
}

/**
 * Deletes a comment.
 * @param {number} commentId - The ID of the comment to delete.
 * @param {number} ticketId - The ticket ID to reload comments for.
 */
async function deleteComment(commentId, ticketId) {
    if (!confirm('Jeste li sigurni da želite obrisati ovaj komentar?')) {
        return;
    }

    try {
        const res = await fetch(`${API}deleteComment.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comment_id: commentId })
        });
        const result = await res.json();

        if (result.success) {
            loadComments(ticketId, true); // Reload with admin view
        } else {
            alert('Greška: ' + (result.error || 'Nije moguće obrisati komentar.'));
        }
    } catch (error) {
        alert('Došlo je do mrežne greške.');
        console.error('Error deleting comment:', error);
    }
}
