<?php

// app/Http/Resources/TrainingTypeResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_mandatory' => $this->is_mandatory,

            // Validity and requirements
            'validity_period_months' => $this->validity_period_months,
            'validity_period_human' => $this->validity_period,
            'warning_period_days' => $this->warning_period_days,
            'requirements' => $this->requirements,
            'learning_objectives' => $this->learning_objectives,

            // Provider and cost
            'default_provider' => $this->whenLoaded('defaultProvider'),
            'estimated_cost' => $this->estimated_cost,
            'formatted_cost' => $this->formatted_cost,
            'estimated_duration_hours' => $this->estimated_duration_hours,

            // Compliance and analytics
            'compliance_target_percentage' => $this->compliance_target_percentage,
            'priority_score' => $this->priority_score,
            'applicable_departments' => $this->applicable_departments,
            'applicable_job_levels' => $this->applicable_job_levels,

            // Statistics (when loaded)
            'statistics' => $this->whenLoaded('statistics'),

            // Relationships
            'training_records_count' => $this->when(
                $request->has('include_counts'),
                fn () => $this->training_records_count
            ),
            'active_records_count' => $this->when(
                $request->has('include_counts'),
                fn () => $this->active_training_records_count
            ),

            // Metadata
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name
            ]),
            'last_analytics_update' => $this->last_analytics_update?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
