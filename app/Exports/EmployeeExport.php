<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Region;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmployeeExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize, WithTitle
{
    /**
     * Fetch all employee data to export
     *
     * @return \Illuminate\Support\Collection
     */
   public function collection()
{
    return Employee::with('department', 'designation')  // Load department and designation
        ->get()
        ->map(function ($employee) {
            // Fetch region names using the region_ids array (handling both parent and sub-regions)
            $regionNames = Region::whereIn('id', $employee->region_ids)->pluck('name')->implode(', ');

            return [
                'Employee ID' => $employee->employee_id,
                'Name' => $employee->name,
                'Email' => $employee->email,
                'Department' => $employee->department ? $employee->department->name : 'N/A',
                'Designation' => $employee->designation ? $employee->designation->name : 'N/A',
                'Roles' => $employee->user->roles->pluck('name')->implode(', '),
                'Region(s)' => $regionNames,  // Display region names, including sub-regions
                'Mobile' => $employee->mobile,
                'Status' => $employee->status == 1 ? 'Active' : 'Inactive',
                'Grade' => $employee->grade ?? 'N/A',
                'Gender' => $employee->user->gender ?? 'N/A',
                'DOB' => $employee->user->dob ? \Carbon\Carbon::parse($employee->user->dob)->format('Y-m-d') : 'N/A',  // Convert DOB to Carbon if not null
                'Report To' => $employee->report_to ?? 'N/A',
                'Created At' => $employee->created_at->format('Y-m-d H:i:s'),
                'Updated At' => $employee->updated_at->format('Y-m-d H:i:s'),
            ];
        });
}


    /**
     * Define the headers of the export file
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Email',
            'Department',
            'Designation',
            'Roles',
            'Region(s)',
            'Mobile',
            'Status',
            'Grade',
            'Gender',
            'DOB',
            'Report To',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Apply styling to the Excel sheet
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     */
    public function styles($sheet)
    {
        // Bold headers
        $sheet->getStyle('A1:O1')->getFont()->setBold(true);

        // Add padding to all rows
        $sheet->getStyle('A1:O' . $sheet->getHighestRow())->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('A1:O' . $sheet->getHighestRow())->getAlignment()->setWrapText(true); // Wrap text for longer content

        // Adjust column width (auto size columns to fit content)
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(30);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(20);
        $sheet->getColumnDimension('L')->setWidth(20);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(20);
    }

    /**
     * Set column formatting
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'J' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2, // Date formatting for Created At
            'K' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2, // Date formatting for Updated At
            'H' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // Format mobile numbers as text to prevent scientific notation
            'L' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // Format Grade as text (if necessary)
        ];
    }

    /**
     * Set the sheet title
     *
     * @return string
     */
    public function title(): string
    {
        return 'Employees Data';
    }
}
