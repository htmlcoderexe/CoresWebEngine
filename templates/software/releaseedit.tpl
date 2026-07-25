<h2>Adding a new release for {%software_name|ERROR%}</h2>
<form action="/software/saverelease" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{%id|-1%}" />
    <input type="hidden" name="software_id" value="{%software_id|-1%}" />
    <table>
        <tr>
            <td>Version name:</td>
            <td><input name="version" />{%title|%}</td>
        </tr>
        <tr>
            <td>Description:</td>
            <td><textarea name="description">{%description|%}</textarea></td>
        </tr>
        <tr>
            <td>Release type:</td>
            <td><select name="type">
                    <option>Installer</option>
                    <option>Source</option>
                    <option>Portable</option>
                    <option>Update</option>
                    <option>Patch</option>
            </select></td>
        </tr>
    </table>
    <script type="text/javascript">
        window.uploadCounter=1;
        function RemoveUploader(uploader_id)
        {
            document.getElementById("uploader_"+uploader_id).remove();  
        }
        function AddUploader()
        {
            uploader=document.createElement("span");
            uploader.id="uploader_"+window.uploadCounter;
            upinput=document.createElement("input");
            upinput.name="release_files[]";
            upinput.type="file";
            uploader.appendChild(upinput);
            upcomment=document.createElement("input");
            upcomment.name="file_comments[]";
            uploader.appendChild(upcomment);
            rmbutton=document.createElement("button");
            rmbutton.type="button";
            i=window.uploadCounter;
            rmbutton.onclick=function() {
                RemoveUploader(i);
            };
            rmbutton.append("-");
            uploader.appendChild(rmbutton);
            uploader.appendChild(document.createElement("br"));
            document.getElementById("form_end").insertAdjacentElement("beforebegin",uploader);
            window.uploadCounter++;

        }
    </script>
    <span id="uploader_0"><input name="release_files[]" type="file"/><input name="file_comments[]" /><button type="button" onclick="RemoveUploader(0);return false;">-</button><br /></span>
    <button id="form_end" type="button" onclick="AddUploader();">Add file</button><br/>
    <button type="submit">Save</button>
</form>
