<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kontrak Export</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        
        .container {
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 10px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #333;
        }
        
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .summary-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .summary-title {
            background-color: #4472C4;
            color: white;
            padding: 8px;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .summary-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        
        .summary-table tr:first-child td {
            background-color: #e7e6e6;
            font-weight: bold;
        }
        
        .numeric {
            text-align: right;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KONTRAK EXPORT REPORT</h1>
            <p>Generated on {{ $generatedAt }}</p>
        </div>

        @if($includeTables)
        <table>
            <thead>
                <tr>
                    <th>Nomor DO/SI</th>
                    <th>Nomor Kontrak</th>
                    <th>Nama Pembeli</th>
                    <th>Tgl Kontrak</th>
                    <th>Volume</th>
                    <th>Harga</th>
                    <th>Nilai</th>
                    <th>Inc. PPN</th>
                    <th>Tgl Bayar</th>
                    <th>Unit</th>
                    <th>Mutu</th>
                    <th>Tgl DO/SI</th>
                    <th>Port</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>{{ $item['nomor_dosi'] ?? $item->nomor_dosi ?? '' }}</td>
                    <td>{{ $item['nomor_kontrak'] ?? $item->nomor_kontrak ?? '' }}</td>
                    <td>{{ $item['nama_pembeli'] ?? $item->nama_pembeli ?? '' }}</td>
                    <td>{{ $item['tgl_kontrak'] ?? $item->tgl_kontrak ?? '' }}</td>
                    <td class="numeric">{{ $item['volume'] ?? $item->volume ?? '' }}</td>
                    <td class="numeric">{{ $item['harga'] ?? $item->harga ?? '' }}</td>
                    <td class="numeric">{{ $item['nilai'] ?? $item->nilai ?? '' }}</td>
                    <td class="numeric">{{ $item['inc_ppn'] ?? $item->inc_ppn ?? '' }}</td>
                    <td>{{ $item['tgl_bayar'] ?? $item->tgl_bayar ?? '' }}</td>
                    <td>{{ $item['unit'] ?? $item->unit ?? '' }}</td>
                    <td>{{ $item['mutu'] ?? $item->mutu ?? '' }}</td>
                    <td>{{ $item['tgl_dosi'] ?? $item->tgl_dosi ?? '' }}</td>
                    <td>{{ $item['port'] ?? $item->port ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if($includeCharts)
        <div class="summary-section">
            <div class="summary-title">RINGKASAN DATA (SUMMARY)</div>
            
            <table class="summary-table">
                <tr>
                    <td>Total Records</td>
                    <td class="numeric"><strong>{{ count($data) }}</strong></td>
                </tr>
                <tr>
                    <td>Total Nilai</td>
                    <td class="numeric"><strong>Rp {{ number_format($summary['total_nilai'], 0, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td>Total Volume</td>
                    <td class="numeric"><strong>{{ number_format($summary['total_volume'], 2, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td>Average Harga per Record</td>
                    <td class="numeric"><strong>Rp {{ number_format($summary['avg_harga'], 0, ',', '.') }}</strong></td>
                </tr>
            </table>

            <div class="summary-title" style="margin-top: 20px;">BREAKDOWN PER MUTU</div>
            
            <table class="summary-table">
                <tr>
                    <th style="text-align: left; color: white; background-color: #4472C4;">Mutu</th>
                    <th style="text-align: right; color: white; background-color: #4472C4;">Jumlah</th>
                    <th style="text-align: right; color: white; background-color: #4472C4;">Total Nilai</th>
                    <th style="text-align: right; color: white; background-color: #4472C4;">Total Volume</th>
                </tr>
                @foreach($summary['by_mutu'] as $mutu => $stats)
                <tr>
                    <td>{{ $mutu }}</td>
                    <td class="numeric">{{ $stats['count'] }}</td>
                    <td class="numeric">Rp {{ number_format($stats['nilai'], 0, ',', '.') }}</td>
                    <td class="numeric">{{ number_format($stats['volume'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif

        <div class="footer">
            <p>This is an automatically generated report. For questions, please contact the system administrator.</p>
        </div>
    </div>
</body>
</html>
