<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithCustomStartCell,
    WithEvents
{
    use Exportable;

    private Collection $rows;

    private array $meta;

    private int $rowIndex = 0;

    public function __construct(Collection $rows, array $meta = [])
    {
        $this->rows = $rows;
        $this->meta = $meta;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function headings(): array
    {
        return [
            '#',
            'Mã giao dịch',
            'Mã đơn hàng',
            'Loại giao dịch',
            'Cổng / Phương thức',
            'Trạng thái',
            'Số tiền (VND)',
            'Thời gian',
            'Ghi chú',
        ];
    }

    public function map($row): array
    {
        $this->rowIndex++;

        return [
            $this->rowIndex,
            $row['code'] ?? '',
            $row['orderCode'] ?? '—',
            $row['typeLabel'] ?? '',
            $row['gateway'] ?? '',
            $row['statusLabel'] ?? '',
            (float) ($row['amount'] ?? 0),
            $this->formatDateTime($row['time'] ?? null),
            $row['note'] ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 5;

        $headerRange = sprintf('A%d:I%d', $headerRow, $headerRow);

        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FF0F172A');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFCBD5F5');

        $rowCount = $this->rows->count();
        if ($rowCount > 0) {
            $moneyRange = sprintf('G6:G%d', 5 + $rowCount);
            $sheet->getStyle($moneyRange)->getNumberFormat()->setFormatCode('#,##0 [$₫-vi-VN]');
            $sheet->getStyle($moneyRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $title = $this->meta['title'] ?? 'Báo cáo thanh toán & giao dịch';
                $subtitle = $this->meta['subtitle'] ?? 'Tổng hợp giao dịch';
                $period = $this->meta['period'] ?? 'Toàn bộ thời gian';
                $filters = $this->meta['filters'] ?? 'Không áp dụng bộ lọc bổ sung';
                $generatedAt = $this->meta['generatedAt'] ?? Carbon::now();
                $generatedLabel = $generatedAt instanceof Carbon
                    ? $generatedAt->copy()->timezone(config('app.timezone'))
                        ->format('d/m/Y H:i')
                    : (string) $generatedAt;

                $sheet->setCellValue('A1', $title);
                $sheet->mergeCells('A1:I1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A2', "Phạm vi: {$subtitle}");
                $sheet->mergeCells('A2:I2');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);

                $sheet->setCellValue('A3', "Khoảng thời gian: {$period}");
                $sheet->mergeCells('A3:I3');
                $sheet->getStyle('A3')->getFont()->setSize(10);

                $sheet->setCellValue('A4', "Bộ lọc: {$filters} | Xuất lúc: {$generatedLabel}");
                $sheet->mergeCells('A4:I4');
                $sheet->getStyle('A4')->getFont()->setSize(10)->getColor()->setARGB('FF475569');
            },

            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = $this->rows->count();
                $dataEndRow = 5 + max(1, $rowCount);
                $summaryRow = $dataEndRow + 1;
                $success = $this->meta['successCount'] ?? 0;
                $refunded = $this->meta['refundedCount'] ?? 0;
                $failed = $this->meta['failedCount'] ?? 0;
                $total = $this->meta['totalCount'] ?? $rowCount;

                $sheet->setCellValue(
                    "A{$summaryRow}",
                    sprintf(
                        'Tổng %d giao dịch | Thành công: %d | Hoàn: %d | Khác: %d',
                        $total,
                        $success,
                        $refunded,
                        max(0, $total - $success - $refunded)
                    )
                );
                $sheet->mergeCells("A{$summaryRow}:E{$summaryRow}");
                $sheet->getStyle("A{$summaryRow}:I{$summaryRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$summaryRow}:I{$summaryRow}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');

                $sheet->setCellValue("F{$summaryRow}", 'Tổng số tiền');
                $sheet->setCellValue("G{$summaryRow}", (float) ($this->meta['totalAmount'] ?? 0));
                $sheet->mergeCells("H{$summaryRow}:I{$summaryRow}");
                $sheet->getStyle("G{$summaryRow}")
                    ->getNumberFormat()->setFormatCode('#,##0 [$₫-vi-VN]');
                $sheet->getStyle("F{$summaryRow}:I{$summaryRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setARGB('FF94A3B8');

                $sheet->setAutoFilter('A5:I5');
            },
        ];
    }

    private function formatDateTime($value): string
    {
        if ($value instanceof Carbon) {
            return $value->copy()->timezone(config('app.timezone'))->format('d/m/Y H:i');
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i');
        }

        return '—';
    }
}

