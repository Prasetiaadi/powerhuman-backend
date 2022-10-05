<?php

namespace App\Http\Controllers\API;

use App\Models\Company;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function fetch(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $limit = $request->input('limit', 10);
    // get single data
        if ($id) {
            $company = Company::whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
            })->with(['users'])->find($id);

            if ($company) {
                return ResponseFormatter::success($company, 'company found');
            }
            return ResponseFormatter::error('Company not found', 404);
        }
    // Get multiple data
        $companies = Company::with(['users'])->whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
        });

        if ($name) {
            $companies->where('name', 'like', '%' . $name . '%');
        }

        return ResponseFormatter::success(
            $companies->paginate($limit),
            'companies found'
        );
    }

    public function create(CreateCompanyRequest $request)
    {
        try {
            if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/logos');
        }
        $company = Company::create([
            'name' => $request->name,
            'logo' => $path,
        ]);

        if (!$company) {
            throw new Exception('Company not created');
        }

        $user = User::find(Auth::id());
        $user->companies()->attach($company->id);

        $company->load('users');

        return ResponseFormatter::success($company, 'Company created');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }

    public function update(UpdateCompanyRequest $request, $id)
    {
        try {
            // Get company
            $company = Company::find($id);

            // Check if company exist
            if (!$company) {
                throw new Exception("Company not found");
            }

            // Upload logo
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('public/logos');
            }

            // Upload company
            $company->update([
                'name'=>$request->name,
                'logo'=>$path
            ]);

            return ResponseFormatter::success($company, 'Company update');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }
}
