<?php

namespace App\Http\Requests\Logistic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLogisticPlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'planning_type_id' => 'sometimes|integer|exists:planning_type,id',
            'service_type_id' => 'sometimes|integer|exists:service_type,id',
            'attendance_type_id' => 'sometimes|integer|exists:attendance_type,id',
            'driver' => 'sometimes|string',
            'tag' => 'sometimes|string',
            'destination_client' => 'sometimes|string',
            'destination_address' => 'sometimes|string',
            'source_address' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
            'status' => 'sometimes|string',
            'planning_date' => 'sometimes|date',
        ];
    }
}
