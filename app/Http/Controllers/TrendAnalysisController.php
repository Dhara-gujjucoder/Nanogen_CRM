<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\CityManagement;
use App\Models\OrderManagement;
use App\Models\OrderManagementProduct;
use App\Models\DistributorsDealers;
use App\Models\Target;
use Carbon\Carbon;


use Illuminate\Support\Facades\DB;

class TrendAnalysisController extends Controller
{

    protected $order_management, $order_management_product, $dealer_distributor, $product, $target, $city;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        OrderManagement $order_management,
        DistributorsDealers $dealer_distributor,
        Product $product,
        Target $target,
        CityManagement $city,
        OrderManagementProduct $order_management_product
    ) {
        $this->middleware('auth');
        $this->order_management = $order_management;
        $this->dealer_distributor = $dealer_distributor;
        $this->product = $product;
        $this->order_management_product = $order_management_product;
        $this->target = $target;
        $this->city = $city;
    }

    /**
     * Display a listing of the resource.
     */
    public function product_report(Request $request)
    {
        $product_data       = $request->all();
        $data['page_title'] = 'Trend Analysis';
        $data['products']   = $this->product->where('status', 1)->get();
        $data['citys']      = $this->city->where('status', 1)->get();
        $product_id         = isset($product_data['product_id']) ? $product_data['product_id'] : null;
        $city_id            = isset($product_data['city_id']) ? $product_data['city_id'] : null;
        $from_date          = isset($product_data['start_date']) ? Carbon::createFromFormat('d-m-Y', $product_data['start_date'])->format('Y-m-d')  : null;
        $to_date            = isset($product_data['end_date']) ? Carbon::createFromFormat('d-m-Y', $product_data['end_date'])->format('Y-m-d') : null;

        $query = OrderManagementProduct::selectRaw('COUNT(DISTINCT order_id) as number_of_orders, SUM(total) as revenue,SUM(qty) as gqty')
            ->join('order_management', 'order_management_products.order_id', '=', 'order_management.id')
            ->where('product_id', $product_id)
            ->when($city_id, function ($q) use ($city_id) {
                $q->whereHas('order.distributors_dealers', function ($q2) use ($city_id) {
                    $q2->where('city_id', $city_id);
                });
            })
            ->when($from_date, fn($q) => $q->whereDate('order_management.order_date', '>=', $from_date))
            ->when($to_date, fn($q) => $q->whereDate('order_management.order_date', '<=', $to_date))
            ->first();

        $query2 = OrderManagementProduct::query()
            ->when($city_id, function ($q) use ($city_id) {
                $q->whereHas('order.distributors_dealers', function ($q2) use ($city_id) {
                    $q2->where('city_id', $city_id);
                });
            })
            ->when($from_date, fn($q) => $q->whereDate('order_management.order_date', '>=', $from_date))
            ->when($to_date, fn($q) => $q->whereDate('order_management.order_date', '<=', $to_date))
            ->join('order_management', 'order_management_products.order_id', '=', 'order_management.id')
            ->select(
                'order_management_products.packing_size_id',
                'variation_options.value as packing_size_name',
                'variation_options.unit as packing_size_unit',
                DB::raw('SUM(order_management_products.qty) as total_qty'),
                // DB::raw('COUNT(DISTINCT order_id) as number_of_orders')

            )
            ->join('variation_options', 'order_management_products.packing_size_id', '=', 'variation_options.id')

            ->where('order_management_products.product_id', $product_id)
            ->groupBy('order_management_products.packing_size_id', 'variation_options.value', 'variation_options.unit')
            ->get();


        $data['number_of_orders'] = $query->number_of_orders;
        $data['revenue']          = $query->revenue;
        $data['variation_qty']    = $query2;
        $data['city_id']          = $city_id;

        // chart data

        $city_wise_chart = OrderManagement::query()
            ->join('order_management_products', 'order_management.id', '=', 'order_management_products.order_id')
            ->join('distributors_dealers', 'distributors_dealers.id', '=', 'order_management.dd_id')
            ->join('city_management', 'city_management.id', '=', 'distributors_dealers.city_id')
            ->when($city_id, function ($q) use ($city_id) {
                $q->whereHas('distributors_dealers', function ($q2) use ($city_id) {
                    $q2->where('city_id', $city_id);
                });
            })
            ->where('order_management_products.product_id', $product_id)
            ->when($from_date, fn($q) => $q->whereDate('order_management.order_date', '>=', $from_date))
            ->when($to_date, fn($q) => $q->whereDate('order_management.order_date', '<=', $to_date))
            ->select(
                'city_management.city_name as city_name',
                'city_management.id as city_id',
                DB::raw('SUM(order_management_products.qty) as total_qty'),
                DB::raw('SUM(order_management_products.total) as amount'),
            )
            ->groupBy('city_management.city_name', 'city_management.id')
            ->get();

        foreach ($city_wise_chart as $key => $city) {
            $unit_totals = OrderManagementProduct::query()
                ->join('order_management', 'order_management.id', '=', 'order_management_products.order_id')
                ->join('variation_options', 'order_management_products.packing_size_id', '=', 'variation_options.id')
                ->join('distributors_dealers', 'distributors_dealers.id', '=', 'order_management.dd_id')
                ->where('order_management_products.product_id', $product_id)
                ->where('distributors_dealers.city_id', $city->city_id)
                ->when($from_date, fn($q) => $q->whereDate('order_management.order_date', '>=', $from_date))
                ->when($to_date, fn($q) => $q->whereDate('order_management.order_date', '<=', $to_date))
                ->select(
                    'variation_options.unit',
                    DB::raw('SUM(order_management_products.qty * variation_options.value) as unit_total')
                )
                ->groupBy('variation_options.unit')
                ->get();

            $city_wise_chart[$key]['unit_totals'] = $unit_totals->map(fn($u) => [
                'unit' => $u->unit,
                'total' => $u->unit_total,
            ]);
            // $city_wise_chart[$key]['unit_totals2']  = $unit_totals->map(fn($u) => " {$u->unit_total} {$u->unit}")->implode(' ');

            // $city_wise_chart[$key]['unit_totals2'] = $unit_totals->map(function ($u) {
            //     return number_format($u->unit_total) . ' ' . $u->unit;
            // })->implode(' ');

            $city_wise_chart[$key]['unit_totals2'] = $unit_totals->map(function ($u) {
                $formatted = number_format($u->unit_total) . ' ' . $u->unit;

                // Add tonne if unit is kg
                if (strtolower($u->unit) === 'kg') {
                    $tonnes = $u->unit_total / 1000;
                    $formatted .= ' → ' . number_format($tonnes, 2) . ' Tonne';
                }

                return $formatted;
            })->implode("\n");
        }
        $data['city_wise_chart'] = $city_wise_chart;

        return view('admin.trend_analysis.product_report', $data);
    }
}
