<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {       
    return view('welcome');
});


Route::get('migrate', function(){
    try {
        Artisan::call('migrate');
        $output = Artisan::output();
        echo "Migrations successfully executed:\n$output";
    } catch (\Exception $e) {
        // Handle any exceptions
        echo "Failed to execute migrations: " . $e->getMessage();
    }
});

Route::get('clear', function(){
    try {
        Artisan::call('config:clear');
        $output = Artisan::output();
        echo "Configuration cache cleared:\n$output";
        
        Artisan::call('cache:clear');
        $output = Artisan::output();
        echo "Application cache cleared:\n$output";
        
        Artisan::call('route:clear');
        $output = Artisan::output();
        echo "Route cache cleared:\n$output";
        
        Artisan::call('view:clear');
        $output = Artisan::output();
        echo "View cache cleared:\n$output";
    } catch (\Exception $e) {
        // Handle any exceptions
        echo "Failed to execute migrations: " . $e->getMessage();
    }
});

// Route for running database seeders
Route::get('seed', function(){
    try {
        Artisan::call('db:seed');
        $output = Artisan::output();
        echo "Database seeding successfully executed:\n$output";
    } catch (\Exception $e) {
        echo "Failed to execute seeding: " . $e->getMessage();
    }
});

Route::get('sal_com', function(){
    try {
        Artisan::call('sal_com:generate');
        $output = Artisan::output();
        echo "Salary & Commission Generated successfully executed:\n$output";
    } catch (\Exception $e) {
        echo "Failed to Salary & Commission Generating: " . $e->getMessage();
    }
});

Route::get('invoices-monthly', function(){
    try {
        Artisan::call('invoices:send-monthly');
        $output = Artisan::output();
        echo "Invoice Monthly Generated successfully executed:\n$output";
    } catch (\Exception $e) {
        echo "Failed to Invoice Monthly Generating: " . $e->getMessage();
    }
});