<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Carbon\Carbon;
use DataTables, Auth, DB;

class StockOpnamImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try{

            // dd($rows);
            // dd($_POST);
            $bulan  = date('m');
            $tahun  = date('Y');

            $opnamNumber = $ptaNumber = generateNextNumber('OPNAM', 'OPNAM', $tahun, $bulan, '');

            $stockOpnamID = DB::table('t_stock_opnam01')->insertGetId([
                'pidnumber'         => $opnamNumber,
                'piddate'           => $_POST['tglupload'],
                'pidnote'           => $_POST['remark'],
                'piduser'           => Auth::user()->username,
                'whsid'             => $_POST['whscode'],
                'createdon'         => getLocalDatabaseDateTime(),
                'createdby'         => Auth::user()->email ?? Auth::user()->username
            ]);

            $count = 0;
            $insertData = array();
            foreach ($rows as $index => $row) {
                $matName = '';
                $material = DB::table('t_material')->where('material',$row['part_number'])->first();
                if($material){
                    $matName = $material->matdesc;
                }else{
                    $matName = $row['part_name'];
                }
                $count = $count + 1;
                DB::table('t_stock_opnam02')->insert([
                    'pidnumber'    => $opnamNumber,
                    'header_id'    => $stockOpnamID,
                    'piditem'      => $count,
                    'material'     => strval($row['part_number']),
                    'matdesc'      => $matName,
                    'actual_qty'   => $row['actual_stock'],
                    'matunit'      => $row['uom'],
                    'unit_price'   => $row['harga_satuan'],
                    'total_price'  => $row['actual_stock'] * $row['harga_satuan']
                ]);

                $excelData = array(
                    'pidnumber'    => $opnamNumber,
                    'header_id'    => $stockOpnamID,
                    'piditem'      => $count,
                    'material'     => strval($row['part_number']),
                    'matdesc'      => $matName,
                    'actual_qty'   => $row['actual_stock'],
                    'matunit'      => $row['uom'],
                    'unit_price'   => $row['harga_satuan'],
                    'total_price'  => $row['actual_stock'] * $row['harga_satuan']

                );
                array_push($insertData, $excelData);
            }
            // insertOrUpdate($insertData,'t_stock_opnam02');
            // dd($insertData);
            $approval = DB::table('v_workflow_budget')->where('object', 'OPNAM')->where('requester', Auth::user()->id)->get();
            if(sizeof($approval) > 0){
                // DB::table('t_opnam_approval')->where('pidnumber', $opnamNumber)->delete();
                $insertApproval = array();
                foreach($approval as $row){
                    $is_active = 'N';
                    if($row->approver_level == 1){
                        $is_active = 'Y';
                    }
                    $approvals = array(
                        'pidnumber'         => $opnamNumber,
                        'approver_level'    => $row->approver_level,
                        'approver'          => $row->approver,
                        'requester'         => Auth::user()->id,
                        'is_active'         => $is_active,
                        'createdon'         => getLocalDatabaseDateTime()
                    );
                    array_push($insertApproval, $approvals);
                }
                insertOrUpdate($insertApproval,'t_opnam_approval');
            }else{
                DB::table('t_stock_opnam01')->where('id', $stockOpnamID)
                ->update([
                    'approval_status' => 'A'
                ]);
            }

            DB::commit();
            // dd('aa');
            return true;
        }catch(\Exception $e){
            DB::rollBack();
            dd($e);
            return false;
            // return Redirect::to("/transaksi/withdraw")->withError($e->getMessage());
        }
    }
}
