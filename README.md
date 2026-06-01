# My Office

A self-hosted freelancer management application built with Laravel, Inertia.js, and Vue 3. Manage clients, prospects, quotes, invoices, and document templates from a single dashboard.

---

## ✨ Features

### 📊 Dashboard
- Configurable KPI tiles (clients, prospects, pending quotes, unpaid invoices, revenue, etc.)
- Quick access to recent invoices and quotes
- Favorite clients and prospects
- Dark/light mode toggle, persisted per user

### 👥 CRM
- **Clients** — contact details, notes, linked invoices and quotes
- **Prospects** — pipeline tracking, conversion to client
- **Notes** — freeform notes attached to any entity

### 🧾 Invoicing
- Create, edit, and send invoices with line items
- Quote-to-invoice conversion
- PDF generation and export
- Invoice numbering rules (prefix, year, sequence)
- Status tracking (draft, sent, paid, overdue)

### 📝 Quotes
- Structured quotes with line items and totals
- Configurable quote number settings
- Accept / reject / convert to invoice workflow

### 📄 Document Builder
- Custom document templates with rich text editing (TipTap)
- Reusable templates for proposals, contracts, etc.

### ⚙️ Settings
- Company profile (name, address, logo, VAT, IBAN)
- Appearance preferences (theme mode, colors, radius)
- Notification preferences

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3 / Laravel 11 |
| Frontend | Vue 3 + Inertia.js |
| Build | Vite 7 + TypeScript |
| Styling | Tailwind CSS v3 + daisyUI |
| Database | SQLite (default) / MySQL |
| Rich text | TipTap |
| State | Pinia |
| PDF | Laravel DomPDF |
| E2E tests | Laravel Dusk |

---

## 🚀 Getting Started

### Requirements
- PHP 8.3+
- Composer
- Node.js 20+
- SQLite or MySQL

### Install

```bash
git clone https://github.com/tourneur-maxime/My_Office.git
cd My_Office

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Run

```bash
npm run dev
# Opens PHP dev server on :8080 + Vite HMR in parallel
```

### Build for production

```bash
npm run build
php artisan serve
```

---

## 📁 Project Structure

```
app/
  Http/Controllers/   # InvoiceController, QuoteController, ProspectController...
  Models/             # Invoice, Quote, Prospect, Client, CompanyProfile...
  Services/           # PDF generation, import, notifications
resources/
  js/
    Pages/            # Inertia.js pages (Dashboard, Invoices/, Quotes/, Clients/...)
    Components/       # Shared Vue components
    Layouts/          # AuthenticatedLayout, GuestLayout
    Stores/           # Pinia stores (theme, toasts)
  views/
    app.blade.php     # Inertia root template
    invoices/         # Invoice PDF Blade views
database/
  migrations/
  seeders/
.codex/               # Multi-agent dev config (planner / executor / critic)
```

---

## 🤖 .codex — Agentic Dev Setup

The `.codex/` folder contains a multi-agent configuration for AI-assisted development:

- **Planner** — breaks down features into strict execution plans
- **Executor** — implements plans deterministically
- **Critic** — validates results before finalizing

Uses `gpt-5-codex` / `gpt-5` models with sequential orchestration and automatic retry on failure.

---

## License

MIT
