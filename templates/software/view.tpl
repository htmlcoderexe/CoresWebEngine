<h2>{%title%}</h2>
{#ifeq|{%publisher_name|%}|||<h3>By <a href="/software/publisher/{%publisher_id|0%}">{%publisher_name|%}</a></h3>#}
<p>{%description%}</p>
{%album|%}
{#foreach|{%releases%}|
    <div class="software_release">
        <h3><a href="/software/view/{:software_id:}/{:id:}">{:version:}</a></h3>
        <p>{:description:}</p>
    </div>
#}