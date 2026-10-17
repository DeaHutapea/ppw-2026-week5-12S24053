<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('peminjamans')
            ->where('status', 'kembali')
            ->update(['status' => 'dikembalikan']);
    }

    public function down(): void
    {
        DB::table('peminjamans')
            ->where('status', 'dikembalikan')
            ->update(['status' => 'kembali']);
    }
};
