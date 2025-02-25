<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Admin',                //1
            'HR-Manager',           //2
            'Operation-Manager',    //3
            'Sales-Manager',        //4
            'Client',               //5
            'Accountant',           //6
            'Recovery-Officer',     //7
            'Sales-Officer',        //8
            'Sales-Man',            //9
        ];

        // Loop through and create or update each role
        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role]);
        }

        // Create a default admin user
        $admin = User::updateOrCreate(
            ['role_id' => 1],
            [
                'email' => 'admin@gmail.com',
                'name' => 'Default Admin',
                'password' => Hash::make('12345678'), // Set your default password
            ]
        );

    
        $permissions = [
            //Employee
            ['name' => 'View Employee', 'icon'=>'', 'api_route' => 'employee','frontend_url'=>'employee', 'is_main'=>1],
                ['name' => 'Create Employee', 'icon'=>'', 'api_route' => 'employee.create','frontend_url'=>'employee/create', 'is_main'=>0, 'parent_api_route'=>'employee'],
                ['name' => 'Assign Stock', 'icon'=>'', 'api_route' => 'employee.stock.assign','frontend_url'=>'employee/stock/assign', 'is_main'=>0, 'parent_api_route'=>'employee'],
                ['name' => 'Get Sales Managers', 'icon'=>'', 'api_route' => 'employee.sales_manager.get','frontend_url'=>'employee/sales_manager/get', 'is_main'=>0, 'parent_api_route'=>'employee'],
                ['name' => 'Get Employee Stock History', 'icon'=>'', 'api_route' => 'employee.stock.history','frontend_url'=>'employee/stock/history', 'is_main'=>0, 'parent_api_route'=>'employee'],
                
            //Vendor
            ['name' => 'View Vendor', 'icon'=>'', 'api_route' => 'vendor','frontend_url'=>'vendor', 'is_main'=>1],
                ['name' => 'Create Vendor', 'icon'=>'', 'api_route' => 'vendor.create','frontend_url'=>'vendor/create', 'is_main'=>0, 'parent_api_route'=>'vendor'],
                ['name' => 'Create Vendor Bank Info', 'icon'=>'', 'api_route' => 'vendor.bank_info.add','frontend_url'=>'vendor/bank_info/add', 'is_main'=>0, 'parent_api_route'=>'vendor'],
                ['name' => 'Update Vendor Bank Info', 'icon'=>'', 'api_route' => 'vendor.bank_info.update','frontend_url'=>'vendor/bank_info/update', 'is_main'=>0, 'parent_api_route'=>'vendor'],

           //Brands
           ['name' => 'View Brand', 'icon'=>'', 'api_route' => 'brand','frontend_url'=>'brand', 'is_main'=>1],
                ['name' => 'Create Brand', 'icon'=>'', 'api_route' => 'brand.create','frontend_url'=>'brand/create', 'is_main'=>0, 'parent_api_route'=>'brand'],
                ['name' => 'Update Brand', 'icon'=>'', 'api_route' => 'brand.update','frontend_url'=>'brand/update', 'is_main'=>0, 'parent_api_route'=>'brand'],

            // Suppliers
            ['name' => 'View Supplier', 'icon'=>'', 'api_route' => 'supplier','frontend_url'=>'supplier', 'is_main'=>1],
                ['name' => 'Create Supplier', 'icon'=>'', 'api_route' => 'supplier.create','frontend_url'=>'supplier/create', 'is_main'=>0, 'parent_api_route'=>'supplier'],
                ['name' => 'Supplier Add Pyament', 'icon'=>'', 'api_route' => 'supplier.add_payment','frontend_url'=>'supplier/add_payment', 'is_main'=>0, 'parent_api_route'=>'supplier'],
                ['name' => 'Get Supplier Leger', 'icon'=>'', 'api_route' => 'supplier.ledger.get','frontend_url'=>'supplier/ledger/get', 'is_main'=>0, 'parent_api_route'=>'supplier'],
                ['name' => 'Create Supplier Bank Info', 'icon'=>'', 'api_route' => 'supplier.bank_info.add','frontend_url'=>'supplier/bank_info/add', 'is_main'=>0, 'parent_api_route'=>'supplier'],
                ['name' => 'Update Supplier Bank Info', 'icon'=>'', 'api_route' => 'supplier.bank_info.update','frontend_url'=>'supplier/bank_info/update', 'is_main'=>0, 'parent_api_route'=>'supplier'],

            // Services
            ['name' => 'View Service', 'icon'=>'', 'api_route' => 'service','frontend_url'=>'service', 'is_main'=>1],
                ['name' => 'Create Service', 'icon'=>'', 'api_route' => 'service.create','frontend_url'=>'service/create', 'is_main'=>0, 'parent_api_route'=>'service'],
                ['name' => 'Update Service', 'icon'=>'', 'api_route' => 'service.update','frontend_url'=>'service/update', 'is_main'=>0, 'parent_api_route'=>'service'],

            // Clients & Addresses & Bank info
            ['name' => 'View Client & Addresses', 'icon'=>'', 'api_route' => 'client','frontend_url'=>'client', 'is_main'=>1],
                ['name' => 'Create Client', 'icon'=>'', 'api_route' => 'client.create','frontend_url'=>'client/create', 'is_main'=>0, 'parent_api_route'=>'client'],
                ['name' => 'Create Client Address', 'icon'=>'', 'api_route' => 'client.address.create','frontend_url'=>'client/address/create', 'is_main'=>0, 'parent_api_route'=>'client'],
                ['name' => 'Update Client Address', 'icon'=>'', 'api_route' => 'client.address.update','frontend_url'=>'client/address/update', 'is_main'=>0, 'parent_api_route'=>'client'],
                ['name' => 'Create Client Bank Info', 'icon'=>'', 'api_route' => 'client.bank_info.add','frontend_url'=>'client/bank_info/add', 'is_main'=>0, 'parent_api_route'=>'client'],
                ['name' => 'Update Client Bank Info', 'icon'=>'', 'api_route' => 'client.bank_info.update','frontend_url'=>'client/bank_info/update', 'is_main'=>0, 'parent_api_route'=>'client'],
                
            //References
            ['name' => 'View References', 'icon'=>'', 'api_route' => 'client.references.get','frontend_url'=>'client/references/get', 'is_main'=>1],


            // Products
            ['name' => 'View Product', 'icon'=>'', 'api_route' => 'product','frontend_url'=>'product', 'is_main'=>1],
                ['name' => 'Create Product', 'icon'=>'', 'api_route' => 'product.create','frontend_url'=>'product/create', 'is_main'=>0, 'parent_api_route'=>'product'],
                ['name' => 'Get Product Stock', 'icon'=>'', 'api_route' => 'product.stock.get','frontend_url'=>'product/stock/get', 'is_main'=>0, 'parent_api_route'=>'product'],
        
            //Vehicle
            ['name' => 'View Vehicle', 'icon'=>'', 'api_route' => 'vehicle','frontend_url'=>'vehicle', 'is_main'=>1],
                ['name' => 'Create Vehicle', 'icon'=>'', 'api_route' => 'vehicle.create','frontend_url'=>'vehicle/create', 'is_main'=>0, 'parent_api_route'=>'vehicle'],
                ['name' => 'Update Vehicle', 'icon'=>'', 'api_route' => 'vehicle.update','frontend_url'=>'vehicle/update', 'is_main'=>0, 'parent_api_route'=>'vehicle'],

            //Banks
            ['name' => 'View Bank', 'icon'=>'', 'api_route' => 'bank','frontend_url'=>'bank', 'is_main'=>1],
                ['name' => 'Create Bank', 'icon'=>'', 'api_route' => 'bank.create','frontend_url'=>'bank/create', 'is_main'=>0, 'parent_api_route'=>'bank'],
                ['name' => 'Update Bank', 'icon'=>'', 'api_route' => 'bank.update','frontend_url'=>'bank/update', 'is_main'=>0, 'parent_api_route'=>'bank'],

            // Expense Category
            ['name' => 'View Expense Category', 'icon'=>'', 'api_route' => 'expense_category','frontend_url'=>'expense_category', 'is_main'=>1],
                ['name' => 'Create Expense Category', 'icon'=>'', 'api_route' => 'expense_category.create','frontend_url'=>'expense_category/create', 'is_main'=>0, 'parent_api_route'=>'expense_category'],
                ['name' => 'Update Expense Category', 'icon'=>'', 'api_route' => 'expense_category.update','frontend_url'=>'expense_category/update', 'is_main'=>0, 'parent_api_route'=>'expense_category'],

            // Expense
            ['name' => 'View Expense', 'icon'=>'', 'api_route' => 'expense','frontend_url'=>'expense', 'is_main'=>1],
                ['name' => 'Create Expense', 'icon'=>'', 'api_route' => 'expense.create','frontend_url'=>'expense/create', 'is_main'=>0, 'parent_api_route'=>'expense'],

            // Vehicle Expense
            ['name' => 'View Vehicle Expense', 'icon'=>'', 'api_route' => 'vehicle_expense','frontend_url'=>'vehicle_expense', 'is_main'=>1],
                ['name' => 'Create Vehicle Expense', 'icon'=>'', 'api_route' => 'vehicle_expense.create','frontend_url'=>'vehicle_expense/create', 'is_main'=>0, 'parent_api_route'=>'vehicle_expense'],

            // Delivery Note
            ['name' => 'View Delivery Note', 'icon'=>'', 'api_route' => 'delivery_note','frontend_url'=>'delivery_note', 'is_main'=>1],
                ['name' => 'Create Delivery Note', 'icon'=>'', 'api_route' => 'delivery_note.create','frontend_url'=>'delivery_note/create', 'is_main'=>0, 'parent_api_route'=>'delivery_note'],

            // Company or Admin
            ['name' => 'View Company or Admin', 'icon'=>'', 'api_route' => 'admin','frontend_url'=>'admin', 'is_main'=>1],
                ['name' => 'Get Admin Dashboard', 'icon'=>'', 'api_route' => 'admin.dashboard','frontend_url'=>'admin/dashboard', 'is_main'=>0, 'parent_api_route'=>'admin'],

            // Terms And Condition
            ['name' => 'View Terms & Condition', 'icon'=>'', 'api_route' => 'terms_and_condition','frontend_url'=>'terms_and_condition', 'is_main'=>1],
                ['name' => 'Create Terms & Condition', 'icon'=>'', 'api_route' => 'terms_and_condition.create','frontend_url'=>'terms_and_condition/create', 'is_main'=>0, 'parent_api_route'=>'terms_and_condition'],
                ['name' => 'Update Terms & Condition', 'icon'=>'', 'api_route' => 'terms_and_condition.update','frontend_url'=>'terms_and_condition/update', 'is_main'=>0, 'parent_api_route'=>'terms_and_condition'],

            // Treatment Method
            ['name' => 'View Treatment Method', 'icon'=>'', 'api_route' => 'treatment_method','frontend_url'=>'treatment_method', 'is_main'=>1],
                ['name' => 'Create Treatment Method', 'icon'=>'', 'api_route' => 'treatment_method.create','frontend_url'=>'treatment_method/create', 'is_main'=>0, 'parent_api_route'=>'treatment_method'],
                ['name' => 'Update Treatment Method', 'icon'=>'', 'api_route' => 'treatment_method.update','frontend_url'=>'treatment_method/update', 'is_main'=>0, 'parent_api_route'=>'treatment_method'],

            //Quote & Contracts
            ['name' => 'View Quote', 'icon'=>'', 'api_route' => 'quote','frontend_url'=>'quote', 'is_main'=>1],
                ['name' => 'Manange Quote', 'icon'=>'', 'api_route' => 'quote.manage','frontend_url'=>'quote/manage', 'is_main'=>0, 'parent_api_route'=>'quote'],
                ['name' => 'Move Contract', 'icon'=>'', 'api_route' => 'quote.move.contract','frontend_url'=>'quote/move/contract', 'is_main'=>0, 'parent_api_route'=>'quote'],

            //Jobs
            ['name' => 'View Job', 'icon'=>'', 'api_route' => 'job','frontend_url'=>'job', 'is_main'=>1],
                ['name' => 'Create Job', 'icon'=>'', 'api_route' => 'job.create','frontend_url'=>'job/create', 'is_main'=>0, 'parent_api_route'=>'job'],
                ['name' => 'Reschedule Job', 'icon'=>'', 'api_route' => 'job.reschedule','frontend_url'=>'job/reschedule', 'is_main'=>0, 'parent_api_route'=>'job'],
                ['name' => 'Assign Job', 'icon'=>'', 'api_route' => 'job.sales_manager.assign','frontend_url'=>'job/sales_manager/assign', 'is_main'=>0, 'parent_api_route'=>'job'],
                ['name' => 'Start Job', 'icon'=>'', 'api_route' => 'job.start','frontend_url'=>'job/start', 'is_main'=>0, 'parent_api_route'=>'job'],
                ['name' => 'Move Job to Complete', 'icon'=>'', 'api_route' => 'job.move.complete','frontend_url'=>'job/move/complete', 'is_main'=>0, 'parent_api_route'=>'job'],

                //Jobs Service Report 
                ['name' => 'View Job Service Report', 'icon'=>'', 'api_route' => 'job.service_report','frontend_url'=>'job/service_report', 'is_main'=>0, 'parent_api_route'=>'job'],
                ['name' => 'Create Job Service Report', 'icon'=>'', 'api_route' => 'job.service_report.create','frontend_url'=>'job/service_report/create', 'is_main'=>0, 'parent_api_route'=>'job'],

            //Service Invoice
            ['name' => 'View Service Invoice', 'icon'=>'', 'api_route' => 'service_invoices','frontend_url'=>'service_invoices', 'is_main'=>1],
                ['name' => 'Service Invoice Add Payment', 'icon'=>'', 'api_route' => 'service_invoices.add_payment','frontend_url'=>'service_invoices/add_payment', 'is_main'=>0, 'parent_api_route'=>'service_invoices'],

            // Customers
            ['name' => 'View Customer', 'icon'=>'', 'api_route' => 'customer','frontend_url'=>'customer', 'is_main'=>1],
            ['name' => 'Create Customer', 'icon'=>'', 'api_route' => 'customer.create','frontend_url'=>'customer/create', 'is_main'=>0, 'parent_api_route'=>'customer'],
            ['name' => 'Customer Add Pyament', 'icon'=>'', 'api_route' => 'customer.add_payment','frontend_url'=>'customer/add_payment', 'is_main'=>0, 'parent_api_route'=>'customer'],
            ['name' => 'Get Customer Leger', 'icon'=>'', 'api_route' => 'customer.ledger.get','frontend_url'=>'customer/ledger/get', 'is_main'=>0, 'parent_api_route'=>'customer'],
            
            //Sales Order
            ['name' => 'View Sale Order', 'icon'=>'', 'api_route' => 'sale_order','frontend_url'=>'sale_order', 'is_main'=>1],
            ['name' => 'Create Sale Order', 'icon'=>'', 'api_route' => 'sale_order.create','frontend_url'=>'sale_order/create', 'is_main'=>0, 'parent_api_route'=>'sale_order'],
        
        ];


        foreach ($permissions as $permissionData) {        
            // Check if the permission already exists with the provided api_route
            $permission = Permission::updateOrCreate(
                ['api_route' => $permissionData['api_route']],
                [
                    'name' => $permissionData['name'],
                    'icon' => $permissionData['icon'],
                    'frontend_url' => $permissionData['frontend_url'],
                    'is_main' => $permissionData['is_main'] ?? 0,
                    'parent_api_route' => $permissionData['parent_api_route']?? null,
                ]
            );
        }
        
        // Find or create role with ID 1
        $role = Role::firstOrCreate(['id' => 1]);

        // Get all permissions
        $allPermissions = Permission::all();

        // Attach all permissions to the role
        $role->permissions()->sync($allPermissions->pluck('id'));

    }
}
