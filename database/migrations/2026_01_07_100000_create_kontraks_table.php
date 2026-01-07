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
        Schema::create('kontraks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kontrak')->unique()->index();
            $table->string('loex')->nullable();
            $table->string('nama_pembeli')->nullable();
            $table->date('tgl_kontrak')->nullable();
            $table->decimal('volume', 15, 2)->nullable();
            $table->decimal('harga', 15, 2)->nullable();
            $table->decimal('nilai', 15, 2)->nullable();
            $table->decimal('total_layan', 15, 2)->nullable();
            $table->decimal('sisa_akhir', 15, 2)->nullable();
            $table->string('origin_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontraks');
    }
};
