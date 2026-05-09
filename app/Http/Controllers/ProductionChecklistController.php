<?php

namespace App\Http\Controllers;

use App\Models\ProductionChecklist;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ProductionChecklistController extends Controller
{
    public function index()
    {
        $checklists = ProductionChecklist::orderByDesc('check_date')->paginate(15);
        return view('admin.checklists.index', compact('checklists'));
    }

    public function create(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $checklist = ProductionChecklist::where('check_date', $date)->first();
        
        $items = $checklist ? $checklist->items_json : ProductionChecklist::getDefaultItems();
        
        return view('admin.checklists.form', compact('date', 'items', 'checklist'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'check_date' => 'required|date',
            'inspector_name' => 'required|string',
            'items' => 'required|array',
        ]);

        ProductionChecklist::updateOrCreate(
            ['check_date' => $request->check_date],
            [
                'items_json' => $request->items,
                'inspector_name' => $request->inspector_name,
                'status' => 'completed'
            ]
        );

        return redirect()->route('admin.checklists.index')->with('success', 'Checklist saved successfully.');
    }

    public function exportPdf(ProductionChecklist $checklist)
    {
        $pdf = Pdf::loadView('exports.checklist_pdf', compact('checklist'));
        $filename = 'observation_checklist_' . $checklist->check_date->format('Ymd') . '.pdf';
        
        return $pdf->download($filename);
    }
}
