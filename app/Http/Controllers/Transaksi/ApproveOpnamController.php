<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use DataTables, Auth, DB;
use Validator,Redirect,Response;
use PDF;


class ApproveOpnamController extends Controller
{
    public function index()
    {
        return view('transaksi.opnam.approvelist');
    }

    public function approveDetail($id){
        $checkData = DB::table('t_stock_opnam01')->where('id', $id)->first();
        if($checkData){
            $items = DB::table('v_stock_opnam_approval_details')
                        ->where('id', $id)
                        ->where('approver', Auth::user()->id)
                        ->get();

            $approvals = DB::table('v_stock_opnam_approval_details')
                        ->distinct()
                        ->select('pidnumber','approver_name','approver_level','apprv_stat','approval_date','approval_remark')
                        ->where('pidnumber', $checkData->pidnumber)
                        ->orderBy('approver_level','asc')
                        // ->orderBy('piditem','asc')
                        ->get();

            $isApprovedbyUser = DB::table('v_opnam_approval')
                        ->where('pidnumber',  $checkData->pidnumber)
                        ->where('approver', Auth::user()->id)
                        ->where('is_active', 'Y')
                        ->first();

            return view('transaksi.opnam.approvedetail',
                [
                    'header'           => $checkData,
                    'items'            => $items,
                    'approvals'        => $approvals,
                    'isApprovedbyUser' => $isApprovedbyUser,
                ]);

        }else{

        }
    }

    public function approvalList(Request $request){
        $query = DB::table('v_opnam_approval')
                 ->where('approver',Auth::user()->id)
                 ->where('is_active','Y')
                 ->where('apprv_stat','N')
                 ->orderBy('id', 'DESC');
        return DataTables::queryBuilder($query)
        ->editColumn('piddate', function ($query){
            return [
                'piddate' => \Carbon\Carbon::parse($query->piddate)->format('d-m-Y')
             ];
        })
        ->toJson();
    }

    public function getNextApproval($dcnNum){
        $userLevel = DB::table('t_opnam_approval')
                    ->where('pidnumber', $dcnNum)
                    ->where('approver', Auth::user()->id)
                    ->first();

        $nextApproval = DB::table('t_opnam_approval')
                        ->where('pidnumber', $dcnNum)
                        ->where('approver_level', '>', $userLevel->approver_level)
                        ->orderBy('approver_level', 'ASC')
                        ->first();

        // return $userLevel;
        if($nextApproval){
            return $nextApproval->approver_level;
        }else{
            return null;
        }
    }

    public function saveApproveHeader(Request $req){
        DB::beginTransaction();
        try{

            $ptaNumber = $req->pidnumber;

            $userAppLevel = DB::table('t_opnam_approval')
                            ->select('approver_level')
                            ->where('pidnumber', $ptaNumber)
                            ->where('approver', Auth::user()->id)
                            ->first();


            DB::table('t_opnam_approval')
                    ->where('pidnumber', $ptaNumber)
                    ->where('approver_level',$userAppLevel->approver_level)
                    ->update([
                        'approval_status' => 'A',
                        'approval_remark' => $req->approvernote,
                        'approved_by'     => Auth::user()->username,
                        'approval_date'   => getLocalDatabaseDateTime()
                ]);

            $nextApprover = $this->getNextApproval($ptaNumber);
            if($nextApprover  != null){
                DB::table('t_opnam_approval')
                ->where('pidnumber', $ptaNumber)
                ->where('approver_level', $nextApprover)
                ->update([
                    'is_active' => 'Y'
                ]);
            }

            $checkIsFullApprove = DB::table('t_opnam_approval')
                                      ->where('pidnumber', $ptaNumber)
                                      ->where('approval_status', '!=', 'A')
                                      ->get();
            if(sizeof($checkIsFullApprove) > 0){
                // go to next approver
            }else{
                //Full Approve
                DB::table('t_stock_opnam01')->where('pidnumber', $ptaNumber)->update([
                    'approval_status'   => 'A'
                ]);

                $buangStockLama = $this->postOldDocument($ptaNumber);

                if($buangStockLama['msgtype'] == '200'){
                    $postPID        = $this->postPIDDocument($ptaNumber);
                    if($postPID['msgtype'] == '200'){
                        // $postPID        = $this->postPIDDocument($ptaNumber);
                        // dd($postPID);
                    }else{
                        DB::rollBack();
                        $result = array(
                            'msgtype' => '500',
                            'message' => $postPID['message']
                        );
                        return $result;
                    }
                }else{
                    DB::rollBack();
                    $result = array(
                        'msgtype' => '500',
                        'message' => $buangStockLama['message']
                    );
                    return $result;
                }
            }

            DB::commit();
            $result = array(
                'msgtype' => '200',
                'message' => 'Stock Opnam : '. $ptaNumber . ' berhasil di approve'
            );
            return $result;
        }
        catch(\Exception $e){
            DB::rollBack();
            $result = array(
                'msgtype' => '500',
                'message' => $e->getMessage()
            );
            return $result;
        }
    }

    public function saveApproveItems(Request $req){
        DB::beginTransaction();
        try{

            $ptaNumber = $req->pidnumber;

            DB::commit();
            $result = array(
                'msgtype' => '200',
                'message' => 'Stock Opnam : '. $ptaNumber . ' berhasil di approve'
            );
            return $result;
        }
        catch(\Exception $e){
            DB::rollBack();
            $result = array(
                'msgtype' => '500',
                'message' => $e->getMessage()
            );
            return $result;
        }
    }

    public function postPIDDocument($pidNumber){
        DB::beginTransaction();
        try{
            $pidData = DB::table('v_stock_opname_detail')
                ->where('pidnumber', $pidNumber)
                ->orWhere('id', $pidNumber)
                ->get();

            // dd($_POST);
            $postDate = date('Y-m-d');
            $bulan  = date('m');
            $tahun  = date('Y');
            $prefix = 'PID';
            $ptaNumber = generateNextNumber($prefix, 'PID', $tahun, $bulan, '');

            DB::table('t_inv01')->insert([
                'docnum'            => $ptaNumber,
                'docyear'           => $tahun,
                'docdate'           => $postDate,
                'postdate'          => $postDate,
                'received_by'       => Auth::user()->username,
                'movement_code'     => '661',
                'remark'            => 'Stock Opnam '. $pidNumber,
                'refdoc'            => $pidNumber,
                'createdon'         => getLocalDatabaseDateTime(),
                'createdby'         => Auth::user()->email ?? Auth::user()->username
            ]);


            $count = 0;
            foreach ($pidData as $index => $row) {
                // Kosongin Existing Stock
                DB::table('t_inv_stock')
                    ->where('material', $row->material)
                    ->where('whscode', $row->whsid)
                    ->update([
                        'quantity' => 0
                    ]);

                DB::table('t_inv_batch_stock')
                    ->where('material', $row->material)
                    ->where('whscode', $row->whsid)
                    ->update([
                        'quantity' => 0
                    ]);
                // dd($row);
                $batchNumber = generateBatchNumber();
                $count = $count + 1;
                $insertData = array();
                $excelData = array(
                    'docnum'       => $ptaNumber,
                    'docyear'      => $tahun,
                    'docitem'      => $count,
                    'movement_code'=> '661',
                    'material'     => $row->material,
                    'matdesc'      => $row->matdesc,
                    'batch_number' => $batchNumber,
                    'quantity'     => $row->actual_qty,
                    'unit'         => $row->matunit,
                    'unit_price'   => $row->unit_price,
                    'total_price'  => $row->total_price,
                    'whscode'      => $row->whsid,
                    'shkzg'        => '+',
                    'createdon'    => getLocalDatabaseDateTime(),
                    'createdby'    => Auth::user()->email ?? Auth::user()->username

                );
                array_push($insertData, $excelData);
                insertOrUpdate($insertData,'t_inv02');

                DB::table('t_inv_batch_stock')->insert([
                    'material'     => $row->material,
                    'whscode'      => $row->whsid,
                    'batchnum'     => $batchNumber,
                    'quantity'     => $row->actual_qty,
                    'unit'         => $row->matunit,
                    'last_udpate'  => getLocalDatabaseDateTime()
                ]);

                DB::table('t_inv_stock')->insert([
                    'material'     => $row->material,
                    'whscode'      => $row->whsid,
                    'batchnum'     => $batchNumber,
                    'quantity'     => $row->actual_qty,
                    'unit'         => $row->matunit,
                    'last_udpate'  => getLocalDatabaseDateTime()
                ]);
            }

            DB::commit();

            $result = array(
                'msgtype' => '200',
                'message' => 'Success'
            );
            return $result;
        }catch(\Exception $e){
            DB::rollBack();
            // dd($e);
            $result = array(
                'msgtype' => '500',
                'message' => $e->getMessage()
            );
            return $result;
        }
    }

    // Buat Transaksi Negatif untuk existing Stock supaya stock Balance
    public function postOldDocument($pidNumber){
        DB::beginTransaction();
        try{
            $pidData = DB::table('v_stock_opname_detail')
                ->where('pidnumber', $pidNumber)
                ->orWhere('id', $pidNumber)
                ->get();

            // dd($_POST);
            $postDate = date('Y-m-d');
            $bulan    = date('m');
            $tahun    = date('Y');
            $prefix   = 'ISSUEPID';
            $ptaNumber = generateNextNumber($prefix, 'ISSUEPID', $tahun, $bulan, '');

            DB::table('t_inv01')->insert([
                'docnum'            => $ptaNumber,
                'docyear'           => $tahun,
                'docdate'           => $postDate,
                'postdate'          => $postDate,
                'received_by'       => Auth::user()->username,
                'movement_code'     => '201',
                'remark'            => 'Stock Opnam '. $pidNumber,
                'refdoc'            => $pidNumber,
                'createdon'         => getLocalDatabaseDateTime(),
                'createdby'         => Auth::user()->email ?? Auth::user()->username
            ]);

            // Create Inventory Movement Negatif untuk meng 0 kan stock Lama
            foreach ($pidData as $index => $row) {
                $oldItems = DB::table('t_inv_batch_stock')
                            ->where('material', $row->material)
                            ->where('whscode', $row->whsid)
                            ->where('quantity', '>', 0)
                            ->get();
                $count = 0;
                foreach($oldItems as $olddata => $old){
                    $count = $count + 1;
                    $insertData = array();
                    $excelData = array(
                        'docnum'       => $ptaNumber,
                        'docyear'      => $tahun,
                        'docitem'      => $count,
                        'movement_code'=> '201',
                        'material'     => $row->material,
                        'matdesc'      => $row->matdesc,
                        'batch_number' => $old->batchnum,
                        'quantity'     => $old->quantity,
                        'unit'         => $row->matunit,
                        'unit_price'   => $row->unit_price,
                        'total_price'  => $row->total_price,
                        'whscode'      => $row->whsid,
                        'shkzg'        => '-',
                        'createdon'    => getLocalDatabaseDateTime(),
                        'createdby'    => Auth::user()->email ?? Auth::user()->username

                    );
                    array_push($insertData, $excelData);
                    insertOrUpdate($insertData,'t_inv02');
                }
                // DB::commit();
            }

            DB::commit();

            $result = array(
                'msgtype' => '200',
                'message' => 'Success'
            );
            return $result;
        }catch(\Exception $e){
            DB::rollBack();
            // dd($e);
            $result = array(
                'msgtype' => '500',
                'message' => $e->getMessage()
            );
            return $result;
        }
    }
}
