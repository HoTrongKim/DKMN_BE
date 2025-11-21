<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PaymentReportExport;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ThanhToan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:online,manual',
            'status' => 'nullable|string|in:pending,success,failed,refunded',
            'method' => 'nullable|string|max:50',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ]);

        $hasPayments = $this->hasPaymentsTable();
        $type = $validated['type'] ?? ($hasPayments ? 'online' : 'manual');

        if ($type === 'manual') {
            return $this->manualPayments($request, $validated);
        }

        if (!$hasPayments) {
            return $this->emptyOnlinePaymentsResponse($request);
        }

        return $this->onlinePayments($request, $validated);
    }

    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|string|in:online,manual',
            'status' => 'nullable|string|in:pending,success,failed,refunded',
            'method' => 'nullable|string|max:50',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
            'limit' => 'nullable|integer|min:10|max:10000',
        ]);

        $hasPayments = $this->hasPaymentsTable();
        $type = $validated['type'] ?? ($hasPayments ? 'online' : 'manual');
        $limit = $this->resolveExportLimit($request);

        if ($type === 'manual') {
            $records = $this->manualPaymentsQuery($validated)->limit($limit)->get();
            $rows = $this->mapManualExportRows($records);
        } else {
            if (!$hasPayments) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bảng payments chưa sẵn sàng. Không thể xuất báo cáo giao dịch online.',
                ], 422);
            }
            $records = $this->onlinePaymentsQuery($validated)->limit($limit)->get();
            $rows = $this->mapOnlineExportRows($records);
        }

        if ($rows->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Không có giao dịch phù hợp để xuất.',
            ], 422);
        }

        $now = Carbon::now(config('app.timezone'));
        $summary = $this->summarizeExportRows($rows);

        $meta = array_merge($summary, [
            'title' => 'Báo cáo thanh toán & giao dịch',
            'subtitle' => $type === 'manual' ? 'Thanh toán thủ công' : 'Thanh toán online',
            'period' => $this->describePeriod($validated),
            'filters' => $this->describeFilters($validated, $type),
            'generatedAt' => $now,
        ]);

        $export = new PaymentReportExport($rows, $meta);
        $fileName = sprintf('dkmn-payments-%s.xlsx', $now->format('Ymd_His'));

        return Excel::download($export, $fileName);
    }

    private function onlinePayments(Request $request, array $filters)
    {
        $paginator = $this->onlinePaymentsQuery($filters)->paginate($this->resolvePerPage($request));
        $data = $paginator->getCollection()->map(function (Payment $payment) {
            $order = $payment->ticket?->donHang;

            return [
                'id' => $payment->id,
                'orderId' => $order?->id,
                'amount' => (int) $payment->amount_vnd,
                'method' => $payment->method,
                'provider' => $payment->provider,
                'status' => $payment->status,
                'statusLabel' => $this->mapOnlineStatusLabel($payment->status),
                'paidAt' => optional($payment->paid_at)->toIso8601String(),
            ];
        });

        return $this->respondWithPagination($paginator, $data);
    }

    private function manualPayments(Request $request, array $filters)
    {
        $paginator = $this->manualPaymentsQuery($filters)->paginate($this->resolvePerPage($request));
        $data = $paginator->getCollection()->map(function (ThanhToan $payment) {
            $order = $payment->donHang;

            return [
                'id' => $payment->id,
                'orderId' => $order?->id,
                'amount' => (float) $payment->so_tien,
                'method' => 'MANUAL',
                'provider' => $payment->cong_thanh_toan,
                'status' => $payment->trang_thai,
                'statusLabel' => $this->mapManualStatusLabel($payment->trang_thai),
                'paidAt' => optional($payment->thoi_diem_thanh_toan)->toIso8601String(),
            ];
        });

        return $this->respondWithPagination($paginator, $data);
    }

    private function onlinePaymentsQuery(array $filters): Builder
    {
        $query = Payment::query()->with('ticket.donHang')->orderByDesc('paid_at');

        if (!empty($filters['status'])) {
            $query->where('status', $this->mapOnlineStatus($filters['status']));
        }

        if (!empty($filters['method'])) {
            $query->where('method', Str::upper($filters['method']));
        }

        if (!empty($filters['dateFrom'])) {
            $query->where('paid_at', '>=', Carbon::parse($filters['dateFrom'])->startOfDay());
        }

        if (!empty($filters['dateTo'])) {
            $query->where('paid_at', '<=', Carbon::parse($filters['dateTo'])->endOfDay());
        }

        return $query;
    }

    private function manualPaymentsQuery(array $filters): Builder
    {
        $query = ThanhToan::query()->with('donHang')->orderByDesc('thoi_diem_thanh_toan');

        if (!empty($filters['status'])) {
            $query->where('trang_thai', $this->mapManualStatus($filters['status']));
        }

        if (!empty($filters['method'])) {
            $query->where('cong_thanh_toan', $filters['method']);
        }

        if (!empty($filters['dateFrom'])) {
            $query->where('thoi_diem_thanh_toan', '>=', Carbon::parse($filters['dateFrom'])->startOfDay());
        }

        if (!empty($filters['dateTo'])) {
            $query->where('thoi_diem_thanh_toan', '<=', Carbon::parse($filters['dateTo'])->endOfDay());
        }

        return $query;
    }

    private function mapOnlineStatus(string $status): string
    {
        return match ($status) {
            'success' => 'SUCCEEDED',
            'failed' => 'FAILED',
            'refunded' => 'REFUNDED',
            default => 'PENDING',
        };
    }

    private function mapManualStatus(string $status): string
    {
        return match ($status) {
            'success' => 'thanh_cong',
            'refunded' => 'hoan_tien',
            default => 'cho',
        };
    }

    private function mapOnlineStatusLabel(?string $status): string
    {
        return match ($status) {
            'SUCCEEDED' => 'Thành công',
            'FAILED' => 'Thất bại',
            'REFUNDED' => 'Đã hoàn',
            'EXPIRED' => 'Hết hạn',
            default => 'Đang chờ',
        };
    }

    private function mapManualStatusLabel(?string $status): string
    {
        return match ($status) {
            'thanh_cong' => 'Thành công',
            'hoan_tien' => 'Hoàn tiền',
            default => 'Đang chờ',
        };
    }

    private function emptyOnlinePaymentsResponse(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'currentPage' => 1,
                'lastPage' => 1,
                'perPage' => $perPage,
                'total' => 0,
            ],
            'warning' => 'Báº£ng payments chÆ°a sẵn sàng. Vui lòng chạy migrate để kích hoạt dữ liệu giao dịch trực tuyến.',
        ]);
    }

    private function mapOnlineExportRows(Collection $payments): Collection
    {
        return $payments->map(function (Payment $payment) {
            $order = $payment->ticket?->donHang;
            $statusKey = $this->statusKeyFromOnline($payment->status);

            return [
                'code' => sprintf('ONL-%06d', $payment->id),
                'orderCode' => $this->formatOrderCode($order?->id),
                'typeLabel' => $statusKey === 'refunded' ? 'Hoàn tiền' : 'Thanh toán',
                'gateway' => sprintf('%s / %s', Str::upper($payment->method ?? '—'), Str::upper($payment->provider ?? '—')),
                'statusLabel' => $this->mapOnlineStatusLabel($payment->status),
                'statusKey' => $statusKey,
                'amount' => (int) $payment->amount_vnd,
                'time' => $payment->paid_at ? $payment->paid_at->copy() : null,
                'note' => $payment->provider_ref ? 'Mã tham chiếu: ' . $payment->provider_ref : '',
            ];
        });
    }

    private function mapManualExportRows(Collection $payments): Collection
    {
        return $payments->map(function (ThanhToan $payment) {
            $order = $payment->donHang;
            $statusKey = $this->statusKeyFromManual($payment->trang_thai);

            return [
                'code' => sprintf('MAN-%06d', $payment->id),
                'orderCode' => $this->formatOrderCode($order?->id),
                'typeLabel' => $statusKey === 'refunded' ? 'Hoàn tiền' : 'Thanh toán',
                'gateway' => sprintf('Thủ công / %s', Str::upper($payment->cong_thanh_toan ?? '—')),
                'statusLabel' => $this->mapManualStatusLabel($payment->trang_thai),
                'statusKey' => $statusKey,
                'amount' => (float) $payment->so_tien,
                'time' => $payment->thoi_diem_thanh_toan ? Carbon::parse($payment->thoi_diem_thanh_toan) : null,
                'note' => $payment->ma_thanh_toan ? 'Mã thanh toán: ' . $payment->ma_thanh_toan : '',
            ];
        });
    }

    private function summarizeExportRows(Collection $rows): array
    {
        return [
            'totalAmount' => (float) $rows->sum('amount'),
            'totalCount' => $rows->count(),
            'successCount' => $rows->where('statusKey', 'success')->count(),
            'refundedCount' => $rows->where('statusKey', 'refunded')->count(),
            'failedCount' => $rows->where('statusKey', 'failed')->count(),
        ];
    }

    private function statusKeyFromOnline(?string $status): string
    {
        return match ($status) {
            'SUCCEEDED' => 'success',
            'REFUNDED' => 'refunded',
            'FAILED', 'EXPIRED' => 'failed',
            default => 'pending',
        };
    }

    private function statusKeyFromManual(?string $status): string
    {
        return match ($status) {
            'thanh_cong' => 'success',
            'hoan_tien' => 'refunded',
            default => 'pending',
        };
    }

    private function resolveExportLimit(Request $request): int
    {
        $limit = (int) $request->input('limit', 2000);

        return max(100, min(10000, $limit));
    }

    private function describeFilters(array $filters, string $type): string
    {
        $parts = [
            'Loại: ' . ($type === 'manual' ? 'Thanh toán thủ công' : 'Thanh toán online'),
        ];

        if (!empty($filters['method'])) {
            $parts[] = 'Phương thức: ' . Str::upper($filters['method']);
        }

        if (!empty($filters['status'])) {
            $parts[] = 'Trạng thái: ' . $this->filterStatusLabel($filters['status']);
        }

        return implode(' | ', array_filter($parts)) ?: 'Không áp dụng bộ lọc bổ sung';
    }

    private function describePeriod(array $filters): string
    {
        $from = $filters['dateFrom'] ?? null;
        $to = $filters['dateTo'] ?? null;

        if ($from && $to) {
            return sprintf('%s → %s', $this->formatDateLabel($from), $this->formatDateLabel($to));
        }

        if ($from) {
            return sprintf('Từ %s đến nay', $this->formatDateLabel($from));
        }

        if ($to) {
            return sprintf('Đến %s', $this->formatDateLabel($to));
        }

        return 'Toàn bộ thời gian';
    }

    private function formatDateLabel(string $value): string
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

    private function filterStatusLabel(string $status): string
    {
        return match ($status) {
            'success' => 'Thành công',
            'failed' => 'Thất bại',
            'refunded' => 'Hoàn tiền',
            default => 'Đang chờ',
        };
    }

    private function formatOrderCode(?int $orderId): string
    {
        if (!$orderId) {
            return '—';
        }

        return sprintf('DH-%05d', $orderId);
    }
}
