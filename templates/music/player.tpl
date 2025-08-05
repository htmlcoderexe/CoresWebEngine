<script type="text/javascript">
    
    class CoresPlayer
    {
        id = "";
        playlist = [];
        current_track = 0;
        library = [];
        #current_time_indicator;
        #duration_indicator;
        #seekbar;
        #volume_backlight;
        #volume_thumb;
        #playpausebutton;
        #audio;
        #song_display;
        #title_animation_counter = 0;
        
        constructor(id)
        {
            // create the player elements
            // attach to the indicated div
            var root = document.getElementById(id);
            root.classList.add("coresplayer");
            // time display
            var timescreen = document.createElement("div");
            timescreen.classList.add("playertime");
            this.#current_time_indicator = document.createElement("span");
            this.#duration_indicator = document.createElement("span");
            this.#current_time_indicator.append("--:--");
            this.#duration_indicator.append("--:--");
            timescreen.appendChild(this.#current_time_indicator);
            timescreen.appendChild(document.createElement("br"));
            timescreen.appendChild(this.#duration_indicator);
            root.appendChild(timescreen);
            // seek bar
            var seek_container = document.createElement("div");
            seek_container.classList.add("coresplayer_scrubber_track");
            seek_container.addEventListener("mousemove",(e)=>{
                this.#processScrubber(e);
            });
            this.#seekbar = document.createElement("span");
            this.#seekbar.classList.add("coresplayer_scrubber_fill");
            this.#seekbar.innerHTML = "&nbsp;";
            seek_container.appendChild(this.#seekbar);
            root.append(seek_container);
            // song display
            this.#song_display = document.createElement("div");
            this.#song_display.classList.add("coresplayer_song_title");
            root.append(this.#song_display);
            // buttons
            
            
            // volume control
            
            
            
        }
    }
    
    playlist_offset = 0;
    playlist = [];
    
    title_offset = 0;
    
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
            case "volup":
            {
                VolumeUp(params);
                break;
            }
            case "voldown":
            {
                VolumeDown(params);
                break;
            }
            case "volset":
            {
                VolumeSet(params);
                break;
            }
            case "pause":
            {
                PauseSong(params);
                break;
            }
            case "play":
            {
                Play(params);
                break;
            }
        }
    }
    
    function VolumeUp(params)
    {
        var player = document.getElementById('chipsound');
        if(!player)
            return;
        player.volume+=0.05;
        
    }
    
    function VolumeDown(params)
    {
        var player = document.getElementById('chipsound');
        if(!player)
            return;
        player.volume-=0.05;
    }
    function VolumeSet(params)
    {
        var player = document.getElementById('chipsound');
        if(!player)
            return;
        player.volume=params;
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
    
    
    
    
    function PlayerStop()
    {
        player.pause();
        player.fastSeek(0);
    }
    
    function PlayerPause()
    {
        player.pause();
    }
    function PlayerPlay()
    {
        player.play();
    }
    
    function PlayerTogglePlay()
    {
        if(player.paused)
        {
            player.play();
        }
        else
        {
            player.pause();
        }
    }
    function PlayerSeek(e)
    {
        if(e.buttons !=1)
        {
            return;
        }
        var bar = e.target;
        var percent = e.offsetX / bar.clientWidth;
        player.fastSeek(player.duration * percent);
    }
    
    function PlayerNext()
    {
        playlist_offset++;
        if(playlist_offset >= playlist.length)
        {
            playlist_offset = 0;
        }
        PlayerSetSong(playlist_offset);
    }
    function PlayerPrevious()
    {
        playlist_offset--;
        if(playlist_offset < 0)
        {
            playlist_offset = playlist.length-1;
        }
        PlayerSetSong(playlist_offset);
    }
    
    
    function PlayerVolume(e)
    {
        if(e.buttons !=1)
        {
            return;
        }
        var bar = e.target;
        var read = e.offsetY;
        if(read<0)
        {
            read = 0;
        }
        if(read> bar.clientHeight)
        {
            read = bar.clientHeight;
        }
        var percent = read / bar.clientHeight;
        player.volume = 1-percent;
    }
    
    
    
    
    
    function PlayerUpdateTime()
    {
        
            var ctime = document.getElementById('player_current_time');
            var ttime = document.getElementById('player_total_time');
            var scrubber = document.getElementById('scrubberpos');
            var current=player.currentTime;
            var total = player.duration;
            try
            {
            ctime.innerHTML = new Date(1000 * current).toISOString().substring(14, 19);
            ttime.innerHTML = new Date(1000 * total).toISOString().substring(14, 19);
            var pct = (current/total) * 100;
            scrubber.style.width = pct + "%";
                
            }
            catch(ex)
            {
                console.log(current);
                console.log(total);
            }
    }
    
    function PlayerUpdateVolume()
    {
        
            
            var volumefill = document.getElementById('volumebg');
            var volumethumb = document.getElementById('volumethumb');
            var volumebox = document.getElementById('volumecontrol');
            volumefill.style.height = player.volume*100 + "%";
            volumethumb.style.bottom = ((player.volume * volumebox.clientHeight)-4)+"px"; 
    }
    function PlayerUpdatePlayPauseButton()
    {
        
    }
    
    function ConnectPlayer(id)
    {
        player = document.getElementById(id);
        player.addEventListener("timeupdate",(e)=>{
            PlayerUpdateTime();
        });
        player.addEventListener("volumechange",(e)=>{
            PlayerUpdateVolume();
        });
        
        player.addEventListener("playing",(e)=>{
            document.getElementById('playpausebutton').classList.add('pressed');
        });
        player.addEventListener("pause",(e)=>{
            document.getElementById('playpausebutton').classList.remove('pressed');
        });
        
        PlayerUpdateVolume();
    }
    
    function AnimateSongTitle()
    {
        var displaywidth = 21;
        if(!playlist)
            return;
        var title = playlist[playlist_offset]['title'];
        if(title.length <= displaywidth)
        {
            document.getElementById('player_current_song').innerHTML = title;
            return;
        }
        title+=" ";
        if(title_offset >= title.length)
        {
            title_offset = 0;
        }
        var end_offset = title_offset + displaywidth;
        var display_title = title.substring(title_offset, end_offset);
        if(display_title.length < displaywidth)
        {
            start_offset = displaywidth-display_title.length;
            display_title+=title.substring(0,start_offset);
        }
        title_offset++;
        document.getElementById('player_current_song').innerHTML = display_title;
    }
    function PlayerSetSong(offset)
    {
        var song = playlist[offset];
        title_offset = 0;
        player.src = "/files/stream/" + song.file + "/" + song.file + ".mp3";
        player.fastSeek(0);
        document.getElementById('player_current_song').innerHTML = song.title;
        
    }
    
    function PlayerEnqueue(id, playnow=false)
    {
        if(!playlist)
        {
            playlist = [];
        }
        var url = "/music/getsong/"+id;
         
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
                        playlist.push(result);
                        if(playnow)
                        {
                            PlayerNext();
                        }
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
</script>
<style type="text/css">
    #musicplayer
    {
        background-color: #101010;
        padding: 1em;
        border-radius: 1em;
        border-color: #00A000;
        border-width: 2px;
        border-style: solid;
        width: 34em;
        position:relative;
        z-index: 1;
    }
    #musicplayer .playertime
    {
        background-color: black;
        border: 5px solid #007000;
        border-top-color: #00B000;
        border-bottom-color: #004000;
        border-radius: 6px;
        width: 7.9rem;
        display: inline-block;
        text-shadow: 0 2px 0px #007000, 0px 0px 3px #00C000, 0px 0px 3px #00C000;
        padding: 20px;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-right: 15px;
        pointer-events: none;
        top: 0;
        left: 0;
        text-align: center;
        position: relative;
    }
    #musicplayer .playertime span
    {
        
        font-size: 2.5rem;
        font-family: "Curved Seven Segment";
        line-height:2.5rem;
        letter-spacing: 4px;
        user-select: none;
        
    }
    .scrubber
    {
        background-color:#002000;
        height: 6px;
        border: 1px solid #007000;
        border-top-color: #00B000;
        border-bottom-color: #004000;
        border-radius: 2px;
        padding:1px;
        overflow:hidden;
        width: 20rem;
        top: 4rem;
        left: 9.6rem;
        position: absolute;
    }
    #scrubberpos
    {
        background-color: #00F000;
        display:inline-block;
        height: 3px;
        pointer-events: none;
        user-select: none;
    }
    
    #volumecontrol
    {
        background-color:#101010;
        aaborder: 1px solid #007000;
        /*height: 6px;
        border-top-color: #00B000;
        border-bottom-color: #004000;
        border-radius: 2px;*/
        padding:2px;
        overflow:visible;
        width: 24px;
        top: 18px;
        left: 31rem;
        position: absolute;   
        height: 7rem;
    }
    
    #volumebg
    {
        bottom: 0;
        height:100%;
        background-color: #00F000;
        display:inline-block;
        pointer-events: none;
        user-select: none;
        margin-left:auto;
        margin-right:auto;
        width:4px;
        position: absolute;
        left:10px;
        border-radius: 4px;
    }
    #volumetrack
    {
        bottom: 0;
        height:100%;
        background-color: #003000;
        display:inline-block;
        pointer-events: none;
        user-select: none;
        margin-left:auto;
        margin-right:auto;
        width:4px;
        position: absolute;
        left:10px;
        border-radius: 4px;
    }
    #volumethumb
    {
        height:8px;
        width: 1rem;
        left:4px;
        background-color: #101010;
        display:inline-block;
        pointer-events: none;
        user-select: none;
        bottom: 0;
        border-width: 1px;
        border-style:solid;
        border-top-color: #004000;
        border-right-color: #007000;
        border-left-color: #007000;
        border-bottom-color: #009000;
        border-bottom-width: 2px;
        position: absolute;
        z-index: 3;
        
    }
    #volumeticks
    {
        position: absolute;
        z-index: 2;
        white-space: nowrap;
        pointer-events: none;
        user-select: none;
        font-size:8px;
        font-family: monospace;
        text-shadow: 0 0px 6px #007000, 0px 0px 3px #00C000, 0px 0px 3px #00C000;
        left: -10px;
        top:-2px;
    }
    
    #player_current_song
    {
        font-family: "HD44780 5x8";
        display: block;
        position: absolute;
        left: 9.6rem;
        top: 18px;
        background-color: black;
        border: 3px solid #007000;
        border-top-color: #00B000;
        border-bottom-color: #004000;
        border-radius: 4px;
        width: 20rem;
        display: inline-block;
        text-shadow: 0 2px 0px #007000, 0px 0px 3px #00C000, 0px 0px 3px #00C000;
        padding: 15px;
        padding-top: 5px;
        padding-bottom: 10px;
        overflow: hidden;
        white-space: nowrap;
        pointer-events: none;
        user-select: none;
    }
    .playercontrols
    {
        
        position: absolute;
        left: 9.6rem;
        top: 5rem;
    }
    .playercontrols button
    {
        display: inline-block;
        width: 2.5rem;
        font-size: 1.3rem;
        border-top-color: #004000;
        border-right-color: #007000;
        border-left-color: #007000;
        border-bottom-color: #009000;
        border-bottom-width: 2px;
        text-shadow: 0 0px 6px #007000, 0px 0px 3px #00C000, 0px 0px 3px #00C000;
        padding-top: 1px;
        position:relative;
        top:0;
    }
    .playercontrols button.pressed
    {
        top: 2px;
        border-bottom-width: 1px;
        
    }
</style>
<template id="cores_music_player">
    
    <div class="playertime"><span class="cores_player_current_time">--:--</span><br /><span class="cores_player_total_time">--:--</span></div>
    <div class="cores_player_scrubber"><span class="cores_player_scrubberpos">&nbsp;</span></div>
    <div class="cores_player_current_song">Unknown Artist</div>
    <div class="cores_player_playercontrols"><button>&#x23f9;&#xfe0e;</button><button>&#x23ee;&#xfe0e;</button><button class="cores_player_playpausebutton">&#x23ef;&#xfe0e;</button><button>&#x23ed;&#xfe0e;</button></div>
    <div class="cores_player_volumecontrol"><span class="cores_player_volumeticks">11 -&nbsp;&nbsp;- 11<br />
    10 -&nbsp;&nbsp;- 10<br />
    &nbsp;9 -&nbsp;&nbsp;- 9<br />
    &nbsp;8 -&nbsp;&nbsp;- 8<br />
    &nbsp;7 -&nbsp;&nbsp;- 7<br />
    &nbsp;6 -&nbsp;&nbsp;- 6<br />
    &nbsp;5 -&nbsp;&nbsp;- 5<br />
    &nbsp;4 -&nbsp;&nbsp;- 4<br />
    &nbsp;3 -&nbsp;&nbsp;- 3<br />
    &nbsp;2 -&nbsp;&nbsp;- 2<br />
    &nbsp;1 -&nbsp;&nbsp;- 1<br />
    &nbsp;0 -&nbsp;&nbsp;- 0<br />    
        </span><span class="cores_player_volumetrack">&nbsp;</span><span class="cores_player_volumebg">&nbsp;</span><span class="cores_player_volumethumb">&nbsp;</span></div>

</template>
<div id="musicplayer">
    <div class="playertime"><span id="player_current_time">--:--</span><br /><span id="player_total_time">--:--</span></div>
    <div class="scrubber" onmousemove="PlayerSeek(event);"><span id="scrubberpos">&nbsp;</span></div>
    <div id="player_current_song">Unknown Artist</div>
    <div class="playercontrols"><button onclick="PlayerStop();">&#x23f9;&#xfe0e;</button><button onclick="PlayerPrevious();">&#x23ee;&#xfe0e;</button><button id="playpausebutton" onclick="PlayerTogglePlay();">&#x23ef;&#xfe0e;</button><button onclick="PlayerNext();">&#x23ed;&#xfe0e;</button></div>
    <div id="volumecontrol" onmousemove="PlayerVolume(event);"><span id="volumeticks">11 -&nbsp;&nbsp;- 11<br />
    10 -&nbsp;&nbsp;- 10<br />
    &nbsp;9 -&nbsp;&nbsp;- 9<br />
    &nbsp;8 -&nbsp;&nbsp;- 8<br />
    &nbsp;7 -&nbsp;&nbsp;- 7<br />
    &nbsp;6 -&nbsp;&nbsp;- 6<br />
    &nbsp;5 -&nbsp;&nbsp;- 5<br />
    &nbsp;4 -&nbsp;&nbsp;- 4<br />
    &nbsp;3 -&nbsp;&nbsp;- 3<br />
    &nbsp;2 -&nbsp;&nbsp;- 2<br />
    &nbsp;1 -&nbsp;&nbsp;- 1<br />
    &nbsp;0 -&nbsp;&nbsp;- 0<br />    
        </span><span id="volumetrack">&nbsp;</span><span id="volumebg">&nbsp;</span><span id="volumethumb">&nbsp;</span></div>
</div>

<script type="text/javascript">

    ConnectPlayer('player');
    setInterval(AnimateSongTitle, 500);
</script>