<h3>List of albums</h3>
<table class="sortable" style="width:100%">
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Images</th>
        </tr>
    </thead>
    <tbody>
{#foreach|{%albums%}|      <tr>      
            <td><a href="/pixdb/viewalbum/{:id:}">{:title:}</a></td>
            <td>{:description:}</td>
            <td>{:cached_count:}</td>
        </tr>#}
    </tbody>
