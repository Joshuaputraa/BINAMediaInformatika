<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('bk_user_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('grade_id')->nullable()->after('homeroom_teacher_id')->constrained('grades');
            $table->foreignId('major_id')->nullable()->after('grade_id')->constrained('majors');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('major_id');
            $table->dropConstrainedForeignId('grade_id');
        });

        Schema::dropIfExists('majors');
        Schema::dropIfExists('grades');
    }
};