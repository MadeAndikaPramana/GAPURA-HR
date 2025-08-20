<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Employees/Index', [
            'employees' => \App\Models\Employee::all(),
        ]);
    }

    public function import()
    {
        return Inertia::render('Employees/Import');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Employees/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|unique:employees',
            'name' => 'required',
            'position' => 'required',
            'status' => 'required',
        ]);

        \App\Models\Employee::create($request->all());

        return redirect()->route('employees.index');
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
        return Inertia::render('Employees/Edit', [
            'employee' => \App\Models\Employee::find($id),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'employee_id' => 'required|unique:employees,employee_id,'.$id,
            'name' => 'required',
            'position' => 'required',
            'status' => 'required',
        ]);

        $employee = \App\Models\Employee::find($id);
        $employee->update($request->all());

        return redirect()->route('employees.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $employee = \App\Models\Employee::find($id);
        $employee->delete();

        return redirect()->route('employees.index');
    }

    public function handleImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new \App\Imports\EmployeesImport, $request->file('file'));

        return redirect()->route('employees.index')->with('success', 'Employees imported successfully!');
    }

    public function export()
    {
        return Excel::download(new \App\Exports\EmployeesExport, 'employees.xlsx');
    }
}
