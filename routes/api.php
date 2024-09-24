<?php

use App\Http\Controllers\{AdminController, BankController, BrandController,ClientController,EmployeeController, ExpenseCategoryController, ExpenseController, JobController, JobServiceReportController, ProductController, PurchaseOrderController, QuoteController, ServiceController,SupplierController, TermsAndConditionController, TreatmentMethodController, UserAuthController, VehicleController, VehicleExpenseController, VendorController};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login',[UserAuthController::class,'login']);


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
  
    // Employee
    Route::get('employee/{id?}',[EmployeeController::class,'index']);
    Route::post('employee/create',[EmployeeController::class,'store']);
    Route::post('employee/stock/assign',[EmployeeController::class,'assignStock']);
    Route::get('employee/sales_manager/get',[EmployeeController::class,'getSalesManager']);

    // Vendors
    Route::get('vendor/{id?}',[VendorController::class,'index']);
    Route::post('vendor/create',[VendorController::class,'store']);

    // Brands
    Route::get('brand/{id?}',[BrandController::class,'index']);
    Route::post('brand/create',[BrandController::class,'store']);
    Route::post('brand/update/{id}',[BrandController::class,'update']);

    // Suppliers
    Route::get('supplier/{id?}',[SupplierController::class,'index']);
    Route::post('supplier/create',[SupplierController::class,'store']);
    Route::post('supplier/add_payment',[SupplierController::class,'addPayment']);
    Route::get('supplier/ledger/get/{id?}',[SupplierController::class,'getSupplierLedger']);

    // Services
    Route::get('service/{id?}',[ServiceController::class,'index']);
    Route::post('service/create',[ServiceController::class,'store']);
    Route::post('service/update/{id}',[ServiceController::class,'update']);

    // Clients & Addresses
    Route::get('client/{id?}',[ClientController::class,'index']);
    Route::get('client/references/get',[ClientController::class,'getReference']);
    Route::post('client/create',[ClientController::class,'storeClient']);
    Route::post('client/address/create',[ClientController::class,'storeClientAddress']);
    Route::post('client/address/update/{id}',[ClientController::class,'updateClientAddress']);

    // Products
    Route::get('product/{id?}',[ProductController::class,'index']);
    Route::post('product/create',[ProductController::class,'store']);
    Route::get('product/stock/get/{id?}',[ProductController::class,'getProductStok']);

    // Vehicle
    Route::get('vehicle/{id?}',[VehicleController::class,'index']);
    Route::post('vehicle/create',[VehicleController::class,'store']);
    Route::post('vehicle/update/{id}',[VehicleController::class,'update']);

    // Banks
    Route::get('bank/{id?}',[BankController::class,'index']);
    Route::post('bank/create',[BankController::class,'store']);
    Route::post('bank/update/{id}',[BankController::class,'update']);

    // Expense Category
    Route::get('expense_category/{id?}',[ExpenseCategoryController::class,'index']);
    Route::post('expense_category/create',[ExpenseCategoryController::class,'store']);
    Route::post('expense_category/update/{id}',[ExpenseCategoryController::class,'update']);
    
    // Expense
    Route::get('expense/{id?}',[ExpenseController::class,'index']);
    Route::post('expense/create',[ExpenseController::class,'store']);

    // Vehicle Expense
    Route::get('vehicle_expense/{id?}',[VehicleExpenseController::class,'index']);
    Route::post('vehicle_expense/create',[VehicleExpenseController::class,'store']);

    // Purchase Orders
    Route::get('purchase_order/{id?}',[PurchaseOrderController::class,'index']);
    Route::post('purchase_order/create',[PurchaseOrderController::class,'store']);

    //Company or Admin
    Route::get('admin/ledger/get/{id?}',[AdminController::class,'getAdminLedger']);
    Route::get('admin/dashboard',[AdminController::class,'getAdminDashboard']);

    // Terms And Condition
    Route::get('terms_and_condition/{id?}',[TermsAndConditionController::class,'index']);
    Route::post('terms_and_condition/create',[TermsAndConditionController::class,'store']);
    Route::post('terms_and_condition/update/{id}',[TermsAndConditionController::class,'update']);

    // Treatment Method
    Route::get('treatment_method/{id?}',[TreatmentMethodController::class,'index']);
    Route::post('treatment_method/create',[TreatmentMethodController::class,'store']);
    Route::post('treatment_method/update/{id}',[TreatmentMethodController::class,'update']);

    //Quote & Contracts
    Route::get('quote/{id?}',[QuoteController::class,'index']);
    Route::post('quote/manage',[QuoteController::class,'manage']);
    Route::get('quote/move/contract/{id}',[QuoteController::class,'moveToContract']);

    //Jobs
    Route::get('job/{id?}',[JobController::class,'index']);
    Route::post('job/create',[JobController::class,'store']);
    Route::post('job/reschedule',[JobController::class,'rescheduleJob']);
    Route::post('job/sales_manager/assign',[JobController::class,'assignJob']);
    Route::get('job/move/complete/{id}',[JobController::class,'moveToComplete']);

    Route::get('job/service_report/{id}',[JobServiceReportController::class,'index']);
    Route::post('job/service_report/create',[JobServiceReportController::class,'store']);


    Route::post('logout',[UserAuthController::class,'logout']);
});

