<script>
    function submitfrm(e)
    {
        let frm = document.getElementById("searchform");
        let tags = document.querySelectorAll('[name="searchtags[]"]');
        let taglist = [];
        tags.forEach((input)=>{
            taglist.push(input.value);
            input.parentNode.removeChild(input);
            
        });
        let totalURL=taglist.join("/");
        console.log(frm.action);
        frm.action="/pixdb/tag/"+totalURL;
        console.log(frm.action);
        frm.submit();
    }
</script>
<form id="searchform" method="GET" action="/pixdb/tag">
{{tagpicker|inputname=searchtags}}
<input name="search" /><button type="button" onclick="submitfrm();return false;">Search</button>
</form>
