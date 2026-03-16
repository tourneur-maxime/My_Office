<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveTemplateRequest;
use App\Models\Template;
use App\Services\TemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    protected TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Settings/Templates/Index', [
            'templates' => $request->user()->templates()->orderBy('name')->get(),
            'companyProfile' => $request->user()->companyProfile,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        $template = Auth::user()->templates()->create($validated);

        if ($request->boolean('is_default')) {
            $this->templateService->setDefault(Auth::user(), $template);
        }

        return redirect()->back()->with('success', 'Le modèle a été enregistré avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Template $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->back()->with('success', 'Le modèle a été supprimé.');
    }

    /**
     * Set the specified template as default.
     */
    public function setDefault(Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $this->templateService->setDefault(Auth::user(), $template);

        return redirect()->back()->with('success', 'Le modèle par défaut a été mis à jour.');
    }
}