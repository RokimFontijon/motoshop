<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\JobOrderPart;
use App\Models\Motorcycle;
use App\Models\MotorcycleModel;
use App\Models\Part;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Fictional customers, motorcycles and service history (database/seeders/data/demo_data.php).
 * Adding a part line to a job order lowers the stock automatically (database trigger).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $demo = require database_path('seeders/data/demo_data.php');

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $mechanicRole = Role::where('name', 'mechanic')->firstOrFail();

        // The password is hashed automatically by the "hashed" cast on the User model.
        User::create(['name' => 'Shop Admin', 'email' => 'admin@motorshop.test', 'password' => 'password', 'role_id' => $adminRole->id]);
        $mechanics = [
            User::create(['name' => 'Tonyo Alvarez', 'email' => 'mechanic1@motorshop.test', 'password' => 'password', 'role_id' => $mechanicRole->id]),
            User::create(['name' => 'Ben Castillo', 'email' => 'mechanic2@motorshop.test', 'password' => 'password', 'role_id' => $mechanicRole->id]),
        ];

        $customers = [];
        foreach ($demo['customers'] as $key => $row) {
            $customers[$key] = Customer::create($row);
        }

        $serviceTypes = ServiceType::pluck('id', 'name');
        $parts = Part::all()->keyBy('part_code');

        foreach ($demo['motorcycles'] as $row) {
            $model = MotorcycleModel::all()->first(fn ($m) => $m->label() === $row['model']);

            $motorcycle = Motorcycle::create([
                'customer_id' => $customers[$row['owner']]->id,
                'motorcycle_model_id' => $model->id,
                'plate_number' => $row['plate'],
                'year' => $row['year'],
                'color' => $row['color'],
                'current_odometer' => max(array_column($row['visits'], 'odometer')),
            ]);

            foreach ($row['visits'] as $visit) {
                $jobOrder = JobOrder::create([
                    'motorcycle_id' => $motorcycle->id,
                    'mechanic_id' => $mechanics[$visit['mechanic'] - 1]->id,
                    'service_date' => now()->subDays($visit['days_ago'])->toDateString(),
                    'odometer_at_service' => $visit['odometer'],
                    'complaint' => $visit['complaint'],
                    'status' => $visit['status'] ?? 'released',
                    'labor_cost' => $visit['labor'],
                ]);
                $jobOrder->update(['job_number' => sprintf('JO-%05d', $jobOrder->id)]);

                $jobOrder->serviceTypes()->attach(
                    array_map(fn ($name) => $serviceTypes[$name], $visit['services'])
                );

                foreach ($visit['parts'] as [$code, $quantity]) {
                    JobOrderPart::create([
                        'job_order_id' => $jobOrder->id,
                        'part_id' => $parts[$code]->id,
                        'quantity' => $quantity,
                        'unit_price' => $parts[$code]->unit_price,
                    ]);
                }
            }
        }
    }
}
