<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'name' => 'Magnova Consulting Pvt Ltd',
            'slug' => 'magnova',
            'gstin' => '21AAFCM1234B1Z5',
            'pan' => 'AAFCM1234B',
            'contact_email' => 'contact@magnovaconsulting.in',
            'contact_phone' => '9861000000',
            'address' => 'Plot No. 123, Nayapalli, Bhubaneswar, Odisha - 751012',
            'bank_name' => 'State Bank of India',
            'bank_account_no' => '30012345678',
            'bank_ifsc' => 'SBIN0001234',
            'bank_branch' => 'Nayapalli, Bhubaneswar',
            'subscription_status' => 'active',
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Raghunatha Maharana',
            'email' => 'raghunatha.maharana@gmail.com',
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => 'owner',
        ]);

        $clients = [
            Client::create([
                'tenant_id' => $tenant->id,
                'name' => 'Puri FC of ICAR-CMFRI',
                'unit_label' => null,
                'address' => 'Balighai, Puri, Odisha - 752002',
                'gstin' => null,
                'sac_code' => '998513',
                'billing_name' => 'The Head, Visakhapatnam Regional Centre of CMFRI',
                'billing_address' => 'Behind Aqua Sports Complex, Pandurangapuram, A.U. Post, Visakhapatnam, Andhra Pradesh-530003',
                'contact_person' => 'Dr. S. K. Otta',
                'contact_phone' => '9437000001',
                'contact_email' => 'puri.fc@icar-cmfri.example',
                'active' => true,
            ]),
            Client::create([
                'tenant_id' => $tenant->id,
                'name' => 'Bhubaneswar Institute of Technology',
                'unit_label' => 'Campus Security',
                'address' => 'Chandaka Industrial Estate, Bhubaneswar, Odisha - 751024',
                'gstin' => '21AABCB5678C1Z2',
                'sac_code' => '998513',
                'contact_person' => 'Mr. A. Panda',
                'contact_phone' => '9437000002',
                'contact_email' => 'admin@bit.example',
                'active' => true,
            ]),
            Client::create([
                'tenant_id' => $tenant->id,
                'name' => 'Odisha State Warehousing Corporation',
                'unit_label' => 'Cuttack Godown',
                'address' => 'Nimchowk, Cuttack, Odisha - 753001',
                'gstin' => '21AAACO9012D1Z8',
                'sac_code' => '998513',
                'gemc_no' => 'GEM/2026/B/1234567',
                'contact_person' => 'Mr. B. Behera',
                'contact_phone' => '9437000003',
                'contact_email' => 'admin@oswc.example',
                'active' => true,
            ]),
        ];

        $employeeNames = [
            ['Ramesh Chandra Sahoo', 'skilled', 'Security Supervisor', 600],
            ['Bijay Kumar Das', 'unskilled', 'Security Guard', 480],
            ['Suresh Nayak', 'unskilled', 'Security Guard', 480],
            ['Prakash Chandra Jena', 'skilled', 'Security Supervisor', 600],
            ['Manoj Kumar Behera', 'unskilled', 'Security Guard', 480],
            ['Dillip Kumar Rout', 'unskilled', 'Security Guard', 480],
            ['Ashok Kumar Pradhan', 'skilled', 'Security Supervisor', 600],
            ['Gopal Krishna Swain', 'unskilled', 'Security Guard', 480],
            ['Bhagaban Sethi', 'unskilled', 'Attendant', 450],
        ];

        $employees = collect();
        $i = 0;
        foreach ($clients as $client) {
            for ($j = 0; $j < 3; $j++) {
                [$name, $skill, $designation, $rate] = $employeeNames[$i];
                $employees->push(Employee::create([
                    'tenant_id' => $tenant->id,
                    'client_id' => $client->id,
                    'employee_code' => sprintf('MCPL-%03d', $i + 1),
                    'name' => $name,
                    'uan' => '10012345' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'epf_member_id' => 'OR/BBS/0012345/000/' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'esic_no' => '31001234560000' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'skill_category' => $skill,
                    'designation' => $designation,
                    'rate_per_day' => $rate,
                    'date_of_joining' => now()->subYears(1)->subDays($i * 10),
                    'status' => 'active',
                    'bank_name' => 'State Bank of India',
                    'bank_account_no' => '3801234567' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'bank_ifsc' => 'SBIN0001234',
                ]));
                $i++;
            }
        }

        $lastMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $daysInMonth = $lastMonth->daysInMonth;

        foreach ($employees as $index => $employee) {
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $lastMonth->copy()->day($day);

                $status = match (true) {
                    $date->isSunday() => 'sunday',
                    $day % 15 === ($index % 15) => 'leave',
                    $day % 19 === ($index % 19) => 'holiday',
                    $day % 17 === ($index % 17) => 'absent',
                    default => 'present',
                };

                Attendance::create([
                    'tenant_id' => $tenant->id,
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'status' => $status,
                ]);
            }
        }
    }
}
