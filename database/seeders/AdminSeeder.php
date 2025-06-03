<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Medicine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'full_name' => 'Admin User',
            'email' => 'admin@hospital.com',
            'password' => Hash::make('password123'),
            'phone_number' => '081234567890',
            'role' => 'admin',
        ]);

        // Create sample medicines
        $medicines = [
            [
                'medicine_name' => 'Amlodipine',
                'medicine_type' => 'tablet',
                'dosage' => '5mg',
                'unit' => 'tablet',
                'stock' => 100,
                'description' => 'Calcium channel blocker for hypertension'
            ],
            [
                'medicine_name' => 'Amoxicillin',
                'medicine_type' => 'capsule',
                'dosage' => '500mg',
                'unit' => 'capsule',
                'stock' => 50,
                'description' => 'Antibiotic for bacterial infections'
            ],
            [
                'medicine_name' => 'Paracetamol',
                'medicine_type' => 'tablet',
                'dosage' => '500mg',
                'unit' => 'tablet',
                'stock' => 200,
                'description' => 'Pain reliever and fever reducer'
            ],
            [
                'medicine_name' => 'Omeprazole',
                'medicine_type' => 'capsule',
                'dosage' => '20mg',
                'unit' => 'capsule',
                'stock' => 75,
                'description' => 'Proton pump inhibitor for stomach acid reduction'
            ],
            [
                'medicine_name' => 'Salbutamol Syrup',
                'medicine_type' => 'syrup',
                'dosage' => '2mg/5ml',
                'unit' => 'bottle',
                'stock' => 30,
                'description' => 'Bronchodilator for asthma and respiratory conditions'
            ]
        ];

        foreach ($medicines as $medicine) {
            Medicine::create($medicine);
        }
    }
}
