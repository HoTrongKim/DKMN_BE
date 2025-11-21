<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\PriceNormalizer;

class TripResource extends JsonResource
{
    public function toArray($request): array
    {
        $vehicleTypeKey = $this->vehicleTypeKey();
        $rawPrice = max(0, (int) $this->gia_co_ban);
        $fromCityName = $this->noiDiTinhThanh->ten ?? data_get($this->tramDi, 'tinhThanh.ten');
        $toCityName = $this->noiDenTinhThanh->ten ?? data_get($this->tramDen, 'tinhThanh.ten');
        $fromCityId = $this->noi_di_tinh_thanh_id ?? data_get($this->tramDi, 'tinh_thanh_id');
        $toCityId = $this->noi_den_tinh_thanh_id ?? data_get($this->tramDen, 'tinh_thanh_id');

        return [
            'id' => $this->id,
            'company' => $this->nhaVanHanh->ten ?? null,
            'companyId' => $this->nha_van_hanh_id,
            'vehicleTypeKey' => $vehicleTypeKey,
            'vehicleType' => $this->vehicleTypeLabel($vehicleTypeKey),
            'departureTime' => optional($this->gio_khoi_hanh)->format('H:i'),
            'arrivalTime' => optional($this->gio_den)->format('H:i'),
            'departureDateTime' => optional($this->gio_khoi_hanh)->toIso8601String(),
            'arrivalDateTime' => optional($this->gio_den)->toIso8601String(),
            'fromStation' => optional($this->tramDi)->ten,
            'toStation' => optional($this->tramDen)->ten,
            'pickupStationName' => optional($this->tramDi)->ten,
            'dropoffStationName' => optional($this->tramDen)->ten,
            'fromCity' => $fromCityName,
            'toCity' => $toCityName,
            'fromCityId' => $fromCityId,
            'toCityId' => $toCityId,
            'pickupStationId' => $this->tram_di_id,
            'dropoffStationId' => $this->tram_den_id,
            'duration' => $this->formatDuration(),
            'price' => (float) $rawPrice,
            'basePrice' => (float) $rawPrice,
            'displayPrice' => (float) $rawPrice,
            'rating' => $this->ratingValue(),
            'availableSeats' => (int) $this->ghe_con,
            'totalSeats' => (int) $this->tong_ghe,
            'seatType' => $this->seatTypeLabel($vehicleTypeKey),
            'freeCancel' => $this->trang_thai === 'CON_VE',
            'status' => $this->trang_thai,
        ];
    }

    private function formatDuration(): string
    {
        if (!$this->gio_khoi_hanh || !$this->gio_den) {
            return '';
        }

        $minutes = $this->gio_den->diffInMinutes($this->gio_khoi_hanh, false);
        if ($minutes < 0) {
            return '';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%dh%02d', $hours, $mins);
    }

    private function vehicleTypeKey(): string
    {
        return match ($this->nhaVanHanh->loai ?? null) {
            'tau_hoa' => 'train',
            'may_bay' => 'plane',
            default => 'bus',
        };
    }

    private function vehicleTypeLabel(string $vehicleTypeKey): string
    {
        return match ($vehicleTypeKey) {
            'train' => 'Tau hoa',
            'plane' => 'May bay',
            default => 'Xe khach',
        };
    }

    private function seatTypeLabel(string $vehicleTypeKey): string
    {
        return match ($vehicleTypeKey) {
            'plane' => 'Hang pho thong',
            'train' => 'Ghe mem',
            default => 'Ghe ngoi',
        };
    }

    private function ratingValue(): float
    {
        if ($this->rating ?? null) {
            return round((float) $this->rating, 1);
        }

        return round(4.0 + (($this->id % 10) / 20), 1);
    }
}
