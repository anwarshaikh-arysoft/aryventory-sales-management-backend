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
        return true;
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
            'branches' => 'nullable|integer',
            'gps_location' => 'required|string|max:255',
            'business_type' => 'nullable|exists:business_types,id',
            'current_system' => 'nullable|exists:current_systems,id',
            'lead_status' => 'nullable|exists:lead_statuses,id',
            'plan_interest' => 'nullable|string|max:255',
            'next_follow_up_date' => 'nullable|date',
            'meeting_notes' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'action' => 'nullable|string|max:255',
        ];
    }

    // Optional: nicer field names / messages
    public function attributes(): array
    {
        return [
            'shop_name' => 'shop name',
            'contact_person' => 'contact person',
            'mobile_number' => 'mobile number',
            'alternate_number' => 'alternate number',
            'email' => 'email',
            'address' => 'address',
            'area_locality' => 'area/locality',
            'pincode' => 'pincode',
            'gps_location' => 'GPS location',
            'business_type' => 'business type',
            'current_system' => 'current system',
            'lead_status' => 'lead status',
            'plan_interest' => 'plan interest',
            'next_follow_up_date' => 'next follow-up date',
            'meeting_notes' => 'meeting notes',
            'assigned_to' => 'assigned to',
            'branches' => 'branches',
            'action' => 'action',
        ];
    }
}
