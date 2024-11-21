<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DataTables, Auth, DB;
use Validator,Redirect,Response;
use PDF;

class ReturBastController extends Controller
{
    public function index()
    {
        return view('transaksi.returbast.list_bast');
    }

    public function create($id)
    {
        $header = DB::table('t_bast01')->where('id', $id)->first();
        if($header){
            $items = DB::table('t_bast02')->where('bast_id', $id)->get();
            return view('transaksi.returbast.retur_bast',
                [
                    'header' => $header,
                    'items'  => $items
                ]);
        }else{
            abort(404, 'BAST not found');
        }
    }

    public function listRetur(){
        return view('transaksi.returbast.list_retur_bast');
    }

    public function printretur($id){
        $header = DB::table('v_retur_bast')
                ->distinct()
                ->select('id', 'nota_retur', 'no_bast', 'tgl_retur','createdby','diserahkan_oleh','whscode','whsname','remark','s_signfile')
                ->where('id', $id)->first();
        $items = DB::table('v_retur_bast')->where('id', $id)->get();

        $pdf = PDF::loadview('transaksi.returbast.formretur', ['header' => $header, 'items' => $items]);
        return $pdf->stream();
    }

    public function getListReturBast(Request $req)
    {
        $strDate  = $req->strdate;
        $endDate  = $req->enddate;


        $query = DB::table('v_retur_bast')
                ->distinct()
                ->select('id', 'nota_retur', 'no_bast', 'tgl_retur','createdby','diserahkan_oleh','whscode','whsname','remark');

        if(isset($req->strdate) && isset($req->enddate)){
            $query->whereBetween('tgl_retur', [$strDate, $endDate]);
        }else{
            if(isset($req->strdate)){
                $query->where('tgl_retur', $strDate);
            }

            if(isset($req->enddate)){
                $query->where('tgl_retur', '<=', $endDate);
            }
        }

        $query->orderBy('id', 'ASC');

        return DataTables::queryBuilder($query)
        ->editColumn('tgl_retur', function ($query){
            return [
                'tgl_retur' => \Carbon\Carbon::parse($query->tgl_retur)->format('d-m-Y')
             ];
        })
        ->toJson();

    }

    public function save(Request $req)
    {
        // return $req;
        DB::beginTransaction();
        try{
            $bulan = date('m');
            $tahun = date('Y');
            $prefix = 'RETBAST';

            $ptaNumber = generateNextNumber($prefix, 'RETBAST', $tahun, $bulan, '');
            // dd($ptaNumber);
            // $bastnum = $req['no_bast'];

            DB::table('t_retur_bast')->insert([
                'nota_retur'   => $ptaNumber,
                'tahun'        => $tahun,
                'no_bast'      => $req['no_bast'],
                'tgl_retur'    => $req['retdate'],
                'createdby'    => Auth::user()->username,
                'receive_from' => $req['diserahkan'],
                'remark'       => $req['remark'],
                'createdon'    => getLocalDatabaseDateTime(),
            ]);

            DB::table('t_inv01')->insert([
                'docnum'            => $ptaNumber,
                'docyear'           => $tahun,
                'docdate'           => $req['retdate'],
                'postdate'          => $req['retdate'],
                'received_by'       => $req['recipient'],
                'movement_code'     => '561',
                'remark'            => $req['remark'],
                'createdon'         => getLocalDatabaseDateTime(),
                'createdby'         => Auth::user()->email ?? Auth::user()->username
            ]);

            $parts    = $req['material'];
            $partdsc  = $req['matdesc'];
            $quantity = $req['returqty'];
            $uom      = $req['unit'];
            $whscode  = $req['whscode'];
            // $price    = $req['unitprice'];
            $bastnum  = $req['no_bast'];
            $bastitm  = $req['bastitem'];
            // $kodebudget = $req['kodebudget'];
            $pbjnum   = $req['pbjnum'];
            $pbjitem  = $req['pbjitem'];
            $itemtext = $req['item_remark'];

            $insertData = array();
            $count = 0;

            for($i = 0; $i < sizeof($parts); $i++){
                if($quantity[$i]){
                    $batchNumber = generateBatchNumber();
                    $qty    = $quantity[$i];
                    $qty    = str_replace(',','',$qty);

                    $issuedPrice = DB::table('t_inv02')
                                    ->where('wonum', $pbjnum[$i])
                                    ->where('woitem', $pbjitem[$i])
                                    ->where('movement_code','201')
                                    ->orderBy('createdon', 'DESC')
                                    ->first();

                    // $matPrice = $price[$i];
                    // $matPrice = str_replace(',','',$matPrice);

                    $count = $count + 1;
                    $data = array(
                        'docnum'       => $ptaNumber,
                        'docyear'      => $tahun,
                        'docitem'      => $count,
                        'movement_code'=> '561',
                        'material'     => $parts[$i],
                        'matdesc'      => $partdsc[$i],
                        'batch_number' => $batchNumber,
                        'quantity'     => $qty,
                        'unit'         => $uom[$i],
                        'unit_price'   => $issuedPrice->unit_price ?? 0,
                        'total_price'  => $issuedPrice->unit_price * $qty ?? 0,
                        'no_bast'      => $bastnum,
                        'bast_item'    => $bastitm[$i] ?? null,
                        'whscode'      => $whscode,
                        'shkzg'        => '+',
                        'remark'       => $itemtext[$i],
                        // 'budget_code'  => $kodebudget[$i],
                        'createdon'    => getLocalDatabaseDateTime(),
                        'createdby'    => Auth::user()->email ?? Auth::user()->username
                    );
                    array_push($insertData, $data);

                    DB::table('t_inv_batch_stock')->insert([
                        'material'     => $parts[$i],
                        'whscode'      => $whscode,
                        'batchnum'     => $batchNumber,
                        'quantity'     => $qty,
                        'unit'         => $uom[$i],
                        'last_udpate'  => getLocalDatabaseDateTime()
                    ]);

                    DB::table('t_inv_stock')->insert([
                        'material'     => $parts[$i],
                        'whscode'      => $whscode,
                        'batchnum'     => $batchNumber,
                        'quantity'     => $qty,
                        'unit'         => $uom[$i],
                        'last_udpate'  => getLocalDatabaseDateTime()
                    ]);

                    $BastItem = DB::table('t_bast02')
                    ->where('no_bast', $bastnum)
                    ->where('id', $bastitm[$i])->first();
                    if($BastItem){
                        $retQty = $BastItem->retur_qty + $qty;
                        DB::table('t_bast02')
                        ->where('no_bast', $bastnum)
                        ->where('id', $bastitm[$i])
                        ->update([
                            'retur_qty'    => $retQty
                        ]);
                    }
                }
            }
            insertOrUpdate($insertData,'t_inv02');

            DB::commit();
            return Redirect::to("/logistic/returbast")->withSuccess('Retur BAST Berhasil dengan nomor : '. $ptaNumber);
            // $result = array(
            //     'msgtype' => '200',
            //     'message' => 'BAST Berhasil disimpan '. $bastID . ' ' . $req['nomorbast']
            // );
            // return $result;

        }catch(\Exception $e){
            DB::rollBack();
            dd($e);
            // return Redirect::to("/logistic/terimapo")->withError($e->getMessage());
            return Redirect::to("/logistic/returbast/create/".$id)->withError($e->getMessage());
        }
    }
}
