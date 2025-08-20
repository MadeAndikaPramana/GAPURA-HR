<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Employee;
use App\Models\TrainingType;

class TrainingRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('TrainingRecords/Index', [
            'trainingRecords' => \App\Models\TrainingRecord::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('TrainingRecords/Create', [
            'employees' => \App\Models\Employee::all(['id', 'name']),
            'trainingTypes' => \App\Models\TrainingType::all(['id', 'name', 'validity_months']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'certificate_number' => 'required|string|unique:training_records',
            'issuer' => 'required|string',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'notes' => 'nullable|string',
        ]);

        \App\Models\TrainingRecord::create($request->all());

        return redirect()->route('training-records.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return Inertia::render('TrainingRecords/Edit', [
            'trainingRecord' => \App\Models\TrainingRecord::find($id),
            'employees' => \App\Models\Employee::all(['id', 'name']),
            'trainingTypes' => \App\Models\TrainingType::all(['id', 'name', 'validity_months']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'training_type_id' => 'required|exists:training_types,id',
            'certificate_number' => 'required|string|unique:training_records,certificate_number,'.$id,
            'issuer' => 'required|string',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'notes' => 'nullable|string',
        ]);

        $trainingRecord = \App\Models\TrainingRecord::find($id);
        $trainingRecord->update($request->all());

        return redirect()->route('training-records.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $trainingRecord = \App\Models\TrainingRecord::find($id);
        $trainingRecord->delete();

        return redirect()->route('training-records.index');
    }

    public function bulkImport()
    {
        return Inertia::render('TrainingRecords/BulkImport');
    }

    public function handleBulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new \App\Imports\TrainingRecordsImport, $request->file('file'));

        return redirect()->route('training-records.index')->with('success', 'Training records imported successfully!');
    }

    public function bulkExport()
    {
        return Excel::download(new \App\Exports\TrainingRecordsExport, 'training_records.xlsx');
    }
}
