<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\OrderManagement;
use Yajra\DataTables\DataTables;
use App\Models\SalesPersonDetail;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\DistributorsDealers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\OrderManagementProduct;

class OrderManagementController extends Controller
{

    public function __construct() {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $role = Role::findByName('sales'); // or 'sales_manager', etc.
        // $role->givePermissionTo('manage orders');

        $data['page_title'] = 'Order Management';
        if ($request->ajax()) {
            $records = OrderManagement::query();

            // Apply salesman filter if user has sales role
            if (auth()->user()->hasRole('sales')) {
                $records->where('salesman_id', auth()->id());
            }

            
                $records->when($request->salemn_id, function ($query) use ($request) {
                    $query->where('salesman_id', $request->salemn_id);
                });

                $records->when($request->start_date && $request->end_date, function ($sub) use ($request) {
                    $startDate = Carbon::createFromFormat('d-m-Y', $request->start_date)->format('Y-m-d');
                    $endDate = Carbon::createFromFormat('d-m-Y', $request->end_date)->format('Y-m-d');
                    $sub->whereBetween('order_date', [$startDate, $endDate]);
                });

                return DataTables::of($records)
                    ->addIndexColumn()
                    ->addColumn('checkbox', function ($row) {
                        return '<label class="checkboxs">
                            <input type="checkbox" class="checkbox-item order_checkbox" data-id="' . $row->id . '">
                            <span class="checkmarks"></span>
                        </label>';
                    })
                    ->addColumn('action', function ($row) {
                        $edit_btn = '<a href="' . route('order_management.edit', $row->id) . '" class="dropdown-item"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"><i class="ti ti-edit text-warning"></i> Edit</a>';

                        $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteOrder"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"> <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '</a><form action="' . route('order_management.destroy', $row->id) . '" method="post" class="delete-form" id="delete-form-' . $row->id . '" >'
                            . csrf_field() . method_field('DELETE') . '</form>';

                        $action_btn = '<div class="dropdown table-action">
                                             <a href="#" class="action-icon " data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                             <div class="dropdown-menu dropdown-menu-right">';

                        // Auth::user()->can('manage orders') ? $action_btn .= $edit_btn : '';
                        // Auth::user()->can('manage orders') ? $action_btn .= $delete_btn : '';
                        $action_btn .= $edit_btn;
                        $action_btn .= $delete_btn;

                        return $action_btn . ' </div></div>';
                    })
                    ->editColumn('order_date', function ($row) {
                        // return $row->order_date->format('d-m-Y');
                        return Carbon::parse($row->order_date)->format('d M Y');
                    })
                    ->editColumn('dd_id', function ($row) {
                        if ($row->distributors_dealers) {
                            $type = $row->distributors_dealers->user_type == 1 ? '(Distributor)' : ($row->distributors_dealers->user_type == 2 ? '(Dealer)' : '');
                            return $row->distributors_dealers->applicant_name . ' ' . $type;
                        }
                        return '-';
                    })
                    ->addColumn('firm_shop_name', function ($row) {
                        if ($row->distributors_dealers) {
                            $firmShopName = $row->distributors_dealers->firm_shop_name ?? '-';
                            $url = route('order_management.edit', $row->id);
                            return '<a href="' . $url . '">' . e($firmShopName) . '</a>';
                        }
                        return '-';
                    })
                    ->addColumn('unique_ord_id', function ($row) {
                        $url = route('order_management.edit', $row->id);
                        return '<a href="' . $url . '">' . e($row->unique_order_id) . '</a>';
                    })


                    ->editColumn('city', function ($row) {
                        if ($row->distributors_dealers) {
                            return $row->distributors_dealers->city->city_name ?? '-';
                        }
                        return '-';
                    })

                    ->editColumn('salesman_id', function ($row) {
                        if ($row->sales_person_detail) {
                            return $row->sales_person_detail->first_name . ' ' . $row->sales_person_detail->last_name;
                        }
                        return '-';
                    })
                    ->editColumn('grand_total', function ($row) {
                        if ($row->grand_total) {
                            return IndianNumberFormat($row->grand_total);
                        }
                        return '-';
                    })

                    ->addColumn('order_status', function ($row) {
                        $order_status = '';

                        if ($row->status < 1) {
                            $order_status .= '<a href="javascript:void(0)" class="dropdown-item change-status" data-id="' . $row->id . '" data-status="1">
                                            <span class="badge bg-warning">Pending</span>
                                          </a>';
                        }
                        if ($row->status < 2) {
                            $order_status .= '<a href="javascript:void(0)" class="dropdown-item change-status" data-id="' . $row->id . '" data-status="2">
                                            <span class="badge bg-warning">Processing</span>
                                          </a>';
                        }
                        if ($row->status < 3) {
                            $order_status .= '<a href="javascript:void(0)" class="dropdown-item change-status" data-id="' . $row->id . '" data-status="3">
                                            <span class="badge bg-info">Shipping</span>
                                          </a>';
                        }
                        if ($row->status < 4) {
                            $order_status .= '<a href="javascript:void(0)" class="dropdown-item change-status" data-id="' . $row->id . '" data-status="4">
                                            <span class="badge bg-success">Delivered</span>
                                          </a>';
                        }

                        if ($row->status < 4 && Auth::user()->hasAnyRole(['admin', 'staff'])) { // Auth::user()->can('manage users')
                            $action_btn = '<div class="dropdown table-action order_drpdown">' . $row->statusBadge() . '
                                        <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-pencil"></i></a>
                                        <div class="dropdown-menu dropdown-menu-right">' . $order_status . '</div>
                                      </div>';
                            return $action_btn;
                        }

                        return $row->statusBadge();
                    })
                    ->filterColumn('order_status', function ($query, $keyword) {
                        $statuses = ['pending' => 1, 'processing' => 2, 'shipping' => 3, 'delivered' => 4, 'inactive' => 0];
                        // Find the matching status key (case-insensitive)
                        foreach ($statuses as $label => $value) {
                            if (stripos($label, $keyword) !== false) {
                                return $query->where('status', $value);
                            }
                        }
                        // If not matched, prevent any result
                        return $query->whereRaw('0 = 1');
                    })
                    ->filterColumn('dd_id', function ($query, $keyword) {
                        $query->whereHas('distributors_dealers', function ($q) use ($keyword) {
                            $q->where('applicant_name', 'like', "%{$keyword}%")
                                ->orWhereRaw("CASE 
                              WHEN user_type = 1 THEN 'Distributor'
                              WHEN user_type = 2 THEN 'Dealer'
                              ELSE ''
                          END LIKE ?", ["%{$keyword}%"]);
                        });
                    })
                    ->filterColumn('salesman_id', function ($query, $keyword) {
                        $query->whereHas('sales_person_detail', function ($q) use ($keyword) {
                            // $q->where('first_name', 'like', "%{$keyword}%");
                            $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                        });
                    })
                    ->filterColumn('order_date', function ($query, $keyword) {
                        $query->whereRaw("DATE_FORMAT(order_date, '%d-%m-%Y') LIKE ?", ["%{$keyword}%"]);
                    })
                    ->filterColumn('city', function ($query, $keyword) {
                        $query->whereHas('distributors_dealers.city', function ($q) use ($keyword) {
                            $q->where('city_name', 'like', "%{$keyword}%");
                        });
                    })
                    ->rawColumns(['checkbox', 'action', 'order_status', 'firm_shop_name', 'unique_ord_id']) //'value',
                    ->make(true);
            }
        

        return view('admin.order_management.index', $data);
    }

    public function order_status(Request $request, string $id)
    {
        $order = OrderManagement::findOrFail($id);
        $order->status = $request->status;
        if ($request->status == 3) {
            $order->shipping_date = Carbon::now();
        }
        $order->save();

        try {
            if($request->status == 3)
            {
                $admin_email = getSetting('company_email');
                if($admin_email)
                {
                    $order = [];
                    $order = OrderManagement::with(['distributors_dealers', 'sales_person_detail'])->findOrFail($id);
                    $order->admin_email = 'for_admin_email';
                    Mail::send('email.order_email.order_shipping_status', compact('order'), fn($message) => $message->to($admin_email)->subject('Order Shipped'));
                }


                $sales_person_email = $order->sales_person_detail->user->email;
                if($sales_person_email) {
                    $order = [];
                    $order = OrderManagement::with(['distributors_dealers', 'sales_person_detail'])->findOrFail($id);
                    Mail::send('email.order_email.order_shipping_status', compact('order'), fn($message) => $message->to($sales_person_email)->subject('Order Shipped'));
                }
            }
        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['error' => 'Something went wrong!'], 500); 
        }

        return response()->json(['success' => true]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['page_title'] = 'Create Order';
        $data['products'] = Product::where('status', 1)->get();
        $data['distributor_dealers'] = DistributorsDealers::get();
        $data['salesmans'] = SalesPersonDetail::where('deleted_at', NULL)->get();

        $latest_order_id = OrderManagement::withTrashed()->max('id');
        $next_id = $latest_order_id ? $latest_order_id + 1 : 1;
        $data['unique_order_id'] = 'ORD' . str_pad($next_id, max(6, strlen($next_id)), '0', STR_PAD_LEFT);

        return view('admin.order_management.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $latest_order_id = OrderManagement::withTrashed()->max('id');
        $order = OrderManagement::create($request->only([
            'dd_id',
            'order_date',
            'mobile_no',
            'salesman_id',
            'transport',
            'freight',
            'gst_no',
            'address',
            'total_order_amount',
            'gst',
            'gst_amount',
            'grand_total'
        ]));

        $next_id = $latest_order_id ? $latest_order_id + 1 : 1;
        $data['unique_order_id'] = 'ORD' . str_pad($next_id, max(6, strlen($next_id)), '0', STR_PAD_LEFT);
        $order->unique_order_id = $data['unique_order_id'];
        $order->save();



        if ($request->has(['product_id', 'price', 'qty', 'packing_size_id', 'total'])) {
            $product_id = $request->input('product_id');
            $price = $request->input('price');
            $qty = $request->input('qty');
            $packing_size_id = $request->input('packing_size_id');
            $total = $request->input('total');
            // $grand_total     = 0;

            foreach ($product_id as $key => $p) {

                if (isset($price[$key]) && isset($qty[$key]) && isset($packing_size_id[$key]) && isset($total[$key])) {
                    // $grand_total = $grand_total + $total[$key];
                    OrderManagementProduct::create([
                        'order_id'   => $order->id,
                        'product_id' => $p,
                        'price'      => $price[$key],
                        'qty'        => $qty[$key],
                        'packing_size_id' => $packing_size_id[$key],
                        'total'      => $total[$key],
                    ]);
                }
            }

            // $order->grand_total = $grand_total;
            $order->status = 1;
            $order->save();
        }


        // try {
        //     $order = OrderManagement::with(['distributors_dealers', 'sales_person_detail', 'products'])->findOrFail($order->id);

        // } catch (\Throwable $th) {
        //     dd($th);
        //     return response()->json(['error' => 'Something went wrong!'], 500); 
        //     // return redirect()->back()->with('error', 'Something is wrong!!');
        // }


        if(getSetting('company_email'))
        {
            Mail::send('email.order_email.order_create', compact('order'), fn($message) => $message->to(getSetting('company_email'))->subject('Order Created'));
        }
        return redirect()->route('order_management.index')->with('success', 'Order created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $order = OrderManagement::findOrFail($id);
        $data = [
            'page_title' => 'Edit Order',
            'order' => $order,
            'products' => Product::get(),
            'distributor_dealers' => DistributorsDealers::get(),
            'salesmans' => SalesPersonDetail::where('deleted_at', NULL)->get(),
        ];
        return view('admin.order_management.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = OrderManagement::findOrFail($id);

        $order->update($request->only(['dd_id', 'order_date', 'mobile_no', 'salesman_id', 'transport', 'freight', 'gst_no', 'address', 'total_order_amount', 'gst', 'gst_amount', 'grand_total']));

        if ($request->only(['product_id', 'price', 'qty', 'packing_size_id', 'total'])) {

            OrderManagementProduct::where('order_id', $id)->delete();

            $product_id = $request->input('product_id');
            $price = $request->input('price');
            $qty = $request->input('qty');
            $packing_size_id = $request->input('packing_size_id');
            $total = $request->input('total');
            foreach ($product_id as $key => $p) {

                if (isset($price[$key]) && isset($qty[$key]) && isset($packing_size_id[$key]) && isset($total[$key])) {
                    // $grand_total = $grand_total + $total[$key]; 
                    OrderManagementProduct::create([
                        'order_id' => $order->id,
                        'product_id' => $p,
                        'packing_size_id' => $packing_size_id[$key],
                        'price' => $price[$key],
                        'qty' => $qty[$key],
                        'total' => $total[$key],
                    ]);
                }
            }
            // $order->grand_total = $grand_total;
            // $order->save();
        }
        return redirect()->route('order_management.index')->with('success', 'Order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = OrderManagement::findOrFail($id);
        OrderManagementProduct::where('order_id', $id)->delete();
        $order->delete();
        return redirect()->route('order_management.index')->with('success', 'Order deleted successfully!');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (!empty($ids)) {
            $order = OrderManagement::whereIn('id', $ids)->delete();
            OrderManagementProduct::whereIn('order_id', $ids)->delete();

            return response()->json(['message' => 'Selected orders deleted successfully!']);
        }
        return response()->json(['message' => 'No records selected!'], 400);
    }
}
