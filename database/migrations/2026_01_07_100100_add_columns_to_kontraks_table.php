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
        Schema::table('kontraks', function (Blueprint $table) {
            // Tambahan kolom dari Google Sheet
            $table->string('inc_ppn')->nullable()->after('nilai');
            $table->date('tgl_bayar')->nullable()->after('inc_ppn');
            $table->string('unit')->nullable()->after('tgl_bayar');
            $table->string('mutu')->nullable()->after('unit');
            $table->string('nomor_dosi')->nullable()->after('mutu');
            $table->date('tgl_dosi')->nullable()->after('nomor_dosi');
            $table->string('port')->nullable()->after('tgl_dosi');
            $table->string('kontrak_sap')->nullable()->after('port');
            $table->string('dp_sap')->nullable()->after('kontrak_sap');
            $table->string('so_sap')->nullable()->after('dp_sap');
            $table->string('kode_do')->nullable()->after('so_sap');
            $table->decimal('sisa_awal', 15, 2)->nullable()->after('kode_do');
            $table->date('jatuh_tempo')->nullable()->after('sisa_awal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontraks', function (Blueprint $table) {
            $table->dropColumn([
                'inc_ppn', 'tgl_bayar', 'unit', 'mutu', 'nomor_dosi', 'tgl_dosi',
                'port', 'kontrak_sap', 'dp_sap', 'so_sap', 'kode_do', 'sisa_awal', 'jatuh_tempo'
            ]);
        });
    }
};
