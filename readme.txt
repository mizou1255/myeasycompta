=== myEasyCompta ===
Tags: accounting, quotes, invoices, expenses, freelancers
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

myEasyCompta is a comprehensive and modern accounting solution for WordPress, specifically designed for freelancers and small businesses.

== Description ==

**myEasyCompta v2.0.0 — The biggest update ever.**

myEasyCompta is a comprehensive and modern accounting solution for WordPress, specifically designed for freelancers and small businesses. Built with Vue 3 and Tailwind CSS, it offers a sleek, responsive, dark-mode-ready interface directly inside the WordPress admin — no external SaaS subscription required.

---

### 🆕 What's new in version 2.0.0

**Complete UI overhaul**
All screens have been redesigned from the ground up — collapsible animated sidebar, dark mode, improved typography and spacing, new KPI cards on the dashboard.

**Partial payments**
Record multiple payments against a single invoice and track the paid amount vs remaining balance in real time. A new "partial" invoice status is automatically set when a payment is recorded but the invoice is not yet fully settled.

**Invoice history & audit log**
Every status change on an invoice is now logged — who changed it, when, and from which status to which. Full traceability for accounting compliance.

**Automated invoice reminders**
A daily WP-Cron job automatically sends configurable reminder emails to clients for overdue and unpaid invoices. Fully customizable delay and email template.

**Dashboard analytics**
- Top 5 clients by revenue (paid amount)
- Overdue invoices widget — count and total amount at a glance
- Cash flow bar chart — monthly breakdown of income vs expenses vs net

**Global search**
Search across clients, invoices, quotes, and expenses from anywhere in the app. Keyboard shortcut ⌘K (Ctrl+K on Windows).

**Internal notes on invoices and quotes**
Add private memo notes directly on an invoice or quote. Notes are visible only to admins — they never appear on the PDF.

**Client archiving**
Archive inactive clients to keep your list clean without permanently deleting them. Archived clients retain all their historical invoices and quotes. A toggle lets you show/hide archived clients at any time.

**Invoice and quote templates**
Save any invoice or quote as a reusable template. Create new documents in one click from a saved template — same items, same structure, fresh numbering.

**Optional line items on quotes**
Mark individual line items on a quote as optional. Clients can see which items are mandatory and which are extras — helpful for tiered or à-la-carte proposals.

**Invoice duplication**
Clone any existing invoice in one click. The duplicate gets a new number, "draft" status, and inherits the same client and line items.

**Overdue badge on invoice list**
Invoices past their due date and still unpaid display a red "Overdue" badge directly in the list — no need to open each one.

**Client financial summary**
The client detail panel now shows three key figures at a glance: total invoiced, total paid, and outstanding balance.

**Payment methods on recorded payments**
Link a payment method (wire transfer, cheque, card, cash, etc.) to each individual payment entry. Displayed in the invoice detail and useful for bookkeeping.

**Electronic invoicing (FacturX / CII / UBL)**
Generate legally compliant e-invoices in FacturX, Cross Industry Invoice (CII), and UBL formats. PDP-ready workflow with support for Chorus Pro, Pennylane, JeFacture, and generic PDP providers. Track fiscal transmission status per invoice.

**Email notifications system**
Configurable per-event email alerts: invoice paid, partial payment received, new client added, quote accepted or rejected, backup created or deleted, planning event. Enable/disable each event independently.

**In-app notifications**
A notification center in the admin shows real-time alerts for key accounting events — no more missing an important status change.

**Setup wizard**
A modern multi-step onboarding wizard guides new users through company setup, VAT configuration, numbering preferences, and currency formatting with a live preview.

**Date range filters & quick period shortcuts**
All list pages (invoices, quotes, payments, expenses) now include a date range picker with one-click shortcuts: this week, this month, this quarter, this year.

---

🚀 **myEasyCompta Addons** — take it even further

Extend myEasyCompta with premium addons available at [myeasycompta.com/addons](https://myeasycompta.com/addons):

- 📅 **Planning** — Schedule tasks, appointments, and deadlines
- 📧 **Email** — Custom email templates per event
- 📊 **Statistics** — Advanced analytics and export
- 📦 **Export** — CSV and Excel export for all data
- 💳 **Stripe Payment** — QR code and online payment links on invoices
- ✍️ **Electronic Signature** — Sign quotes and invoices online
- 🔄 **Recurring Invoices** — Automated recurring invoice generation
- 👥 **Client Portal** — Dedicated frontend dashboard for clients
- 🏢 **SIRET Lookup** — Auto-fill company info from the SIRET/SIREN registry
- 🚚 **Delivery Notes** — Generate delivery notes linked to invoices
- 📝 **Contracts** — Full contract management with e-signature workflow
- ⏱️ **Time Tracking** — Track time per client and convert to invoice
- 🌐 **Online Quotes** — Shareable quote links your clients can accept online
- 💬 **SMS & WhatsApp** — Send invoice and payment notifications by message
- ⏩ **Advance Payments** — Manage deposits and advances on quotes
- 🔁 **SureCart Integration** — Sync SureCart orders as invoices
- 🛒 **WooCommerce Integration** — Auto-generate invoices for WooCommerce orders
- 💾 **Backup** — Automated backups with cloud storage (FTP, S3, Dropbox) and cron scheduling

== Key Features ==

* **Quotes Management**: Create and manage quotes for your clients. Track status and convert accepted quotes to invoices with one click.
* **Invoices Management**: Generate professional invoices with customizable templates, partial payment tracking, duplication, and overdue detection.
* **Expenses Tracking**: Record and categorize business expenses. Attach receipts and generate detailed reports.
* **Partial Payments**: Record multiple payments per invoice with a running balance and automatic status progression.
* **Templates**: Save invoice and quote templates for fast, consistent document creation.
* **E-Invoicing**: FacturX / CII / UBL generation for regulatory compliance and PDP submission.
* **Dashboard Analytics**: Revenue overview, top clients, overdue amounts, and cash flow chart.
* **Global Search**: Instant full-text search across all records with keyboard shortcut.
* **Dark Mode**: Full dark mode support on all screens, toggled per user.
* **Secure & Reliable**: All REST endpoints protected with nonce verification, capability checks, and input sanitization. Monetary data encrypted at rest.
* **Modern Interface**: Built with Vue 3 and Tailwind CSS — fast, responsive, works on any screen size.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/my-easy-compta`, or install through the WordPress plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. The setup wizard will launch automatically on first activation.
4. Follow the wizard to configure your company details, currency, VAT, and invoice numbering.
5. Start creating quotes, invoices, and tracking your finances.

**Upgrading from v1.x:**
Database migrations run automatically on the first admin page load after upgrading. All existing data (clients, invoices, quotes, payments, expenses) is preserved. No manual action required.

== Frequently Asked Questions ==

= What are the minimum requirements? =

- WordPress 6.2 or higher
- PHP 8.0 or higher
- MySQL 5.7 or higher (or MariaDB 10.3+)

= Does myEasyCompta replace my accounting software? =

myEasyCompta is designed for invoicing, quoting, and expense tracking directly inside WordPress. It is not a full double-entry bookkeeping system. For complex accounting needs, use myEasyCompta alongside your accounting software — the Export addon provides CSV/Excel files compatible with most tools.

= Is my data secure? =

All monetary fields are encrypted at rest using AES encryption. All REST API endpoints require WordPress authentication and nonce verification. No data is sent to external servers (except for addon licence verification).

= Is myEasyCompta compatible with my theme? =

myEasyCompta runs entirely in the WordPress admin area and does not affect your public theme. The client portal addon adds a frontend page, which is theme-compatible.

= How do I upgrade from v1.x to v2.0.0? =

Simply update the plugin through WordPress. All database migrations run automatically. Your data is untouched. We recommend taking a backup first (the Backup addon can help with this).

= Where can I get support? =

Visit [myeasycompta.com](https://myeasycompta.com) or open a ticket from the Support tab inside the plugin settings.

= How can I contribute? =

Submit a pull request on our [GitHub repository](https://github.com/mizou1255/my-easy-compta).

== Screenshots ==

1. **Dashboard** — Revenue KPIs, top clients, cash flow chart, overdue widget.
2. **Invoice List** — Date filters, quick shortcuts, overdue badges, status chips.
3. **Invoice Detail** — Partial payments, audit log, internal notes, action buttons.
4. **Client Card** — Financial summary (invoiced / paid / outstanding) and invoices shortcut.
5. **Settings** — Notification center, e-invoicing configuration, payment methods.
6. **Setup Wizard** — Modern multi-step onboarding with live preview.
7. **Global Search** — Instant cross-module search with keyboard shortcut.

== Changelog ==

= 2.0.1 =
* Fix: Client not saved on invoice creation — client_id was undefined when ModelSelect emitted a string instead of an object.

= 2.0.0 =
* New: Complete UI redesign — Vue 3 + Tailwind v3, dark mode, collapsible sidebar with animated tooltips.
* New: Partial payments — record multiple payments per invoice, track paid/remaining balance, new "partial" status.
* New: Invoice history — full audit log of every status change (who, when, from/to).
* New: Invoice reminders — daily WP-Cron sends configurable reminder emails for overdue/unpaid invoices.
* New: Dashboard analytics — Top 5 clients, overdue invoices widget, monthly cash flow bar chart.
* New: Global search across clients, invoices, quotes, and expenses (⌘K shortcut).
* New: Internal notes on invoices and quotes — private memos, never printed on PDFs.
* New: Client archiving — archive inactive clients without deleting historical data.
* New: Invoice and quote templates — save reusable document templates.
* New: Optional line items on quotes — mark individual items as optional or mandatory.
* New: Invoice duplication — clone any invoice in one click with a new number.
* New: Overdue badge on invoice list — red badge for unpaid invoices past their due date.
* New: Client financial summary — invoiced / paid / outstanding directly in the client card.
* New: Payment methods on recorded payments — link wire transfer, cheque, card, cash, etc. to each payment.
* New: Electronic invoicing (FacturX / CII / UBL) — PDP-ready with Chorus Pro, Pennylane, JeFacture support.
* New: Email notifications — per-event alerts (invoice paid, partial, new client, quote accepted, backup, planning).
* New: In-app notification center — real-time alerts for key accounting events.
* New: Setup wizard — modern multi-step onboarding with live number format preview.
* New: Date range filters and quick period shortcuts on all list pages (week / month / quarter / year).
* Enhance: Invoice and quote detail pages fully redesigned.
* Enhance: PDF generator — improved layout, watermark, FacturX XML embedding, signature addon support.
* Enhance: Client card shows financial summary (invoiced / paid / outstanding balance).
* Fix: Security hardening — nonce verification, sanitization, capability checks across all REST endpoints.
* Fix: Permalink check now shows an informative overlay instead of a blank page.

= 1.5.1 =
* Add: Payment methods table — store and manage accepted payment methods.
* Add: Payment method selector when recording a payment.
* Fix: Currency display inconsistencies in multi-currency setups.

= 1.5.0 =
* New: Electronic invoicing module (e-invoicing) — FacturX, CII, UBL format generation.
* New: Fiscal transmission tracking — status logs per invoice for PDP providers.
* New: Mock PDP provider for testing e-invoicing workflows.
* Add: Fiscal status badge on invoice detail page.
* Add: E-invoicing notices and configuration in settings.

= 1.4.6 =
* Fix: Disable invoice status change in invoice modification mode.

= 1.4.5 =
* Fix: Problem modifying and deleting disbursements for validated invoices.

= 1.4.4 =
* Enhance: Added a modal to easily select and apply articles for new items.
* Enhance: Improved the user interface for the item addition process.

= 1.4.3 =
* Enhance: Updated the design of error notifications with a modern, responsive look.
* Enhance: Improved success notification styling after database migration.
* Enhance: Redesigned the permalink structure warning notification.
* Enhance: Added a background blur effect to highlight notifications.

= 1.4.2 =
* Fix: Resolved issues with item field editing.
* Add: Ability to edit a disbursement directly in the invoice details.
* Add: Ability to delete a disbursement with confirmation.
* Fix: Corrected deletion notifications to display properly and consistently.

= 1.4.1 =
* Fix: Banner notification was displayed on unintended admin pages.

= 1.4.0 =
* Add: Loader during auto-completion in quotes and invoices.
* Add: Expense (disbursement) system in invoice details, including integration in the PDF.

= 1.3.1 =
* Fix: Quantity display issue in PDF when the quantity is a decimal number.

= 1.3.0 =
* Fix: License activation issue resolved.
* Add: Search filters for clients, quotes, invoices, payments, and expenses.
* Add: Ability to add a new client directly from the Add Quote or Invoice page.

= 1.2.4 =
* Fix: Database migration issue corrected.
* Fix: File upload system for expenses updated to enhance security.

= 1.2.3 =
* Fix: Quantity field now accepts decimal values.
* Add: Option to refresh the license.

= 1.2.2 =
* Fix: Issue with installed version verification and database migration.
* Add: Ability to integrate external items in the auto-completion system.

= 1.2.1 =
* Add: Option to select a payment method when marking an invoice as paid.

= 1.2.0 =
* UPT: Updated the mPDF library.
* Add: Ability to add a watermark to invoices.
* Fix: PDF display issue when VAT is enabled.

= 1.1.0 =
* Add: Database migration function for new versions.
* Add: VAT field for clients.
* Add: New options in invoice and quote settings (payment conditions, IBAN, etc.).
* Fix: PDF display issue when a discount is applied.

= 1.0.1 =
* Fix minor bugs.

= 1.0.0 =
* Initial release of myEasyCompta.

== License ==

myEasyCompta is licensed under the GPLv2 or later. For more information, see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

== Contributing ==

If you would like to contribute to the development of myEasyCompta, please visit our [GitHub repository](https://github.com/mizou1255) and submit a pull request.

== Credits ==

* **Author:** Moez BETTOUMI
* **Website:** [https://moezbettoumi.fr](https://moezbettoumi.fr)
* **Donate:** [https://buymeacoffee.com/mizou1253](https://buymeacoffee.com/mizou1253)
