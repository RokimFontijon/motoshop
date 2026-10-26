<?php

namespace Tests\Concerns;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\Motorcycle;
use App\Models\MotorcycleModel;
use App\Models\Part;
use App\Models\Role;
use App\Models\ServiceInterval;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Small helpers that create test data, so each test stays short and easy to read. */
trait CreatesShopData
{
    private int $counter = 0;

    protected function makeUser(string $role = 'mechanic', array $attributes = []): User
    {
        $this->counter++;
        $roleModel = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);

        return User::create(array_merge([
            'name' => ucfirst($role).' '.$this->counter,
            'email' => $role.$this->counter.'@test.local',
            'password' => 'password123',
            'role_id' => $roleModel->id,
        ], $attributes));
    }

    protected function makeMotorcycle(array $attributes = []): Motorcycle
    {
        $this->counter++;
        $customer = Customer::create(['full_name' => 'Customer '.$this->counter, 'phone' => '0917 000 0000']);
        $model = MotorcycleModel::firstOrCreate(['brand' => 'Honda', 'name' => 'Click 125'], ['engine_cc' => 125]);

        return Motorcycle::create(array_merge([
            'customer_id' => $customer->id,
            'motorcycle_model_id' => $model->id,
            'plate_number' => 'TST '.(1000 + $this->counter),
            'year' => 2022,
            'color' => 'Red',
            'current_odometer' => 1000,
        ], $attributes));
    }

    protected function makeServiceType(string $name = 'Change Oil'): ServiceType
    {
        return ServiceType::firstOrCreate(['name' => $name]);
    }

    protected function makeInterval(Motorcycle $motorcycle, ServiceType $type, int $km = 3000, int $months = 3): ServiceInterval
    {
        return ServiceInterval::create([
            'motorcycle_model_id' => $motorcycle->motorcycle_model_id,
            'service_type_id' => $type->id,
            'interval_km' => $km,
            'interval_months' => $months,
        ]);
    }

    protected function makePart(string $code = 'OIL-1L', int $stock = 10, float $price = 280, int $reorder = 5): Part
    {
        return Part::create([
            'part_code' => $code,
            'name' => 'Part '.$code,
            'unit_price' => $price,
            'stock_qty' => $stock,
            'reorder_level' => $reorder,
        ]);
    }

    /** A job order that was done $daysAgo days ago. Pass service types to record what was done. */
    protected function makeJobOrder(Motorcycle $motorcycle, array $attributes = [], array $serviceTypes = [], int $daysAgo = 0): JobOrder
    {
        // Row level security (database/sql/01_db_objects.sql) protects job_orders even for the table
        // owner. A test that inserts rows directly must say "I am acting as an admin", exactly like
        // the DatabaseSeeder does. Real requests set this themselves (SetDatabaseSession middleware).
        DB::select("select set_config('app.role', 'admin', false)");

        $jobOrder = JobOrder::create(array_merge([
            'motorcycle_id' => $motorcycle->id,
            'mechanic_id' => null,
            'service_date' => now()->subDays($daysAgo)->toDateString(),
            'odometer_at_service' => $motorcycle->current_odometer,
            'complaint' => 'Test',
            'status' => 'released',
            'labor_cost' => 100,
        ], $attributes));

        $jobOrder->update(['job_number' => sprintf('JO-%05d', $jobOrder->id)]);
        $jobOrder->serviceTypes()->sync(collect($serviceTypes)->pluck('id')->all());

        return $jobOrder;
    }

    /** The form data of a valid new job order. */
    protected function jobOrderForm(Motorcycle $motorcycle, array $override = []): array
    {
        return array_merge([
            'motorcycle_id' => $motorcycle->id,
            'service_date' => now()->toDateString(),
            'odometer_at_service' => $motorcycle->current_odometer + 100,
            'status' => 'pending',
            'labor_cost' => 150,
            'complaint' => 'Change oil',
        ], $override);
    }
}
