# Changelog

All notable changes to the ProSpine Clinic Management Dashboard will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---


## [Unreleased]
- Ongoing improvements and bug fixes.

## [2.2.6] - 2025-10-11
**Feature & Improvements**

**October 11, 2025 · UID System, Logo Unification, Table Enhancements, System Update Banner, Bug Fixes**

### Added
- **Test UID System:**
	- Introduced a unique, date-prefixed `test_uid` for all test records (e.g., `23101101` for the first test on 2023-10-11).
	- UID is auto-generated on test submission, ensuring traceability and easier referencing in reports and UI.
	- Database migration: Added `test_uid` column to the `tests` table with a unique index.
- **System Update Banner & Overlay:**
	- Dashboard now features a dismissible banner and overlay for scheduled maintenance or urgent system updates.
	- Banner and overlay can be toggled via JavaScript, with customizable messages for real-time communication to users.

### Improved
- **Logo Handling Across All Views:**
	- All major admin/reception views (patients, registration, billing, attendance, reports, schedule, etc.) now display the correct branch logo dynamically.
	- Fallback placeholder is shown if no logo is set, ensuring consistent branding and a professional look.
- **Table Columns & Filtering:**
	- Registration and patient tables now display the new UID columns (`patient_uid`, `test_uid`) for easier cross-referencing.
	- Added "Inquiry Type" column and filter to registration and appointment tables, allowing staff to filter by consultation type.
	- Test and patient tables updated to use the new UID columns as primary identifiers.
- **Photo Column Sizing:**
	- Patient photo and initials columns in tables increased to 60x60px for better visibility and a more modern appearance.
- **Table Search, Filter, and Sort Logic:**
	- Fixed column index mismatches in patients, registration, and test JS, ensuring accurate filtering and sorting by doctor, treatment, status, and new columns.
- **Database & Backend:**
	- Updated backend logic to support new UID fields and ensure all APIs and reports fetch and display the correct identifiers.

### Fixed
- **Logo Path Consistency:**
	- All logo image paths now use `/admin/` as the base, resolving issues with missing or broken images in various modules and print views.
- **UI/UX Bugs:**
	- Corrected table header and cell mismatches, improved accessibility, and fixed minor layout glitches in modals and drawers.

---

> For previous versions or detailed release notes, contact the ProSpine Clinic IT team.
