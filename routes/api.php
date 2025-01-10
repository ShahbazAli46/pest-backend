<?php

use App\Http\Controllers\{AdminController, BankController, BrandController,ClientController, CustomerController, DashboardController, DeviceController, EmployeeController, ExpenseCategoryController, ExpenseController, JobController, JobServiceReportController, ProductController, PurchaseOrderController, QuoteController, ReceivedCashRecordController, SalaryController, SaleOrderController, ServiceController, ServiceInvoiceController, SupplierController, TermsAndConditionController, TreatmentMethodController, UserAuthController, VehicleController, VehicleExpenseController, VendorController};
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

Route::get('quote/{id}',[QuoteController::class,'index'])->name('single_quote');

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Route::middleware(['auth:sanctum','permission'])->group(function () {
    Route::post('device/token',[DashboardController::class,'deviceToken'])->name('device.token');


    //Dashboard
    Route::get('dashboard/count_clients',[DashboardController::class,'getCountClients'])->name('dashboard.count_clients');
    Route::get('dashboard/count_jobs',[DashboardController::class,'getCountJobs'])->name('dashboard.count_jobs');
    Route::get('dashboard/cash_collection',[DashboardController::class,'getCashCollection'])->name('dashboard.cash_collection');
    Route::get('dashboard/pos_collection',[DashboardController::class,'getPosCollection'])->name('dashboard.pos_collection');
    Route::get('dashboard/expense_collection',[DashboardController::class,'getExpenseCollection'])->name('dashboard.expense_collection');
    Route::get('dashboard/bank_collection',[DashboardController::class,'getBankCollection'])->name('dashboard.bank_collection');
    Route::get('dashboard/monthly_financial_report/{month?}',[DashboardController::class,'getMonthlyFinancialReport'])->name('dashboard.monthly_financial_report');
    
    // Employee
    Route::get('employee/{id?}',[EmployeeController::class,'index'])->name('employee');
    Route::post('employee/create',[EmployeeController::class,'store'])->name('employee.create');
    Route::post('employee/update',[EmployeeController::class,'update'])->name('employee.update');
    Route::post('employee/update_docs',[EmployeeController::class,'updateDocs'])->name('employee.update_docs');
    Route::post('employee/stock/assign',[EmployeeController::class,'assignStock'])->name('employee.stock.assign');
    Route::get('employee/sales_manager/get',[EmployeeController::class,'getSalesManager'])->name('employee.sales_manager.get');
    Route::get('employee/sales_manager/job/history/{id}',[EmployeeController::class,'getEmployeeJobHistory'])->name('employee.sales_manager.job.history');
    Route::post('employee/stock/used',[EmployeeController::class,'getUsedStock'])->name('employee.stock.used');
    Route::get('employee/fired_at/{id}',[EmployeeController::class,'fireEmployee'])->name('employee.fired_at');
    Route::get('employee/reactive/{id}',[EmployeeController::class,'reActiveEmployee'])->name('employee.reactive');
    Route::get('employee/fired/get',[EmployeeController::class,'getFiredEmployees'])->name('employee.fired.get');
    

    Route::get('employee/salary/get',[EmployeeController::class,'getEmployeeSalary'])->name('employee.salary.get');
    Route::get('employee/salary/set_salary_on_per/{id}/{per}',[EmployeeController::class,'setSalaryOnPer'])->name('employee.salary.set_salary_on_per');
    Route::post('employee/salary/paid',[EmployeeController::class,'paidEmployeeSalary'])->name('employee.salary.paid');
    Route::post('employee/salary/advance',[EmployeeController::class,'paidAdvanceEmployee'])->name('employee.salary.advance');
    Route::post('employee/vehicle/fine',[EmployeeController::class,'vehicleEmployeeFine'])->name('employee.vehicle.fine');
    Route::post('employee/adv_received',[EmployeeController::class,'advanceReceived'])->name('employee.adv_received');
    
    Route::get('salary/detail/{month?}',[SalaryController::class,'getSalaryDetails'])->name('salary.detail');

    //Sales-Manager

    Route::get('employee/commission/get',[EmployeeController::class,'getEmployeeCommission'])->name('employee.commission.get');
    Route::post('employee/commission/paid',[EmployeeController::class,'paidEmployeeCommission'])->name('employee.commission.paid');

    
    // Vendors
    Route::get('vendor/{id?}',[VendorController::class,'index'])->name('vendor');
    Route::post('vendor/create',[VendorController::class,'store'])->name('vendor.create');
    Route::post('vendor/bank_info/add',[VendorController::class,'storeVendorBankInfo'])->name('vendor.bank_info.add');
    Route::post('vendor/bank_info/update/{id}',[VendorController::class,'updateVendorBankInfo'])->name('vendor.bank_info.update');

    // Brands
    Route::get('brand/{id?}',[BrandController::class,'index'])->name('brand');
    Route::post('brand/create',[BrandController::class,'store'])->name('brand.create');
    Route::post('brand/update/{id}',[BrandController::class,'update'])->name('brand.update');

    // Suppliers
    Route::get('supplier/{id?}',[SupplierController::class,'index'])->name('supplier');
    Route::post('supplier/create',[SupplierController::class,'store'])->name('supplier.create');
    Route::post('supplier/add_payment',[SupplierController::class,'addPayment'])->name('supplier.add_payment');
    Route::get('supplier/ledger/get/{id?}',[SupplierController::class,'getSupplierLedger'])->name('supplier.ledger.get');
    Route::post('supplier/bank_info/add',[SupplierController::class,'storeSupplierBankInfo'])->name('supplier.bank_info.add');
    Route::post('supplier/bank_info/update/{id}',[SupplierController::class,'updateSupplierBankInfo'])->name('supplier.bank_info.update');

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
    Route::get('client/ledger/get/{id?}',[ClientController::class,'getClientLedger'])->name('client.ledger.get');
    Route::get('client/received_amount/get/{id?}',[ClientController::class,'getClientReceivedAmt'])->name('client.received_amount.get');
    //Jobs & Clients
    Route::get('client/jobs/get/{id}',[ClientController::class,'getClientJobs'])->name('client.jobs.get');

    // Products
    Route::get('product/{id?}',[ProductController::class,'index'])->name('product');
    Route::post('product/create',[ProductController::class,'store'])->name('product.create');
    Route::get('product/stock/get/{id?}',[ProductController::class,'getProductStok'])->name('product.stock.get');

    // Vehicle
    Route::get('vehicle/{id?}',[VehicleController::class,'index'])->name('vehicle');
    Route::post('vehicle/create',[VehicleController::class,'store'])->name('vehicle.create');
    Route::post('vehicle/update/{id}',[VehicleController::class,'update'])->name('vehicle.update');

    // Devices 
    Route::get('device/{id?}',[DeviceController::class,'index'])->name('device');
    Route::post('device/create',[DeviceController::class,'store'])->name('device.create');
    Route::post('device/assign',[DeviceController::class,'assignDevice'])->name('device.assign');
    Route::post('device/update/{id}',[DeviceController::class,'update'])->name('device.update');
    Route::get('device/history/{id}',[DeviceController::class,'getHistory'])->name('device.history');
    
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
    Route::post('vehicle_expense/create',[VehicleExpenseController::class,'store'])->name('vehicle_expense.create');

    // Purchase Orders
    Route::get('purchase_order/{id?}',[PurchaseOrderController::class,'index'])->name('purchase_order');
    Route::post('purchase_order/create',[PurchaseOrderController::class,'store'])->name('purchase_order.create');

    //Company or Admin
    Route::get('admin/ledger/get/{id?}',[AdminController::class,'getAdminLedger'])->name('admin.ledger.get');
    Route::get('admin/current/balance/get',[AdminController::class,'getAdminCurrentBalance'])->name('admin.current.balance.get');
    Route::get('admin/dashboard',[AdminController::class,'getAdminDashboard'])->name('admin.dashboard');
    Route::post('admin/cash_balance/add',[AdminController::class,'addCashBalanceAdd'])->name('admin.cash_balance.add');

    // Terms And Condition
    Route::get('terms_and_condition/{id?}',[TermsAndConditionController::class,'index'])->name('terms_and_condition');
    Route::post('terms_and_condition/create',[TermsAndConditionController::class,'store'])->name('terms_and_condition.create');
    Route::post('terms_and_condition/update/{id}',[TermsAndConditionController::class,'update'])->name('terms_and_condition.update');

    // Treatment Method
    Route::get('treatment_method/{id?}',[TreatmentMethodController::class,'index'])->name('treatment_method');
    Route::post('treatment_method/create',[TreatmentMethodController::class,'store'])->name('treatment_method.create');
    Route::post('treatment_method/update/{id}',[TreatmentMethodController::class,'update'])->name('treatment_method.update');

    //Quote & Contracts
    Route::get('quote',[QuoteController::class,'index'])->name('quote');
    Route::post('quote/manage',[QuoteController::class,'manage'])->name('quote.manage');
    Route::post('quote/move/contract/{id}',[QuoteController::class,'moveToContract'])->name('quote.move.contract');
    Route::post('quote/contract/date/update',[QuoteController::class,'updateContractDate'])->name('quote.contract.date.update');

    Route::post('quote/move/cancel/{id}',[QuoteController::class,'moveToCancel'])->name('quote.move.cancel');
    Route::get('quote/service_invoices/{id}',[QuoteController::class,'getContractServiceInvoices'])->name('quote.service_invoices');

    //Jobs
    Route::get('job/{id?}',[JobController::class,'index'])->name('job');
    Route::post('job/create',[JobController::class,'store'])->name('job.create');
    Route::post('job/reschedule',[JobController::class,'rescheduleJob'])->name('job.reschedule');
    Route::post('job/sales_manager/assign',[JobController::class,'assignJob'])->name('job.sales_manager.assign');
    Route::get('job/start/{id}',[JobController::class,'startJob'])->name('job.start');
    Route::get('job/move/complete/{id}',[JobController::class,'moveToComplete'])->name('job.move.complete');

    //service report
    Route::get('job/service_report/{id}',[JobServiceReportController::class,'index'])->name('job.service_report');
    Route::post('job/service_report/create',[JobServiceReportController::class,'store'])->name('job.service_report.create');
    Route::post('job/service_report/feedback/create',[JobServiceReportController::class,'storeFeedback'])->name('job.service_report.feedback.create');
    
    //service invoices
    Route::get('service_invoices/{id?}',[ServiceInvoiceController::class,'index'])->name('service_invoices');
    Route::post('service_invoices/add_payment',[ServiceInvoiceController::class,'addPayment'])->name('service_invoices.add_payment');
    
    Route::get('outstandings',[ServiceInvoiceController::class,'outstandings'])->name('outstandings');


    //received cash records
    Route::get('received_cash_record/{id?}',[ReceivedCashRecordController::class,'index'])->name('received_cash_records');
    Route::post('received_cash_record/approve',[ReceivedCashRecordController::class,'approvePayment'])->name('received_cash_record.approve');

    //customers
    Route::get('customer/{id?}',[CustomerController::class,'index'])->name('customer');
    Route::post('customer/create',[CustomerController::class,'store'])->name('customer.create');
    Route::post('customer/add_payment',[CustomerController::class,'addPayment'])->name('customer.add_payment');
    Route::get('customer/ledger/get/{id?}',[CustomerController::class,'getCustomerLedger'])->name('customer.ledger.get');

    // Sale Orders
    Route::get('sale_order/{id?}',[SaleOrderController::class,'index'])->name('sale_order');
    Route::post('sale_order/create',[SaleOrderController::class,'store'])->name('sale_order.create');

    Route::post('logout',[UserAuthController::class,'logout'])->name('logout');
});
