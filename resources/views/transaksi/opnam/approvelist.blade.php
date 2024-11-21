@extends('layouts/App')

@section('title', 'List Approve Stok Opnam')

@section('additional-css')
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">List Approve Stok Opnam</h3>
                    <div class="card-tools">
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="table-responsive">
                            <table id="tbl-budget-list" class="table table-bordered table-hover table-striped table-sm" style="width:100%;">
                                <thead>
                                    <th>No</th>
                                    <th>Nomor Retur</th>
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
        let _token   = $('meta[name="csrf-token"]').attr('content');

        $('.btn-search').on('click', function(){
            var param = '?datefrom='+ $('#datefrom').val() +'&dateto='+ $('#dateto').val();
            loadDocument(param);
        });

        $("#tbl-budget-list").DataTable();

        loadDocument('');

        function loadDocument(_params){
            var params = {
                    "strdate"   : $('#datefrom').val(),
                    "enddate"   : $('#dateto').val(),
                    "_token" : _token
                }

            $("#tbl-budget-list").DataTable({
                serverSide: true,
                ajax: {
                    url: base_url+'/approve/opnam/approvelist'+_params,
                    data: function (data) {
                        data.params = {
                            sac: "sac"
                        }
                    },
                    data: params,
                    // type: 'POST'
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
                    {data: "piduser"},
                    {data: "pidnote", className: 'uid'},
                    {"defaultContent":
                        `
                        <button class='btn btn-primary btn-sm button-detail'> <i class='fa fa-search'></i> View Detail</button>
                        `,
                        "className": "text-center",
                    }
                ]
            });

            $('#tbl-budget-list tbody').on( 'click', '.button-detail', function () {
                var table = $('#tbl-budget-list').DataTable();
                selected_data = [];
                selected_data = table.row($(this).closest('tr')).data();
                window.location = "/approve/opnam/details/"+selected_data.id;
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
