<?php

/*
 * Demo data: fictional customers, motorcycles and service history.
 * All names, phone numbers and plate numbers are made up.
 *
 * Visit dates are written as "days_ago" so the demo always shows a mix of
 * OK, due soon and overdue services no matter what day you run the seeder.
 *
 * mechanic: 1 = first mechanic account, 2 = second mechanic account.
 * Visits without a status are saved as "released" (finished work).
 */

return [

    'customers' => [
        'juan' => ['full_name' => 'Juan Dela Cruz', 'phone' => '0917 000 1101', 'address' => 'Brgy. San Isidro, Calauan, Laguna'],
        'maria' => ['full_name' => 'Maria Santos', 'phone' => '0917 000 1102', 'address' => 'Brgy. Dayap, Calauan, Laguna'],
        'jose' => ['full_name' => 'Jose Ramirez', 'phone' => '0917 000 1103', 'address' => 'Brgy. Lamot 1, Calauan, Laguna'],
        'ana' => ['full_name' => 'Ana Villanueva', 'phone' => '0917 000 1104', 'address' => 'Brgy. Santo Tomas, Calauan, Laguna'],
        'mark' => ['full_name' => 'Mark Reyes', 'phone' => '0917 000 1105', 'address' => 'Brgy. Imok, Calauan, Laguna'],
        'liza' => ['full_name' => 'Liza Bautista', 'phone' => '0917 000 1106', 'address' => 'Brgy. Bangyas, Calauan, Laguna'],
        'rico' => ['full_name' => 'Rico Mendoza', 'phone' => '0917 000 1107', 'address' => 'Brgy. Masiit, Calauan, Laguna'],
        'carla' => ['full_name' => 'Carla Navarro', 'phone' => '0917 000 1108', 'address' => 'Brgy. Hanggan, Calauan, Laguna'],
    ],

    'motorcycles' => [

        [
            'owner' => 'juan', 'model' => 'Honda Click 125', 'plate' => 'NBX 4821', 'year' => 2021, 'color' => 'Red',
            'visits' => [
                ['days_ago' => 200, 'odometer' => 6200, 'mechanic' => 1, 'services' => ['Change Oil', 'CVT Cleaning'], 'parts' => [['OIL-1L', 1], ['GEAR-OIL', 1]], 'labor' => 250, 'complaint' => 'Regular maintenance'],
                ['days_ago' => 120, 'odometer' => 8000, 'mechanic' => 2, 'services' => ['Change Oil', 'Air Filter Replacement'], 'parts' => [['OIL-1L', 1], ['AIR-FLT', 1]], 'labor' => 150, 'complaint' => 'Change oil and clean air filter'],
                ['days_ago' => 25, 'odometer' => 10100, 'mechanic' => 1, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'Change oil'],
            ],
        ],
        [
            'owner' => 'juan', 'model' => 'Honda XRM 125', 'plate' => 'RST 4402', 'year' => 2016, 'color' => 'Black',
            'visits' => [
                ['days_ago' => 10, 'odometer' => 55300, 'mechanic' => 2, 'services' => ['Change Oil', 'Brake Pad Replacement'], 'parts' => [['OIL-1L', 1], ['BPF-01', 1]], 'labor' => 200, 'complaint' => 'Brake squeaks'],
                ['days_ago' => 0, 'odometer' => 55900, 'mechanic' => 2, 'services' => [], 'parts' => [['BPR-01', 1]], 'labor' => 120, 'complaint' => 'Rear brake noise', 'status' => 'ongoing'],
            ],
        ],
        [
            'owner' => 'maria', 'model' => 'Honda Click 160', 'plate' => 'TQD 5513', 'year' => 2023, 'color' => 'White',
            'visits' => [
                ['days_ago' => 160, 'odometer' => 1100, 'mechanic' => 1, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'First change oil'],
                ['days_ago' => 80, 'odometer' => 3300, 'mechanic' => 2, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'Change oil'],
            ],
        ],
        [
            'owner' => 'jose', 'model' => 'Yamaha Mio i 125', 'plate' => 'KLM 7390', 'year' => 2019, 'color' => 'Blue',
            'visits' => [
                ['days_ago' => 300, 'odometer' => 15000, 'mechanic' => 1, 'services' => ['Change Oil', 'Spark Plug Replacement'], 'parts' => [['OIL-1L', 1], ['SPK-STD', 1]], 'labor' => 200, 'complaint' => 'Hard to start'],
                ['days_ago' => 190, 'odometer' => 16500, 'mechanic' => 2, 'services' => ['Change Oil', 'Air Filter Replacement'], 'parts' => [['OIL-1L', 1], ['AIR-FLT', 1]], 'labor' => 150, 'complaint' => 'Regular maintenance'],
                ['days_ago' => 100, 'odometer' => 18000, 'mechanic' => 1, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'Change oil'],
            ],
        ],
        [
            'owner' => 'ana', 'model' => 'Yamaha NMAX 155', 'plate' => 'PRZ 2046', 'year' => 2022, 'color' => 'Gray',
            'visits' => [
                ['days_ago' => 140, 'odometer' => 4400, 'mechanic' => 2, 'services' => ['Change Oil', 'CVT Cleaning', 'Brake Pad Replacement'], 'parts' => [['OIL-1L', 1], ['GEAR-OIL', 1], ['BPF-01', 1]], 'labor' => 350, 'complaint' => 'Regular maintenance and brake check'],
                ['days_ago' => 15, 'odometer' => 8300, 'mechanic' => 1, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'Change oil'],
            ],
        ],
        [
            'owner' => 'mark', 'model' => 'Suzuki Raider R150', 'plate' => 'XJL 9902', 'year' => 2020, 'color' => 'Yellow',
            'visits' => [
                ['days_ago' => 120, 'odometer' => 22000, 'mechanic' => 1, 'services' => ['Change Oil', 'Chain Cleaning & Lube'], 'parts' => [['OIL-1L', 1]], 'labor' => 220, 'complaint' => 'Noisy chain'],
                ['days_ago' => 40, 'odometer' => 24000, 'mechanic' => 2, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'Change oil'],
            ],
        ],
        [
            'owner' => 'liza', 'model' => 'Honda XRM 125', 'plate' => 'DFE 3317', 'year' => 2018, 'color' => 'Red',
            'visits' => [
                ['days_ago' => 250, 'odometer' => 40000, 'mechanic' => 2, 'services' => ['Change Oil', 'Spark Plug Replacement'], 'parts' => [['OIL-1L', 1], ['SPK-STD', 1]], 'labor' => 180, 'complaint' => 'Regular maintenance'],
                ['days_ago' => 165, 'odometer' => 41500, 'mechanic' => 1, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'Change oil'],
                ['days_ago' => 80, 'odometer' => 43000, 'mechanic' => 2, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'Change oil'],
            ],
        ],
        [
            'owner' => 'rico', 'model' => 'Honda TMX 125 Alpha', 'plate' => 'GHT 1175', 'year' => 2017, 'color' => 'Green',
            'visits' => [
                ['days_ago' => 90, 'odometer' => 60200, 'mechanic' => 1, 'services' => ['Change Oil', 'Chain Cleaning & Lube'], 'parts' => [['OIL-1L', 1]], 'labor' => 220, 'complaint' => 'Regular maintenance'],
                ['days_ago' => 30, 'odometer' => 61400, 'mechanic' => 2, 'services' => ['Change Oil'], 'parts' => [['OIL-1L', 1]], 'labor' => 100, 'complaint' => 'Change oil'],
            ],
        ],
        [
            'owner' => 'carla', 'model' => 'Honda Click 125', 'plate' => 'LMN 6648', 'year' => 2022, 'color' => 'Pink',
            'visits' => [
                ['days_ago' => 45, 'odometer' => 5000, 'mechanic' => 1, 'services' => ['Change Oil', 'Air Filter Replacement'], 'parts' => [['OIL-1L', 1], ['AIR-FLT', 1]], 'labor' => 150, 'complaint' => 'Regular maintenance'],
                ['days_ago' => 0, 'odometer' => 5350, 'mechanic' => 1, 'services' => [], 'parts' => [], 'labor' => 0, 'complaint' => 'Hard to start in the morning', 'status' => 'pending'],
            ],
        ],
        [
            'owner' => 'carla', 'model' => 'Yamaha Mio i 125', 'plate' => 'UVW 8821', 'year' => 2020, 'color' => 'Black',
            'visits' => [
                ['days_ago' => 20, 'odometer' => 31000, 'mechanic' => 2, 'services' => ['Change Oil', 'Spark Plug Replacement'], 'parts' => [['OIL-1L', 1], ['SPK-STD', 1]], 'labor' => 180, 'complaint' => 'Regular maintenance'],
            ],
        ],
    ],
];
