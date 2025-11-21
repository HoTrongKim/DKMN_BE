<?php

namespace App\Http\Controllers;

use App\Http\Resources\TripResource;
use App\Models\ChuyenDi;
use App\Models\Ghe;
use App\Services\TripSeatSynchronizer;
use Illuminate\Support\Collection;

class GheController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => Ghe::orderByDesc('ngay_tao')->get()]);
    }
    public function getByChuyenDi(ChuyenDi $chuyenDi)
    {
        TripSeatSynchronizer::sync($chuyenDi);
        $chuyenDi->loadMissing(['ghes', 'nhaVanHanh', 'tramDi.tinhThanh', 'tramDen.tinhThanh']);

        $seats = $chuyenDi->ghes
            ->sortBy('so_ghe', SORT_NATURAL)
            ->values()
            ->map(function (Ghe $ghe) {
                return [
                    'id' => $ghe->id,
                    'code' => $ghe->so_ghe,
                    'label' => $ghe->so_ghe,
                    'type' => $ghe->loai_ghe,
                    'price' => (float) $ghe->gia,
                    'status' => $ghe->trang_thai,
                    'available' => $ghe->trang_thai === 'trong',
                    'booked' => $ghe->trang_thai === 'da_dat',
                    'unavailable' => $ghe->trang_thai === 'khoa',
                ];
            });

        $seatLayout = $this->buildSeatLayout($seats, $chuyenDi);

        return response()->json([
            'status' => true,
            'data' => [
                'trip' => TripResource::make($chuyenDi),
                'seats' => $seats->values(),
                'layout' => $seatLayout,
                'stats' => [
                    'total' => $seats->count(),
                    'available' => $seats->where('available', true)->count(),
                    'booked' => $seats->where('booked', true)->count(),
                ],
            ],
        ]);
    }

    private function buildSeatLayout(Collection $seats, ChuyenDi $chuyenDi): array
    {
        $vehicleKey = match ($chuyenDi->nhaVanHanh->loai ?? null) {
            'tau_hoa' => 'train',
            'may_bay' => 'plane',
            default => 'bus',
        };

        $chunkSize = match ($vehicleKey) {
            'plane' => 6,
            'train' => 8,
            default => 4,
        };

        return $seats
            ->chunk($chunkSize)
            ->map(fn ($chunk) => $chunk->map(function ($seat) {
                return [
                    'id' => $seat['id'],
                    'label' => $seat['label'],
                    'available' => $seat['available'],
                    'booked' => $seat['booked'],
                    'unavailable' => $seat['unavailable'],
                ];
            })->values())
            ->values()
            ->toArray();
    }
}
