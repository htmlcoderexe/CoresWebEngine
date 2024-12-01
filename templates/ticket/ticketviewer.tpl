<style>
    form {
    display:inline;
    }
    </style>
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
            upinput.name="update_attachment[]";
            upinput.type="file";
            uploader.appendChild(upinput);
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
<a href="/ticket/list/">Back to index</a> | <a href="/ticket/submit/">Submit a ticket</a>
<h2>{%number%}</h2>
<h4>Submitted by {#userinfo|username|{%submitter%}#}</h4>
<h4>Status: {%status%}</h4>
{#ifeq|{%statuscode%}|0|<form action="/ticket/modify/{%number%}" method="POST">
    <input name="newstate" value="1" type="hidden" />
    <button>Begin work</button>
</form>|#}
{#ifeq|{%statuscode%}|6||<form action="/ticket/modify/{%number%}" method="POST">
    <input name="newstate" value="6" type="hidden" />
    <button>Close</button>
</form>#}

<h3>{%title%}</h3>
<p>{%description%}</p>

{#ifeq|{%statuscode%}|6||<form action="/ticket/modify/{%number%}" method="POST"  enctype="multipart/form-data">
    <input type="hidden" name="newupdate" value="bepis" />
    <textarea class="" name="update_text"></textarea><br />
    <span id="uploader_0"><input name="update_attachment[]" type="file"/><button type="button" onclick="RemoveUploader(0);return false;">-</button><br /></span>
    <button id="form_end" type="button" onclick="AddUploader();">Add file</button><br/>
    <button>Update</button>
</form>#}