<h3><a href="/software/view/{%software_id%}">&lt; {%software_name|ERROR%}</a></h3>
<h2>Version {%version%} of {%software_name|ERROR%}</h2>
{#ifeq|{%publisher_name|%}|||<h3>By <a href="/software/publisher/{%publisher_id|0%}">{%publisher_name|%}</a></h3>#}
<p>{%description%}</p>
{%album|%}
{#foreach|{%files%}|
    <div class="software_release_file"> 
    <p>{:comment:}</p>
    <div class="filelink"><a href="/files/stream/{:blobid:}/{#urlencode|{:fullname:}#}"><img onerror="this.src='/images-site/icons/file.unknown.svg'" src="/images-site/icons/file.{:filext:}.svg" width="64" height="64    " /><span class="filename">{:fullname:}, {#hread|{:size:}#}b</span></a></div>
    </div>
#}
