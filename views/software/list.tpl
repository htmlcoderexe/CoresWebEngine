{#ifeq|{#ifpermission|super#}|true|<a href="/software/new">Add</a><br />#}
{%extra_text|%}
<table class="sortable" style="width:100%">
    <thead>
    <tr>
        <th>Name</th>
        <th>Short description</th>
    </tr>
    </thead><tbody>
    {#foreach|{%items%}|<tr>
        <td><a href="/software/view/{:id:}">{:title:}</a></td>
        <td>{#ellipsis|{:description:}|50#}</td>
    </tr>#}
    </tbody>
</table>

