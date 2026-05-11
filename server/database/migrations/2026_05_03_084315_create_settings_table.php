<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->unique()->cascadeOnDelete();

            // Branch Info
            $table->string('clinic_name')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('currency')->default('USD');

            // Working Hours (JSON structure explained below)
            $table->json('working_hours')->nullable();

            // WhatsApp Templates
            $table->text('wa_patient_reminder')->nullable();
            $table->text('wa_patient_cancel')->nullable();
            $table->text('wa_patient_complete')->nullable();
            $table->text('wa_supplier_order')->nullable();
            $table->text('wa_supplier_cancel')->nullable();

            // Receptionist Permissions
            $table->boolean('rec_can_edit_whatsapp')->default(false);
            $table->boolean('rec_can_view_phones')->default(true);
            $table->boolean('rec_show_kpi')->default(false);
            $table->boolean('rec_show_suppliers')->default(false);
            $table->boolean('rec_log_actions')->default(false);
            $table->boolean('rec_can_void_transactions')->default(false);
            $table->boolean('rec_can_edit_devices')->default(false);
            $table->boolean('rec_can_contact_support')->default(true);

            // Doctor Permissions
            $table->boolean('doc_view_appointments')->default(true);
            $table->boolean('doc_save_xrays')->default(false);
            $table->boolean('doc_view_files')->default(true);
            $table->boolean('doc_view_contact')->default(true);
            $table->boolean('doc_edit_assets')->default(false);
            $table->boolean('doc_issue_prescriptions')->default(true);

            // Clinic Services & Prescription Layout
            $table->json('clinic_items')->nullable();
            $table->json('clinic_procedures')->nullable();
            $table->json('prescription_template')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
