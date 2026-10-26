<?php

namespace App\Http\Controllers;

use App\Services\ServiceDueReport;
use Illuminate\Support\Facades\DB;

/** Reports for the study's objective 5: sales, services, inventory usage, customers, estimated vs actual dates. */
class ReportController extends Controller
{
    public function index(ServiceDueReport $report)
    {
        // Finished job orders only (completed or released).
        $finished = fn () => DB::table('job_orders')
            ->join('v_job_order_totals as totals', 'totals.job_order_id', '=', 'job_orders.id')
            ->whereIn('job_orders.status', ['completed', 'released']);

        $salesPerDay = $finished()
            ->where('job_orders.service_date', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('job_orders.service_date as day, count(*) as jobs, sum(totals.grand_total) as revenue')
            ->groupBy('job_orders.service_date')
            ->orderByDesc('job_orders.service_date')
            ->get();

        $topCustomers = $finished()
            ->join('motorcycles', 'motorcycles.id', '=', 'job_orders.motorcycle_id')
            ->join('customers', 'customers.id', '=', 'motorcycles.customer_id')
            ->selectRaw('customers.full_name as name, count(*) as jobs, sum(totals.grand_total) as spent')
            ->groupBy('customers.id', 'customers.full_name')
            ->orderByDesc('spent')
            ->limit(5)
            ->get();

        $topServices = DB::table('job_order_services')
            ->join('service_types', 'service_types.id', '=', 'job_order_services.service_type_id')
            ->selectRaw('service_types.name as name, count(*) as times')
            ->groupBy('service_types.name')
            ->orderByDesc('times')
            ->limit(6)
            ->get();

        $partsUsage = DB::table('job_order_parts')
            ->join('parts', 'parts.id', '=', 'job_order_parts.part_id')
            ->selectRaw('parts.name as name, sum(job_order_parts.quantity) as qty, sum(job_order_parts.quantity * job_order_parts.unit_price) as value')
            ->groupBy('parts.name')
            ->orderByDesc('qty')
            ->limit(6)
            ->get();

        return view('reports.index', [
            'salesPerDay' => $salesPerDay,
            'salesTotal' => $salesPerDay->sum('revenue'),
            'topCustomers' => $topCustomers,
            'topServices' => $topServices,
            'partsUsage' => $partsUsage,
            'accuracy' => $report->accuracy(),
        ]);
    }
}
