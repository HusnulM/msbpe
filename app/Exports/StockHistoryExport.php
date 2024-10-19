<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\AfterSheet;
use DB;

class StockHistoryExport implements FromCollection, WithHeadings, WithMapping
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
        $req = $_POST;
        $whsCode = 0;
        if($req['Warehouse'] != 0){
            // $whsCode = $req['Warehouse'];
            $materials = DB::table('v_material_movements_2')
                        ->where('whscode', $req['Warehouse'])
                        ->orderBy('whscode', 'ASC')
                        ->orderBy('material', 'ASC')
                        ->get();
        }else{
            $materials = DB::table('v_material_movements_2')
                        ->orderBy('whscode', 'ASC')
                        ->orderBy('material', 'ASC')
                        ->get();
        }

        $strDate = date('Y-m-d');
        if(isset($req['datefrom'])){
            $strDate = $req['datefrom'];
        }

        $endDate = date('Y-m-d');
        if(isset($req['dateto'])){
            $endDate = $req['dateto'];
        }

        $beginQty = DB::table('v_inv_movement')
                    ->select(DB::raw('material'), DB::raw('whscode'), DB::raw('sum(quantity) as begin_qty'))
                    ->where('postdate', '<', $strDate)
                    ->groupBy(DB::raw('material'), DB::raw('whscode'))
                    ->get();

        $query = DB::select('call spGetStockHistory(
            "'. $strDate .'",
            "'. $endDate .'",
            "'. $whsCode .'")');

        $mtMat = array();
        foreach ($query as $sg) {
            $mtMat[] = $sg->material;
        }

        $ftWhs = array();
        foreach ($query as $sg) {
            $ftWhs[] = $sg->whscode;
        }

        $stocks = array();
        foreach($materials as $key => $row){
            // $bQty = 0;
            if(in_array($row->material, $mtMat)){
                if(in_array($row->whscode, $ftWhs)){
                    foreach($query as $mat => $mrow){
                        if($row->material == $mrow->material && $row->whscode == $mrow->whscode){
                            $bQty = 0;
                            foreach($beginQty as $bqty => $mtqy){
                                if($mtqy->material == $mrow->material && $mtqy->whscode == $mrow->whscode){
                                    $bQty = $bQty + $mtqy->begin_qty;
                                }
                            }
                            $data = array(
                                'id'        => $row->id,
                                'material'  => $row->material,
                                'matdesc'   => $row->matdesc,
                                'begin_qty' => $bQty,
                                'qty_in'    => $mrow->qty_in,
                                'qty_out'   => $mrow->qty_out,
                                'whscode'   => $mrow->whscode,
                                'whsname'   => $mrow->whsname,
                                'unit'      => $mrow->unit,
                                'avg_price' => $row->avg_price,
                            );
                            array_push($stocks, $data);
                        }
                    }
                }
            }else{
                $bQty = 0;
                foreach($beginQty as $bqty => $mtqy){
                    if($mtqy->material == $row->material && $mtqy->whscode == $row->whscode){
                        $bQty = $bQty + $mtqy->begin_qty;
                    }
                }
                $data = array(
                    'id'        => $row->id,
                    'material'  => $row->material,
                    'matdesc'   => $row->matdesc,
                    'begin_qty' => $bQty,
                    'qty_in'    => 0,
                    'qty_out'   => 0,
                    'whscode'   => $row->whscode,
                    'whsname'   => $row->whsname,
                    'unit'      => $row->unit,
                    'avg_price' => $row->avg_price,
                );
                array_push($stocks, $data);
            }
        }
        $stocks = collect($stocks)->sortBy('whscode')->values();

        return $stocks;
    }

    public function map($row): array{
        // dd($row);
        $fields = [
            $row['material'],
            $row['matdesc'],
            $row['whsname'],
            $row['begin_qty'],
            $row['qty_in'],
            $row['qty_out'],
            $row['begin_qty'] + $row['qty_in'] + $row['qty_out'],
            $row['unit'],
            (int)$row['begin_qty'] + (int)$row['qty_in'] - (int)$row['qty_out']  * (int)$row['avg_price'],
        ];

        return $fields;
    }

    public function headings(): array
    {
        return [
                "Material",
                "Deskripsi",
                "Warehouse",
                "Begin Qty",
                "IN",
                "OUT",
                "End Qty",
                "Unit",
                "Total Value"
        ];
    }
    
  
}
