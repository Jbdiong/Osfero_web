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
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('system_roles')->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        // Migrate existing data from users table
        \DB::statement('INSERT INTO tenant_user (user_id, tenant_id, role_id, display_name, created_at, updated_at) 
                        SELECT id, tenant_id, role_id, name, created_at, updated_at 
                        FROM users 
                        WHERE tenant_id IS NOT NULL');

        // Rename original tenant_id to last_active_tenant_id
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('tenant_id', 'last_active_tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('last_active_tenant_id', 'tenant_id');
        });

        Schema::dropIfExists('tenant_user');
    }
};
