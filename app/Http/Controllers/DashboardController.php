<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\Motorcycle;
use App\Models\Part;
use App\Services\ServiceDueReport;

/** The home page after login: numbers, service reminders, open work and low stock. */
class DashboardController extends Controller
{
    public function __invoke(ServiceDueReport $report)
    {
        // Only services that need attention (overdue or due soon), most urgent first.
        $reminders = array_values(array_filter(
            $report->dueRows(),
            fn ($row) => $row['status'] !== 'ok'
        ));

        return view('dashboard.index', [
            'customerCount' => Customer::count(),
            'motorcycleCount' => Motorcycle::count(),
            'openJobOrders' => JobOrder::whereIn('status', ['pending', 'ongoing'])
                ->with(['motorcycle.customer', 'motorcycle.motorcycleModel', 'mechanic'])
                ->orderBy('service_date')
                ->get(),
            'lowStockParts' => Part::lowStock()->orderBy('name')->get(),
            'overdueCount' => count(array_filter($reminders, fn ($row) => $row['status'] === 'overdue')),
            'dueSoonCount' => count(array_filter($reminders, fn ($row) => $row['status'] === 'due_soon')),
            'reminders' => array_slice($reminders, 0, 12),
            'totalReminders' => count($reminders),
        ]);
    }
}
