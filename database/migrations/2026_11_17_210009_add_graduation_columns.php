<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->boolean('is_final')->default(false)->after('bk_user_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('graduated_at')->nullable()->after('bk_handled_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('graduated_at'));
        Schema::table('grades', fn (Blueprint $table) => $table->dropColumn('is_final'));
    }
};