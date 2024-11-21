@extends('layouts/App')

@section('title', 'Retur BAST')

@section('additional-css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style type="text/css">
        .select2-container {
            display: block
        }

        .select2-container .select2-selection--single {
            height: 36px;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <form action="{{ url('logistic/returbast/save') }}" method="post">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Retur BAST</h3>
                        <div class="card-tools">
                            <button type="submit" class="btn btn-primary btn-sm btn-add-dept">
                                <i class="fas fa-save"></i> Simpan Return BAST
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-2 col-md-12">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12">
                                        <div class="form-group">
                                            <label for="no_bast">No BAST</label>
                                            <input type="text" name="no_bast" class="form-control" value="{{ $header->no_bast }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-12">
                                        <div class="form-group">
                                            <label for="retdate">Tanggal Retur</label>
                                            <input type="date" name="retdate" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-12">
                                        <div class="form-group">
                                            <label for="grdate">Warehouse</label>
                                            <select name="whscode" id="find-whscode" class="form-control" required></select>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 col-md-12">
                                        <div class="form-group">
                                            <label for="recipient">Di Proses Oleh</label>
                                            <input type="text" name="recipient" class="form-control" value="{{ Auth::user()->name }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-12">
                                        <div class="form-group">
                                            <label for="diserahkan">Di Serahkan Oleh</label>
                                            <select name="diserahkan" id="find-user" class="form-control" required></select>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-12">
                                        <div class="form-group">
                                            <label for="remark">Remark</label>
                                            <textarea name="remark" id="remark" cols="30" rows="4" class="form-control" placeholder="Remark..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-10 col-md-12">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <table class="table table-sm">
                                            <thead>
                                                <th>Kode / Nama Barang</th>
                                                <th>BAST Qty</th>
                                                <th>Retur Qty</th>
                                                <th>Unit</th>
                                                <th>Keterangan</th>

                                            </thead>
                                            <tbody id="tbl-pbj-body">
                                                @foreach ($items as $key => $val )
                                                    <tr>
                                                        <td>
                                                            {{ $val->material }} <br> {{ $val->matdesc }}
                                                            <input type="hidden" name="material[]" class="form-control" value="{{ $val->material }}">
                                                            <input type="hidden" name="matdesc[]" class="form-control" value="{{ $val->matdesc }}">
                                                            <input type="hidden" name="bastitem[]" class="form-control" value="{{ $val->id }}">
                                                            <input type="hidden" name="pbjnum[]" class="form-control" value="{{ $val->refdoc }}">
                                                            <input type="hidden" name="pbjitem[]" class="form-control" value="{{ $val->refdocitem }}">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="bastqty[]" class="form-control" value="{{ $val->quantity }}" readonly>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="returqty[]" class="form-control" value="">
                                                        </td>
                                                        <td>
                                                            {{ $val->unit }}
                                                            <input type="hidden" name="unit[]" class="form-control" value="{{ $val->unit }}">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" name="item_remark[]">
                                                        </td>
                                                        {{-- <td style="text-align:right;">
                                                            <button type="button" class="btn btn-sm btn-danger">Delete Item</button>
                                                        </td> --}}
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <!-- <tfoot>
                                                <tr>
                                                    <td colspan="7"></td>
                                                    <td style="text-align:right;">
                                                        <button type="button" class="btn btn-success btn-sm btn-add-pbj-item">
                                                            <i class="fa fa-plus"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tfoot> -->
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('additional-modal')

@endsection

@section('additional-js')
<script src="{{ asset('/assets/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function(){
        var count = 0;
        let selected_po_items = [];
        let _token   = $('meta[name="csrf-token"]').attr('content');

        $(document).on('select2:open', (event) => {
            const searchField = document.querySelector(
                `.select2-search__field`,
            );
            if (searchField) {
                searchField.focus();
            }
        });

        $('#find-user').select2({
            placeholder: 'Ketik Nama User',
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: base_url + '/master/listuser/findusers',
                dataType: 'json',
                delay: 250,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': _token
                },
                data: function (params) {
                    var query = {
                        search: params.term,
                        // custname: $('#find-customer').val()
                    }
                    return query;
                },
                processResults: function (data) {
                    // return {
                    //     results: response
                    // };
                    console.log(data)
                    return {
                        results: $.map(data.data, function (item) {
                            return {
                                text: item.name,
                                slug: item.name,
                                id: item.id,
                                ...item
                            }
                        })
                    };
                },
                cache: true
            }
        });

        $('#find-whscode').select2({
            placeholder: 'Ketik Nama Gudang',
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: base_url + '/master/warehouse/findwhs',
                dataType: 'json',
                delay: 250,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': _token
                },
                data: function (params) {
                    var query = {
                        search: params.term,
                        // custname: $('#find-customer').val()
                    }
                    return query;
                },
                processResults: function (data) {
                    // return {
                    //     results: response
                    // };
                    console.log(data)
                    return {
                        results: $.map(data.data, function (item) {
                            return {
                                text: item.whsname,
                                slug: item.whsname,
                                id: item.whscode,
                                ...item
                            }
                        })
                    };
                },
                cache: true
            }
        });


    });
</script>
@endsection
