<?php

namespace App\Exports;

use App\Models\Program;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProgramExport implements WithMultipleSheets
{
    protected Program $program;

    public function __construct(Program $program)
    {
        $this->program = $program;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Summary sheet
        $sheets[] = new ProgramSummarySheet($this->program);

        // One sheet per sub-program
        $subIndex = 1;
        foreach ($this->program->subPrograms as $sub) {
            $sheets[] = new SubProgramSheet($sub, $this->program->prefix, $subIndex);
            $subIndex++;
        }

        return $sheets;
    }
}
