<style>
    form {
    display:inline;
    }
    </style>
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