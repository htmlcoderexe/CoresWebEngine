<table width="100%" class="sortable">
    <thead>
        <tr>
            <th data-sort-mode="num">Index</th>
            <th>Link text</th>
            <th>Link target</th>
        </tr>
    </thead>
    <tbody>
        {#foreach|{%menu%}|
        <tr>
            <td>{:id:}</td>
            <td><input data-endpoint="/cpanel/menu/update/{:id:}" name="text" value="{:text:}" /><span></span></td>
            <td><input data-endpoint="/cpanel/menu/update/{:id:}" name="link" value="{:link:}" /><span></span></td>
            <td><form action="/cpanel/menu/delete/" method="POST"><input name="id" value="{:id:}" type="hidden" /><button>&#x274C;</button></form></td>
        </tr>
#}
    </tbody>
    <tbody>
        <tr>
            <td colspan="3">
                <form action="/cpanel/menu/create" method="POST">
                    <input name="text" placeholder="link text" /><input name="link" placeholder="link target"/><button>Create</button>
                </form>
            </td>
        </tr>
    </tbody>
</table>
