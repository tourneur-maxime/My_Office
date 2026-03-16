<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceNumber;
use App\Services\InvoiceNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $settings = InvoiceNumber::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'prefix' => 'FAC',
                'digit_count' => 4,
                'reset_frequency' => 'yearly',
                'current_number' => 0,
                'counter_year' => Carbon::now()->year,
                'separator' => '-',
                'include_year' => true,
            ]
        );

        $service = new InvoiceNumberService();
        $preview = $service->previewNext($request->user());

        return Inertia::render('Settings/Invoices/Numbering', [
            'settings' => $settings,
            'preview' => $preview,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $settings = InvoiceNumber::where('user_id', $user->id)->first();

        if ($settings && $settings->current_number > 0) {
            if (
                $request->input('prefix') !== $settings->prefix ||
                $request->input('suffix') !== $settings->suffix ||
                (int) $request->input('digit_count') !== $settings->digit_count ||
                $request->input('reset_frequency') !== $settings->reset_frequency ||
                $request->input('separator') !== $settings->separator ||
                (bool)$request->input('include_year') !== $settings->include_year
            ) {
                $hasInvoicesThisYear = Invoice::where('user_id', $user->id)
                    ->whereYear('issue_date', $settings->counter_year)
                    ->exists();

                if ($hasInvoicesThisYear) {
                    throw ValidationException::withMessages([
                        'settings' => 'Les paramètres de numérotation ne peuvent pas être modifiés car des factures ont déjà été émises pour la période en cours.',
                    ]);
                }
            }
        }

        $validated = $request->validate([
            'prefix' => 'nullable|string|max:10',
            'suffix' => 'nullable|string|max:10',
            'digit_count' => 'required|integer|min:3|max:10',
            'reset_frequency' => 'required|in:yearly,never',
            'separator' => 'nullable|string|max:5',
            'include_year' => 'boolean',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $settings = InvoiceNumber::firstOrNew(['user_id' => $user->id]);

            if (!$settings->exists) {
                $settings->current_number = 0;
                $settings->counter_year = Carbon::now()->year;
            }

            $settings->fill($validated);
            // Handle unchecked checkbox (if not sent) or ensure boolean cast
            $settings->include_year = $validated['include_year'] ?? false;
            
            $settings->save();
        });

        return back()->with('success', 'Paramètres de numérotation mis à jour.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $hasInvoices = Invoice::where('user_id', $user->id)->exists();

        if ($hasInvoices) {
            return back()->with('error', 'Impossible de réinitialiser car des factures existent déjà.');
        }

        InvoiceNumber::where('user_id', $user->id)->delete();

        return back()->with('success', 'Paramètres réinitialisés.');
    }
}
