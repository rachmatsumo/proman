<?php

namespace App\Exports;

use App\Models\SubProgram;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SubProgramSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    protected SubProgram $sub;
    protected ?string $prefix;
    protected int $subIndex;
    protected string $sheetCode;
    protected array $rowMeta = []; // store row-level metadata for styling

    protected array $timelineMeta = []; // store month labels and week counts

    public function __construct(SubProgram $sub, ?string $prefix, int $subIndex)
    {
        $this->sub       = $sub->load(['program', 'milestones.activities.subActivities']);
        $this->prefix    = $prefix ?? '1';
        $this->subIndex  = $subIndex;
        $this->sheetCode = $this->prefix . '.' . $this->subIndex;

        $this->initTimeline();
    }

    protected function initTimeline()
    {
        $start = $this->sub->program->start_date?->copy()->startOfMonth() ?? now()->startOfYear();
        $end   = $this->sub->program->end_date?->copy()->endOfMonth() ?? now()->endOfYear();

        $current = $start->copy();
        while ($current <= $end) {
            $this->timelineMeta[] = [
                'month' => $current->translatedFormat('F'),
                'year'  => $current->year,
                'month_val' => $current->month,
            ];
            $current->addMonth();
        }
    }

    public function title(): string
    {
        // Sheet name is just the numbering (e.g., "1.1.1")
        return $this->sheetCode;
    }

    public function array(): array
    {
        $rows = [];

        // Calculate Overall Sub Program Progress
        $msProgValues = [];
        foreach ($this->sub->milestones as $ms) {
            if ($ms->type === 'divider') continue;
            
            $acts = $ms->activities;
            $msAvg = $acts->isEmpty() ? 0 : $acts->avg('progress');
            $msProgValues[] = ['progress' => $msAvg, 'bobot' => $ms->bobot];
        }

        $overallProgress = 0;
        if (count($msProgValues) > 0) {
            $allHaveBobot = collect($msProgValues)->every(fn($p) => $p['bobot'] !== null);
            if ($allHaveBobot) {
                $weighted = 0;
                foreach ($msProgValues as $p) { $weighted += $p['progress'] * $p['bobot'] / 100; }
                $overallProgress = round($weighted);
            } else {
                $overallProgress = round(collect($msProgValues)->avg('progress'));
            }
        }

        // ── Sub Program Header ─────────────────────────────────────────
        $rows[] = [
            'type' => 'header',
            'data' => array_merge(
                [$this->sheetCode. ' ' .$this->sub->name, '', '', '', '', '', '', '', ''],
                array_fill(0, count($this->timelineMeta) * 4, '')
            )
        ];
        $rows[] = [
            'type' => 'info',
            'data' => array_merge(
                ['Progress: ' . $overallProgress . '%', '', '', '', '', '', '', '', ''],
                array_fill(0, count($this->timelineMeta) * 4, '')
            )
        ];
        $rows[] = [
            'type' => 'blank',
            'data' => array_merge(
                ['', '', '', '', '', '', '', '', ''],
                array_fill(0, count($this->timelineMeta) * 4, '')
            )
        ];

        // ── Timeline Header Rows ───────────────────────────────────────
        $monthRow = ['', '', '', '', '', '', '', '', ''];
        $weekRow  = ['No', 'Milestone/Aktivitas/Sub Aktivitas', '', '', 'Bobot', 'Progress', 'Mulai', 'Selesai', 'Status'];

        foreach ($this->timelineMeta as $m) {
            $monthRow[] = $m['month'];
            $monthRow[] = '';
            $monthRow[] = '';
            $monthRow[] = '';

            $weekRow[] = '1';
            $weekRow[] = '2';
            $weekRow[] = '3';
            $weekRow[] = '4';
        }

        $rows[] = ['type' => 'months', 'data' => $monthRow];
        $rows[] = ['type' => 'th',     'data' => $weekRow];

        $mGroup  = 1;
        $mCount  = 0;
        $krCount = 0;

        foreach ($this->sub->milestones as $ms) {
            if ($ms->type === 'divider') {
                $rows[] = ['type' => 'divider', 'data' => ['', '— ' . $ms->name, '', '', '', '', '', '', '']];
                $mGroup++;
                $mCount = 0;
                continue;
            }

            // Milestone progress calculation for display
            $acts = $ms->activities;
            $msAvg = $acts->isEmpty() ? 0 : round($acts->avg('progress'));

            if ($ms->type === 'key_result') {
                $krCount++;
                $rows[] = [
                    'type' => 'kr',
                    'data' => [
                        'KR.' . $krCount,
                        $ms->name,
                        '',
                        '',
                        $ms->bobot ? $ms->bobot . '%' : '',
                        $msAvg . '%',
                        $ms->start_date?->format('d/m/Y') ?? '-',
                        $ms->end_date?->format('d/m/Y') ?? '-',
                        '',
                    ],
                ];
                continue;
            }

            // Regular milestone
            $mCount++;
            $msNum = 'M.' . $mGroup . '.' . $mCount;

            $rows[] = [
                'type' => 'milestone',
                'data' => array_merge([
                    $msNum,      // Col A: No Milestone
                    $ms->name,   // Col B: Nama Milestone
                    '',          // Col C: empty
                    '',          // Col D: empty
                    $ms->bobot ? $ms->bobot . '%' : '',
                    $msAvg . '%',
                    $ms->start_date?->format('d/m/Y') ?? '-',
                    $ms->end_date?->format('d/m/Y') ?? '-',
                    '',
                ], array_fill(0, count($this->timelineMeta) * 4, '')),
                'dates' => ['start' => $ms->start_date, 'end' => $ms->end_date]
            ];

            // Activity numbering: mGroup.mCount.actCount
            $actCount = 0;
            foreach ($ms->activities as $act) {
                $actCount++;
                $actNum = $mGroup . '.' . $mCount . '.' . $actCount;
                $rows[] = [
                    'type' => 'activity',
                    'data' => array_merge([
                        '',           // Col A: empty
                        $actNum,      // Col B: No Activity
                        $act->name,   // Col C: Nama Activity
                        '',           // Col D: empty
                        '',           // Col E: Bobot
                        $act->progress . '%', // Col F: Progress
                        $act->start_date?->format('d/m/Y') ?? '-',
                        $act->end_date?->format('d/m/Y') ?? '-',
                        $act->status ?? '',
                    ], array_fill(0, count($this->timelineMeta) * 4, '')),
                    'dates' => ['start' => $act->start_date, 'end' => $act->end_date]
                ];

                // Sub Activities
                $subActCount = 0;
                foreach ($act->subActivities as $sa) {
                    $subActCount++;
                    $rows[] = [
                        'type' => 'subactivity',
                        'data' => array_merge([
                            '',           // Col A: empty
                            '',           // Col B: empty
                            $actNum . '.' . $subActCount, // Col C: No SubActivity
                            $sa->name,    // Col D: Nama SubActivity
                            '',           // Col E: Bobot
                            $sa->progress . '%', // Col F: Progress
                            $sa->start_date?->format('d/m/Y') ?? '-',
                            $sa->end_date?->format('d/m/Y') ?? '-',
                            $sa->status ?? '',
                        ], array_fill(0, count($this->timelineMeta) * 4, '')),
                        'dates' => ['start' => $sa->start_date, 'end' => $sa->end_date]
                    ];
                }
            }
        }

        // Store meta for styling, return only data
        $this->rowMeta = $rows;
        return array_column($rows, 'data');
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 10,
            'B' => 12,
            'C' => 15,
            'D' => 55,
            'E' => 10,
            'F' => 12, // Progress
            'G' => 15, // Mulai
            'H' => 15, // Selesai
            'I' => 12, // Status
        ];

        // Narrow columns for timeline
        $timelineCols = count($this->timelineMeta) * 4;
        for ($i = 0; $i < $timelineCols; $i++) {
            $colName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(10 + $i);
            $widths[$colName] = 4;
        }

        return $widths;
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        return []; // Styling done in AfterSheet event
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $timelineCount = count($this->timelineMeta) * 4;
                $maxColNum  = 9 + $timelineCount;
                $maxCol     = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxColNum);

                $programStart = $this->sub->program->start_date?->copy()->startOfMonth();

                foreach ($this->rowMeta as $i => $rowObj) {
                    $row   = $i + 1;
                    $type  = $rowObj['type'];
                    $range = "A{$row}:{$maxCol}{$row}";

                    // Wrap text and vertical alignment for all
                    $sheet->getStyle($range)->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(Alignment::VERTICAL_TOP);

                    // Grid borders for table parts (skip header, blank, info, months)
                    if (!in_array($type, ['header', 'blank', 'info', 'months'])) {
                        $sheet->getStyle($range)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['argb' => 'FFE2E8F0'],
                                ],
                            ],
                        ]);
                    }

                    switch ($type) {
                        case 'header':
                            $sheet->mergeCells("A{$row}:{$maxCol}{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF312E81']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                            ]);
                            $sheet->getRowDimension($row)->setRowHeight(22);
                            break;

                        case 'info':
                            $sheet->mergeCells("A{$row}:{$maxCol}{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F46E5']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                            ]);
                            $sheet->getRowDimension($row)->setRowHeight(18);
                            break;

                        case 'months':
                            // Style the month header
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);
                            // Merge months
                            for ($m = 0; $m < count($this->timelineMeta); $m++) {
                                $startM = 10 + ($m * 4);
                                $endM   = $startM + 3;
                                $sCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startM);
                                $eCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endM);
                                $sheet->mergeCells("{$sCol}{$row}:{$eCol}{$row}");
                            }
                            break;

                        case 'th':
                            $sheet->mergeCells("B{$row}:D{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'font'    => ['bold' => true, 'color' => ['argb' => 'FF1E1B4B']],
                                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);
                            $sheet->getRowDimension($row)->setRowHeight(18);
                            break;

                        case 'divider':
                            $sheet->mergeCells("A{$row}:{$maxCol}{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'italic' => true, 'color' => ['argb' => 'FF92400E']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
                            ]);
                            break;

                        case 'milestone':
                            $sheet->mergeCells("B{$row}:D{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['argb' => 'FF1E1B4B']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F3FF']],
                            ]);
                            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
                            $this->colorTimeline($sheet, $row, $rowObj['dates'], $programStart, 'FF818CF8');
                            break;

                        case 'kr':
                            $sheet->mergeCells("B{$row}:D{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['argb' => 'FF991B1B']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF1F2']],
                            ]);
                            break;

                        case 'activity':
                            $sheet->mergeCells("C{$row}:D{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFAFAFA']],
                            ]);
                            $this->colorTimeline($sheet, $row, $rowObj['dates'], $programStart, 'FF3B82F6');
                            break;

                        case 'subactivity':
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF475569']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                            ]);
                            $this->colorTimeline($sheet, $row, $rowObj['dates'], $programStart, 'FF94A3B8');
                            break;
                    }

                    // Outer border for table rows (skip header, blank, info, months)
                    if (!in_array($type, ['header', 'blank', 'info', 'months'])) {
                        $sheet->getStyle($range)->getBorders()->getOutline()
                            ->setBorderStyle(Border::BORDER_THIN)
                            ->getColor()->setARGB('FFE2E8F0');
                    }
                }

                // Freeze top 5 rows (Sub Header + Info + Blank + Months + Weeks)
                $sheet->freezePane('A6');
            }
        ];
    }

    protected function colorTimeline($sheet, $row, $dates, $programStart, $color)
    {
        if (!($dates['start'] ?? null) || !($dates['end'] ?? null) || !$programStart) return;

        $start = $dates['start'];
        $end   = $dates['end'];

        $getCol = function($date) use ($programStart) {
            $mDiff = ($date->year - $programStart->year) * 12 + ($date->month - $programStart->month);
            $day = (int)$date->format('j');
            $week = min(4, ceil($day / 7.75));
            return 10 + ($mDiff * 4) + ($week - 1); // Shifting to column 10
        };

        $startCol = $getCol($start);
        $endCol   = $getCol($end);

        $timelineCount = count($this->timelineMeta) * 4;
        $maxTimelineCol = 9 + $timelineCount;

        $startCol = max(10, $startCol);
        $endCol   = min($maxTimelineCol, $endCol);

        if ($startCol <= $endCol) {
            $sCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startCol);
            $eCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endCol);
            $sheet->getStyle("{$sCol}{$row}:{$eCol}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($color);
        }
    }
}
