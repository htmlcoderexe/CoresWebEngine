<h3><a href="/pixdb/ingest/list">Go back</a></h3>
<h3>Modify or create an ingest task</h3>
<form action="/pixdb/ingest/create" method="POST">
    <label for="foldername">Folder:</label><input name="foldername" id="foldername" value="{%foldername|%}"/><br />
    <h3>Visibility</h3>
    <label for="visibility0">Private</label><input type="radio" name="visibility" id="visibility0" value="0" {#ifeq|{%visibility|0%}|0|checked |#}/>
    <label for="visibility1">Shared</label><input type="radio" name="visibility" id="visibility1" value="1" {#ifeq|{%visibility|0%}|1|checked |#}/>
    <label for="visibility2">Logged in</label><input type="radio" name="visibility" id="visibility2" value="2" {#ifeq|{%visibility|0%}|2|checked |#}/>
    <label for="visibility3">Public</label><input type="radio" name="visibility" id="visibility3" value="3" {#ifeq|{%visibility|0%}|3|checked |#}/>
    <h3>Active</h3>
    <label for="visibility0">Yes</label><input type="radio" name="active" id="active1" value="1" {#ifeq|{%active|0%}|1|checked |#}/>
    <label for="visibility1">No</label><input type="radio" name="active" id="active0" value="0" {#ifeq|{%active|0%}|0|checked |#}/>
    <input type="hidden" name="id" value="{%id|-1%}" /><br />
    <button type="submit">Save</button>
</form>
