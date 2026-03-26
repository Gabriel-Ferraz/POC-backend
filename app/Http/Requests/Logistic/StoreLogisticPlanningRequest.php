<?php

namespace App\Http\Requests\Logistic;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogisticPlanningRequest extends FormRequest
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
            'planning_type_id' => 'required|integer|exists:planning_type,id',
            'service_type_id' => 'required|integer|exists:service_type,id',
            'attendance_type_id' => 'required|integer|exists:attendance_type,id',
            'driver' => 'required|string',
            'tag' => 'required|string',
            'destination_client' => 'required|string',
            'destination_address' => 'required|string',
            'source_address' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
            'planning_date' => 'required|date',
        ];
    }
}
