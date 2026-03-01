<?php

namespace App\Exports;

use App\Models\Program;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProgramSummarySheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    protected Program $program;

    protected array $rowMeta = [];
    protected array $timelineMeta = [];

    public function __construct(Program $program)
    {
        $this->program = $program->load(['subPrograms.milestones.activities']);
        $this->initTimeline();
    }

    protected function initTimeline()
    {
        $start = $this->program->start_date?->copy()->startOfMonth() ?? now()->startOfYear();
        $end   = $this->program->end_date?->copy()->endOfMonth() ?? now()->endOfYear();

        $current = $start->copy();
        while ($current <= $end) {
            $this->timelineMeta[] = [
                'month' => $current->translatedFormat('F'),
                'year'  => $current->year,
            ];
            $current->addMonth();
        }
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function array(): array
    {
        $rows = [];

        // 1. Calculate All Progress for Rollup
        $allSubProgValues = [];
        foreach ($this->program->subPrograms as $sub) {
            $msProgValues = [];
            foreach ($sub->milestones as $ms) {
                if ($ms->type === 'divider') continue;
                $acts = $ms->activities;
                $msAvg = $acts->isEmpty() ? 0 : $acts->avg('progress');
                $msProgValues[] = ['progress' => $msAvg, 'bobot' => $ms->bobot];
            }
            
            $subProgress = 0;
            if (count($msProgValues) > 0) {
                $allHaveBobot = collect($msProgValues)->every(fn($p) => $p['bobot'] !== null);
                if ($allHaveBobot) {
                    $weighted = 0;
                    foreach ($msProgValues as $p) { $weighted += $p['progress'] * $p['bobot'] / 100; }
                    $subProgress = round($weighted);
                } else {
                    $subProgress = round(collect($msProgValues)->avg('progress'));
                }
            }
            $allSubProgValues[] = ['progress' => $subProgress, 'bobot' => $sub->bobot, 'sub' => $sub];
        }

        $overallProgramProgress = 0;
        if (count($allSubProgValues) > 0) {
            $allSubsHaveBobot = collect($allSubProgValues)->every(fn($p) => $p['bobot'] !== null);
            if ($allSubsHaveBobot) {
                $weighted = 0;
                foreach ($allSubProgValues as $p) { $weighted += $p['progress'] * $p['bobot'] / 100; }
                $overallProgramProgress = round($weighted);
            } else {
                $overallProgramProgress = round(collect($allSubProgValues)->avg('progress'));
            }
        }

        // ── Program Info ──────────────────────────────────────────────
        $rows[] = ['type' => 'info', 'data' => ['Inisiatif Program', $this->program->name, '', '', '', '', '', '', '']];
        $rows[] = ['type' => 'info', 'data' => ['Tema', $this->program->theme, '', '', '', '', '', '', '']];
        $rows[] = [
            'type' => 'info',
            'data' => [
                'Periode',
                ($this->program->start_date?->format('d/m/Y') ?? '-') . ' s/d ' . ($this->program->end_date?->format('d/m/Y') ?? '-'),
                '', '', '', '', '', '', ''
            ]
        ];
        $rows[] = ['type' => 'info', 'data' => ['Progress', $overallProgramProgress . '%', '', '', '', '', '', '', '']];
        $rows[] = ['type' => 'blank', 'data' => ['', '', '', '', '', '', '', '', '']];

        // ── Timeline Header Rows ───────────────────────────────────────
        $monthRow = ['', '', '', '', '', '', '', '', ''];
        $weekRow  = ['No', 'Sub Program / Milestone', '', '', 'Bobot (%)', 'Progress', 'Mulai', 'Selesai', 'Jml Activity'];

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

        $prefix   = $this->program->prefix ?? '1';

        foreach ($allSubProgValues as $idx => $item) {
            $sub = $item['sub'];
            $subIndex = $idx + 1;
            $sheetCode = $prefix . '.' . $subIndex;
            $actTotal  = $sub->milestones->flatMap->activities->count();

            $rows[] = [
                'type' => 'sub',
                'data' => array_merge([
                    $sheetCode, // Col A: Sub No
                    $sub->name, // Col B: Sub Name
                    '',         // Col C
                    '',         // Col D
                    $sub->bobot ?? '',
                    $item['progress'] . '%',
                    $sub->start_date?->format('d/m/Y') ?? '-',
                    $sub->end_date?->format('d/m/Y') ?? '-',
                    $actTotal,
                ], array_fill(0, count($this->timelineMeta) * 4, '')),
                'dates' => ['start' => $sub->start_date, 'end' => $sub->end_date]
            ];

            $mGroup = 1;
            $mCount = 0;
            foreach ($sub->milestones as $ms) {
                if ($ms->type === 'divider') { $mGroup++; $mCount = 0; continue; }
                if ($ms->type === 'key_result') continue;

                $mCount++;
                $msNum = 'M.' . $mGroup . '.' . $mCount;
                $msProgress = $ms->activities->isEmpty() ? 0 : round($ms->activities->avg('progress'));

                $rows[] = [
                    'type' => 'milestone',
                    'data' => array_merge([
                        '',         // Col A
                        $msNum,     // Col B: Milestone No
                        $ms->name,  // Col C: Milestone Name
                        '',         // Col D
                        $ms->bobot ?? '',
                        $msProgress . '%',
                        $ms->start_date?->format('d/m/Y') ?? '-',
                        $ms->end_date?->format('d/m/Y') ?? '-',
                        $ms->activities->count(),
                    ], array_fill(0, count($this->timelineMeta) * 4, '')),
                    'dates' => ['start' => $ms->start_date, 'end' => $ms->end_date]
                ];
            }
        }

        $this->rowMeta = $rows;
        return array_column($rows, 'data');
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 10,
            'B' => 12,
            'C' => 35,
            'D' => 25,
            'E' => 12, // Bobot
            'F' => 12, // Progress
            'G' => 16, // Mulai
            'H' => 16, // Selesai
            'I' => 14, // Jml Activity
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
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $timelineCount = count($this->timelineMeta) * 4;
                $maxColNum  = 9 + $timelineCount;
                $maxCol     = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxColNum);

                $programStart = $this->program->start_date?->copy()->startOfMonth();

                foreach ($this->rowMeta as $i => $rowObj) {
                    $row   = $i + 1;
                    $type  = $rowObj['type'];
                    $range = "A{$row}:{$maxCol}{$row}";

                    // Alignment
                    $sheet->getStyle($range)->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                    // Grid borders for table
                    if (in_array($type, ['th', 'sub', 'milestone', 'months'])) {
                        $sheet->getStyle($range)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['argb' => 'FFE2E8F0'],
                                ],
                            ],
                        ]);
                    }

                    switch ($type) {
                        case 'info':
                            $sheet->mergeCells("B{$row}:I{$row}");
                            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
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
                                'font'  => ['bold' => true, 'color' => ['argb' => 'FF1E1B4B']],
                                'fill'  => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E7FF']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);
                            $sheet->getRowDimension($row)->setRowHeight(20);
                            break;

                        case 'sub':
                            $sheet->mergeCells("B{$row}:D{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['argb' => 'FF1E1B4B']],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
                            ]);
                            $this->colorTimeline($sheet, $row, $rowObj['dates'], $programStart, 'FF312E81');
                            break;
                        
                        case 'milestone':
                            $sheet->mergeCells("C{$row}:D{$row}");
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => false, 'color' => ['argb' => 'FF334155']],
                            ]);
                            // Special background for Col B in milestones
                            $sheet->getStyle("B{$row}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setARGB('FFE0E7FF');
                            $this->colorTimeline($sheet, $row, $rowObj['dates'], $programStart, 'FF818CF8');
                            break;
                    }
                }

                $sheet->freezePane('A8'); // Freeze after info and headers (4 info + 1 blank + 2 timeline = 7)
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
            return 10 + ($mDiff * 4) + ($week - 1); // Shift to 10
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
