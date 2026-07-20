<a href="/docs/new">Upload new document</a><br />
{#ifeq|{%shownav|false%}|true|<div id="docnavtabs">
{#ifeq|{%current|other%}|books|<span class="navtab"> Books </span>|<a class="navtab" href="/docs/list/books"> Books </a>#}
{#ifeq|{%current|other%}|whitepapers|<span class="navtab"> Whitepapers </span>|<a class="navtab" href="/docs/list/whitepapers"> Whitepapers </a>#}
{#ifeq|{%current|other%}|manuals|<span class="navtab"> Manuals </span>|<a class="navtab" href="/docs/list/manuals"> Manuals </a>#}
{#ifeq|{%current|other%}|references|<span class="navtab"> References </span>|<a class="navtab" href="/docs/list/references"> References </a>#}
{#ifeq|{%current|other%}|other|<span class="navtab"> Other </span>|<a class="navtab" href="/docs/list/other"> Other </a>#}</div>
|#}
<table class="sortable" style="width:100%">
    <thead>
    <tr>
        <th>Name</th>
        <th>Short description</th>
    </tr>
    </thead><tbody>
    {#foreach|{%docs%}|<tr>
        <td><a href="/docs/view/{:id:}">{:title:}</a></td>
        <td>{#ellipsis|{:description:}|50#}</td>
    </tr>#}
    </tbody>
</table>
