<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Final Tahunan {{ $fiscalYear }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1, h2, h3 { margin: 0; }
        .header { margin-bottom: 20px; }
        .header h1 { font-size: 20px; margin-bottom: 4px; }
        .header p { margin: 0; color: #4B5563; }
        .stats { margin: 18px 0; }
        .stats td { padding: 8px 10px; border: 1px solid #D1D5DB; }
        .section { margin-top: 22px; }
        .section h2 { font-size: 14px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #D1D5DB; padding: 8px 10px; vertical-align: top; }
        th { background: #F3F4F6; text-align: left; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Arsip Final Tahunan {{ $fiscalYear }}</h1>
        <p>Dibuat pada {{ $generatedAt->format('d-m-Y H:i') }}</p>
    </div>

    <table class="stats">
        <tr>
            <td><strong>Total Personel</strong></td>
            <td>{{ $snapshot['total_personnel'] }}</td>
            <td><strong>Sudah Input Ukuran</strong></td>
            <td>{{ $snapshot['submitted_personnel'] }}</td>
        </tr>
    </table>

    <div class="section">
        <h2>Ringkasan Personel per Satker</h2>
        <table>
            <thead>
                <tr>
                    <th>Satker</th>
                    <th>Total</th>
                    <th>Sudah Input</th>
                    <th>Belum Input</th>
                </tr>
            </thead>
            <tbody>
                @foreach($snapshot['satker_summaries'] as $row)
                    <tr>
                        <td>{{ $row['satker_name'] }}</td>
                        <td>{{ $row['total_personnel'] }}</td>
                        <td>{{ $row['submitted_count'] }}</td>
                        <td>{{ $row['pending_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Ringkasan Paket Anggaran</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama Paket</th>
                    <th>Status</th>
                    <th>Total Item</th>
                    <th>Total Anggaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($snapshot['budget_packages'] as $package)
                    <tr>
                        <td>{{ $package['name'] }}</td>
                        <td>{{ $package['status'] }}</td>
                        <td>{{ $package['items_count'] }}</td>
                        <td>{{ $package['total_budget'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Tidak ada paket anggaran pada tahun ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
