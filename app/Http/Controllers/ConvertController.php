<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ConvertController extends Controller
{
    public function index()
    {
        return view('convert');
    }

    public function convert(Request $request)
    {
        $request->validate([
            'new_file' => ['required','file','mimes:xls,xlsx'],
        ]);


        $uploaded = $request->file('new_file')->store('tmp', 'public');
        $newPath  = Storage::disk('public')->path($uploaded);

        // 2) Load source sheet
        $wb    = IOFactory::load($newPath);
        $sheet = $wb->getSheet(0);

        $toNorm = function($v) {
            $s = trim((string)$v);
            return mb_strtolower(preg_replace('/\s+/u',' ', $s));
        };
        $colL = fn($i) => Coordinate::stringFromColumnIndex($i);
        $highestRow = $sheet->getHighestDataRow();
        $highestColIdx = Coordinate::columnIndexFromString($sheet->getHighestColumn());


        $employees = []; // [empCode => ['name'=>..., 'days'=> ['Y-m-d'=>['in'=>..,'out'=>..,'total'=>..]]]]
        $allDates  = []; // set

        $r = 1;
        while ($r <= $highestRow) {

            // find "Employee Code:" in this row
            $empCode = null;
            $empName = null;

            for ($c = 1; $c <= $highestColIdx; $c++) {
                $val = (string)$sheet->getCell($colL($c).$r)->getValue();
                if ($this->startsWithLabel($val, 'Employee Code')) {
                    // value generally in same row further right OR next cell
                    // try right-side cells on same row
                    for ($c2 = $c+1; $c2 <= min($c+5, $highestColIdx); $c2++) {
                        $v2 = trim((string)$sheet->getCell($colL($c2).$r)->getValue());
                        if ($v2 !== '') { $empCode = $v2; break; }
                    }
                }
                if ($this->startsWithLabel($val, 'Employee Name')) {
                    for ($c2 = $c+1; $c2 <= min($c+8, $highestColIdx); $c2++) {
                        $v2 = trim((string)$sheet->getCell($colL($c2).$r)->getValue());
                        if ($v2 !== '') { $empName = $v2; break; }
                    }
                }
            }

            if (!$empCode) { $r++; continue; } // move until we hit a block

            // 3a) find the table header row below (looks for "Date", "InTime", "OutTime", "Total Duration")
            $headerRow = null;
            $colDate = $colIn = $colOut = $colTotal = null;

            for ($sr = $r; $sr <= min($r+10, $highestRow); $sr++) {
                $found = 0;
                for ($c = 1; $c <= $highestColIdx; $c++) {
                    $text = $toNorm($sheet->getCell($colL($c).$sr)->getValue());
                    if ($text === 'date')                { $colDate  = $c; $found++; }
                    if ($text === 'intime' || $text === 'in time')     { $colIn    = $c; $found++; }
                    if ($text === 'outtime' || $text === 'out time')   { $colOut   = $c; $found++; }
                    if ($text === 'total duration' || $text === 'total hrs' || $text === 'total hours') { $colTotal = $c; $found++; }
                }
                if ($found >= 3) { $headerRow = $sr; break; } // at least Date/In/Out found
            }

            if (!$headerRow) { $r++; continue; } // couldn’t find table, skip

            // 3b) read data rows until a "Total Duration=" line OR a blank separator OR next "Employee Code:"
            $dr = $headerRow + 1;
            while ($dr <= $highestRow) {
                // break conditions
                $rowStr = '';
                for ($c=1; $c<=min(8,$highestColIdx); $c++) {
                    $rowStr .= ' ' . (string)$sheet->getCell($colL($c).$dr)->getValue();
                }
                $rowStrNorm = $toNorm($rowStr);
                if ($rowStr === '' || str_contains($rowStrNorm, 'total duration=')
                    || str_contains($rowStrNorm, 'employee code')) {
                    break;
                }

                // read Date, In, Out, Total
                $dateYmd = null;
                if ($colDate) {
                    $raw = $sheet->getCell($colL($colDate).$dr)->getValue();
                    $dateYmd = $this->toYmd($raw);
                }

                $in  = $colIn  ? $this->cellText($sheet, $colL($colIn).$dr)  : '';
                $out = $colOut ? $this->cellText($sheet, $colL($colOut).$dr) : '';
                $tot = $colTotal ? $this->cellText($sheet, $colL($colTotal).$dr) : '';

                if ($dateYmd) {
                    $employees[$empCode]['name'] = $empName ?? '';
                    $employees[$empCode]['days'][$dateYmd] = [
                        'in'    => $in,
                        'out'   => $out,
                        'total' => $tot,
                    ];
                    $allDates[$dateYmd] = true;
                }

                $dr++;
            }

            // move pointer to line after the block we just read
            $r = $dr + 1;
        }

        // 4) Build OLD matrix workbook
        $dates = array_keys($allDates);
        sort($dates); // ascending

        $out = new Spreadsheet();
        $tgt = $out->getActiveSheet();

        // Header row
        $tgt->setCellValue('A1', 'Employee ID');
        $tgt->setCellValue('B1', 'Employee Name');
        // col C left blank like your screenshot
        $colIdx = 4; // start from column D for dates
        foreach ($dates as $d) {
            $tgt->setCellValue($colL($colIdx).'1', $d);
            $colIdx++;
        }

        // Write employee rows: 3 rows per employee
        $rowOut = 2;
        foreach ($employees as $code => $info) {
            $name = $info['name'] ?? '';
            $days = $info['days'] ?? [];

            // Row 1: In Time
            $tgt->setCellValue('A'.$rowOut, $code);
            $tgt->setCellValue('B'.$rowOut, $name);
            $tgt->setCellValue('C'.$rowOut, 'In Time');
            $colIdx = 4;
            foreach ($dates as $d) {
                $tgt->setCellValueExplicit($colL($colIdx).$rowOut, $days[$d]['in'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $colIdx++;
            }

            // Row 2: Out Time
            $rowOut++;
            $tgt->setCellValue('C'.$rowOut, 'Out Time');
            $colIdx = 4;
            foreach ($dates as $d) {
                $tgt->setCellValueExplicit($colL($colIdx).$rowOut, $days[$d]['out'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $colIdx++;
            }

            // Row 3: Total Hrs
            $rowOut++;
            $tgt->setCellValue('C'.$rowOut, 'Total Hrs');
            $colIdx = 4;
            foreach ($dates as $d) {
                $tgt->setCellValueExplicit($colL($colIdx).$rowOut, $days[$d]['total'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $colIdx++;
            }

            // gap row
            $rowOut += 2;
        }

        for ($c=1; $c<=($colIdx-1); $c++) {
            $tgt->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

    
        $dir = storage_path('app/public/converted');
        File::ensureDirectoryExists($dir, 0775, true);  

        $fileName = 'converted_old_matrix_' . now()->format('Ymd_His') . '.xlsx';
        $fullPath = $dir . DIRECTORY_SEPARATOR . $fileName;

        $writer = IOFactory::createWriter($out, 'Xlsx');
        $writer->save($fullPath);

        // cleanup uploaded temp
        Storage::disk('public')->delete($uploaded);

        return response()->download($fullPath)->deleteFileAfterSend(true);

    }

    private function startsWithLabel(string $text, string $label): bool
    {
        $t = mb_strtolower(trim($text));
        $l = mb_strtolower(trim($label));
        return str_starts_with($t, $l);
    }

    private function cellText($sheet, string $addr): string
    {
        $v = $sheet->getCell($addr)->getCalculatedValue();
        if ($v === null || $v === '') $v = $sheet->getCell($addr)->getValue();
        // keep as plain text (like "008:12"), don’t coerce to number
        return is_string($v) ? trim($v) : (is_numeric($v) ? (string)$v : trim((string)$v));
    }

    private function toYmd($raw): ?string
    {
        if ($raw === null || $raw === '') return null;
        // Excel serial?
        if (is_numeric($raw)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($raw);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {}
        }
        $s = trim((string)$raw);
        // Try common textual formats
        $s = str_replace(['.', '/'], ['-', '-'], $s);
        // 01-Nov-2025 → 2025-11-01
        $ts = strtotime($s);
        if ($ts) return date('Y-m-d', $ts);
        return null;
    }
}
