<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\HomeroomTeacher;
use App\Models\Major;
use App\Services\PromotionService;
use App\Services\StudentImportService;
use App\Services\StudentTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BulkUpdateController extends Controller
{
    public function index(Request $request, PromotionService $promotion)
    {
        $filters = $promotion->filters($request);

        $students = $promotion->query($filters)
            ->with(['classroom.grade:id,name', 'classroom.major:id,name', 'homeroomTeacher:id,name', 'bk:id,name'])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'name' => $s->name,
                'classroom' => $s->classroom?->name,
                'major' => $s->classroom?->major?->name,
                'teacher' => $s->homeroomTeacher?->name,
                'bk' => $s->bk?->name,
            ]);

        return Inertia::render('BulkUpdate/Index', [
            'students' => $students,
            'filters' => $filters,
            'classrooms' => Classroom::with(['grade.bk:id,name', 'major:id,name'])
                ->withCount(['students as active_count' => fn ($q) => $q->whereNotIn('status', ['dropout', 'lulus'])])
                ->get()
                ->sortBy(fn ($c) => $c->name, SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'major' => $c->major->name,
                    'bk' => $c->grade->bk?->name,
                    'final' => (bool) $c->grade->is_final,
                    'active' => $c->active_count,
                ]),
            'majors' => Major::orderBy('name')->get(['id', 'name']),
            'teachers' => HomeroomTeacher::orderBy('name')->get(['id', 'nip', 'name']),
            'nisDigits' => (int) config('school.nis_digits'),
            'maxRows' => StudentTemplateService::MAX_ROWS,
        ]);
    }

    public function template(StudentTemplateService $template)
    {
        $spreadsheet = $template->build();

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-siswa-baru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Import khusus siswa baru. */
    public function import(Request $request, StudentImportService $importer)
    {
        $request->validate([
            'file' => [
                'bail', 'required', 'file', 'max:5120',
                function ($attribute, $value, $fail) {
                    if (strtolower($value->getClientOriginalExtension()) !== 'xlsx') {
                        $fail('File harus berformat .xlsx (gunakan template yang disediakan).');
                    }
                },
            ],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $result = $importer->import($request->file('file'), $request->boolean('dry_run'));

        return back()->with('import_result', [
            'ok' => ! $result->failed(),
            'dry_run' => $result->dryRun,
            'created' => $result->created,
            'error_count' => $result->errorCount,
            'errors' => $result->errors,
        ]);
    }
}