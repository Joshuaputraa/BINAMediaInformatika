<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades');
            $table->foreignId('major_id')->constrained('majors');
            $table->unsignedSmallInteger('number');
            $table->timestamps();

            $table->unique(['grade_id', 'major_id', 'number']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullable()->after('homeroom_teacher_id')->constrained('classrooms');
        });

        // Pindahkan data lama: tiap pasangan (tingkat, jurusan) menjadi kelas bernomor 1
        DB::table('students')
            ->whereNotNull('grade_id')
            ->whereNotNull('major_id')
            ->select('grade_id', 'major_id')
            ->distinct()
            ->get()
            ->each(function ($pair) {
                $id = DB::table('classrooms')->insertGetId([
                    'grade_id' => $pair->grade_id,
                    'major_id' => $pair->major_id,
                    'number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('students')
                    ->where('grade_id', $pair->grade_id)
                    ->where('major_id', $pair->major_id)
                    ->update(['classroom_id' => $id]);
            });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_id');
            $table->dropConstrainedForeignId('major_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('grade_id')->nullable()->after('homeroom_teacher_id')->constrained('grades');
            $table->foreignId('major_id')->nullable()->after('grade_id')->constrained('majors');
        });

        DB::statement('UPDATE students s JOIN classrooms c ON c.id = s.classroom_id SET s.grade_id = c.grade_id, s.major_id = c.major_id');

        Schema::table('students', fn (Blueprint $table) => $table->dropConstrainedForeignId('classroom_id'));
        Schema::dropIfExists('classrooms');
    }
};