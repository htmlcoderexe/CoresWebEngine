<h2>Current ingest tasks:</h2>
<h3><a href="/pixdb/ingest/create">Create new...</a></h3>
<table>
    <thead>
        <tr>
            <th>Folder</th>
            <th>Active</th>
            <th colspan=2>Actions</th>
        </tr>
    </thead>
    <tbody>
{#foreach|{%ingests%}|      <tr>      
            <td>{:folder:}</td>
            <td>{#ifeq|{:active:}|1|Yes|No#}</td>
            <td><a href="/pixdb/ingest/view/{:id:}">View</a></td>
            <td><a href="/pixdb/ingest/manage/{:id:}">Manage</a></td>
        </tr>#}
    </tbody>
</table>