<?php

use App\Http\Controllers\{AdminController, BankController, BrandController,ClientController,EmployeeController, ExpenseCategoryController, ExpenseController, JobController, JobServiceReportController, ProductController, PurchaseOrderController, QuoteController, ServiceController, ServiceInvoiceController, SupplierController, TermsAndConditionController, TreatmentMethodController, UserAuthController, VehicleController, VehicleExpenseController, VendorController};
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
Route::middleware(['auth:sanctum','permission'])->group(function () {
  
    // Employee
    Route::get('employee/{id?}',[EmployeeController::class,'index'])->name('employee');
    Route::post('employee/create',[EmployeeController::class,'store'])->name('employee.create');
    Route::post('employee/stock/assign',[EmployeeController::class,'assignStock'])->name('employee.stock.assign');
    Route::get('employee/sales_manager/get',[EmployeeController::class,'getSalesManager'])->name('employee.sales_manager.get');

    // Vendors
    Route::get('vendor/{id?}',[VendorController::class,'index'])->name('vendor');
    Route::post('vendor/create',[VendorController::class,'store'])->name('vendor.create');

    // Brands
    Route::get('brand/{id?}',[BrandController::class,'index'])->name('brand');
    Route::post('brand/create',[BrandController::class,'store'])->name('brand.create');
    Route::post('brand/update/{id}',[BrandController::class,'update'])->name('brand.update');

    // Suppliers
    Route::get('supplier/{id?}',[SupplierController::class,'index'])->name('supplier');
    Route::post('supplier/create',[SupplierController::class,'store'])->name('supplier.create');
    Route::post('supplier/add_payment',[SupplierController::class,'addPayment'])->name('supplier.add_payment');
    Route::get('supplier/ledger/get/{id?}',[SupplierController::class,'getSupplierLedger'])->name('supplier.ledger.get');

    // Services
    Route::get('service/{id?}',[ServiceController::class,'index'])->name('service');
    Route::post('service/create',[ServiceController::class,'store'])->name('service.create');
    Route::post('service/update/{id}',[ServiceController::class,'update'])->name('service.update');

    // Clients & Addresses
    Route::get('client/{id?}',[ClientController::class,'index'])->name('client');
    Route::get('client/references/get',[ClientController::class,'getReference'])->name('client.references.get');
    Route::post('client/create',[ClientController::class,'storeClient'])->name('client.create');
    Route::post('client/address/create',[ClientController::class,'storeClientAddress'])->name('client.address.create');
    Route::post('client/address/update/{id}',[ClientController::class,'updateClientAddress'])->name('client.address.update');
    Route::post('client/bank_info/add',[ClientController::class,'storeClientBankInfo'])->name('client.bank_info.add');
    Route::post('client/bank_info/update/{id}',[ClientController::class,'updateClientBankInfo'])->name('client.bank_info.update');

    // Products
    Route::get('product/{id?}',[ProductController::class,'index'])->name('product');
    Route::post('product/create',[ProductController::class,'store'])->name('product.create');
    Route::get('product/stock/get/{id?}',[ProductController::class,'getProductStok'])->name('product.stock.get');

    // Vehicle
    Route::get('vehicle/{id?}',[VehicleController::class,'index'])->name('vehicle');
    Route::post('vehicle/create',[VehicleController::class,'store'])->name('vehicle.create');
    Route::post('vehicle/update/{id}',[VehicleController::class,'update'])->name('vehicle.update');

    // Banks
    Route::get('bank/{id?}',[BankController::class,'index'])->name('bank');
    Route::post('bank/create',[BankController::class,'store'])->name('bank.create');
    Route::post('bank/update/{id}',[BankController::class,'update'])->name('bank.update');

    // Expense Category
    Route::get('expense_category/{id?}',[ExpenseCategoryController::class,'index'])->name('expense_category');
    Route::post('expense_category/create',[ExpenseCategoryController::class,'store'])->name('expense_category.create');
    Route::post('expense_category/update/{id}',[ExpenseCategoryController::class,'update'])->name('expense_category.update');
    
    // Expense
    Route::get('expense/{id?}',[ExpenseController::class,'index'])->name('expense');
    Route::post('expense/create',[ExpenseController::class,'store'])->name('expense.create');

    // Vehicle Expense
    Route::get('vehicle_expense/{id?}',[VehicleExpenseController::class,'index'])->name('vehicle_expense');
    Route::post('vehicle_expense/',[VehicleExpenseController::class,'store'])->name('vehicle_expense.create');

    // Purchase Orders
    Route::get('purchase_order/{id?}',[PurchaseOrderController::class,'index'])->name('purchase_order');
    Route::post('purchase_order/create',[PurchaseOrderController::class,'store'])->name('purchase_order.create');

    //Company or Admin
    Route::get('admin/ledger/get/{id?}',[AdminController::class,'getAdminLedger'])->name('admin.ledger.get');
    Route::get('admin/dashboard',[AdminController::class,'getAdminDashboard'])->name('admin.dashboard');

    // Terms And Condition
    Route::get('terms_and_condition/{id?}',[TermsAndConditionController::class,'index'])->name('terms_and_condition');
    Route::post('terms_and_condition/create',[TermsAndConditionController::class,'store'])->name('terms_and_condition.create');
    Route::post('terms_and_condition/update/{id}',[TermsAndConditionController::class,'update'])->name('terms_and_condition.update');

    // Treatment Method
    Route::get('treatment_method/{id?}',[TreatmentMethodController::class,'index'])->name('treatment_method');
    Route::post('treatment_method/create',[TreatmentMethodController::class,'store'])->name('treatment_method.create');
    Route::post('treatment_method/update/{id}',[TreatmentMethodController::class,'update'])->name('treatment_method.update');

    //Quote & Contracts
    Route::get('quote/{id?}',[QuoteController::class,'index'])->name('quote');
    Route::post('quote/manage',[QuoteController::class,'manage'])->name('quote.manage');
    Route::get('quote/move/contract/{id}',[QuoteController::class,'moveToContract'])->name('quote.move.contract');

    //Jobs
    Route::get('job/{id?}',[JobController::class,'index'])->name('job');
    Route::post('job/create',[JobController::class,'store'])->name('job.create');
    Route::post('job/reschedule',[JobController::class,'rescheduleJob'])->name('job.reschedule');
    Route::post('job/sales_manager/assign',[JobController::class,'assignJob'])->name('job.sales_manager.assign');
    Route::get('job/start/{id}',[JobController::class,'startJob'])->name('job.start');
    Route::get('job/move/complete/{id}',[JobController::class,'moveToComplete'])->name('job.move.complete');

    Route::get('job/service_report/{id}',[JobServiceReportController::class,'index'])->name('job.service_report');
    Route::post('job/service_report/create',[JobServiceReportController::class,'store'])->name('job.service_report.create');
    
    Route::get('service_invoices/{id?}',[ServiceInvoiceController::class,'index'])->name('service_invoices');
    Route::post('service_invoices/add_payment',[ServiceInvoiceController::class,'addPayment'])->name('service_invoices.add_payment');
    
    Route::post('logout',[UserAuthController::class,'logout'])->name('logout');
});

