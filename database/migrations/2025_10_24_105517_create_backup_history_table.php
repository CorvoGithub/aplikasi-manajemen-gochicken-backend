<?php
// database/migrations/2025_01_24_000000_create_backup_history_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('backup_history')) {
            Schema::create('backup_history', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('file_type')->default('sql');
                $table->bigInteger('file_size')->default(0);
                $table->string('backup_type')->default('manual');
                $table->unsignedInteger('user_id');
                $table->string('user_role');
                $table->text('file_path')->nullable();
                $table->boolean('success')->default(true);
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('backup_history');
    }
};