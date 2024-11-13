<!doctype html>
<html>
    <head>
        <title>Full Screen Frame Page</title>
        <style>
            body,html,iframe
            {
                height: 100%; 
                width:100%;   
                min-height:100%;
            }
        </style>
    </head>
    <body>
        <iframe id="contentframe" src="/" onload="linkEvent();"  frameBorder="0"></iframe>
        <script type="text/javascript">
        window.SetFullSCreen=false;
        window.contentFrame=document.getElementById("contentframe");
        function doFullScreen()
        {
            if(!window.SetFullScreen)
            {
                document.body.requestFullscreen();
                window.SetFullScreen = true;
                window.contentFrame.contentDocument.removeEventListener("click",doFullScreen);
            }
        }
        function linkEvent()
        {
            window.contentFrame.contentDocument.addEventListener("click",doFullScreen);
        }
        </script>
    </body>
</html>