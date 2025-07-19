<script type="text/javascript">
    function CheckForCommands()
    {
        var url = "/main/chip/getcommands";
         
        let ajax = new XMLHttpRequest();
        ajax.onreadystatechange=function()
        {
            if(ajax.readyState===4)
            {
                if(ajax.status === 200)
                {
                    try
                    {
                        var result = JSON.parse(ajax.responseText);
                        result.forEach((item)=>{
                            RunCommand(item['command'],item['params']);
                        });
                    }
                    catch(error)
                    {

                    }

                }
                else
                {

                }
            }
        };
        ajax.open("GET",url,true);
        ajax.send(null);
    }
    
    function RunCommand(command, params)
    {
        switch(command)
        {
            case "playsong":
            {
                PlaySong(params);
                break;
            }
        }
    }
    
    function PlaySong(params)
    {
        var player = document.getElementById('chipsound');
        if(!player)
            return;
        var songdata = params.split(",");
        var songID = songdata[0];
        var songPos = 0;
        if(songdata.length > 1)
        {
            songPos=songdata[1];
        }
        player.src = "/files/stream/" + songID + "/" + songID + ".mp3";
        player.fastSeek(songPos);
        player.play();
    }
    setInterval(CheckForCommands, 1000);
</script>
    <audio id="chipsound">
    </audio>