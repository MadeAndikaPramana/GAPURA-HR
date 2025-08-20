<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('TrainingTypes/Index', [
            'trainingTypes' => \App\Models\TrainingType::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('TrainingTypes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:training_types',
            'validity_months' => 'required|integer',
            'category' => 'required|in:safety,operational,security,technical',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        \App\Models\TrainingType::create($request->all());

        return redirect()->route('training-types.index');
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
        return Inertia::render('TrainingTypes/Edit', [
            'trainingType' => \App\Models\TrainingType::find($id),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:training_types,code,'.$id,
            'validity_months' => 'required|integer',
            'category' => 'required|in:safety,operational,security,technical',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $trainingType = \App\Models\TrainingType::find($id);
        $trainingType->update($request->all());

        return redirect()->route('training-types.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $trainingType = \App\Models\TrainingType::find($id);
        $trainingType->delete();

        return redirect()->route('training-types.index');
    }
}
