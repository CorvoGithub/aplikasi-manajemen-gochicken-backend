<?php
// database/migrations/2025_01_24_000001_create_audit_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->string('action');
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('record_id');
            $table->unsignedInteger('user_id'); // Changed to unsignedInteger to match int
            $table->string('user_role');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Foreign key constraint - now data types match
            $table->foreign('user_id')
                  ->references('id_user')
                  ->on('users')
                  ->onDelete('cascade');
            
            $table->index(['table_name', 'action']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::dropIfExists('audit_logs');
    }
};