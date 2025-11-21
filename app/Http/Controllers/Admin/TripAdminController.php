<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TripCustomerNotificationMail;
use App\Models\ChuyenDi;
use App\Models\NguoiDung;
use App\Models\ThongBao;
use App\Models\Tram;
use App\Services\TripSeatSynchronizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TripAdminController extends Controller
{
    private static ?bool $tripProvinceColumns = null;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:150',
            'status' => 'nullable|string|in:AVAILABLE,SOLD_OUT,CANCELLED,CON_VE,HET_VE,HUY,con_ve,het_ve,huy',
            'type' => 'nullable|string|in:bus,train,plane',
            'operatorId' => 'nullable|integer|exists:nha_van_hanhs,id',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ]);

        $query = ChuyenDi::query()
            ->with([
                'nhaVanHanh',
                'tramDi.tinhThanh',
                'tramDen.tinhThanh',
                'noiDiTinhThanh',
                'noiDenTinhThanh',
            ])
            ->orderByDesc('gio_khoi_hanh');

        if (!empty($validated['keyword'])) {
            $keyword = Str::lower(trim($validated['keyword']));
            $query->where(function ($sub) use ($keyword) {
                $sub->where('id', (int) $keyword)
                    ->orWhereHas('nhaVanHanh', fn ($q) => $q->where('ten', 'like', "%{$keyword}%"))
                    ->orWhereHas('tramDi', fn ($q) => $q->where('ten', 'like', "%{$keyword}%"))
                    ->orWhereHas('tramDen', fn ($q) => $q->where('ten', 'like', "%{$keyword}%"));
            });
        }

        if (!empty($validated['status'])) {
            $status = $this->normalizeStatus($validated['status']);
            if ($status) {
                $query->where('trang_thai', $status);
            }
        }

        if (!empty($validated['type'])) {
            $type = $this->mapFrontendTypeToInternal($validated['type']);
            if ($type) {
                $query->whereHas('nhaVanHanh', fn ($q) => $q->where('loai', $type));
            }
        }

        if (!empty($validated['operatorId'])) {
            $query->where('nha_van_hanh_id', $validated['operatorId']);
        }

        if (!empty($validated['dateFrom'])) {
            $query->where('gio_khoi_hanh', '>=', Carbon::parse($validated['dateFrom'])->startOfDay());
        }

        if (!empty($validated['dateTo'])) {
            $query->where('gio_khoi_hanh', '<=', Carbon::parse($validated['dateTo'])->endOfDay());
        }

        $paginator = $query->paginate($this->resolvePerPage($request));
        $data = $paginator->getCollection()
            ->map(fn (ChuyenDi $trip) => $this->transformTrip($trip));

        return $this->respondWithPagination($paginator, $data);
    }

    public function show(ChuyenDi $chuyenDi)
    {
        $chuyenDi->load([
            'nhaVanHanh',
            'tramDi.tinhThanh',
            'tramDen.tinhThanh',
            'noiDiTinhThanh',
            'noiDenTinhThanh',
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->transformTrip($chuyenDi),
        ]);
    }

    public function store(Request $request)
    {
        $payload = $this->validateTripPayload($request);

        $trip = ChuyenDi::create($payload);
        TripSeatSynchronizer::sync($trip);
        $trip = $trip->fresh([
            'nhaVanHanh',
            'tramDi.tinhThanh',
            'tramDen.tinhThanh',
            'noiDiTinhThanh',
            'noiDenTinhThanh',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tạo chuyến đi thành công.',
            'data' => $this->transformTrip($trip),
        ], 201);
    }

    public function update(Request $request, ChuyenDi $chuyenDi)
    {
        $payload = $this->validateTripPayload($request, true);

        if (!empty($payload)) {
            $payload['ngay_cap_nhat'] = now();
            $chuyenDi->fill($payload)->save();
        }

        TripSeatSynchronizer::sync($chuyenDi);

        $chuyenDi->load([
            'nhaVanHanh',
            'tramDi.tinhThanh',
            'tramDen.tinhThanh',
            'noiDiTinhThanh',
            'noiDenTinhThanh',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật chuyến đi thành công.',
            'data' => $this->transformTrip($chuyenDi),
        ]);
    }

    public function destroy(ChuyenDi $chuyenDi)
    {
        if ($chuyenDi->donHangs()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa chuyến đi vì đã phát sinh đơn hàng.',
            ], 422);
        }

        $chuyenDi->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa chuyến đi.',
        ]);
    }

    public function notify(Request $request, ChuyenDi $chuyenDi)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', Rule::in(['email', 'app', 'sms'])],
            'recipientIds' => ['required', 'array', 'min:1'],
            'recipientIds.*' => ['integer', 'exists:nguoi_dungs,id'],
        ]);

        $channels = collect($validated['channels'])->unique()->values()->all();
        $recipients = NguoiDung::query()
            ->whereIn('id', $validated['recipientIds'])
            ->where('vai_tro', '!=', 'quan_tri')
            ->get();

        if ($recipients->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Khong tim thay khach hang hop le.',
            ], 422);
        }

        $chuyenDi->loadMissing(['nhaVanHanh', 'tramDi.tinhThanh', 'tramDen.tinhThanh']);
        $title = $this->buildNotificationTitle($chuyenDi);
        $summary = $this->buildTripSummary($chuyenDi);

        $appCount = 0;
        $emailCount = 0;

        foreach ($recipients as $recipient) {
            if (in_array('app', $channels, true)) {
                ThongBao::create([
                    'nguoi_dung_id' => $recipient->id,
                    'tieu_de' => $title,
                    'noi_dung' => $validated['message'],
                    'loai' => 'trip_update',
                    'da_doc' => 0,
                ]);
                $appCount++;
            }

            if (in_array('email', $channels, true) && !empty($recipient->email)) {
                Mail::to($recipient->email)->send(
                    new TripCustomerNotificationMail($recipient, $chuyenDi, $validated['message'], $summary)
                );
                $emailCount++;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Da gui thong bao den khach hang.',
            'data' => [
                'recipients' => $recipients->count(),
                'app' => $appCount,
                'email' => $emailCount,
                'sms' => in_array('sms', $channels, true) ? 0 : null,
            ],
        ]);
    }
    private function buildNotificationTitle(ChuyenDi $trip): string
    {
        $from = $trip->tramDi->ten ?? 'Điểm đi';
        $to = $trip->tramDen->ten ?? 'Điểm đến';
        $time = optional($trip->gio_khoi_hanh)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');

        return trim(sprintf('Thông báo chuyến %s → %s %s', $from, $to, $time ? "($time)" : ''));
    }

    private function buildTripSummary(ChuyenDi $trip): array
    {
        return [
            'route' => trim(($trip->tramDi->ten ?? '...') . ' → ' . ($trip->tramDen->ten ?? '...')),
            'operator' => $trip->nhaVanHanh->ten ?? null,
            'vehicle' => $trip->nhaVanHanh->loai ?? null,
            'departure' => optional($trip->gio_khoi_hanh)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
            'arrival' => optional($trip->gio_den)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
        ];
    }

    private function ascii(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::ascii($value);
    }
    private function validateTripPayload(Request $request, bool $isUpdate = false): array
    {
        $hasProvinceColumns = $this->hasTripProvinceColumns();
        $provinceRule = $hasProvinceColumns
            ? [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:tinh_thanhs,id']
            : ['nullable', 'integer', 'exists:tinh_thanhs,id'];

        $rules = [
            'operatorId' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:nha_van_hanhs,id'],
            'fromProvinceId' => $provinceRule,
            'toProvinceId' => $provinceRule,
            'fromStationId' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:trams,id', 'different:toStationId'],
            'toStationId' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:trams,id'],
            'departureTime' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'arrivalTime' => [$isUpdate ? 'sometimes' : 'required', 'date', 'after:departureTime'],
            'basePrice' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'totalSeats' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:1', 'max:200'],
            'remainingSeats' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['AVAILABLE', 'SOLD_OUT', 'CANCELLED', 'CON_VE', 'HET_VE', 'HUY'])],
        ];

        $validated = $request->validate($rules);
        $payload = [];
        $fromStationModel = null;
        $toStationModel = null;

        if (array_key_exists('operatorId', $validated)) {
            $payload['nha_van_hanh_id'] = $validated['operatorId'];
        }
        if ($hasProvinceColumns && array_key_exists('fromProvinceId', $validated)) {
            $payload['noi_di_tinh_thanh_id'] = $validated['fromProvinceId'];
        }
        if ($hasProvinceColumns && array_key_exists('toProvinceId', $validated)) {
            $payload['noi_den_tinh_thanh_id'] = $validated['toProvinceId'];
        }
        if (array_key_exists('fromStationId', $validated)) {
            $payload['tram_di_id'] = $validated['fromStationId'];
            $fromStationModel = Tram::find($validated['fromStationId']);
            if (!$fromStationModel) {
                throw ValidationException::withMessages([
                    'fromStationId' => 'Bến xuất phát không hợp lệ.',
                ]);
            }
        }
        if (array_key_exists('toStationId', $validated)) {
            $payload['tram_den_id'] = $validated['toStationId'];
            $toStationModel = Tram::find($validated['toStationId']);
            if (!$toStationModel) {
                throw ValidationException::withMessages([
                    'toStationId' => 'Bến đến không hợp lệ.',
                ]);
            }
        }
        if ($hasProvinceColumns) {
            if (!array_key_exists('noi_di_tinh_thanh_id', $payload) && $fromStationModel) {
                $payload['noi_di_tinh_thanh_id'] = $fromStationModel->tinh_thanh_id;
            }
            if (!array_key_exists('noi_den_tinh_thanh_id', $payload) && $toStationModel) {
                $payload['noi_den_tinh_thanh_id'] = $toStationModel->tinh_thanh_id;
            }
            $this->validateStationProvinceConsistency($fromStationModel, $payload['noi_di_tinh_thanh_id'] ?? null, 'fromProvinceId');
            $this->validateStationProvinceConsistency($toStationModel, $payload['noi_den_tinh_thanh_id'] ?? null, 'toProvinceId');
        }

        if (array_key_exists('departureTime', $validated)) {
            $payload['gio_khoi_hanh'] = Carbon::parse($validated['departureTime']);
        }
        if (array_key_exists('arrivalTime', $validated)) {
            $payload['gio_den'] = Carbon::parse($validated['arrivalTime']);
        }
        if (array_key_exists('basePrice', $validated)) {
            $payload['gia_co_ban'] = $validated['basePrice'];
        }
        if (array_key_exists('totalSeats', $validated)) {
            $payload['tong_ghe'] = $validated['totalSeats'];
            if (!isset($validated['remainingSeats']) && !$isUpdate) {
                $payload['ghe_con'] = $validated['totalSeats'];
            }
        }
        if (array_key_exists('remainingSeats', $validated)) {
            $remaining = $validated['remainingSeats'];
            if (isset($payload['tong_ghe'])) {
                $remaining = min($payload['tong_ghe'], $remaining);
            }
            $payload['ghe_con'] = $remaining;
        }
        if (array_key_exists('status', $validated)) {
            $normalized = $this->normalizeStatus($validated['status']);
            if ($normalized) {
                $payload['trang_thai'] = $normalized;
            }
        }

        return $payload;
    }

    private function validateStationProvinceConsistency(?Tram $station, ?int $provinceId, string $field): void
    {
        if (!$station || !$provinceId) {
            return;
        }

        if ((int) $station->tinh_thanh_id !== (int) $provinceId) {
            throw ValidationException::withMessages([
                $field => 'Bến không thuộc tỉnh/thành đã chọn.',
            ]);
        }
    }

    private function hasTripProvinceColumns(): bool
    {
        if (self::$tripProvinceColumns === null) {
            self::$tripProvinceColumns =
                Schema::hasColumn('chuyen_dis', 'noi_di_tinh_thanh_id') &&
                Schema::hasColumn('chuyen_dis', 'noi_den_tinh_thanh_id');
        }

        return self::$tripProvinceColumns;
    }

    private function transformTrip(ChuyenDi $trip): array
    {
        $operator = $trip->nhaVanHanh;
        $from = $trip->tramDi;
        $to = $trip->tramDen;
        $departureTime = $trip->gio_khoi_hanh?->toIso8601String();
        $arrivalTime = $trip->gio_den?->toIso8601String();
        $fromProvinceId = $trip->noi_di_tinh_thanh_id ?? $from?->tinh_thanh_id;
        $toProvinceId = $trip->noi_den_tinh_thanh_id ?? $to?->tinh_thanh_id;
        $fromProvinceName = $trip->noiDiTinhThanh->ten ?? $from?->tinhThanh?->ten;
        $toProvinceName = $trip->noiDenTinhThanh->ten ?? $to?->tinhThanh?->ten;

        $derivedStatus = $this->deriveStatus($trip);

        return [
            'id' => $trip->id,
            'type' => $this->mapOperatorType($operator?->loai),
            'operatorType' => $operator?->loai,
            'carrier' => $operator?->ten,
            'carrierAscii' => $this->ascii($operator?->ten),
            'operatorId' => $operator?->id,
            'operator' => $operator?->ten,
            'route' => trim(($from?->ten ?? '') . ' - ' . ($to?->ten ?? '')),
            'routeAscii' => $this->ascii(trim(($from?->ten ?? '') . ' - ' . ($to?->ten ?? ''))),
            'departureLocation' => $from?->ten ?? '',
            'departureLocationAscii' => $this->ascii($from?->ten ?? ''),
            'arrivalLocation' => $to?->ten ?? '',
            'arrivalLocationAscii' => $this->ascii($to?->ten ?? ''),
            'fromStationId' => $from?->id,
            'fromStation' => $from?->ten,
            'toStationId' => $to?->id,
            'toStation' => $to?->ten,
            'fromProvinceId' => $fromProvinceId,
            'fromProvinceName' => $fromProvinceName,
            'toProvinceId' => $toProvinceId,
            'toProvinceName' => $toProvinceName,
            'departureTime' => $departureTime,
            'arrivalTime' => $arrivalTime,
            'departAt' => $departureTime,
            'arrivalAt' => $arrivalTime,
            'basePrice' => (float) $trip->gia_co_ban,
            'seats' => [
                'total' => (int) $trip->tong_ghe,
                'remaining' => (int) $trip->ghe_con,
            ],
            'totalSeats' => (int) $trip->tong_ghe,
            'availableSeats' => (int) $trip->ghe_con,
            'status' => $derivedStatus['code'],
            'statusLabel' => $derivedStatus['label'],
            'rawStatus' => $trip->trang_thai,
            'createdAt' => $trip->ngay_tao?->toIso8601String(),
            'updatedAt' => $trip->ngay_cap_nhat?->toIso8601String(),
        ];
    }

    private function deriveStatus(ChuyenDi $trip): array
    {
        $rawNormalized = $this->normalizeStatus($trip->trang_thai);

        if ($rawNormalized === 'HUY') {
            return [
                'code' => 'CANCELLED',
                'label' => 'Đã hủy',
            ];
        }

        $arrival = $trip->gio_den;
        if ($arrival && $arrival->isPast()) {
            return [
                'code' => 'COMPLETED',
                'label' => 'Đã hoàn thành',
            ];
        }

        $code = $this->mapTripStatusCode($trip->trang_thai);

        return [
            'code' => $code,
            'label' => $this->mapTripStatus($trip->trang_thai),
        ];
    }

    private function mapTripStatusCode(?string $status): string
    {
        return match ($status) {
            'CON_VE' => 'AVAILABLE',
            'HET_VE' => 'SOLD_OUT',
            'HUY' => 'CANCELLED',
            'COMPLETED' => 'COMPLETED',
            default => 'UNKNOWN',
        };
    }

    private function mapTripStatus(?string $status): string
    {
        return match ($status) {
            'CON_VE' => 'Còn vé',
            'HET_VE' => 'Hết vé',
            'HUY' => 'Đã hủy',
            'COMPLETED' => 'Đã hoàn thành',
            default => 'Không xác định',
        };
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match (strtoupper($status)) {
            'AVAILABLE', 'CON_VE' => 'CON_VE',
            'SOLD_OUT', 'HET_VE' => 'HET_VE',
            'CANCELLED', 'HUY' => 'HUY',
            default => null,
        };
    }

    private function mapOperatorType(?string $type): string
    {
        return match ($type) {
            'tau_hoa' => 'train',
            'may_bay' => 'plane',
            'xe_khach' => 'bus',
            default => 'bus',
        };
    }

    private function mapFrontendTypeToInternal(?string $type): ?string
    {
        if (!$type) {
            return null;
        }

        return match (strtolower($type)) {
            'bus' => 'xe_khach',
            'train' => 'tau_hoa',
            'plane' => 'may_bay',
            default => null,
        };
    }
}
