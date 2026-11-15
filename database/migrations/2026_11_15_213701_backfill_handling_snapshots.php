<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('handlings')
            ->orderBy('student_id')
            ->orderBy('id')
            ->get()
            ->groupBy('student_id')
            ->each(function ($rows) {
                $teacher = null;
                $bk = null;

                foreach ($rows as $h) {
                    if ($h->homeroom_teacher_id) {
                        $teacher = $h->homeroom_teacher_id;
                    }
                    if ($h->type === 'bk' && $h->bk_user_id) {
                        $bk = $h->bk_user_id;
                    }

                    $changes = [];

                    if (! $h->homeroom_teacher_id && $teacher) {
                        $changes['homeroom_teacher_id'] = $teacher;
                    }
                    if (! $h->bk_user_id && $bk && $h->type !== 'wali_kelas') {
                        $changes['bk_user_id'] = $bk;
                    }

                    if ($changes) {
                        DB::table('handlings')->where('id', $h->id)->update($changes);
                    }
                }
            });
    }

    public function down(): void
    {
        // data tambahan saja, tidak perlu dibalik
    }
};