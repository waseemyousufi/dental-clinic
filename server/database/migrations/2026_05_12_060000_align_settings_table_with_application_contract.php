<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'clinic_name')) {
                $table->string('clinic_name')->nullable()->after('branch_id');
            }

            if (!Schema::hasColumn('settings', 'address')) {
                $table->string('address')->nullable()->after('clinic_name');
            }

            if (!Schema::hasColumn('settings', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }

            if (!Schema::hasColumn('settings', 'currency')) {
                $table->string('currency')->default('USD')->after('phone');
            }

            if (!Schema::hasColumn('settings', 'working_hours')) {
                $table->json('working_hours')->nullable()->after('currency');
            }

            if (!Schema::hasColumn('settings', 'wa_patient_reminder')) {
                $table->text('wa_patient_reminder')->nullable()->after('working_hours');
            }

            if (!Schema::hasColumn('settings', 'wa_patient_cancel')) {
                $table->text('wa_patient_cancel')->nullable()->after('wa_patient_reminder');
            }

            if (!Schema::hasColumn('settings', 'wa_patient_complete')) {
                $table->text('wa_patient_complete')->nullable()->after('wa_patient_cancel');
            }

            if (!Schema::hasColumn('settings', 'wa_supplier_order')) {
                $table->text('wa_supplier_order')->nullable()->after('wa_patient_complete');
            }

            if (!Schema::hasColumn('settings', 'wa_supplier_cancel')) {
                $table->text('wa_supplier_cancel')->nullable()->after('wa_supplier_order');
            }

            if (!Schema::hasColumn('settings', 'rec_can_edit_whatsapp')) {
                $table->boolean('rec_can_edit_whatsapp')->default(false)->after('wa_supplier_cancel');
            }

            if (!Schema::hasColumn('settings', 'rec_can_view_phones')) {
                $table->boolean('rec_can_view_phones')->default(true)->after('rec_can_edit_whatsapp');
            }

            if (!Schema::hasColumn('settings', 'rec_show_kpi')) {
                $table->boolean('rec_show_kpi')->default(false)->after('rec_can_view_phones');
            }

            if (!Schema::hasColumn('settings', 'rec_show_suppliers')) {
                $table->boolean('rec_show_suppliers')->default(false)->after('rec_show_kpi');
            }

            if (!Schema::hasColumn('settings', 'rec_log_actions')) {
                $table->boolean('rec_log_actions')->default(false)->after('rec_show_suppliers');
            }

            if (!Schema::hasColumn('settings', 'rec_can_void_transactions')) {
                $table->boolean('rec_can_void_transactions')->default(false)->after('rec_log_actions');
            }

            if (!Schema::hasColumn('settings', 'rec_can_edit_devices')) {
                $table->boolean('rec_can_edit_devices')->default(false)->after('rec_can_void_transactions');
            }

            if (!Schema::hasColumn('settings', 'rec_can_contact_support')) {
                $table->boolean('rec_can_contact_support')->default(true)->after('rec_can_edit_devices');
            }

            if (!Schema::hasColumn('settings', 'doc_view_appointments')) {
                $table->boolean('doc_view_appointments')->default(true)->after('rec_can_contact_support');
            }

            if (!Schema::hasColumn('settings', 'doc_save_xrays')) {
                $table->boolean('doc_save_xrays')->default(false)->after('doc_view_appointments');
            }

            if (!Schema::hasColumn('settings', 'doc_view_files')) {
                $table->boolean('doc_view_files')->default(true)->after('doc_save_xrays');
            }

            if (!Schema::hasColumn('settings', 'doc_view_contact')) {
                $table->boolean('doc_view_contact')->default(true)->after('doc_view_files');
            }

            if (!Schema::hasColumn('settings', 'doc_edit_assets')) {
                $table->boolean('doc_edit_assets')->default(false)->after('doc_view_contact');
            }

            if (!Schema::hasColumn('settings', 'doc_issue_prescriptions')) {
                $table->boolean('doc_issue_prescriptions')->default(true)->after('doc_edit_assets');
            }

            if (!Schema::hasColumn('settings', 'clinic_items')) {
                $table->json('clinic_items')->nullable()->after('doc_issue_prescriptions');
            }

            if (!Schema::hasColumn('settings', 'clinic_procedures')) {
                $table->json('clinic_procedures')->nullable()->after('clinic_items');
            }

            if (!Schema::hasColumn('settings', 'prescription_template')) {
                $table->json('prescription_template')->nullable()->after('clinic_procedures');
            }
        });
    }

    public function down(): void
    {
        // No-op: this migration repairs drift in existing environments.
    }
};
