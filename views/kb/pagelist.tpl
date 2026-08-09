{#ifset|tags|{{system/showtags|tags={%tags%}|boxid=tag_box_search}}
#}<ul>
{#foreach|{%pages%}|<li><a href="/kb/view/{:id:}">{:title:}</a></li>#}
</ul>

