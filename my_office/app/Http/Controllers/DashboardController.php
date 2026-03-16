<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'stats' => [
                'clients' => $user->prospects()->where('status', 'client')->count(),
                'prospects' => $user->prospects()->where('status', 'prospect')->count(),
                'quotes_pending' => $user->quotes()->where('status', QuoteStatus::Envoyé)->count(),
                'invoices_unpaid' => $user->invoices()->whereIn('status', [
                    InvoiceStatus::Brouillon,
                    InvoiceStatus::Envoyé,
                ])->count(),
                'revenue_this_month' => $user->invoices()
                    ->where('status', InvoiceStatus::Payé)
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('total'),
                'revenue_total' => $user->invoices()
                    ->where('status', InvoiceStatus::Payé)
                    ->sum('total'),
            ],
            'recent_invoices' => $user->invoices()
                ->with('client')
                ->latest()
                ->take(5)
                ->get(),
            'recent_quotes' => $user->quotes()
                ->with('client')
                ->latest()
                ->take(5)
                ->get(),
            'favorites' => $user->prospects()
                ->where('is_favorite', true)
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get(),
            'favorite_clients' => $user->prospects()
                ->where('status', 'client')
                ->where('is_favorite', true)
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get(),
            'favorite_prospects' => $user->prospects()
                ->where('status', 'prospect')
                ->where('is_favorite', true)
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get(),
            'recent_clients' => $user->prospects()
                ->where('status', 'client')
                ->latest()
                ->take(5)
                ->get(),
            'recent_prospects' => $user->prospects()
                ->where('status', 'prospect')
                ->latest()
                ->take(5)
                ->get(),
            'kpi_preferences' => $user->dashboard_kpi_preferences ?? [],
        ]);
    }

    /**
     * Update user's KPI preferences.
     */
    public function updateKpiPreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.id' => 'required|string',
            'preferences.*.visible' => 'required|boolean',
        ]);

        $user = $request->user();
        $user->update([
            'dashboard_kpi_preferences' => $validated['preferences'],
        ]);

        return back();
    }
}
