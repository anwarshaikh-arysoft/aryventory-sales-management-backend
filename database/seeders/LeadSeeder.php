<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        Lead::create([
            'created_by' => 4,
            'assigned_to' => 2,
            'last_updated_by' => 4,
            'shop_name' => 'Star Kirana',
            'contact_person' => 'Rajesh Kumar',
            'mobile_number' => '9876543210',
            'alternate_number' => '9123456780',
            'email' => 'rajesh@starkirana.com',
            'address' => '12 MG Road',
            'area_locality' => 'Andheri East',
            'pincode' => '400069',
            'gps_location' => '19.12345,72.87654',
            'business_type' => 'Mobile Store',
            'current_system' => 'Manual Ledger',
            'lead_status' => 'Interested',
            'plan_interest' => 'Basic',
            'next_follow_up_date' => '2025-08-10',
            'meeting_notes' => 'Requested demo and pricing details',
            'created_at' => '2025-08-07 09:30:00',
            'updated_at' => '2025-08-07 09:30:00',
        ]);

        Lead::create([
            'created_by' => 5,
            'assigned_to' => 2,
            'last_updated_by' => 4,
            'shop_name' => 'Elite Electronics',
            'contact_person' => 'Neha Verma',
            'mobile_number' => '9988776655',
            'alternate_number' => null,
            'email' => 'contact@eliteelect.com',
            'address' => '88 Linking Road',
            'area_locality' => 'Bandra West',
            'pincode' => '400050',
            'gps_location' => '19.05678,72.83456',
            'business_type' => 'Electronics',
            'current_system' => 'Billing Software',
            'lead_status' => 'Follow-up',
            'plan_interest' => 'Premium',
            'next_follow_up_date' => '2025-08-15',
            'meeting_notes' => 'Waiting for manager approval',
            'created_at' => '2025-08-07 10:00:00',
            'updated_at' => '2025-08-07 10:00:00',
        ]);

        Lead::create([
            'created_by' => 6,
            'assigned_to' => 3,
            'last_updated_by' => 4,
            'shop_name' => 'Shree Stationers',
            'contact_person' => 'Manoj Desai',
            'mobile_number' => '9090909090',
            'alternate_number' => '9898989898',
            'email' => 'manoj@shreest.com',
            'address' => '5 Gandhi Market',
            'area_locality' => 'Dadar East',
            'pincode' => '400014',
            'gps_location' => '19.08445,72.84123',
            'business_type' => 'Mini Mall',
            'current_system' => 'Excel Sheet',
            'lead_status' => 'Cold',
            'plan_interest' => 'Not Interested',
            'next_follow_up_date' => '2025-08-20',
            'meeting_notes' => 'Not interested right now, revisit next month',
            'created_at' => '2025-08-07 09:30:00',
            'updated_at' => '2025-08-07 09:30:00',
        ]);
    }
}
