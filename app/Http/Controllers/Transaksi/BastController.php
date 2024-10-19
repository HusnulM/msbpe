<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DataTables, Auth, DB;
use Validator,Redirect,Response;

class BastController extends Controller
{
    public function index(){
        return view('transaksi.bast.index');
    }

    public function create($pbjID){
        $pbjheader = DB::table('v_pbj_to_bast')->where('id', $pbjID)->first();
        if($pbjheader){
            $pbjitems = DB::table('t_pbj02')->where('pbjnumber', $pbjheader->pbjnumber)
                        ->where('approvestat', 'A')
                        ->Where('bast_created', 'N')
                        ->get();
            // return $pbjitems;
            return view('transaksi.bast.create',[
                'pbjheader' => $pbjheader,
                'pbjitems'  => $pbjitems
            ]);
        }else{
            return Redirect::to("/logistic/bast"); //return view('transaksi.bast.index');
        }
    }

    public function viewListBast(){
        return view('transaksi.bast.list');
    }

    public function detailBAST($id){
        $header = DB::table('v_bast_01')->where('id', $id)->first();
        $items  = DB::table('t_bast02')->where('bast_id', $id)->get();
        $attachment = DB::table('t_attachments')->where('doc_object', 'BAST')
                     ->where('doc_number', $id)->first();

        if(!$attachment){
            $attachment = null;
        }
        return view('transaksi.bast.detail',[
            'header' => $header,
            'items'  => $items,
            'file'   => $attachment
        ]);
    }

    function listDataBast(Request $req){
        $params = $req->params;
        $whereClause = $params['sac'];
        $query = DB::table('v_bast_01');

        if(isset($req->datefrom) && isset($req->dateto)){
            $query->whereBetween('tanggal_bast', [$req->datefrom, $req->dateto]);
        }elseif(isset($req->datefrom)){
            $query->where('tanggal_bast', $req->datefrom);
        }elseif(isset($req->dateto)){
            $query->where('tanggal_bast', '<=', $req->dateto);
        }

        $query->orderBy('id');

        return DataTables::queryBuilder($query)
        ->toJson();
    }

    public function dataBast(Request $request){
        $params = $request->params;
        $whereClause = $params['sac'];
        $query = DB::table('v_pbj_to_bast')->orderBy('id');
        return DataTables::queryBuilder($query)
        ->toJson();
    }

    public function findUser(Request $request){
        $query['data'] = DB::table('users')->select('id','name','email','username')
        ->where('name', 'like', '%'. $request->search . '%')->get();
        return $query;
    }

    public function save(Request $req){
        // return $req;
        DB::beginTransaction();
        try{

            $bastID = DB::table('t_bast01')->insertGetId([
                'no_bast'         => $req['nomorbast'],
                'userid_pemberi'  => Auth::user()->id,
                'userid_penerima' => $req['penerima'],
                'tanggal_bast'    => $req['tglbast'],
                'remark'          => $req['remark'],
                'createdon'       => getLocalDatabaseDateTime(),
                'createdby'       => Auth::user()->email ?? Auth::user()->username
            ]);

            $parts    = $req['material'];
            $partdsc  = $req['matdesc'];
            $quantity = $req['bastquantity'];
            $uom      = $req['unit'];
            $pbjnum   = $req['pbjnumber'];
            $pbjitm   = $req['pbjitem'];
            $wonum    = $req['wonum'];
            // return $wonum;
            $insertData = array();
            $count = 0;

            $pbjheader = DB::table('t_pbj01')->where('pbjnumber', $pbjnum[0])->first();

            $ptaNumber = generateIssueNumber(date('Y'), date('m'));

                DB::table('t_inv01')->insert([
                    'docnum' => $ptaNumber,
                    'docyear' => date('Y'),
                    'docdate' => date('Y-m-d'),
                    'postdate' => date('Y-m-d'),
                    'movement_code' => '201',
                    'remark' => 'Issued BAST',
                    'createdon'         => date('Y-m-d H:m:s'),
                    'createdby'         => Auth::user()->email ?? Auth::user()->username
                ]);

            for($i = 0; $i < sizeof($parts); $i++){
                if(!isset($quantity[$i]) || $quantity[$i] == 0){
                    continue;
                }

                $qty    = 0;
                $qty    = $quantity[$i];
                $qty    = str_replace(',','',$qty);
                $inputQty = $qty;
                $qty = (int)$qty;

                $warehouseID = 0;

                if($pbjheader->pbjtype == 1){
                    // dd('1');
                    if($wonum[$i]){
                        $wodata = DB::table('t_wo01')->where('wonum', $wonum[$i])->first();
                        $warehouseID = $wodata->whscode;
                    }else{
                        $pbjdtl = DB::table('t_pbj02')
                        ->where('pbjnumber', $pbjnum[$i])
                        ->where('pbjitem', $pbjitm[$i])->first();
                        $warehouseID = $pbjdtl->whscode;

                        $qty = (int)$qty + (int)$pbjdtl->realized_qty;
                    }
                    $latestStock = DB::table('v_inv_summary_stock')
                                   ->where('material', $parts[$i])
                                   ->where('whsid',    $warehouseID)->first();

                    if($latestStock){
                        if((int)$latestStock->quantity < (int)$inputQty){
                            DB::rollBack();
                            $result = array(
                                'msgtype' => '400',
                                'message' => 'Stock Tidak Mencukupi untuk part : '. $parts[$i]
                            );
                            return $result;
                            //return Redirect::to("/logistic/bast/create/".$req['pbjID'])->withError('Stock Tidak Mencukupi untuk part : '. $parts[$i]);
                        }
                    }else{
                        DB::rollBack();
                        $result = array(
                            'msgtype' => '400',
                            'message' => 'Stock Tidak Mencukupi untuk part : '. $parts[$i]
                        );
                        return $result;
                        // return Redirect::to("/logistic/bast/create/".$req['pbjID'])->withError('Stock Tidak Mencukupi untuk part : '. $parts[$i]);
                    }

                    if((int)$pbjdtl->quantity < (int)$qty){
                        DB::rollBack();
                        $openQty = (int)$pbjdtl->quantity - (int)$pbjdtl->realized_qty;
                        $result = array(
                            'msgtype' => '400',
                            'message' => 'Quantity BAST lebih besar dari Open Quantity PBJ Partnumber '. $parts[$i] . '. Open Quantity ( '. $openQty . ' )'
                        );
                        return $result;
                    }
                }else{
                    // dd('2');
                    $pbjdtl = DB::table('t_pbj02')
                            ->where('pbjnumber', $pbjnum[$i])
                            ->where('pbjitem', $pbjitm[$i])->first();
                    // $qty = $qty*1 + $pbjdtl->realized_qty;
                    $qty = (int)$qty + (int)$pbjdtl->realized_qty;
                    //dd($qty);
                    $warehouseID = $pbjdtl->whscode;
                    $latestStock = DB::table('v_inv_summary_stock')
                                   ->where('material', $parts[$i])
                                   ->where('whsid',    $pbjdtl->whscode)->first();
                    //dd((int)($latestStock->quantity));
                    if($latestStock){
                        if((int)$latestStock->quantity < (int)$inputQty){
                            DB::rollBack();
                            $result = array(
                                'msgtype' => '400',
                                'message' => 'Stock Tidak Mencukupi untuk part : '. $parts[$i]
                            );
                            return $result;
                            // return Redirect::to("/logistic/bast/create/".$req['pbjID'])->withError('Stock Tidak Mencukupi untuk part : '. $parts[$i]);
                        }
                    }else{
                        DB::rollBack();
                        $result = array(
                            'msgtype' => '400',
                            'message' => 'Stock Tidak Mencukupi untuk part : '. $parts[$i]
                        );
                        return $result;
                        // return Redirect::to("/logistic/bast/create/".$req['pbjID'])->withError('Stock Tidak Mencukupi untuk part : '. $parts[$i]);
                    }

                    if((int)$pbjdtl->quantity < (int)$qty){
                        $openQty = (int)$pbjdtl->quantity - (int)$pbjdtl->realized_qty;
                        DB::rollBack();
                        $result = array(
                            'msgtype' => '400',
                            'message' => 'Quantity BAST lebih besar dari Open Quantity PBJ Partnumber '. $parts[$i] . '. Open Quantity ( '. $openQty . ' )'
                        );
                        return $result;
                    }
                }


                //dd($result);

                $matdesc = str_replace('"','\"',$partdsc[$i]);

                $data = array(
                    'bast_id'      => $bastID,
                    'no_bast'      => $req['nomorbast'],
                    'material'     => $parts[$i],
                    'matdesc'      => $partdsc[$i],
                    'quantity'     => $qty,
                    'unit'         => $uom[$i],
                    'refdoc'       => $pbjnum[$i] ?? 0,
                    'refdocitem'   => $pbjitm[$i] ?? 0,
                    'createdon'    => getLocalDatabaseDateTime(),
                    'createdby'    => Auth::user()->email ?? Auth::user()->username
                );
                array_push($insertData, $data);

                $pbjitem = DB::table('t_pbj02')
                            ->where('pbjnumber', $pbjnum[$i])
                            ->where('pbjitem', $pbjitm[$i])->first();

                $relQty = $pbjitem->realized_qty + $qty;

                if($relQty >= $pbjitem->quantity){
                    DB::table('t_pbj02')->where('pbjnumber', $pbjnum[$i])->where('pbjitem', $pbjitm[$i])
                    ->update([
                        'itemstatus'   => 'C',
                        'bast_created' => 'Y',
                        'realized_qty' => $relQty
                    ]);
                }else{
                    DB::table('t_pbj02')->where('pbjnumber', $pbjnum[$i])->where('pbjitem', $pbjitm[$i])
                    ->update([
                        'realized_qty' => $relQty
                    ]);
                }

                $matdesc = str_replace('"','\"',$partdsc[$i]);
                $matCode = str_replace('"','\"',$parts[$i]);

                DB::select('call spIssueMaterialWithBatchFIFO(
                    "'. $matCode .'",
                    "'. $warehouseID .'",
                    "'. $qty .'",
                    "'. $ptaNumber .'",
                    "'. date('Y') .'",
                    "201",
                    "'. $matdesc .'",
                    "'. $uom[$i] .'",
                    "-",
                    "'. $pbjnum[$i] .'",
                    "'. $pbjitm[$i] .'",
                    "'. Auth::user()->email .'")');
            }
            insertOrUpdate($insertData,'t_bast02');


            //Insert Attachments | t_attachments
            if(isset($req['efile'])){
                $files = $req['efile'];
                $insertFiles = array();

                foreach ($files as $efile) {
                    $filename = $efile->getClientOriginalName();
                    $upfiles = array(
                        'doc_object' => 'BAST',
                        'doc_number' => $bastID,
                        'efile'      => $filename,
                        'pathfile'   => '/files/BAST/'. $filename,
                        'createdon'  => getLocalDatabaseDateTime(),
                        'createdby'  => Auth::user()->username ?? Auth::user()->email
                    );
                    array_push($insertFiles, $upfiles);

                    // $efile->move(public_path().'/files/PO/', $filename);
                    $efile->move('files/BAST/', $filename);
                }
                if(sizeof($insertFiles) > 0){
                    insertOrUpdate($insertFiles,'t_attachments');
                }
            }
            DB::commit();

            // $pbjnum   = $req['pbjnumber'];
            // $pbjitm   = $req['pbjitem'];

            sleep(2);

            $checkBastAll = DB::table('t_pbj02')
                            ->where('pbjnumber', $pbjheader->pbjnumber)
                            ->where('bast_created', 'N')->first();
            if(!$checkBastAll){
                DB::table('t_pbj01')->where('pbjnumber', $pbjheader->pbjnumber)
                    ->update([
                        'bast_created' => 'Y'
                    ]);
            }

            DB::commit();
            $result = array(
                'msgtype' => '200',
                'message' => 'BAST Berhasil disimpan '. $bastID
            );
            return $result;
            // return Redirect::to("/logistic/bast")->withSuccess('BAST Berhasil disimpan');
        } catch(\Exception $e){
            DB::rollBack();
            $result = array(
                'msgtype' => '400',
                'message' => $e->getMessage()
            );
            return $result;
            // dd($e);
            // return Redirect::to("/logistic/bast")->withError($e->getMessage());
            // return Redirect::to("/logistic/bast/create/".$req['pbjID'])->withError($e->getMessage());
        }
    }

    function mysql_escape_mimic($inp) {

        if(is_array($inp))
            return array_map(__METHOD__, $inp);

        if(!empty($inp) && is_string($inp)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
        }

        return $inp;
    }
}
