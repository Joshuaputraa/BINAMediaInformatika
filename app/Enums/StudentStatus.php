<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Normal = 'normal';
    case WaliKelas = 'wali_kelas';
    case Bk = 'bk';
    case Sp1 = 'sp1';
    case Sp2 = 'sp2';
    case Sp3 = 'sp3';
    case Dropout = 'dropout';
    case Graduated = 'lulus';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::WaliKelas => 'Pengawasan wali kelas',
            self::Bk => 'Pengawasan guru BK',
            self::Sp1 => 'SP 1',
            self::Sp2 => 'SP 2',
            self::Sp3 => 'SP 3',
            self::Dropout => 'Dropout',
            self::Graduated => 'Lulus',
        };
    }

    public function isProblem(): bool
    {
        return ! in_array($this, [self::Normal, self::Graduated], true);
    }

    public function isFinal(): bool
    {
        return $this === self::Dropout;
    }

    public function isGraduated(): bool
    {
        return $this === self::Graduated;
    }

    /** Tindakan penanganan yang boleh dipilih (bukan status normal atau lulus). */
    public static function actions(): array
    {
        return array_filter(self::cases(), fn ($c) => ! in_array($c, [self::Normal, self::Graduated], true));
    }
}