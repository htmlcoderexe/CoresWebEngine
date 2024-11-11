<form action="/docs/new" method="post" enctype="multipart/form-data">
    <label for="title">Document title: </label><br />
    <input id="title" name="title" />
    <label for="description">Description:</label> <br />
    <textarea id="description" name="description"></textarea>
    Document sensitivity:<br />
    <label for="sensitivity_public">Public</label>
    <label for="sensitivity_group">Internal</label>
    <label for="sensitivity_private">Private</label>
    <label for="dontshare">Do not share</label>
    <input type="radio" id="sensitivity_public" name="sensitivity" value="0"checked="checked" />
    <input type="radio" id="sensitivity_group" name="sensitivity" value="1" />
    <input type="radio" id="sensitivity_private" name="sensitivity" value="2" />
    <input type="checkbox" id="dontshare" name="noshare" value="true" />
    <input name="up" type="hidden" value="yes" />
    <input name="fileup" type="file" />
    <button type="submit">Create</button>
</form>