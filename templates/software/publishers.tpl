<a href="/software/">Back to index</a><br />
<table class="sortable" style="width:100%">
    <thead>
    <tr>
        <th>Name</th>
        <th>Short description</th>
    </tr>
    </thead><tbody>
    {#foreach|{%publishers%}|<tr>
        <td><a href="/software/publisher/{:pid:}">{:name:}</a></td>
        <td>{#ellipsis|{:description:}|50#}</td>
        <td>{:swcount:}</td>
    </tr>#}
    </tbody>
</table>
