@extends('layouts/App')

@section('title', 'List Stock Opnam')

@section('additional-css')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">List Stock Opnam</h3>
                    <div class="card-tools">
                        <a href="{{ url('/logistic/stockopname') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-back"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ url('report/exportpr') }}" method="post">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-2">
                                        <label for="">Tanggal Stock Opnam</label>
                                        <input type="date" class="form-control" name="datefrom" id="datefrom" value="{{ $_GET['datefrom'] ?? '' }}">
                                    </div>
                                    <div class="col-lg-2">
                                        <label for="">-</label>
                                        <input type="date" class="form-control" name="dateto" id="dateto" value="{{ $_GET['dateto'] ?? '' }}">
                                    </div>

                                    <div class="col-lg-2" style="text-align:right;">
                                        <br>
                                        <button type="button" class="btn btn-default mt-2 btn-search">
                                            <i class="fa fa-search"></i> Filter
                                        </button>
                                        {{-- <button type="submit" class="btn btn-success mt-2 btn-export">
                                            <i class="fa fa-download"></i> Export Data
                                        </button> --}}
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="table-responsive">
                            <table id="tbl-bast-list" class="table table-bordered table-hover table-striped table-sm" style="width:100%;">
                                <thead>
                                    <th>No</th>
                                    <th>Nomor Opnam</th>
                                    <th>Tanggal Opnam</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Remark</th>
                                    <th></th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('additional-modal')
<div class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalDetailOpname">
    <div class="modal-dialog modal-xl">
        <form class="form-horizontal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalApprovalTitle">Detail Stock Opnam</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="position-relative row form-group">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <table id="tbl-pid-details" class="table table-bordered table-hover table-striped table-sm" style="width:100%;">
                                <thead>
                                    <th>No</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Actual Quantity</th>
                                    <th>System Quantity</th>
                                    <th>Diff. Quantity</th>
                                    <th>Uom</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="submit-approval"> OK</button>
            </div>
        </div>
        </form>
    </div>
</div>

<div class="modal fade bd-example-modal-xl" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalOpnameApprovals">
    <div class="modal-dialog modal-xl">
        <form class="form-horizontal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalOpnameApprovalTitle">Stock Opnam Approval Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="position-relative row form-group">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <table id="tbl-pid-approvals" class="table table-bordered table-hover table-striped table-sm" style="width:100%;">
                                <thead>
                                    <th>Approver Name</th>
                                    <th>Approver Level</th>
                                    <th>Approval Status</th>
                                    <th>Approve/Reject Date</th>
                                    <th>Approver Note</th>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="submit-approval"> OK</button>
            </div>
        </div>
        </form>
    </div>
</div>

@endsection

@section('additional-js')
<script>
    function validate(evt) {
        var theEvent = evt || window.event;

        // Handle paste
        if (theEvent.type === 'paste') {
            key = event.clipboardData.getData('text/plain');
        } else {
        // Handle key press
            var key = theEvent.keyCode || theEvent.which;
            key = String.fromCharCode(key);
        }
        var regex = /[0-9]|\./;
        if( !regex.test(key) ) {
            theEvent.returnValue = false;
            if(theEvent.preventDefault) theEvent.preventDefault();
        }
    }

    $(document).ready(function(){

        $('.btn-search').on('click', function(){
            var param = '?datefrom='+ $('#datefrom').val() +'&dateto='+ $('#dateto').val();
            loadDocument(param);
        });

        loadDocument('');

        function loadDocument(_params){
            $("#tbl-bast-list").DataTable({
                serverSide: true,
                ajax: {
                    url: base_url+'/logistic/stockopname/getlist'+_params,
                    data: function (data) {
                        data.params = {
                            sac: "sac"
                        }
                    }
                },
                buttons: false,
                searching: true,
                scrollY: 500,
                scrollX: true,
                scrollCollapse: true,
                bDestroy: true,
                columns: [
                    { "data": null,"sortable": false, "searchable": false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {data: "pidnumber", className: 'uid'},
                    {data: "piddate", className: 'uid',
                        render: function (data, type, row){
                            return ``+ row.piddate.piddate + ``;
                        }
                    },
                    {data: "piduser", className: 'uid'},
                    {data: "pidnote", className: 'uid'},
                    {"defaultContent":
                        `<button class='btn btn-primary btn-sm button-print'> <i class='fa fa-search'></i> View Details </button>
                        <button class='btn btn-primary btn-sm button-view-approval'> <i class='fa fa-search'></i> View Approval </button>
                        `,
                        "className": "text-center",
                    }
                ]
            }).columns.adjust().draw();

            $('#tbl-bast-list tbody').on( 'click', '.button-print', function () {
                var table = $('#tbl-bast-list').DataTable();
                selected_data = [];
                selected_data = table.row($(this).closest('tr')).data();

                loadDetails(selected_data.id);
                $('#modalApprovalTitle').html('');
                $('#modalApprovalTitle').append(`Stock Opnam <b> `+ selected_data.pidnumber + ` </b> details`);
                $('#modalDetailOpname').modal('show');
            });

            $('#tbl-bast-list tbody').on( 'click', '.button-view-approval', function () {
                var table = $('#tbl-bast-list').DataTable();
                selected_data = [];
                selected_data = table.row($(this).closest('tr')).data();

                loadApprovals(selected_data.id);
                $('#modalOpnameApprovalTitle').html('');
                $('#modalOpnameApprovalTitle').append(`Stock Opnam <b> `+ selected_data.pidnumber + ` </b> approval details`);
                $('#modalOpnameApprovals').modal('show');
            });
        }

        function loadDetails(_id){
            $("#tbl-pid-details").DataTable({
                serverSide: true,
                ajax: {
                    url: base_url+'/logistic/stockopname/getdetails/'+_id,
                    data: function (data) {
                        data.params = {
                            sac: "sac"
                        }
                    }
                },
                buttons: false,
                searching: true,
                scrollY: 500,
                scrollX: true,
                scrollCollapse: true,
                bDestroy: true,
                columns: [
                    { "data": null,"sortable": false, "searchable": false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {data: "material", className: 'uid'},
                    {data: "matdesc", className: 'uid'},
                    {data: "actual_qty", className: 'uid', "className": "text-right"},
                    {data: "quantity", className: 'uid',
                        render: function (data, type, row){
                            return ``+ row.quantity.qty1 + ``;
                        },
                        "className": "text-right"
                    },
                    {data: "diffqty", className: 'uid', "className": "text-right"},
                    {data: "matunit", className: 'uid'},
                    {data: "unit_price", className: 'uid', "className": "text-right",
                        render: function (data, type, row){
                            return ``+ row.unit_price.uprice + ``;
                        },
                        "className": "text-right"
                    },
                    {data: "total_price", className: 'uid', "className": "text-right",
                        render: function (data, type, row){
                            return ``+ row.total_price.total + ``;
                        },
                        "className": "text-right"
                    },
                ]
            }).columns.adjust().draw();
        }

        function loadApprovals(_id){
            $("#tbl-pid-approvals").DataTable({
                serverSide: true,
                ajax: {
                    url: base_url+'/logistic/stockopname/approvalstatus/'+_id,
                    data: function (data) {
                        data.params = {
                            sac: "sac"
                        }
                    }
                },
                buttons: false,
                searching: true,
                scrollY: 500,
                scrollX: true,
                scrollCollapse: true,
                bDestroy: true,
                columns: [
                    {data: "approver_name", className: 'uid'},
                    {data: "approver_level", className: 'uid'},
                    {data: "apprv_stat", className: 'uid',
                        render: function (data, type, row){
                            if(row.apprv_stat == "O"){
                                return `Open`;
                            }else if(row.apprv_stat == "A"){
                                return `Approved`;
                            }else if(row.apprv_stat == "R"){
                                return `Rejected`;
                            }else{
                                return `Open`;
                            }
                        }
                    },
                    {data: "approval_date", className: 'uid'},
                    {data: "approval_remark", className: 'uid'},
                ]
            });
        }

        $('.inputNumber').on('change', function(){
            this.value = formatRupiah(this.value,'');
        });

        function formatRupiah(angka, prefix){
            var number_string = angka.toString().replace(/[^.\d]/g, '').toString(),
            split   		  = number_string.split('.'),
            sisa     		  = split[0].length % 3,
            rupiah     		  = split[0].substr(0, sisa),
            ribuan     		  = split[0].substr(sisa).match(/\d{3}/gi);

            if(ribuan){
                separator = sisa ? ',' : '';
                rupiah += separator + ribuan.join(',');
            }

            rupiah = split[1] != undefined ? rupiah + '.' + split[1] : rupiah;
            return prefix == undefined ? rupiah : (rupiah ? '' + rupiah : '');
        }
    });
</script>
@endsection
