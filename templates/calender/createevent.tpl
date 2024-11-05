<h3>{%error|%}
<form action ="/calender/create" method ="POST">
    <label for="title">Event title</label><br />
    <input name="title" id ="title" type ="text" />
    <br />
    <label for="date">Event date YYYY-MM-DD</label><br />
    <input name="date" id ="date" type ="date" value="{%date|%}" />
    <br />
    <label for="time">Event time HH:MM</label><br />
    <input name="time" id ="timee" type ="time" />
    <br />
    <label for="description">Event description</label><br />
    <textarea name="description" id ="description"></textarea>
    <input name ="create" type="hidden" value ="true" />
</form>

