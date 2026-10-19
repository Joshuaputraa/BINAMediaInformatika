<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request)
    {
        $f = $this->filters($request);
        $bkId = $this->bkScope($request);

        $rows = $this->reports->rows($f['from'], $f['to'], $f['type'], $bkId)
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($h) => [
                'id' => $h->id,
                'date' => $h->created_at->format('d/m/Y'),
                'type' => $h->type->value,
                'nis' => $h->student?->nis,
                'student' => $h->student?->name,
                'teacher' => $h->homeroomName(),
                'bk' => $h->bkName(),
                'by' => $h->user?->name,
                'notes' => $h->notes,
            ]);

        return Inertia::render('Reports/Index', [
            'rows' => $rows,
            'summary' => $this->reports->summary($f['from'], $f['to'], $f['type'], $bkId),
            'filters' => $f,
        ]);
    }

    public function pdf(Request $request)
    {
        $f = $this->filters($request);
        $bkId = $this->bkScope($request);

        $pdf = Pdf::loadView('reports.pdf', [
            'rows' => $this->reports->rows($f['from'], $f['to'], $f['type'], $bkId)->get(),
            'summary' => $this->reports->summary($f['from'], $f['to'], $f['type'], $bkId),
            'periode' => $this->periode($f['from'], $f['to']),
            'typeLabel' => $f['type'] ? StudentStatus::from($f['type'])->label() : null,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-penanganan-siswa.pdf');
    }

    /** Guru BK hanya melihat laporan siswa yang dirujuk kepadanya. */
    private function bkScope(Request $request): ?int
    {
        $user = $request->user();

        return $user->role === 'bk' ? $user->id : null;
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'type' => ['nullable', Rule::enum(StudentStatus::class)->only(StudentStatus::actions())],
        ]);

        return [
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'type' => $data['type'] ?? null,
        ];
    }

    private function periode(?string $from, ?string $to): string
    {
        $from = $from ? Carbon::parse($from)->format('d/m/Y') : 'awal';
        $to = $to ? Carbon::parse($to)->format('d/m/Y') : 'sekarang';

        return "$from - $to";
    }
}