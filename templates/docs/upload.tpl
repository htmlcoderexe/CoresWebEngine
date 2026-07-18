<form action="/docs/new" method="post" enctype="multipart/form-data">
    <label for="title">Document title: </label><br />
    <input id="title" name="title" /><br />
    <label for="description">Description:</label> <br />
    <textarea id="description" name="description"></textarea>
    <br />Document sensitivity:<br />
    <label for="sensitivity_public">Public<input type="radio" id="sensitivity_public" name="sensitivity" value="0"checked="checked" /></label>
    <label for="sensitivity_group">Internal<input type="radio" id="sensitivity_group" name="sensitivity" value="1" /></label>
    <label for="sensitivity_private">Private<input type="radio" id="sensitivity_private" name="sensitivity" value="2" /></label>
    <label for="dontshare">Do not share<input type="checkbox" id="dontshare" name="noshare" value="true" /></label>
    <br />Document type:<br />
    <select name="doctype">
        <option value=0>Unspecified</option>
        <option value=1>Book</option>
        <option value=2>User manual</option>
        <option value=3>Whitepaper</option>
        <option value=4>Event-specific</option>
        <option value=5>Adminstrative</option>
        <option value=6>Receipt</option>
        <option value=7>Certificate</option>
        <option value=8>Reference</option>
    </select>
    
    
    <input name="up" type="hidden" value="yes" /><br />
    <input name="fileup" type="file" />
    <button type="submit">Create</button>
</form>