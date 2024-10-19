<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DataTables, Auth, DB;
use Validator,Redirect,Response;

class ReportsController extends Controller
{
    private $totalMaterialValue = 0;

    public function requestbudget(){
        $department = DB::table('t_department')->get();
        return view('laporan.requestbudget', ['department' => $department]);
    }

    public function budgetRequestlist(Request $req){
        $query = DB::table('v_rbudget');

        if(isset($req->department)){
            if($req->department !== 'All'){
                $query->where('deptid', $req->department);
            }
        }

        if(isset($req->approvalstat)){
            if($req->approvalstat === "O"){
                $query->where('budget_status', 'O');
            }elseif($req->approvalstat === "A"){
                $query->where('budget_status', 'A');
            }elseif($req->approvalstat === "R"){
                $query->where('budget_status', 'R');
            }
        }

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('tgl_aju', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('tgl_aju', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('tgl_aju', $req->dateto);
        }

        $query->orderBy('id');

        return DataTables::queryBuilder($query)
        ->editColumn('amount', function ($query){
            return [
                'amount1' => number_format($query->amount,0)
             ];
        })->editColumn('approved_amount', function ($query){
            return [
                'amount2' => number_format($query->approved_amount,0)
             ];
        })
        ->toJson();
    }

    public function pbj(){
        $department = DB::table('t_department')->get();
        return view('laporan.pbj', ['department' => $department]);
    }

    public function pbjList(Request $req){
        $query = DB::table('v_rpbj01');

        if(isset($req->department)){
            if($req->department !== 'All'){
                $query->where('deptid', $req->department);
            }
        }

        if(isset($req->approvalstat)){
            if($req->approvalstat === "O"){
                $query->where('approvestat', 'O');
            }elseif($req->approvalstat === "A"){
                $query->where('approvestat', 'A');
            }elseif($req->approvalstat === "R"){
                $query->where('approvestat', 'R');
            }elseif($req->approvalstat === "C"){
                $query->where('approvestat', 'C');
            }
        }

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('tgl_pbj', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('tgl_pbj', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('tgl_pbj', $req->dateto);
        }

        $query->orderBy('tgl_pbj', 'ASC'); //->orderBy('tgl_pbj', 'ASC');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
             ];
        })
        ->toJson();
    }

    public function po(){
        $department = DB::table('t_department')->get();
        return view('laporan.rpo', ['department' => $department]);
    }

    public function poList(Request $req){
        $query = DB::table('v_rpo_v2');

        if(isset($req->department)){
            if($req->department !== 'All'){
                $query->where('deptid', $req->department);
            }
        }

        if(isset($req->approvalstat)){
            if($req->approvalstat === "O"){
                $query->where('approvestat', 'O');
            }elseif($req->approvalstat === "A"){
                $query->where('approvestat', 'A');
            }elseif($req->approvalstat === "R"){
                $query->where('approvestat', 'R');
            }
        }

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('podat', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('podat', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('podat', $req->dateto);
        }

        $query->orderBy('id');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
            ];
        })->editColumn('grqty', function ($query){
            return [
                'qty2' => number_format($query->grqty,0)
            ];
        })->editColumn('openqty', function ($query){
            return [
                'qty3' => number_format($query->openqty,0)
            ];
        })->editColumn('price', function ($query){
            return [
                'price1' => number_format($query->price,0)
            ];
        })->editColumn('podat', function ($query){
            return [
                'podat1' => \Carbon\Carbon::parse($query->podat)->format('d-m-Y')
             ];
        })
        ->toJson();
    }

    public function pr(){
        $department = DB::table('t_department')->get();
        return view('laporan.rpr', ['department' => $department]);
    }

    public function prList(Request $req){
        $query = DB::table('v_rpr01');

        if(isset($req->department)){
            if($req->department !== 'All'){
                $query->where('deptid', $req->department);
            }
        }

        if(isset($req->approvalstat)){
            if($req->approvalstat === "O"){
                $query->where('approvestat', 'O');
            }elseif($req->approvalstat === "A"){
                $query->where('approvestat', 'A');
            }elseif($req->approvalstat === "R"){
                $query->where('approvestat', 'R');
            }
        }

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('prdate', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('prdate', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('prdate', $req->dateto);
        }

        $query->orderBy('id');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
            ];
        })->editColumn('prdate', function ($query){
            return [
                'prdate1' => \Carbon\Carbon::parse($query->prdate)->format('d-m-Y')
             ];
        })
        ->toJson();
    }

    public function wo(){
        $mekanik = DB::table('t_mekanik')->get();
        return view('laporan.rwo', ['mekanik' => $mekanik]);
    }

    public function woList(Request $req){
        $query = DB::table('v_rwo01');

        if(isset($req->mekanik)){
            if($req->mekanik !== 'All'){
                $query->where('mekanik', $req->mekanik);
            }
        }

        if(isset($req->approvalstat)){
            if($req->approvalstat === "O"){
                $query->where('approvestat', 'O');
            }elseif($req->approvalstat === "A"){
                $query->where('approvestat', 'A');
            }elseif($req->approvalstat === "R"){
                $query->where('approvestat', 'R');
            }
        }

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('wodate', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('wodate', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('wodate', $req->dateto);
        }

        $query->orderBy('id');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
            ];
        })->editColumn('wodate', function ($query){
            return [
                'wodate1' => \Carbon\Carbon::parse($query->wodate)->format('d-m-Y')
             ];
        })
        ->toJson();
    }

    public function grpo(){
        return view('laporan.rgrpo');
    }

    public function grpoList(Request $req){
        $query = DB::table('v_grpo_v2');

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('docdate', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('docdate', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('docdate', $req->dateto);
        }

        $query->orderBy('id');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
            ];
        })->editColumn('docdate', function ($query){
            return [
                'docdate1' => \Carbon\Carbon::parse($query->docdate)->format('d-m-Y')
             ];
        })
        ->toJson();
    }

    public function issue(){
        return view('laporan.rissued');
    }

    public function issueList(Request $req){
        $query = DB::table('v_rissue');

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('docdate', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('docdate', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('docdate', $req->dateto);
        }

        $query->orderBy('id');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
            ];
        })->editColumn('docdate', function ($query){
            return [
                'docdate1' => \Carbon\Carbon::parse($query->docdate)->format('d-m-Y')
             ];
        })
        ->toJson();
    }

    public function stock(){
        $warehouse = DB::table('t_warehouse')->get();
        return view('laporan.rstock', ['warehouse' => $warehouse]);
    }

    public function stockList(Request $req){
        $query = DB::table('v_inv_summary_stock_withvalue');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
            ];
        })
        ->editColumn('total_value', function ($query){
            return [
                'val' => number_format($query->total_value,0)
            ];
        })
        ->toJson();
    }

    public function batchStock(){
        $warehouse = DB::table('t_warehouse')->get();
        return view('laporan.rsbatchtock', ['warehouse' => $warehouse]);
    }

    public function batchStockList(Request $req){
        $query = DB::table('v_inv_batch_stock');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
            ];
        })
        ->toJson();
    }

    public function cost(){
        $mekanik = DB::table('t_mekanik')->get();
        return view('laporan.cost', ['mekanik' => $mekanik]);
    }

    public function costList(Request $req){
        // v_report_cost
        $query = DB::table('v_report_cost');

        if(isset($req->mekanik)){
            if($req->mekanik !== 'All'){
                $query->where('mekanik', $req->mekanik);
            }
        }

        if(isset($req->nopol)){
            $query->where('license_number', $req->nopol);
        }

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('wodate', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('wodate', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('wodate', $req->dateto);
        }

        $query->orderBy('id');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty1' => number_format($query->quantity,0)
            ];
        })->editColumn('wodate', function ($query){
            return [
                'wodate1' => \Carbon\Carbon::parse($query->wodate)->format('d-m-Y')
             ];
        })->editColumn('total_price', function ($query){
            return [
                'totalprice' => number_format($query->total_price,0),
                'totalprice2' => $query->total_price
            ];
        })
        ->toJson();
    }

    public function stockhistory()
    {
        $warehouse = DB::table('t_warehouse')->get();
        return view('laporan.historystock', ['warehouse' => $warehouse]);
    }

    public function getTotalValue(Request $req)
    {
        $data = $this->getHistoryStock($req);
        return $this->totalMaterialValue;
    }

    public function getHistoryStock(Request $req)
    {
        $whsCode = 0;
        if(isset($req->whsid) && $req->whsid != 0){
            $whsCode = $req->whsid;
            $materials = DB::table('v_material_movements_2')
                        ->where('whscode', $req->whsid)
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
        if(isset($req->datefrom)){
            $strDate = $req->datefrom;
        }

        $endDate = date('Y-m-d');
        if(isset($req->dateto)){
            $endDate = $req->dateto;
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
                                'qty_in'    => (int)$mrow->qty_in,
                                'qty_out'   => (int)$mrow->qty_out,
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
                    'qty_in'    => (int)0,
                    'qty_out'   => (int)0,
                    'whscode'   => $row->whscode,
                    'whsname'   => $row->whsname,
                    'unit'      => $row->unit,
                    'avg_price' => $row->avg_price,
                );
                array_push($stocks, $data);
            }
        }

        $stocks = collect($stocks)->sortBy('whscode')->values();
        // return $stocks;
        $totalValue = 0;
        foreach($stocks as $data => $val){
            // return $val['begin_qty'];
            $totalValue = $totalValue + (($val['begin_qty']+$val['qty_in']+$val['qty_out'])*$val['avg_price']);
        }

        $this->totalMaterialValue = $totalValue;
        // if($total === 'X'){
        //     return $totalValue;
        // }

        // $amount = 'A';
        return Datatables::of($stocks)
        ->addIndexColumn()
        ->editColumn('qty_in', function ($stocks){
            return [
                'in' => number_format($stocks['qty_in'],0)
            ];
        })
        ->editColumn('qty_out', function ($stocks){
            return [
                'out' => number_format($stocks['qty_out']*-1,0)
            ];
        })
        ->editColumn('end_qty', function ($stocks){
            return [
                'end' => number_format($stocks['begin_qty']+$stocks['qty_in']+$stocks['qty_out'],0)
            ];
        })
        ->editColumn('amount', function ($stocks){
            return [
                'value' => number_format(($stocks['begin_qty']+$stocks['qty_in']+$stocks['qty_out'])*$stocks['avg_price'],0)
            ];
        })
        ->editColumn('amount2', function ($stocks){
            // $aaa = $amount;
            return [
                'value' => ($stocks['begin_qty']+$stocks['qty_in']+$stocks['qty_out'])*$stocks['avg_price'],
                // 'total_value' => $aaa
            ];
        })
        ->make(true);
    }



    public function stockhistorydetails(Request $req)
    {
        $strDate  = $req->strdate;
        $endDate  = $req->enddate;
        $Material = $req->material;
        $whsCode  = $req->whscode;

        $query = DB::table('v_inv_move_details_v2');
        $query->where('material', $Material);
        $query->where('whscode', $whsCode);
        $query->whereBetween('postdate', [$strDate, $endDate]);

        $query->orderBy('postdate', 'ASC');

        return DataTables::queryBuilder($query)
        ->editColumn('quantity', function ($query){
            return [
                'qty' => number_format($query->quantity,0)
            ];
        })
        ->toJson();;

        // return Datatables::of($matMovement)
        //             ->addIndexColumn()
        //             ->editColumn('quantity', function ($matMovement){
        //                 return [
        //                     'qty' => number_format($matMovement['quantity'],0)
        //                 ];
        //             })
        //             ->make(true);
    }
}
