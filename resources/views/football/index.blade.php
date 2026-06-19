<h1>⚽ Fotball-VM 2026</h1>

<p>Antall kamper: {{ count($data['events']) }}</p>

<table border="1" cellpadding="5">
    <tr>
        <th>Dato</th>
        <th>Gruppe</th>
        <th>Status</th>
    </tr>

    @foreach($data['events'] as $event)
        <tr>
            <td>{{ $event['startDate'] ?? '' }}</td>
            <td>{{ $event['tournament']['groupName'] ?? '' }}</td>
            <td>{{ $event['status']['type'] ?? '' }}</td>
        </tr>
    @endforeach

</table>