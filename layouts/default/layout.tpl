<!doctype html>
<html>

    <head>
        <title>{%title|Test%}</title>
    <!--
    <link href='https://fonts.googleapis.com/css?family=Exo:400,700,400italic|Exo+2:400,700italic,700,400italic|Orbitron:500|Play:400,700' rel='stylesheet' type='text/css'>
    -->
        <link rel="stylesheet" href="/css/main.css" />
        <link rel="stylesheet" href="/css/calender/main.css" />
        <link rel="stylesheet" href="/css/sorTable.css" />
        <script type="text/javascript" src="/js/main.js"></script>
        <script type="text/javascript" src="/js/sorTable.js"></script>

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
        <script type="text/javascript">
        
        </script>
        
    </body>
<!-- generated in ||||generatedtime|||| seconds -->
</html>