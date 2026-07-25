<form action="/software/save" enctype="multipart/form-data" method="POST">
    <input type="hidden" name="id" value="{%id|-1%}" />
    <table>
        <tr>
            <td>Name:</td>
            <td><input name="title" />{%title|%}</td>
        </tr>
        <tr>
            <td>Description:</td>
            <td><textarea name="description">{%description|%}</textarea></td>
        </tr>
        <tr>
            <td>Category:</td>
            <td><select name="category">
                    <option>None</option>
                    <option>Editor</option>
                    <option>Viewer</option>
                    <option>Converter</option>
                    <option>Client</option>
                    <option>Server</option>
                    <option>Game</option>
                    <option>Utility</option>
                    <option>Development</option>
                    <option>System</option>
                    <option>OS</option>
                    <option>Data management</option>
                    <option>Emulation</option>
                    <option>Virtualisation</option>
            </select></td>
        </tr>
        <tr>
            <td>Package type:</td>
            <td><select name="type">
                    <option>Installer</option>
                    <option>ISO</option>
                    <option>Offline</option>
                    <option>Demo</option>
                    <option>Collection</option>
                    <option>Repository</option>
                    <option>Self-contained</option>
            </select></td>
        </tr>{#ifset|publishers|
        <tr>
            <td>Publisher:</td>
            <td><select name="publisher">{#foreach|{%publishers%}|
                <option{#ifeq|{%publisher%}|{:id:}|selected#} value="{:id:}">{:name:}</option>#}
            </select></td>
        </tr>#}
    </table>
</form>
