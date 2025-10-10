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
        Schema::create('bahan_baku_harian', function (Blueprint $table) {
            $table->id('id_pemakaian');
            $table->date('tanggal');
            $table->decimal('jumlah_pakai', 10, 2);
            $table->string('catatan')->nullable();
            $table->unsignedInteger('id_bahan_baku');
            $table->foreign('id_bahan_baku')
                ->references('id_bahan_baku')
                ->on('bahan_baku')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_baku_pakai_harian');
    }
};
