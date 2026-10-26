<?php

namespace Database\Seeders;

use App\Models\MotorcycleModel;
use App\Models\Part;
use App\Models\Role;
use App\Models\ServiceInterval;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

/** Reference data. The values live in database/seeders/data/catalog.php so they are easy to edit. */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require database_path('seeders/data/catalog.php');

        foreach ($catalog['roles'] as $role) {
            Role::create($role);
        }

        $serviceTypes = [];
        foreach ($catalog['service_types'] as $name => $description) {
            $serviceTypes[$name] = ServiceType::create(['name' => $name, 'description' => $description]);
        }

        foreach ($catalog['models'] as $row) {
            $model = MotorcycleModel::create([
                'brand' => $row['brand'],
                'name' => $row['name'],
                'engine_cc' => $row['engine_cc'],
            ]);

            // Start from the group's intervals, then apply the model's own overrides.
            $intervals = $catalog['intervals'][$row['type']];
            foreach ($catalog['interval_overrides'][$model->label()] ?? [] as $service => $values) {
                $intervals[$service] = $values;
            }

            foreach ($intervals as $service => [$km, $months]) {
                ServiceInterval::create([
                    'motorcycle_model_id' => $model->id,
                    'service_type_id' => $serviceTypes[$service]->id,
                    'interval_km' => $km,
                    'interval_months' => $months,
                ]);
            }
        }

        foreach ($catalog['parts'] as [$code, $name, $price, $stock, $reorder]) {
            Part::create([
                'part_code' => $code,
                'name' => $name,
                'unit_price' => $price,
                'stock_qty' => $stock,
                'reorder_level' => $reorder,
            ]);
        }
    }
}
