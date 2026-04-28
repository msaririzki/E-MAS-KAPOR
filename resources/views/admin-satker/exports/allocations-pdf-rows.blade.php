@foreach($rows as $i => $row)
<tr>
    <td class="center">{{ $startIndex + $i + 1 }}</td>
    <td>
        <strong>{{ $row['full_name'] }}</strong><br>
        {{ $row['nrp'] ?: '-' }}
    </td>
    <td>{{ $row['rank'] ?? '-' }}</td>
    <td>
        {{ $row['jabatan'] ?? '-' }}
        @if($row['bagian'] && trim((string)$row['bagian']) !== '-')
            <br><span style="color:#555;">({{ $row['bagian'] }})</span>
        @endif
    </td>
    <td class="center">
        @php $g = strtolower($row['gender']); @endphp
        {{ in_array($g, ['l','laki-laki','pria']) ? 'L' : (in_array($g, ['p','perempuan','wanita']) ? 'P' : ($row['gender'] ?: '-')) }}
    </td>
    <td>
        @foreach($row['items'] as $itemIndex => $item)
            <div class="item-row">
                <span class="item-name">{{ $item }}</span>
                @if(!empty($row['categories'][$itemIndex]))
                    <br><span class="item-cat">{{ $row['categories'][$itemIndex] }}</span>
                @endif
            </div>
        @endforeach
    </td>
    <td class="center">
        @foreach($row['sizes'] as $size)
            <div class="item-size" style="margin-bottom: 3px;">{{ $size }}</div>
        @endforeach
    </td>
    <td class="center" style="font-weight:bold;">{{ $row['item_count'] }}</td>
</tr>
@endforeach
