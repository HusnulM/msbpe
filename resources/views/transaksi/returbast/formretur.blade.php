<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Document Printout</title>
	<style>
        .customers {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
            font-size:12px;
            font-weight:bold;
            margin-bottom:5px;
        }

        .customers td, .customers th {
            /* border: 1px solid #000; */
            /* padding: 5px; */
        }

        /* .customers tr:nth-child(even){background-color: #f2f2f2;}

        .customers tr:hover {background-color: #ddd;} */

        .customers th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            color: black;
        }

        #items {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
            font-size:12px;
        }

        #items td, #items th {
            border: 1px solid #000;
            padding: 3px;
        }

        /* #items tr:nth-child(even){background-color: #f2f2f2;} */

        /* #items tr:hover {background-color: #ddd;} */

        #items th {
            /* padding-top: 12px;
            padding-bottom: 12px; */
            text-align: left;
            background-color: #B4B1B1;
            color: black;
        }
    </style>
</head>
<body>
    <table cellspacing="0" cellpadding="0">
        <tr>
            <td style="text-align:center; width:130px;" rowspan="2">
                @if(checkIsLocalhost())
                <img src="{{ public_path('/assets/img/logo_mbp.png') }}" class="img-thumbnail" alt="Logo" style="width:100px; height:100px;">
                @else
                {{-- <img src="{{ public_path('/assets/img/logo.png') }}" class="img-thumbnail" alt="Logo" style="width:100px; height:100px;"> --}}
                <img src="{{ asset(getCompanyLogo()) }}" class="img-thumbnail" alt="E-sign" style="width:100px; height:100px;">
                @endif
            </td>
            <td style="text-align:center; width:500px;">
                <h2 style="text-align:center; font-family: Arial, Helvetica, sans-serif;" class="mb-0">RETUR BAST</h2>
            </td>
        </tr>
    </table>

    <table border="0" cellspacing="0" cellpadding="0" class="customers" style="margin-bottom: 20px !important;">
        <tr>
            <td style="width:100px;">Nota Retur</td>
            <td style="width:20px;">:</td>
            <td>{{ $header->nota_retur }}</td>
            <td style="width:120px;">Tanggal Rtur</td>
            <td style="width:10px;">:</td>
            <td style="width:150px;">{{ \Carbon\Carbon::parse($header->tgl_retur)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td>No. BAST</td>
            <td>:</td>
            <td>{{ $header->no_bast }}</td>
            <td>Warehouse</td>
            <td>:</td>
            <td>{{ $header->whsname }}</td>
        </tr>
        <tr>
            <td>Di Terima Oleh</td>
            <td>:</td>
            <td>{{ getUserNameByID($header->createdby) }}</td>
            <td>Di Serahkan Oleh</td>
            <td>:</td>
            <td>
                {{ $header->diserahkan_oleh }}
            </td>
        </tr>
    </table>
    <!-- <br> -->
    <table id="items">
        <thead>
            <th>No</th>
            <th style="width:120px;">Part Number</th>
            <th style="width:300px;">Description</th>
            <th style="text-align:right;">Quantity</th>
            <th style="text-align:center;">Unit</th>
            <th style="text-align:center;">Keterangan</th>
        </thead>
        <tbody>
            @foreach($items as $key => $row)
            <tr>
                <td>{{ $key+1 }}</td>
                <td>{{ $row->material }}</td>
                <td>{{ $row->matdesc }}</td>
                <td style="text-align:right;">
                    @if(strpos($row->quantity, '.000') !== false)
                    {{ number_format($row->quantity, 0, ',', '.') }}
                    @else
                    {{ number_format($row->quantity, 3, ',', '.') }}
                    @endif
                </td>
                <td style="text-align:center;">{{ $row->unit }}</td>
                <td>{{ $row->item_remark }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <br>
    Remark : <br>
    {{ $header->remark }}
    <br>
    <br>
    <br>
    <table style="width: 100%">
        <tr>
            <td style="text-align: center;">Dibuat Oleh,</td>
            <td style="width:350px;"></td>
            <td style="text-align: center;">Diserahkan Oleh,</td>
        </tr>
        <tr>
            <td style="text-align: center;">
                <img src="{{ getUserEsignByID($header->createdby) }}" class="img-thumbnail" alt="E-sign" style="width:100px; height:100px;">
            </td>
            <td style="width:30px;"></td>
            <td style="text-align: center;">
                <img src="{{ $header->s_signfile }}" class="img-thumbnail" alt="E-sign" style="width:100px; height:100px;">
            </td>
        </tr>
        <tr>
            <td style="text-align: center;"> <u> {{ getUserNameByID($header->createdby) }} </u></td>
            <td style="width:30px;"></td>
            <td style="text-align: center;"><u>{{ $header->diserahkan_oleh }}</u></td>
        </tr>
    </table>

</body>
</html>
