<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TrainingType;
use App\Models\CertificateSequence;

class CertificateController extends Controller
{
    public function create()
    {
        return Inertia::render('Certificates/Create', [
            'trainingTypes' => TrainingType::all(['id', 'name', 'validity_months']),
        ]);
    }

    public function generateCertificateNumber(Request $request)
    {
        $request->validate([
            'training_type_id' => 'required|exists:training_types,id',
            'issuer' => 'required|string',
            'issue_date' => 'required|date',
        ]);

        $trainingType = TrainingType::find($request->training_type_id);
        $issueDate = new \DateTime($request->issue_date);
        $year = $issueDate->format('Y');
        $month = strtoupper($issueDate->format('M'));

        $certificateSequence = CertificateSequence::firstOrCreate(
            [
                'training_type_id' => $trainingType->id,
                'issuer' => $request->issuer,
                'year' => $year,
                'month' => $month,
            ],
            ['last_number' => 0]
        );

        $certificateSequence->increment('last_number');

        $nextNumber = str_pad($certificateSequence->last_number, 6, '0', STR_PAD_LEFT);

        $certificateNumber = "{$request->issuer}/{$trainingType->code}-{$nextNumber}/{$month}/{$year}";

        return response()->json(['certificate_number' => $certificateNumber]);
    }
}
