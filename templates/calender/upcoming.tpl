    {#foreach|{%events%}|<h4>{:title:}</h4>
    <span><strong>{:day:}.{:month:}</strong> ⌚{#ifeq|{:duration:}|0|All day|{:hour:}:{:minute:} - {:done_hours:}:{:done_minutes:}#}</span><br />
    <p>{:description:}</p><hr />#}
