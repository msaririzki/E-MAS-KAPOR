<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Penerima Barang - {{ $satker->name ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #000; background: #fff; }
        .print-container { width: 100%; padding: 10px 0; }

        .kop-surat { text-align: center; margin-bottom: 16px; }
        .kop-logo { height: 48px; margin-bottom: 4px; }
        .kop-text { font-size: 12px; font-weight: bold; line-height: 1.3; }
        .kop-line { border-bottom: 2.5px solid #000; margin-top: 6px; width: 100%; }

        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
            line-height: 1.4;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 11px;
            margin-bottom: 14px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        .data-table th {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            background: #e5e7eb;
            text-transform: uppercase;
        }
        .data-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 9.5px;
            vertical-align: top;
        }
        .data-table td.center { text-align: center; vertical-align: middle; }
        .item-row { margin-bottom: 3px; padding-bottom: 3px; border-bottom: 1px dashed #ccc; }
        .item-row:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .item-name { font-weight: bold; }
        .item-cat  { font-size: 8.5px; color: #444; }
        .item-size { font-weight: bold; }
    </style>
</head>
<body>
<div class="print-container">

    {{-- Kop Surat --}}
    <div class="kop-surat">
        <?php
            $path = public_path('kop suratt.png');
            $type = pathinfo($path, PATHINFO_EXTENSION);
            if (file_exists($path)) {
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            } else {
                $base64 = '';
            }
        ?>
        @if($base64)
        <img src="{{ $base64 }}" alt="Logo Polri" class="kop-logo">
        @endif
        <div class="kop-text">
            KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
            DAERAH NUSA TENGGARA BARAT<br>
            {{ strtoupper($satker->name ?? '') }}
        </div>
        <div class="kop-line"></div>
    </div>

    <div class="doc-title">DAFTAR ALOKASI PENERIMA BARANG KAPORLAP</div>
    <div class="doc-subtitle">TAHUN ANGGARAN {{ $stats['fiscal_year'] }} — {{ strtoupper($satker->name ?? '') }}</div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%">NO</th>
                <th style="width:19%">NAMA / NRP</th>
                <th style="width:9%">PANGKAT</th>
                <th style="width:16%">JABATAN / BAGIAN</th>
                <th style="width:7%">J.KELAMIN</th>
                <th style="width:36%">BARANG DITERIMA</th>
                <th style="width:9%">UKURAN</th>
                <th style="width:5%">JML</th>
            </tr>
        </thead>
        <tbody>
