{#ifset|today|<h3>Today</h3>{#foreach|{%today%}|<h4>{:title:}</h4>
    <span>{:date:}</span><br />
    <p>{:description:}#}|#}<h3>Upcoming</h3>
    {#foreach|{%upcoming%}|<h4>{:title:}</h4>
    <span>{:date:}</span><br />
    <p>{:description:}#}
