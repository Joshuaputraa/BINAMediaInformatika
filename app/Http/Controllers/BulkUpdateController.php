<?php

namespace App\Http\Controllers;

use App\Services\StudentImportService;
use App\Services\StudentTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BulkUpdateController extends Controller
{
    public function index()
    {
        return Inertia::render('BulkUpdate/Index', [
            'nisDigits' => (int) config('school.nis_digits'),
            'maxRows' => StudentTemplateService::MAX_ROWS,
        ]);
    }

    public function template(StudentTemplateService $template)
    {
        $spreadsheet = $template->build();

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-update-massal-siswa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

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

        $result = $importer->import(
            $request->file('file'),
            $request->boolean('allow_create'),
            $request->boolean('dry_run'),
        );

        return back()->with('import_result', [
            'ok' => ! $result->failed(),
            'dry_run' => $result->dryRun,
            'updated' => $result->updated,
            'created' => $result->created,
            'error_count' => $result->errorCount,
            'errors' => $result->errors,
        ]);
    }
}