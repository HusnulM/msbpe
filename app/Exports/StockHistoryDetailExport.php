<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;

class StockHistoryDetailExport implements FromCollection, WithHeadings, WithMapping
{
    protected $req;

    function __construct($req) {
        $this->req = $req;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = DB::table('v_inv_move_details');

        $strDate  = $req->dtlDate1;
        $endDate  = $req->dtlDate2;
        $Material = $req->Material1;
        $whsCode  = $req->whsCode1;

        $query = DB::table('v_inv_move_details');
        $query->where('material', $Material);
        $query->where('whscode', $whsCode);
        $query->whereBetween('postdate', [$strDate, $endDate]);

        $query->orderBy('postdate', 'ASC');

        return $query->get();
    }

    public function map($row): array{
        $fields = [
            $row->docnum,
            $row->docyear,
            $row->postdate,
            $row->material,
            $row->matdesc,
            $row->whsname,
            $row->quantity,
            $row->unit,
            $row->remark,
            $row->movement_info,
        ];

        return $fields;
    }

    public function headings(): array
    {
        return [
                "Document Number",
                "Year",
                "Date",
                "Material",
                "Deskripsi",
                "Warehouse",
                "Quantity",
                "Unit",
                "Remark",
                "Transaction Note",
        ];
    }
}
