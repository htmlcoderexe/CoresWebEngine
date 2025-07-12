<a href="/pixdb/">Back</a>
<h3>{%w%} x {%h%} </h2>
<img class="singleimage" src="/files/stream/{%blobid%}/{%blobid%}.{%ext%}" />
<div class="tags_container">
    {#foreach|{%tags%}|<a href="/pixdb/tag/{:*:}">{:*:}</a> #}
</div>
<div class="suggestable_input_container">
    <input data-suggestionsource="/main/tag/suggest/" data-evaobject="{%id%}" oninput="doSuggest();" onblur="dismissSuggestions();" id="tag_input" name="tag_input" size=20 /><button type="button" onclick="addTag('tag_input');">Add tag</button>
</div>
