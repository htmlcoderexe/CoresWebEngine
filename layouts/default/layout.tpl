<!doctype html>
<html>

    <head>
        <title>{%title|Test%}</title>
    <!--
    <link href='https://fonts.googleapis.com/css?family=Exo:400,700,400italic|Exo+2:400,700italic,700,400italic|Orbitron:500|Play:400,700' rel='stylesheet' type='text/css'>
    -->
        <link rel="stylesheet" href="{#baseuri|#}/css/main.css" />
        <link rel="stylesheet" href="{#baseuri|#}/css/calender/main.css" />

        <script type="text/javascript" src="/js/main.js"></script>

    <!--{.{websitejs}}-->
    </head>

    <body>
        <div id="wrapper">
        {%menu|%}
            <div id="rightcolumn">
                {%sidebar|%}
            </div>
        <!--end #rightcolumn -->
            <div id="leftcolumn">
            {%content|{#lipsum|500#}%}
            </div><!--end #leftcolumn-->
            {{system/clear}}
        </div><!--end #wrapper-->
    </body>
<!-- generated in ||||generatedtime|||| seconds -->
</html>