# Support Ticketing System

## About The Project

This is a web-based support ticketing system designed to streamline and manage user support requests. Clients can submit new tickets for issues they encounter, and administrators have a dedicated dashboard to view, manage, and resolve these tickets.

### Key Features

*   **User & Admin Roles:** Separate interfaces for clients and administrators.
*   **Ticket Management:** Create, view, update, and cancel support tickets.
*   **Secure Admin Panel:** The admin dashboard is protected and accessible only to authenticated administrators.
*   **Status Tracking:** Tickets are tracked with clear statuses (e.g., Open, In Progress, Canceled, Resolved).
*   **File Attachments:** Users can attach files (up to 5MB) to their support tickets.
*   **Dynamic Device Lists:** Administrators can manage the list of supported devices.
*   **Advanced Filtering:** The admin panel allows for filtering tickets by status, client, or cancellation status.

## Getting Started

Follow these instructions to set up a local development environment.

### Prerequisites

*   A web server with PHP support (e.g., Apache, Nginx)
*   MySQL or MariaDB database
*   PHP MySQLi extension (`php-mysql`)

### Installation

1.  **Clone the repository:**
    ```sh
    git clone <repository-url>
    ```
2.  **Database Setup:**
    *   Create a database for the project.
    *   Import the `.sql` files located in the project root to set up the necessary tables.
    *   Configure your database credentials in `api/config.php`.

3.  **Run the application:**
    *   Point your web server's document root to the `public/` directory.
    *   Alternatively, use PHP's built-in web server for quick testing:
      ```sh
      php -S localhost:8000 -t public
      ```
    *   Access the application in your browser at `http://localhost:8000`.

---

## Recent Updates (Changelog)

### 1. Enhanced Admin Page Security
- Implemented an authentication check on `admin.php` to ensure that only users with an 'admin' role can access the page. Unauthorized users are redirected to the login page.

### 2. Improved UI for Canceled Tickets
- The "Cancel Request" button is now hidden for tickets that already have a status of "canceled," preventing redundant actions.

### 3. Filtered View on Admin Dashboard
- By default, canceled tickets are no longer displayed on the main admin ticket list, providing a cleaner and more focused view of active issues.

### 4. Reason for Cancellation by Admins
- Administrators are now required to provide a reason when they manually cancel a user's ticket, improving communication and record-keeping.

### 5. Backslash Character Bug Fix
- Corrected an issue where backslashes (`/`) in the ticket description were being duplicated upon saving. Text is now handled correctly.
