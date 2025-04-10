<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\DealershipCompanies;
use App\Models\DistributorsDealers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\ProprietorPartnerDirector;

class DistributorsDealersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // dd($request->dealer)
        $data['page_title'] =  $request->dealer == 1 ? 'Dealers' : 'Distributors';
        if ($request->ajax()) {

            $data = DistributorsDealers::where('user_type', $request->dealer ? 2 : 1);

            return DataTables::of($data)
                ->addIndexColumn()
                // ->addColumn('checkbox', function ($row) {
                //     return '<label class="checkboxs">
                //             <input type="checkbox" class="checkbox-item grade_checkbox" data-id="' . $row->id . '">
                //             <span class="checkmarks"></span>
                //         </label>';
                // })
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="' . route('distributors_dealers.edit', $row->id) . '" class="dropdown-item"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"><i class="ti ti-edit text-warning"></i> Edit</a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item delete_d_d"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"> <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '</a><form action="' . route('distributors_dealers.destroy', $row->id) . '" method="post" class="delete-form" id="delete-form-' . $row->id . '" >'
                        . csrf_field() . method_field('DELETE') . '</form>';

                    $action_btn = '<div class="dropdown table-action">
                                             <a href="#" class="action-icon " data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                             <div class="dropdown-menu dropdown-menu-right">';

                    Auth::user()->can('manage users') ? $action_btn .= $edit_btn : '';
                    Auth::user()->can('manage users') ? $action_btn .= $delete_btn : '';
                    return $action_btn . ' </div></div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.distributors_dealers.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['page_title'] = 'Create Distributors and Dealers';
        $data['products'] = Product::where('status', 1)->get()->all();
        return view('admin.distributors_dealers.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $d_d = new DistributorsDealers();
        $d_d->fill($request->all());

        if ($request->hasFile('profile_image')) {
            $file     = $request->file('profile_image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('distributor_dealer_profile_image', $filename, 'public'); /* Save to storage/app/public/distributor_dealer_profile_image */
            $d_d->profile_image = $filename;
        }
        $d_d->save();

        if ($request->has(['company_name', 'product_id', 'quantity', 'company_remarks'])) {
            $company_name    = $request->input('company_name');
            $product_id      = $request->input('product_id');
            $quantity        = $request->input('quantity');
            $company_remarks = $request->input('company_remarks');

            foreach ($company_name as $key => $company_name) {
                DealershipCompanies::create([
                    'dd_id'           => $d_d->id,
                    'company_name'    => $company_name,
                    'product_id'      => $product_id[$key],
                    'quantity'        => $quantity[$key],
                    'company_remarks' => $company_remarks[$key],
                ]);
            }
        }

        if ($request->has(['name', 'birthdate', 'address'])) {
            $name      = $request->input('name');
            $birthdate = $request->input('birthdate');
            $address   = $request->input('address');

            foreach ($name as $key => $name) {
                ProprietorPartnerDirector::create([
                    'dd_id'     => $d_d->id,
                    'name'      => $name,
                    'birthdate' => $birthdate[$key],
                    'address'   => $address[$key],
                ]);
            }
        }

        return redirect()->route('distributors_dealers.index')->with('success', 'Record created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $distributor_dealers = DistributorsDealers::findOrFail($id);
        $data = [
            'page_title'          => 'Edit Distributors and Dealers',
            'distributor_dealers' => $distributor_dealers,
            'products'            => Product::where('status', 1)->get()->all(),
        ];
        return view('admin.distributors_dealers.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $d_d = DistributorsDealers::findOrFail($id);
        $d_d->update($request->all());

        if ($request->hasFile('profile_image')) {
            if ($d_d->profile_image) {
                Storage::disk('public')->delete('distributor_dealer_profile_image/' . $d_d->profile_image);
            }

            $file     = $request->file('profile_image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('distributor_dealer_profile_image', $filename, 'public');
            /** Save to storage/app/public/product_images **/
            $d_d->profile_image = $filename;
            $d_d->save();
        }

        if ($request->has(['company_name', 'product_id', 'quantity', 'company_remarks'])) {
            DealershipCompanies::where('dd_id', $id)->delete();
            $company_name    = $request->input('company_name');
            $product_id      = $request->input('product_id');
            $quantity        = $request->input('quantity');
            $company_remarks = $request->input('company_remarks');

            foreach ($company_name as $key => $company_name) {
                if (!empty($company_name) || !empty($product_id[$key]) || !empty($quantity[$key]) || !empty($company_remarks[$key])) {
                    DealershipCompanies::create([
                        'dd_id'           => $d_d->id,
                        'company_name'    => $company_name,
                        'product_id'      => $product_id[$key],
                        'quantity'        => $quantity[$key],
                        'company_remarks' => $company_remarks[$key],
                    ]);
                }
            }
        }

        if ($request->has(['name', 'birthdate', 'address'])) {
            ProprietorPartnerDirector::where('dd_id', $id)->delete();
            $names      = $request->input('name');
            $birthdate = $request->input('birthdate');
            $address   = $request->input('address');

            foreach ($names as $key => $name) {
                if (!empty($name) || !empty($birthdate[$key]) || !empty($address[$key])) {
                    ProprietorPartnerDirector::create([
                        'dd_id'     => $d_d->id,
                        'name'      => $name,
                        'birthdate' => $birthdate[$key],
                        'address'   => $address[$key],
                    ]);
                }
            }
        }
        return redirect()->route('distributors_dealers.index')->with('success', 'Record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $d_d = DistributorsDealers::findOrFail($id);
        DealershipCompanies::where('dd_id', $id)->delete();
        ProprietorPartnerDirector::where('dd_id', $id)->delete();
        if ($d_d->profile_image) {
            Storage::disk('public')->delete('distributor_dealer_profile_image/' . $d_d->profile_image);
        }
        $d_d->delete();
        return redirect()->route('distributors_dealers.index')->with('success', 'Record deleted successfully!');
    }
}
