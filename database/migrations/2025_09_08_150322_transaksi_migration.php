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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->increments('id_transaksi');
            $table->string('kode_transaksi', 50);
            $table->dateTime('tanggal_waktu')->useCurrent();
            $table->decimal('total_harga', 12, 2);
            $table->string('metode_pembayaran', 50)->nullable();
            $table->enum('source', ['android', 'manual'])->default('manual');
            $table->enum('status_transaksi', ['Selesai', 'OnLoan'])->default('Selesai');
            $table->string('nama_pelanggan', 100)->nullable();
            $table->string('id_cabang')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
