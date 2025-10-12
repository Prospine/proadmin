# 🏥 ProSpine Clinic Management Dashboard

ProSpine Admin is a robust, multi-role web application designed to streamline and manage all operational aspects of a modern physiotherapy and diagnostic clinic. It provides secure, role-based access for receptionists, doctors, and administrators, enabling efficient management of the patient lifecycle, billing, scheduling, diagnostics, and internal communications.

---

## ✨ Key Features

- **Role-Based Access Control**: Modular, secure access for Reception, Doctor, Admin, and Superadmin roles, ensuring each user only sees and manages what’s relevant to their responsibilities.
- **Patient Management**: Track patients from initial inquiry and registration through treatment, diagnostics, billing, and discharge, with a unified view of their journey.
- **Unique ID System**: Human-readable, date-prefixed unique IDs (e.g., `23102601`) for both patients and tests, simplifying tracking and referencing.
- **Appointment & Scheduling**: Dynamic weekly schedule viewer, interactive appointment details, and a real-time slot booking system that prevents double-booking.
- **Billing & Payments**: Comprehensive billing history, inline payment updates, and tracking of total paid, advance, and due amounts for each patient.
- **Test & Diagnostics Module**: Manage diagnostic test records, track statuses (`pending`, `completed`), and handle associated payments.
- **Dynamic Reporting Suite**: Generate and filter reports for registrations, patients, tests, and inquiries by date ranges and other criteria.
- **Real-Time Internal Chat**: Branch-specific chat system for seamless communication between staff members.
- **Audit Logging**: Securely logs all critical actions (create, update, delete) with user details and data snapshots for accountability and traceability.
- **Patient Photo Capture**: Capture and upload patient photos directly from the interface for better identification and record-keeping.
- **System Notifications**: In-app banners and overlays for communicating system maintenance and updates.
- **Theme Support**: Persistent dark/light mode toggle for user comfort.

---

## 🛠️ Technology Stack

- **Backend**: PHP (procedural, modular structure)
- **Frontend**: Vanilla JavaScript (ES6+), HTML5, CSS3
- **Database**: MySQL / MariaDB (using PDO for secure database operations)
- **APIs**: RESTful-style endpoints for AJAX-driven UI updates (fetching data, updating statuses, etc.)
- **Server**: Apache (typically via XAMPP/LAMP stack)

---

## 📂 Project Architecture

The project is organized for scalability and maintainability, with a clear separation of concerns and role-based modularity.

```
admin/
 ├── superadmin/
 │    ├── api/       → PHP backend APIs (AJAX endpoints)
 │    ├── css/       → Role-specific styles
 │    ├── js/        → Role-specific scripts
 │    ├── views/     → HTML + PHP templates (UI pages)
 │    └── index.php  → Super Admin entry point
 │
 ├── admin/          → Same structure as superadmin
 ├── doctor/         → Same structure as superadmin
 ├── jrdoctor/       → Same structure as superadmin
 ├── reception/      → Same structure as superadmin
 │
 ├── common/
 │    ├── db.php     → Database connection (PDO)
 │    ├── auth.php   → Authentication & session checks
 │    ├── helpers.php→ Common utility functions
 │    └── config.php → Config variables (constants, keys, etc.)
 │
 └── assets/
            ├── css/       → Shared global styles
            ├── js/        → Shared global scripts
            └── images/    → Logos, icons, images
```

Other top-level files include landing pages, informational pages, and shared resources (e.g., `index.html`, `aboutus.html`, `style.css`, etc.).

---

## 🚀 Getting Started

### Prerequisites

- **Web Server**: Apache (XAMPP, LAMP, or similar)
- **PHP**: 7.4 or higher recommended
- **MySQL/MariaDB**: For database operations

### Installation

1. **Clone the repository** to your web server’s root directory:
      ```sh
      git clone https://github.com/Prospine/proadmin.git /opt/lampp/htdocs/proadmin
      ```
2. **Configure the database**:
      - Import the provided SQL schema (if available) into your MySQL/MariaDB server.
      - Update `admin/common/config.php` with your database credentials and other configuration constants.

3. **Set up file permissions**:
      - Ensure the `uploads/` directory and its subfolders are writable by the web server for file uploads (patient photos, logos, etc.).

4. **Access the application**:
      - Open your browser and navigate to `http://localhost/proadmin/` (or your server’s domain).

---

## 🧑‍💻 Usage

- **Reception**: Handles patient inquiries, registrations, appointment bookings, and attendance.
- **Doctor/Jr. Doctor**: Manages patient treatments, test requests, and updates patient status.
- **Admin/Superadmin**: Oversees all operations, manages users, generates reports, and audits logs.
- **Internal Chat**: Accessible to all logged-in users for branch-specific communication.

---

## 🛡️ Security

- **Authentication**: Session-based login with role checks.
- **Authorization**: Role-based access to modules and APIs.
- **Audit Logging**: All critical actions are logged for traceability.
- **Input Validation**: All user inputs are validated and sanitized to prevent SQL injection and XSS.

---

## 📈 Extensibility

- **Modular Structure**: Easily add new roles, modules, or features by following the existing directory and code organization.
- **API-Driven**: Frontend and backend communicate via AJAX and RESTful APIs, making it easy to integrate with other systems or mobile apps.

---

## 🤝 Contributing

Contributions are welcome! Please fork the repository and submit a pull request. For major changes, open an issue first to discuss what you would like to change.

---


## 📄 License & EULA

This project is proprietary and intended for use by ProSpine Clinic and its authorized partners. Use of this software is governed by the End User License Agreement (EULA) included in this repository. See [EULA.md](./EULA.md) for full terms and conditions. For licensing inquiries, please contact the project maintainers.

---

## 📬 Contact

For support, feature requests, or business inquiries, please contact the ProSpine Clinic IT team through prospine.in/contact.html .

---