<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Doctor;
use App\Http\Controllers\Receptionist;
use App\Http\Controllers\Shared;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('role:admin')->group(function () {
        Route::post('/create-user', [Admin\EmployeeController::class, 'store']);

        Route::get('/branch', [Admin\BranchController::class, 'index']);
        Route::post('/branch', [Admin\BranchController::class, 'store']);
        Route::put('/branch/{id}', [Admin\BranchController::class, 'update']);
        Route::delete('/branch/{id}', [Admin\BranchController::class, 'delete']);

        Route::match(['put', 'post'], '/settings/{branch?}', [Admin\SettingsController::class, 'update']);
        Route::post('/settings/backup/{type}', [Admin\SettingsController::class, 'backupDatabase']);


    });

    Route::middleware('role:doctor,assistant,admin')->group(function () {
        Route::get('/doc/appointment', [Doctor\AppointmentController::class, 'index']);
        Route::post('/appointment', [Receptionist\AppointmentController::class, 'store']);

        Route::get('/patients/{id}/odontogram', [Doctor\OdontogramController::class, 'show']);
        Route::post('/patients/{id}/odontogram', [Doctor\OdontogramController::class, 'store']);
        Route::get('/condition-library/', [Doctor\ConditionLibraryController::class, 'index']);
        Route::post('/patients/{patient}/odontogram/condition', [Doctor\ToothConditionController::class, 'store']);
        Route::delete('/tooth-conditions/{id}', [Doctor\ToothConditionController::class, 'destroy']);
        Route::apiResource('/treatment', Doctor\TreatmentController::class);

        Route::apiResource('/procedure', Doctor\ProcedureController::class);
    });

    Route::middleware('role:receptionist,admin')->group(function () {
        Route::post('/expense', [Receptionist\ClinicExpenseController::class, 'store']);
        Route::get('/expense', [Receptionist\ClinicExpenseController::class, 'index']);
        Route::put('/expense/{id}', [Receptionist\ClinicExpenseController::class, 'update']);

        Route::post('/employee', [Receptionist\EmployeeController::class, 'store']);
        Route::get('/employee', [Receptionist\EmployeeController::class, 'index']);
        Route::put('/employee/{id}', [Receptionist\EmployeeController::class, 'update']);
        Route::post('/employee-salary/{id}', [Receptionist\EmployeeController::class, 'employeeSalary']);
        Route::delete('/employee/{id}', [Receptionist\EmployeeController::class, 'delete']);
        Route::post('/employee/{id}/update-profile-pic', [Receptionist\EmployeeController::class, 'updateProfliePic']);
        Route::get('/salaries', [Receptionist\EmployeeController::class, 'getSalaries']);

        Route::post('/material', [Receptionist\ClinicMaterialController::class, 'store']);
        Route::get('/material', [Receptionist\ClinicMaterialController::class, 'index']);
        Route::get('/material/{id}', [Receptionist\ClinicMaterialController::class, 'show']);
        Route::put('/material/{id}', [Receptionist\ClinicMaterialController::class, 'update']);
        Route::delete('/material/{id}', [Receptionist\ClinicMaterialController::class, 'delete']);

        Route::post('/reception', [Receptionist\ReceptionController::class, 'store']);

        Route::post('/patient-files', [Receptionist\PatientFileController::class, 'store']);
        Route::get('/patient-files', [Receptionist\PatientFileController::class, 'index']);
        Route::put('/patient-files/{id}', [Receptionist\PatientFileController::class, 'update']);


        Route::post('/account', [Receptionist\AccountController::class, 'store']);
        Route::get('/account', [Receptionist\AccountController::class, 'index']);
        Route::put('/account/{id}', [Receptionist\AccountController::class, 'update']);
    });

    Route::middleware('role:receptionist,doctor,assisstant,admin')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index']);
        Route::get('/appointment', [Receptionist\AppointmentController::class, 'index']);
        Route::put('/appointment/{id}', [Receptionist\AppointmentController::class, 'update']);
        Route::delete('/appointment/{id}', [Receptionist\AppointmentController::class, 'delete']);


        Route::get('/employee', [Receptionist\EmployeeController::class, 'index']);
        Route::post('/appointment', [Receptionist\AppointmentController::class, 'store']);

        Route::post('/patient', [Receptionist\PatientController::class, 'store']);
        Route::get('/patient', [Receptionist\PatientController::class, 'index']);
        Route::get('/patient/{id}', [Receptionist\PatientController::class, 'show']);
        Route::put('/patient/{id}', [Receptionist\PatientController::class, 'update']);
        Route::post('/patient-allergy', [Receptionist\PatientController::class, 'allergy']);
        Route::post('/set-patient-debit/{id}', [Receptionist\PatientController::class, 'setDebit']);

        Route::get('/treatment-plan/', [Doctor\TreatmentPlanController::class, 'index']);
        Route::post('/treatment-plan', [Doctor\TreatmentPlanController::class, 'store']);
        Route::put('/treatment-plan/{id}', [Doctor\TreatmentPlanController::class, 'update']);
        Route::delete('/treatment-plan/{id}', [Doctor\TreatmentPlanController::class, 'delete']);
        Route::put('treatment-plan/update-status/{id}', [Doctor\TreatmentPlanController::class, 'updateStatus']);
        Route::post('/treatment-plan/{id}/appointments', [Doctor\TreatmentPlanController::class, 'addAppointment']);
        Route::post('/treatment-plan/{id}/execute', [Doctor\TreatmentPlanController::class, 'execute']);
        Route::get('/procedure', [Doctor\ProcedureController::class, 'index']);

        Route::post('/prescription', [Shared\PrescriptionController::class, 'store']);
        Route::get('/prescription', [Shared\PrescriptionController::class, 'index']);
        Route::put('/prescription/{id}', [Shared\PrescriptionController::class, 'update']);
        Route::delete('/prescription/{id}', [Shared\PrescriptionController::class, 'delete']);

        Route::post('/transaction', [Shared\TransactionController::class, 'store']);
        Route::get('/transaction', [Shared\TransactionController::class, 'index']);
        Route::put('/transaction/{id}', [Shared\TransactionController::class, 'update']);

        Route::post('dental-xray', [Shared\DentalXrayController::class, 'store']);
        Route::get('dental-xray', [Shared\DentalXrayController::class, 'index']);
        Route::put('dental-xray/{id}', [Shared\DentalXrayController::class, 'update']);

        Route::post('/assets', [Shared\ClinicAssetController::class, 'store']);
        Route::get('/assets', [Shared\ClinicAssetController::class, 'index']);
        Route::get('/assets/{id}', [Shared\ClinicAssetController::class, 'show']);
        Route::put('/assets/{id}', [Shared\ClinicAssetController::class, 'update']);
        Route::delete('/assets/{id}', [Shared\ClinicAssetController::class, 'delete']);

        // Supplier routes
        Route::post('/suppliers', [Shared\SupplierController::class, 'store']);
        Route::get('/suppliers', [Shared\SupplierController::class, 'index']);
        Route::get('/suppliers/{id}', [Shared\SupplierController::class, 'show']);
        Route::put('/suppliers/{id}', [Shared\SupplierController::class, 'update']);
        Route::delete('/suppliers/{id}', [Shared\SupplierController::class, 'delete']);

        // Item routes
        Route::get('/items', [Shared\ItemController::class, 'index']);
        Route::get('/items/{id}', [Shared\ItemController::class, 'show']);
        Route::post('/items', [Shared\ItemController::class, 'store']);
        Route::put('/items/{id}', [Shared\ItemController::class, 'update']);
        Route::delete('/items/{id}', [Shared\ItemController::class, 'delete']);
        Route::get('/items/categories', [Shared\ItemController::class, 'categories']);

        // Order routes
        Route::post('/orders', [Shared\OrderController::class, 'store']);
        Route::get('/orders', [Shared\OrderController::class, 'index']);
        Route::get('/orders/{id}', [Shared\OrderController::class, 'show']);
        Route::put('/orders/{id}', [Shared\OrderController::class, 'update']);
        Route::delete('/orders/{id}', [Shared\OrderController::class, 'delete']);

        // Shelf routes
        Route::post('/shelves', [Shared\ShelfController::class, 'store']);
        Route::get('/shelves', [Shared\ShelfController::class, 'index']);
        Route::get('/shelves/{id}', [Shared\ShelfController::class, 'show']);
        Route::put('/shelves/{id}', [Shared\ShelfController::class, 'update']);
        Route::delete('/shelves/{id}', [Shared\ShelfController::class, 'delete']);

        // Inventory Stock routes
        Route::post('/inventory-stock', [Shared\InventoryStockController::class, 'store']);
        Route::get('/inventory-stock', [Shared\InventoryStockController::class, 'index']);
        Route::get('/inventory-stock/{id}', [Shared\InventoryStockController::class, 'show']);
        Route::put('/inventory-stock/{id}', [Shared\InventoryStockController::class, 'update']);
        Route::delete('/inventory-stock/{id}', [Shared\InventoryStockController::class, 'delete']);
        Route::get('/inventory-stock/pending', [Shared\InventoryStockController::class, 'pending']);
        Route::get('/inventory-stock/placed', [Shared\InventoryStockController::class, 'placed']);

        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/update-profile-pic', [AuthController::class, 'updateProfilePic']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/settings', [Admin\SettingsController::class, 'index']);
    });
});


// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});
