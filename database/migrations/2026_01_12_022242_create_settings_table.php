<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Kunci, misal: 'google_sheet_id'
            $table->text('value')->nullable(); // Isi ID Spreadsheet
            $table->timestamps();
        });

        // Opsional: Insert data awal kosong agar tidak error saat pertama kali query
        DB::table('settings')->insert([
            'key' => 'google_sheet_id',
            'value' => null, 
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};