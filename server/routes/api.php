<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Doctor;
use App\Http\Controllers\Receptionist;
use App\Http\Controllers\Shared;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::post('/create-user', [Admin\EmployeeController::class, 'store']);
    });

    Route::middleware('role:doctor,assisstant')->prefix('doctor')->group(function () {
        Route::get('/appointments', [Doctor\AppointmentController::class, 'index']);
    });

    Route::middleware('role:receptionist')->prefix('receptionist')->group(function () {

        Route::post('/appointment', [Receptionist\AppointmentController::class, 'store']);
        Route::get('/appointment', [Receptionist\AppointmentController::class, 'index']);
        Route::put('/appointment/{id}', [Receptionist\AppointmentController::class, 'update']);
        Route::delete('/appointment/{id}', [Receptionist\AppointmentController::class, 'delete']);

        Route::post('/expense', [Receptionist\ClinicExpenseController::class, 'store']);
        Route::get('/expense', [Receptionist\ClinicExpenseController::class, 'index']);
        Route::put('/expense/{id}', [Receptionist\ClinicExpenseController::class, 'update']);

        Route::post('/patient', [Receptionist\PatientController::class, 'store']);
        Route::get('/patient', [Receptionist\PatientController::class, 'index']);
        Route::get('/patient/{id}', [Receptionist\PatientController::class, 'show']);
        Route::put('/patient/{id}', [Receptionist\PatientController::class, 'update']);
        Route::post('/patient-allergy', [Receptionist\PatientController::class, 'allergy']);

        Route::post('/employee', [Receptionist\EmployeeController::class, 'store']);
        Route::get('/employee', [Receptionist\EmployeeController::class, 'index']);
        Route::put('/employee/{id}', [Receptionist\EmployeeController::class, 'update']);
        Route::post('/employee-salary/{id}', [Receptionist\EmployeeController::class, 'employeeSalary']);
        Route::delete('/employee/{id}', [Receptionist\EmployeeController::class, 'delete']);

        Route::post('/material', [Receptionist\CliniMaterialController::class, 'store']);
        Route::get('/material', [Receptionist\CliniMaterialController::class, 'index']);
        Route::put('/material/{id}', [Receptionist\CliniMaterialController::class, 'update']);
        Route::delete('/material/{id}', [Receptionist\CliniMaterialController::class, 'delete']);

        Route::post('/reception', [Receptionist\ReceptionController::class, 'store']);

        Route::post('/treatment', [Receptionist\TreatmentController::class, 'store']);
        Route::get('/treatment', [Receptionist\TreatmentController::class, 'index']);
        Route::put('/treatment/{id}', [Receptionist\TreatmentController::class, 'update']);
        Route::delete('/treatment/{id}', [Receptionist\TreatmentController::class, 'delete']);

        Route::post('/patient-files', [Receptionist\PatientFileController::class, 'store']);
        Route::get('/patient-files', [Receptionist\PatientFileController::class, 'index']);
        Route::put('/patient-files/{id}', [Receptionist\PatientFileController::class, 'update']);


        Route::post('/account', [Receptionist\AccountController::class, 'store']);
        Route::get('/account', [Receptionist\AccountController::class, 'index']);
        Route::put('/account/{id}', [Receptionist\AccountController::class, 'update']);
    });

    Route::middleware('role:receptionist,doctor,assisstant,admin')->prefix('chores')->group(function () {
        Route::post('/prescription', [Shared\PrescriptionController::class, 'store']);
        Route::get('/prescription', [Shared\PrescriptionController::class, 'index']);
        Route::put('/prescription/{id}', [Shared\PrescriptionController::class, 'update']);

        Route::post('/transaction', [Shared\TransactionController::class, 'store']);
        Route::get('/transaction', [Shared\TransactionController::class, 'index']);
        Route::put('/transaction/{id}', [Shared\TransactionController::class, 'update']);

        Route::post('dental-xray', [Shared\DentalXrayController::class, 'store']);
        Route::get('dental-xray', [Shared\DentalXrayController::class, 'index']);
        Route::put('dental-xray/{id}', [Shared\DentalXrayController::class, 'update']);

        Route::post('/assets', [Shared\ClinicAssetController::class, 'store']);
        Route::get('/assets', [Shared\ClinicAssetController::class, 'index']);
        Route::put('/assets/{id}', [Shared\ClinicAssetController::class, 'update']);
        Route::delete('/assets/{id}', [Shared\ClinicAssetController::class, 'delete']);

        Route::get('/me', function (Request $request) {
            return Auth::user()->load('employee');
        });

        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });
});


// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/create-employee', [Admin\EmployeeController::class, 'store']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
