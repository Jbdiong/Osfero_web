<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('picables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('picable'); // picable_type, picable_id
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate assignments
            $table->unique(['user_id', 'picable_id', 'picable_type']);
        });

        // Migrate LeadPIC
        if (Schema::hasTable('lead_p_i_c_s')) {
            $leads = \Illuminate\Support\Facades\DB::table('lead_p_i_c_s')->get();
            $data = [];
            foreach ($leads as $lead) {
                $data[] = [
                    'user_id' => $lead->user_id,
                    'picable_id' => $lead->lead_id,
                    'picable_type' => 'App\\Models\\Lead',
                    'tenant_id' => $lead->tenant_id,
                    'created_at' => $lead->created_at,
                    'updated_at' => $lead->updated_at,
                ];
            }
            if (count($data) > 0) \Illuminate\Support\Facades\DB::table('picables')->insert($data);
            Schema::dropIfExists('lead_p_i_c_s');
        }

        // Migrate TodolistPIC
        if (Schema::hasTable('todolist_p_i_c_s')) {
            $todolists = \Illuminate\Support\Facades\DB::table('todolist_p_i_c_s')->get();
            $data = [];
            foreach ($todolists as $todo) {
                $data[] = [
                    'user_id' => $todo->user_id,
                    'picable_id' => $todo->todolist_id,
                    'picable_type' => 'App\\Models\\Todolist',
                    'tenant_id' => $todo->tenant_id,
                    'created_at' => $todo->created_at,
                    'updated_at' => $todo->updated_at,
                ];
            }
            if (count($data) > 0) \Illuminate\Support\Facades\DB::table('picables')->insert($data);
            Schema::dropIfExists('todolist_p_i_c_s');
        }

        // Migrate EventPIC
        if (Schema::hasTable('event_p_i_c_s')) {
            $events = \Illuminate\Support\Facades\DB::table('event_p_i_c_s')->get();
            $data = [];
            foreach ($events as $event) {
                $data[] = [
                    'user_id' => $event->user_id,
                    'picable_id' => $event->event_id,
                    'picable_type' => 'App\\Models\\Event',
                    'tenant_id' => $event->tenant_id,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                ];
            }
            if (count($data) > 0) \Illuminate\Support\Facades\DB::table('picables')->insert($data);
            Schema::dropIfExists('event_p_i_c_s');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('picables');
    }
};
