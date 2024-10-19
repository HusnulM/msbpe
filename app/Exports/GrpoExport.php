<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use DB;

class GrpoExport implements FromCollection, WithHeadings, WithMapping
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
        $query = DB::table('v_grpo_v2');

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('docdate', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('docdate', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('docdate', $req->dateto);
        }

        $query->orderBy('id');

        return $query->get();
    }

    public function map($row): array{
        $fields = [
            $row->docnum,
            $row->docdate,
            $row->remark,
            $row->received_by,
            $row->material,
            $row->matdesc,
            $row->quantity,
            $row->unit,
            number_format($row->unit_price,0),
            number_format($row->total_price,0),
            $row->ponum,
            $row->poitem,
            $row->whsname,
        ];

        return $fields;
    }

    public function headings(): array
    {
        return [
                "No. Penerimaan",
                "Tanggal Terima",
                "Remark",
                "Di Terima Oleh",
                "Kode Item",
                "Deskripsi",
                "Quantity",
                "Unit",
                "Unit Price",
                "Total Price",
                "No. PO",
                "PO Item",
                "Warehouse",
        ];
    }
}
