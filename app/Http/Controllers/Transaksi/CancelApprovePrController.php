<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DataTables, Auth, DB;
use Validator,Redirect,Response;

class CancelApprovePrController extends Controller
{
    public function index(){
        return view('transaksi.cancelapprove.cancelpr');
    }

    public function listPR(Request $request){
        if(isset($request->params)){
            $params = $request->params;
            $whereClause = $params['sac'];
        }
        $query = DB::table('v_rpr01')
                 ->select('id', 'prnum', 'prdate', 'requestby', 'deptname')
                 ->distinct()
                 ->orderBy('id');
        return DataTables::queryBuilder($query)
        ->toJson();
    }

    public function resetApprovePR($id){
        DB::beginTransaction();
        try{
            $wodata = DB::table('t_pr01')->where('id', $id)->first();
            if($wodata){
                $ptaNumber = $wodata->prnum;
                $creator   = DB::table('users')->where('email',  $wodata->createdby)->first();
                $approval  = DB::table('v_workflow_budget')->where('object', 'PR')->where('requester', $creator->id)->get();
                $prItems   = DB::table('t_pr02')->where('prnum', $ptaNumber)->get();

                if(sizeof($approval) > 0){
                    DB::table('t_pr_approvalv2')->where('prnum', $ptaNumber)->delete();
                    foreach($prItems as $pitem){
                        $insertApproval = array();
                        foreach($approval as $row){
                            $is_active = 'N';
                            if($row->approver_level == 1){
                                $is_active = 'Y';
                            }
                            $approvals = array(
                                'prnum'             => $ptaNumber,
                                'pritem'            => $pitem->pritem,
                                'approver_level'    => $row->approver_level,
                                'approver'          => $row->approver,
                                'requester'         => $creator->id,
                                'is_active'         => $is_active,
                                'createdon'         => getLocalDatabaseDateTime()
                            );
                            array_push($insertApproval, $approvals);
                        }
                        insertOrUpdate($insertApproval,'t_pr_approvalv2');
                    }
                }

                DB::table('t_pr01')->where('id', $id)->update([
                    'approvestat'   => 'N'
                ]);

                DB::table('t_pr02')->where('prnum', $ptaNumber)->update([
                    'approvestat'   => 'N'
                ]);

                DB::commit();

                $result = array(
                    'msgtype' => '200',
                    'message' => 'Approval PR '. $wodata->prnum . ' berhasil direset'
                );
            }else{
                $result = array(
                    'msgtype' => '500',
                    'message' => 'PR tidak ditemukan'
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

    public function deletePR($id){
        DB::beginTransaction();
        try{
            $wodata = DB::table('t_pr01')->where('id', $id)->first();
            if($wodata){
                DB::table('t_pr01')->where('id', $id)->delete();
                DB::table('t_pr02')->where('prnum', $wodata->prnum)->delete();
                DB::table('t_pr_approvalv2')->where('prnum', $wodata->prnum)->delete();
                DB::commit();

                $result = array(
                    'msgtype' => '200',
                    'message' => 'PR '. $wodata->prnum . ' berhasil dihapus'
                );
            }else{
                $result = array(
                    'msgtype' => '500',
                    'message' => 'PR tidak ditemukan'
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
