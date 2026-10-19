<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('homeroom_teacher_id')->nullable()->after('status')
                ->constrained('homeroom_teachers')->nullOnDelete();
        });

        Schema::table('handlings', function (Blueprint $table) {
            $table->foreignId('bk_user_id')->nullable()->after('homeroom_teacher_id')
                ->constrained('users')->nullOnDelete();
        });

        // Data lama: wali kelas siswa = wali kelas dari penanganan wali kelas terakhir
        DB::table('students')->pluck('id')->each(function ($id) {
            $teacherId = DB::table('handlings')
                ->where('student_id', $id)
                ->where('type', 'wali_kelas')
                ->whereNotNull('homeroom_teacher_id')
                ->orderByDesc('id')
                ->value('homeroom_teacher_id');

            if ($teacherId) {
                DB::table('students')->where('id', $id)->update(['homeroom_teacher_id' => $teacherId]);
            }
        });

        // Data lama: penanganan oleh akun guru BK -> guru BK-nya adalah pencatatnya
        $bkIds = DB::table('users')->where('role', 'bk')->pluck('id');

        DB::table('handlings')
            ->where('type', 'bk')
            ->whereIn('user_id', $bkIds)
            ->update(['bk_user_id' => DB::raw('user_id')]);

        // Data lama: rujukan oleh kesiswaan -> pakai guru BK tujuan siswa saat ini
        DB::table('handlings')->where('type', 'bk')->whereNull('bk_user_id')->get()->each(function ($h) {
            $bk = DB::table('students')->where('id', $h->student_id)->value('bk_user_id');

            if ($bk) {
                DB::table('handlings')->where('id', $h->id)->update(['bk_user_id' => $bk]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('handlings', fn (Blueprint $t) => $t->dropConstrainedForeignId('bk_user_id'));
        Schema::table('students', fn (Blueprint $t) => $t->dropConstrainedForeignId('homeroom_teacher_id'));
    }
};