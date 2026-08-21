<label for="rtype">recur type</label><select id="rtype" name="rtype" onchange="ShowHide();">
    <option value="day"{#ifeq|{%calendar.recurring.type|%}|day| selected |#}>Every X days</option>
    <option value="week"{#ifeq|{%calendar.recurring.type|week%}|week| selected |#}>Weekly</option>
    <option value="month"{#ifeq|{%calendar.recurring.type|%}|month| selected |#}>Monthly</option>
</select>
    <input id="rdata" name="rdata" type="hidden" value="{%calendar.recurring.data|*****..%}"/>
    <input id="rdata_week" type="hidden" value="*****.."/>
    <input id="rdata_day" type="hidden" value="1"/>
    <input id="rdata_month" type="hidden" value="1"/>
    <div id="daily_options" style="display:none">
        Every <input onchange ="DoIntervalValue();" id="rdata_interval" type="number" min="1" /> days
    </div>
    <div id="weekly_options">
        On these days:<br />
        <label for="day0">M</label><input type="checkbox" data-day="0" id="day0" />
        <label for="day1">T</label><input type="checkbox" data-day="1" id="day1" />
        <label for="day2">W</label><input type="checkbox" data-day="2" id="day2" />
        <label for="day3">T</label><input type="checkbox" data-day="3" id="day3" />
        <label for="day4">F</label><input type="checkbox" data-day="4" id="day4" />
        <label for="day5">S</label><input type="checkbox" data-day="5" id="day5" />
        <label for="day6">S</label><input type="checkbox" data-day="6" id="day6" />
    </div>
    <div id="monthly_options" style="display:none">
        On the <input onchange ="DoMonthValue();" id="rdata_date" type="number" min="1" max="31" /><span id="numsuffix">st</span> of every month
    </div>
    
<script>

let boxes = document.querySelectorAll("input[data-day]");
    boxes.forEach((b)=>{
        b.addEventListener("change",HarvestCheckboxes);
        });

function ShowHide()
{
    let rtypeselector = document.getElementById("rtype");
    let rweek = document.getElementById("weekly_options");
    let rday = document.getElementById("daily_options");
    let rmonth = document.getElementById("monthly_options");
    switch(rtypeselector.value)
    {
        case "day":
        {
            rday.style.display="block";
            rweek.style.display="none";
            rmonth.style.display="none";
            break;
        }
        case "week":
        {
            rday.style.display="none";
            rweek.style.display="block";
            rmonth.style.display="none";
            break;
        }
        case "month":
        {
            rday.style.display="none";
            rweek.style.display="none";
            rmonth.style.display="block";
            break;
        }
    }
    CommitValue();
}


function LoadValue()
{
    DispatchValue();
    let rtypeselector = document.getElementById("rtype");
    let rweek = document.getElementById("rdata_week");
    let rday = document.getElementById("rdata_day");
    let rmonth = document.getElementById("rdata_month");
    let rdata = document.getElementById("rdata");
    let boxes = document.querySelectorAll("input[data-day]");
    boxes.forEach((b,i)=>{
        if(rweek.value[i]=="*")
        {
            b.checked=true;
        }
        else
        {
            b.checked=false;
        }
        });
    
    let rday_input = document.getElementById("rdata_interval");
    rday_input.value = rday.value;
    let rmonth_input = document.getElementById("rdata_date");
    rmonth_input.value = rmonth.value;
    UpdateSuffix();
    ShowHide();
}

function CommitValue()
{
    let rtypeselector = document.getElementById("rtype");
    let rweek = document.getElementById("rdata_week");
    let rday = document.getElementById("rdata_day");
    let rmonth = document.getElementById("rdata_month");
    let rdata = document.getElementById("rdata");
    switch(rtypeselector.value)
    {
        case "day":
        {
            rdata.value = rday.value;
            break;
        }
        case "week":
        {
            rdata.value = rweek.value;
            break;
        }
        case "month":
        {
            rdata.value = rmonth.value;
            break;
        }
    }
}
function DispatchValue()
{
    let rtypeselector = document.getElementById("rtype");
    let rweek = document.getElementById("rdata_week");
    let rday = document.getElementById("rdata_day");
    let rmonth = document.getElementById("rdata_month");
    let rdata = document.getElementById("rdata");
    switch(rtypeselector.value)
    {
        case "day":
        {
            rday.value = rdata.value;
            break;
        }
        case "week":
        {
            rweek.value = rdata.value;
            break;
        }
        case "month":
        {
            rmonth.value = rdata.value;
            break;
        }
    }
}

function DoMonthValue()
{
    
    let rmonth_input = document.getElementById("rdata_date");
    let rmonth = document.getElementById("rdata_month");
    rmonth.value = rmonth_input.value;
    let rdata = document.getElementById("rdata");
    rdata.value = rmonth.value;
    UpdateSuffix();
    
}

function GetSuffix(a)
{
    if(a==11 || a==12 || a==13)
    {
        return "th";
    }
    if(a%10==1)
        
    {
        return "st";
    }
    if(a%10==2)
        
    {
        return "nd";
    }
    if(a%10==3)
        
    {
        return "rd";
    }
    return "th";
}

function UpdateSuffix()
{
    let rmonth_input = document.getElementById("rdata_date");
    let rmonth_suffix = document.getElementById("numsuffix");
    let suf = GetSuffix(rmonth_input.value);
    rmonth_suffix.innerText = suf;
    
}

function DoIntervalValue()
{
    
    let rday_input = document.getElementById("rdata_interval");
    let rday = document.getElementById("rdata_day");
    rday.value = rday_input.value;
    let rdata = document.getElementById("rdata");
    rdata.value = rday.value;
}

function HarvestCheckboxes()
{
    let rweek = document.getElementById("rdata_week");
    let rdata = document.getElementById("rdata");

    let weekarray=new Array(7);
    let boxes = document.querySelectorAll("input[data-day]");
    boxes.forEach((b)=>{
        if(b.checked)
        {
            weekarray[b.dataset['day']]="*";
        }
        else
        {
            weekarray[b.dataset['day']]=".";
        }
        });
    let weekstring = weekarray.join("");
    console.log(weekstring);
    rweek.value=weekstring;
    CommitValue();
}

function UpdateRdata()
{
    let rweek = document.getElementById("rdata_week");
    let rday = document.getElementById("rdata_day");
    let rmonth = document.getElementById("rdata_month");
    let rdata = document.getElementById("rdata");
}

LoadValue();
    
</script>