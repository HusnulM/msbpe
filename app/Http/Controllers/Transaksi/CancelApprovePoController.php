<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DataTables, Auth, DB;
use Validator,Redirect,Response;

class CancelApprovePoController extends Controller
{
    public function index(){
        return view('transaksi.cancelapprove.cancelpo');
    }

    public function listPO(Request $request){
        if(isset($request->params)){
            $params = $request->params;
            $whereClause = $params['sac'];
        }
        $query = DB::table('v_rpo')
                 ->select('id', 'ponum', 'podat', 'vendor_name', 'note')
                 ->distinct()
                 ->orderBy('id');
        return DataTables::queryBuilder($query)
        ->toJson();
    }

    public function resetApprovePO($id){
        DB::beginTransaction();
        try{
            $wodata = DB::table('t_po01')->where('id', $id)->first();
            if($wodata){

                $checkGR = DB::table('t_po02')
                    ->where('ponum', $wodata->ponum)
                    ->where('grqty', '>', 0)->first();
                if($checkGR){
                    $result = array(
                        'msgtype' => '500',
                        'message' => 'PO '. $wodata->ponum . ' sudah ada proses penerimaan barang'
                    );
                }else{
                    $ptaNumber = $wodata->ponum;
                    $creator   = DB::table('users')->where('email',  $wodata->createdby)->first();
                    $approval  = DB::table('v_workflow_budget')->where('object', 'PO')->where('requester', $creator->id)->get();
                    $poItems   = DB::table('t_po02')->where('ponum', $ptaNumber)->get();

                    DB::table('t_po01')->where('id', $id)->update([
                        'approvestat'   => 'N'
                    ]);

                    DB::table('t_po02')->where('ponum', $ptaNumber)->update([
                        'approvestat'   => 'N'
                    ]);

                    if(sizeof($approval) > 0){
                        DB::table('t_po_approval')->where('ponum', $ptaNumber)->delete();
                        foreach($poItems as $pitem){
                            $insertApproval = array();
                            foreach($approval as $row){
                                $is_active = 'N';
                                if($row->approver_level == 1){
                                    $is_active = 'Y';
                                }
                                $approvals = array(
                                    'ponum'             => $ptaNumber,
                                    'poitem'            => $pitem->poitem,
                                    'approver_level'    => $row->approver_level,
                                    'approver'          => $row->approver,
                                    'requester'         => $creator->id,
                                    'is_active'         => $is_active,
                                    'createdon'         => getLocalDatabaseDateTime()
                                );
                                array_push($insertApproval, $approvals);
                            }
                            insertOrUpdate($insertApproval,'t_po_approval');
                        }
                    }
                    DB::commit();

                    $result = array(
                        'msgtype' => '200',
                        'message' => 'Approval PO '. $wodata->ponum . ' berhasil direset'
                    );
                }
            }else{
                $result = array(
                    'msgtype' => '500',
                    'message' => 'PO tidak ditemukan'
                );
            }
            return $result;
        }catch(\Exception $e){
            DB::rollBack();
            $result = array(
                'msgtype' => '500',
                'message' => $e->getMessage()
            );
            return $result;
        }
    }

    public function deletePO($id){
        DB::beginTransaction();
        try{
            $wodata = DB::table('t_po01')->where('id', $id)->first();
            if($wodata){
                DB::table('t_po01')->where('id', $id)->delete();
                $checkGR = DB::table('t_po02')
                    ->where('ponum', $wodata->ponum)
                    ->where('grqty', '>', 0)->first();
                if($checkGR){
                    $result = array(
                        'msgtype' => '500',
                        'message' => 'PO '. $wodata->ponum . ' sudah ada proses penerimaan barang'
                    );
                }else{
                    DB::table('t_po02')->where('ponum', $wodata->ponum)->delete();
                    DB::table('t_po03')->where('ponum', $wodata->ponum)->delete();
                    DB::table('t_po_approval')->where('ponum', $wodata->ponum)->delete();
                    DB::commit();

                    $result = array(
                        'msgtype' => '200',
                        'message' => 'PO '. $wodata->ponum . ' berhasil dihapus'
                    );
                }
            }else{
                $result = array(
                    'msgtype' => '500',
                    'message' => 'PO tidak ditemukan'
                );
            }
            return $result;
        }catch(\Exception $e){
            DB::rollBack();
            $result = array(
                'msgtype' => '500',
                'message' => $e->getMessage()
            );
            return $result;
        }
    }
}
