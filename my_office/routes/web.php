<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::patch('/dashboard/kpi-preferences', [\App\Http\Controllers\DashboardController::class, 'updateKpiPreferences'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.updateKpiPreferences');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Logo management routes
    Route::post('/logo', [\App\Http\Controllers\LogoController::class, 'store'])->name('logo.store');
    Route::delete('/logo', [\App\Http\Controllers\LogoController::class, 'destroy'])->name('logo.destroy');

    // Settings routes
    Route::get('/settings/company', [\App\Http\Controllers\Settings\CompanyProfileController::class, 'edit'])->name('settings.company');
    Route::patch('/settings/company', [\App\Http\Controllers\Settings\CompanyProfileController::class, 'update'])->name('settings.company.update');

    // Branding routes removed - features migrated to Company Profile and DocumentBuilder
    // Route::get('/settings/branding', [\App\Http\Controllers\SettingsController::class, 'branding'])->name('settings.branding');
    // Route::patch('/settings/branding', [\App\Http\Controllers\SettingsController::class, 'updateBranding'])->name('settings.branding.update');
    // Route::delete('/settings/branding', [\App\Http\Controllers\SettingsController::class, 'resetBranding'])->name('settings.branding.reset');
    
    // Templates management
    Route::get('/settings/templates', [\App\Http\Controllers\TemplateController::class, 'index'])->name('settings.templates.index');
    Route::post('/settings/branding/save-template', [\App\Http\Controllers\TemplateController::class, 'store'])->name('settings.branding.saveTemplate');
    Route::delete('/settings/templates/{template}', [\App\Http\Controllers\TemplateController::class, 'destroy'])->name('settings.templates.destroy');
    Route::patch('/settings/templates/{template}/set-default', [\App\Http\Controllers\TemplateController::class, 'setDefault'])->name('settings.templates.setDefault');

    // Document Templates (for Document Builder)
    Route::get('/document-templates', [\App\Http\Controllers\DocumentTemplateController::class, 'index'])->name('document-templates.index');
    Route::post('/document-templates', [\App\Http\Controllers\DocumentTemplateController::class, 'store'])->name('document-templates.store');
    Route::get('/document-templates/{template}', [\App\Http\Controllers\DocumentTemplateController::class, 'show'])->name('document-templates.show');
    Route::put('/document-templates/{template}', [\App\Http\Controllers\DocumentTemplateController::class, 'update'])->name('document-templates.update');
    Route::delete('/document-templates/{template}', [\App\Http\Controllers\DocumentTemplateController::class, 'destroy'])->name('document-templates.destroy');

    Route::get('/settings/quotes', [\App\Http\Controllers\QuoteSettingsController::class, 'edit'])->name('settings.quotes');
    Route::patch('/settings/quotes', [\App\Http\Controllers\QuoteSettingsController::class, 'update'])->name('settings.quotes.update');
    Route::delete('/settings/quotes/reset', [\App\Http\Controllers\QuoteSettingsController::class, 'destroy'])->name('settings.quotes.reset');

    Route::get('/settings/invoices/numbering', [\App\Http\Controllers\Settings\InvoiceSettingsController::class, 'edit'])->name('settings.invoices.numbering');
    Route::patch('/settings/invoices/numbering', [\App\Http\Controllers\Settings\InvoiceSettingsController::class, 'update'])->name('settings.invoices.numbering.update');
    Route::delete('/settings/invoices/numbering/reset', [\App\Http\Controllers\Settings\InvoiceSettingsController::class, 'destroy'])->name('settings.invoices.numbering.reset');

    // Data Management
    Route::get('/settings/data', [\App\Http\Controllers\Settings\DataManagementController::class, 'index'])->name('settings.data.index');
    Route::post('/settings/data/export-all', [\App\Http\Controllers\Settings\DataManagementController::class, 'exportAll'])->name('settings.data.export-all');
    Route::get('/settings/data/download-export/{fileName}', [\App\Http\Controllers\Settings\DataManagementController::class, 'downloadExport'])->name('settings.data.download-export');

    // Appearance Settings
    Route::get('/settings/appearance', [\App\Http\Controllers\Settings\AppearanceController::class, 'edit'])->name('settings.appearance');
    Route::patch('/settings/appearance', [\App\Http\Controllers\Settings\AppearanceController::class, 'update'])->name('settings.appearance.update');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-as-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-as-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/settings/data/export-clients', [\App\Http\Controllers\Settings\DataManagementController::class, 'exportClients'])->name('settings.data.export-clients');
    Route::get('/settings/data/export-clients-json', [\App\Http\Controllers\Settings\DataManagementController::class, 'exportClientsJson'])->name('settings.data.export-clients-json');
    Route::get('/settings/data/export-invoices', [\App\Http\Controllers\Settings\DataManagementController::class, 'exportInvoices'])->name('settings.data.export-invoices');
    Route::get('/settings/data/export-invoices-json', [\App\Http\Controllers\Settings\DataManagementController::class, 'exportInvoicesJson'])->name('settings.data.export-invoices-json');
    Route::get('/settings/data/export-fec', [\App\Http\Controllers\Settings\DataManagementController::class, 'exportFec'])->name('settings.data.export-fec');
    Route::post('/settings/data/backup', [\App\Http\Controllers\Settings\DataManagementController::class, 'runBackup'])->name('settings.data.backup');

    // Import routes
    Route::get('/settings/data/import-clients', [\App\Http\Controllers\Settings\ImportController::class, 'showImportClients'])->name('settings.data.import-clients.show');
    Route::post('/settings/data/import-clients', [\App\Http\Controllers\Settings\ImportController::class, 'importClients'])->name('settings.data.import-clients');
    Route::get('/settings/data/import-clients/template', [\App\Http\Controllers\Settings\ImportController::class, 'downloadTemplate'])->name('settings.data.import-clients.template');

    // New route for clients/prospects list
    Route::get('/clients', [\App\Http\Controllers\ProspectController::class, 'index'])->name('clients.index');

    // Search endpoint for clients/prospects
    Route::get('/clients/search', [\App\Http\Controllers\ProspectController::class, 'search'])->name('clients.search');

    // Favorites endpoints
    Route::get('/clients/favorites', [\App\Http\Controllers\ProspectController::class, 'favorites'])->name('clients.favorites');
    Route::post('/clients/{prospect}/toggle-favorite', [\App\Http\Controllers\ProspectController::class, 'toggleFavorite'])->name('clients.toggleFavorite');

    // New route for displaying create prospect form
    Route::get('/clients/create', [\App\Http\Controllers\ProspectController::class, 'create'])->name('clients.create');

    // New route for creating prospects
    Route::post('/clients', [\App\Http\Controllers\ProspectController::class, 'store'])->name('clients.store');

    // New route for displaying a specific client/prospect
    Route::get('/clients/{prospect}', [\App\Http\Controllers\ProspectController::class, 'show'])->name('clients.show');

    Route::get('/clients/{prospect}/edit', [\App\Http\Controllers\ProspectController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{prospect}', [\App\Http\Controllers\ProspectController::class, 'update'])->name('clients.update');
    Route::put('/clients/{prospect}/convert-to-client', [\App\Http\Controllers\ProspectController::class, 'convertToClient'])->name('clients.convertToClient');
    Route::delete('/clients/{prospect}', [\App\Http\Controllers\ProspectController::class, 'destroy'])->name('clients.destroy');

    // Notes management routes
    Route::post('/clients/{prospect}/notes', [\App\Http\Controllers\NoteController::class, 'store'])->name('clients.notes.store');
    Route::put('/clients/{prospect}/notes/{note}', [\App\Http\Controllers\NoteController::class, 'update'])->name('clients.notes.update');
    Route::delete('/clients/{prospect}/notes/{note}', [\App\Http\Controllers\NoteController::class, 'destroy'])->name('clients.notes.destroy');

    // Quote management routes
    Route::get('/quotes', [\App\Http\Controllers\QuoteController::class, 'index'])->name('quotes.index'); // New route for quotes list
    Route::get('/quotes/create', [\App\Http\Controllers\QuoteController::class, 'create'])->name('quotes.create');
    Route::post('/clients/{prospect}/quotes', [\App\Http\Controllers\QuoteController::class, 'store'])->name('quotes.store');
    Route::post('/quotes/preview', [\App\Http\Controllers\QuoteController::class, 'preview'])->name('quotes.preview');

    // Quote status update route
    Route::patch('/quotes/{quote}/status', [\App\Http\Controllers\QuoteController::class, 'updateStatus'])->name('quotes.updateStatus');

    // Quote modification and duplication routes
    Route::get('/quotes/{quote}/edit', [\App\Http\Controllers\QuoteController::class, 'edit'])->name('quotes.edit');
    Route::put('/quotes/{quote}', [\App\Http\Controllers\QuoteController::class, 'update'])->name('quotes.update');
    Route::post('/quotes/{quote}/duplicate', [\App\Http\Controllers\QuoteController::class, 'duplicate'])->name('quotes.duplicate');
    Route::delete('/quotes/{quote}', [\App\Http\Controllers\QuoteController::class, 'destroy'])->name('quotes.destroy');

    // Quote view, PDF generation, and download
    Route::get('/quotes/{quote}', [\App\Http\Controllers\QuoteController::class, 'show'])->name('quotes.show');
    Route::post('/quotes/{quote}/generate-pdf', [\App\Http\Controllers\QuoteController::class, 'generatePdf'])->name('quotes.generatePdf');
    Route::get('/quotes/{quote}/download', [\App\Http\Controllers\QuoteController::class, 'download'])->name('quotes.download');

    // Quote to invoice conversion
    Route::post('/quotes/{quote}/convert-to-invoice', [\App\Http\Controllers\QuoteController::class, 'convertToInvoice'])->name('quotes.convertToInvoice');

    // Invoices
    Route::get('/invoices', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create/{client?}', [\App\Http\Controllers\InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices/{client?}', [\App\Http\Controllers\InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/check-gaps', [\App\Http\Controllers\InvoiceController::class, 'checkGaps'])->name('invoices.check-gaps');
    Route::get('/invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [\App\Http\Controllers\InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\InvoiceController::class, 'download'])->name('invoices.download');
    Route::patch('/invoices/{invoice}/mark-as-paid', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid'])->name('invoices.markAsPaid');
    Route::get('/invoices/{invoice}/generate', [\App\Http\Controllers\InvoiceController::class, 'generate'])->name('invoices.generate');
    Route::post('/invoices/{invoice}/generate', [\App\Http\Controllers\InvoiceController::class, 'generate']);
    Route::post('/invoices/{invoice}/generate-pdf', [\App\Http\Controllers\InvoiceController::class, 'generatePdf'])->name('invoices.generatePdf');
    Route::post('/invoices/{invoice}/credit-note', [\App\Http\Controllers\InvoiceController::class, 'createCreditNote'])->name('invoices.creditNote');
    Route::delete('/invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'destroy'])->name('invoices.destroy');
});

require __DIR__.'/auth.php';
