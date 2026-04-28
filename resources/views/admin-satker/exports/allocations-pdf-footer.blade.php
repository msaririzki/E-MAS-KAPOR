        </tbody>
    </table>

    @php
        $jabatan   = strtoupper($signatorySettings['signatory_title'] ?? 'KEPALA..........................');
        $userName  = strtoupper($signatorySettings['signatory_name'] ?? '..........................................');
        $userNrpNip = $signatorySettings['signatory_nrp'] ?? '.............................';
        $location  = $signatorySettings['location'] ?? 'Mataram';
    @endphp

    <div style="margin-top:24px; float:right; width:260px; text-align:center; font-family:Arial,Helvetica,sans-serif; font-size:10px; line-height:1.5;">
        <div style="margin-bottom:8px;">{{ $location }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
        <div style="margin-bottom:52px;">{{ $jabatan }}</div>
        <div style="font-weight:bold; text-decoration:underline;">{{ $userName }}</div>
        <div>NRP/NIP. {{ $userNrpNip }}</div>
    </div>

</div>
</body>
</html>
