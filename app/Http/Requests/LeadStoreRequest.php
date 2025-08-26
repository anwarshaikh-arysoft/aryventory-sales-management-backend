<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shop_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'alternate_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'area_locality' => 'required|string|max:255',
            'pincode' => 'required|string|max:10',
            'gps_location' => 'required|string|max:255',
            'business_type' => 'required|exists:business_types,id',
            'current_system' => 'required|exists:current_systems,id',
            'lead_status' => 'required|exists:lead_statuses,id',
            'plan_interest' => 'required|string|max:255',
            'next_follow_up_date' => 'required|date',
            'meeting_notes' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
        ];
    }

    // Optional: nicer field names / messages
    public function attributes(): array
    {
        return [
            'shop_name' => 'shop name',
            'contact_person' => 'contact_person',
            'mobile_number' => 'mobile_number',
            'alternate_number' => 'alternate_number',
            'email' => 'email',
            'address' => 'address',
            'area_locality' => 'area_locality',
            'pincode' => 'pincode',
            'gps_location' => 'gps_location',
            'business_type' => 'business_type',
            'current_system' => 'current_system',
            'lead_status' => 'lead_status',
            'plan_interest' => 'plan_interest',
            'next_follow_up_date' => 'next_follow_up_date',
            'meeting_notes' => 'nullable|string',
            'assigned_to' => 'assigned_to',
        ];
    }
}
