<script src="/ckeditor/ckeditor.js"></script>
<form action="/kb/save" method="POST">
<textarea name="text" id ="text" cols="56" rows="20">{%pagetext|%}</textarea>
<script>
CKEDITOR.replace("text");
</script>
<input name="pageid" type="hidden" value="{%pageid|-1%}" />
<button type="submit">Save page</button>
</form> 