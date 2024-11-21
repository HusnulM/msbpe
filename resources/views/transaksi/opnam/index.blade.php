@extends('layouts/App')

@section('title', 'Stock Opnam')

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
<div class="row">
    <div class="col-lg-12 mt-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Upload Stock Opnam</h3>
                <div class="card-tools">
                    <!-- <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                        <i class="fas fa-minus"></i>
                    </button> -->
                    <a href="/excel/Template Stock Opnam.xlsx" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fa fa-download"></i> Download Template
                    </a>

                    <a href="{{ url('/logistic/stockopname/stockopnamelist') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-list"></i> List Stock Opnam
                    </a>
                </div>
            </div>
            <div class="card-body">


                <div class="row">
                    <div class="col-lg-12">
                        <form action="{{ url('/logistic/stockopname/save') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <label for="">Tanggal Upload</label>
                                            <input type="date" name="tglupload" class="form-control" required>
                                        </div>
                                        <div class="col-lg-12">
                                            <label for="currency">Warehouse</label>
                                            <select name="whscode" id="find-whscode" class="form-control" required></select>
                                        </div>
                                        <div class="col-lg-12">
                                            <label for="">Keterangan</label>
                                            <textarea name="remark" id="" cols="30" rows="3" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-10">
                                    <label for="browse-file">File</label>
                                    <input type="file" name="file" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm" style="margin-top:27px; width:100%;">
                                        <i class="fa fa-folder-open"></i> Upload Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-footer">

            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('additional-js')
<script src="{{ asset('/assets/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function(){
        let _token   = $('meta[name="csrf-token"]').attr('content');

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
                                id: item.id,
                                ...item
                            }
                        })
                    };
                },
                cache: true
            }
        });
    })
</script>
@endsection
