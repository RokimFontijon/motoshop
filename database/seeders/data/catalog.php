<?php

/*
 * Reference data (catalog): roles, motorcycle models, service types,
 * service intervals and spare parts.
 *
 * IMPORTANT: the service intervals below are SAMPLE values for the demo.
 * Before the final defense, confirm them with the owner's manual of each
 * model and with the shop's mechanic, then edit this file.
 */

return [

    'roles' => [
        ['name' => 'admin', 'description' => 'Shop owner or manager. Full access.'],
        ['name' => 'mechanic', 'description' => 'Mechanic. Works on job orders.'],
    ],

    'service_types' => [
        'Change Oil' => 'Engine oil replacement',
        'CVT Cleaning' => 'Clean the CVT / transmission (scooters)',
        'Chain Cleaning & Lube' => 'Clean, lubricate and adjust the drive chain',
        'Brake Pad Replacement' => 'Replace worn brake pads',
        'Spark Plug Replacement' => 'Replace the spark plug',
        'Air Filter Replacement' => 'Clean or replace the air filter',
    ],

    // type = which group of intervals the model uses (see 'intervals' below)
    'models' => [
        ['brand' => 'Honda', 'name' => 'Click 125', 'engine_cc' => 125, 'type' => 'scooter'],
        ['brand' => 'Honda', 'name' => 'Click 160', 'engine_cc' => 160, 'type' => 'scooter'],
        ['brand' => 'Yamaha', 'name' => 'Mio i 125', 'engine_cc' => 125, 'type' => 'scooter'],
        ['brand' => 'Yamaha', 'name' => 'NMAX 155', 'engine_cc' => 155, 'type' => 'scooter'],
        ['brand' => 'Suzuki', 'name' => 'Raider R150', 'engine_cc' => 150, 'type' => 'manual'],
        ['brand' => 'Honda', 'name' => 'XRM 125', 'engine_cc' => 125, 'type' => 'manual'],
        ['brand' => 'Honda', 'name' => 'TMX 125 Alpha', 'engine_cc' => 125, 'type' => 'manual'],
    ],

    // [interval_km, interval_months]
    'intervals' => [
        'scooter' => [
            'Change Oil' => [3000, 3],
            'CVT Cleaning' => [6000, 6],
            'Brake Pad Replacement' => [10000, 12],
            'Spark Plug Replacement' => [8000, 12],
            'Air Filter Replacement' => [6000, 6],
        ],
        'manual' => [
            'Change Oil' => [3000, 3],
            'Chain Cleaning & Lube' => [1500, 2],
            'Brake Pad Replacement' => [10000, 12],
            'Spark Plug Replacement' => [8000, 12],
            'Air Filter Replacement' => [6000, 6],
        ],
    ],

    // Intervals that are different for one specific model: 'Brand Name' => [service => [km, months]]
    'interval_overrides' => [
        'Yamaha NMAX 155' => ['Change Oil' => [4000, 4]],
        'Suzuki Raider R150' => ['Chain Cleaning & Lube' => [1000, 2]],
    ],

    // [part_code, name, unit_price, stock_qty, reorder_level]
    'parts' => [
        ['OIL-1L', 'Engine Oil 10W-40 (1 L)', 280.00, 40, 10],
        ['OIL-FLT', 'Oil Filter', 120.00, 15, 5],
        ['GEAR-OIL', 'Gear Oil (120 ml)', 90.00, 20, 5],
        ['SPK-STD', 'Spark Plug', 180.00, 25, 8],
        ['AIR-FLT', 'Air Filter Element', 250.00, 12, 5],
        ['BPF-01', 'Brake Pad (Front)', 260.00, 14, 5],
        ['BPR-01', 'Brake Pad (Rear)', 220.00, 12, 5],
        ['CVT-BLT', 'CVT Drive Belt', 750.00, 6, 3],
        ['CHN-428', 'Drive Chain 428', 650.00, 8, 3],
        ['SPR-SET', 'Sprocket Set', 900.00, 2, 3],   // low stock on purpose (demo)
        ['BAT-12V', 'Battery 12V', 1100.00, 4, 5],   // low stock on purpose (demo)
        ['BRK-FLD', 'Brake Fluid DOT 3', 150.00, 9, 5],
    ],
];
