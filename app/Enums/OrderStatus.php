<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'PENDING';
    case Diproses = 'DIPROSES';
    case Siap = 'SIAP';
    case Selesai = 'SELESAI';
    case Dibatalkan = 'DIBATALKAN';

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::Pending => [self::Diproses, self::Dibatalkan],
            self::Diproses => [self::Siap],
            self::Siap => [self::Selesai],
            self::Selesai, self::Dibatalkan => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu diproses',
            self::Diproses => 'Diproses',
            self::Siap => 'Siap diambil',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function action(): string
    {
        return match ($this) {
            self::Pending => $this->label(),
            self::Diproses => 'Proses pesanan',
            self::Siap => 'Tandai siap',
            self::Selesai => 'Selesaikan pesanan',
            self::Dibatalkan => 'Batalkan pesanan',
        };
    }
}
