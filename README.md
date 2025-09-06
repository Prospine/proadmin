# 🏥 Prospine Admin Dashboard

This project is being rebuilt from scratch with a **clean file structure** so that everyone (including new contributors) can understand where things belong.

---

## 📂 Project Structure

```
admin/
 ├── superadmin/
 │    ├── api/       → PHP files for backend APIs (AJAX endpoints)
 │    ├── css/       → Role-specific styles
 │    ├── js/        → Role-specific scripts
 │    ├── views/     → HTML + PHP templates (UI pages)
 │    └── index.php  → Entry point for Super Admin
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

---

## 🔑 Guidelines

1. **Views (`views/`)**

   * All UI pages (HTML + PHP) go here.
   * Example: `views/inquiry.php` (Reception module).

2. **API (`api/`)**

   * All AJAX endpoints or backend actions.
   * Example: `api/updateStatus.php` updates inquiry status.

3. **CSS & JS**

   * If it’s **role-specific**, put it in that role’s folder.
   * If it’s **used everywhere**, put it inside `/assets/css` or `/assets/js`.

4. **Common Folder**

   * Use only for shared utilities (database connection, authentication, helpers).
   * **Never duplicate code** between roles if it can be placed here.

5. **Naming Conventions**

   * Use lowercase with underscores: `get_inquiries.php`, `update_status.php`.
   * Keep file names short and meaningful.

---

## 🚀 Workflow

* Each student will be assigned **one or more roles** (reception, doctor, etc.).
* Work only inside that role’s folder unless a shared feature is needed.
* If adding something that affects multiple roles (like login, DB changes), discuss first and put it in `/common`.
* Always keep your code modular — avoid mixing HTML, JS, and PHP in one place unnecessarily.

---

## ✅ Example

**Reception Inquiry Page**

* `views/inquiry.php` → The actual page with table + UI.
* `css/inquiry.css` → Styles for inquiry page.
* `js/inquiry.js` → JS handling popup logic & status changes.
* `api/get_inquiries.php` → Fetch inquiries from DB.
* `api/update_status.php` → Update inquiry status in DB.

---

This structure ensures **clarity, modularity, and teamwork**.

---

Do you want me to also **add role responsibilities** (like what your students should focus on for reception vs doctor) so they know *who owns what*?
