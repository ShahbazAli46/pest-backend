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