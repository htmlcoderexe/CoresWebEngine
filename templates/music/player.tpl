<script type="text/javascript">
    
    class CoresPlayer
    {
        
        static player_template_id = "cores_music_player";
        static libraryUrl = "/music/getlibrary";
        
        id = "";
        playlist = [];
        currentIndex = 0;
        library = [];
        currentTrack;
        
        
        #audio;
        
        #current_time_indicator;
        #duration_indicator;
        
        #seek_bar;
        #seek_fill;
        
        #volume_backlight;
        #volume_thumb;
        #volume_bar;
        
        #playpausebutton;
        
        #song_display;
        #title_animation_counter = 0;
        
        
        
        constructor(id)
        {
            // create the player elements
            // attach to the indicated div
            const root = document.getElementById(id);
            this.id = id;
            root.classList.add("cores_player");
            
            const tpl = document.getElementById(CoresPlayer.player_template_id).content.cloneNode(true);
            
            // time display
            let clocks = tpl.querySelectorAll(".cores_player_time span");
            this.#current_time_indicator = clocks[0];
            this.#duration_indicator = clocks[1];
            // seek bar
            this.#seek_bar = tpl.querySelector(".cores_player_scrubber");
            this.#seek_bar.addEventListener("mousemove",(e)=>{
                this.#process_scrubber(e);
            });
            this.#seek_fill = tpl.querySelector(".cores_player_scrubber_fill");
            // song display
            this.#song_display = tpl.querySelector(".cores_player_song_title");
            // buttons
            let buttons = tpl.querySelector(".cores_player_buttons").childNodes;
            buttons[0].addEventListener("click",(e)=>{
                this.stop();
            });
            buttons[1].addEventListener("click",(e)=>{
                this.#previous_button();
            });
            this.#playpausebutton = buttons[2];
            buttons[2].addEventListener("click",(e)=>{
                this.togglePlay();
            });
            buttons[3].addEventListener("click",(e)=>{this.#next_button();});
            // volume control
            this.#volume_bar = tpl.querySelector(".cores_player_volumecontrol");
            this.#volume_bar.addEventListener("mousemove",(e)=>{
                this.#process_volumecontrol(e);
            });
            this.#volume_backlight = tpl.querySelector(".cores_player_volumebg");
            this.#volume_thumb = tpl.querySelector(".cores_player_volumethumb");
            // audio piece
            this.#audio = tpl.querySelector("audio");
            this.#audio.addEventListener("timeupdate",(e)=>{
                this.#update_time(this);
            });
            this.#audio.addEventListener("seeking",(e)=>{
                this.#update_time(this);
            });
            this.#audio.addEventListener("volumechange",(e)=>{
                this.#update_volume(this);
            });

            this.#audio.addEventListener("playing",(e)=>{
                this.#playpausebutton.classList.add('pressed');
            });
            this.#audio.addEventListener("pause",(e)=>{
                this.#playpausebutton.classList.remove('pressed');
            });
            this.#update_volume();
            
            
            // assemble, append and attach
            let element = null;
            while(element = tpl.firstElementChild)
            {
                root.append(element);
            }
            
            setInterval(this.#animate_song_display, 500,this);
            
        }
        
        // exposed control actions
        
        play()
        {
            this.#audio.play();
        }
        
        pause()
        {
            this.#audio.pause();
        }
        
        stop()
        {
            this.#audio.pause();
            this.seek(0);
        }
        
        seek(position)
        {
            this.#audio.fastSeek(position);
        }
        
        next = ()=>
        {
            console.log("fucking fuck fuck shit next");
            console.log(this);
            this.currentIndex++;
            if(this.currentIndex>=this.playlist.length)
            {
                this.currentIndex = 0;
            }
            this.playItem(this.currentIndex);
        };
        
        previous = ()=>
        {
            console.log("fucking fuck fuck shit previous");
            console.log(this);
            this.currentIndex--;
            if(this.currentIndex<0)
            {
                this.currentIndex = this.playlist.length-1;
            }
            this.playItem(this.currentIndex);
        };
        
        togglePlay()
        {
            if(this.#audio.paused)
            {
                this.play();
            }
            else
            {
                this.pause();
            }
        }
        
        playItem(offset, forcePlay = false)
        {
            console.log("fucking fuck fuck shit playItem");
            console.log(this);
            var song = this.playlist[offset];
            console.log(this.playlist);
            console.log(offset);
            console.log(song);
            this.currentTrack = song;
            this.#title_animation_counter = 0;
            this.#audio.src = "/files/stream/" + song.file + "/" + song.file + ".mp3";
            this.#audio.load();
            this.#audio.fastSeek(0);
            if(!forcePlay)
            {
                this.play();
            }
        }
        
        set volume(new_volume)
        {
            this.#audio.volume = new_volume;
        }
        get volume()
        {
            return this.#audio.volume;
        }
        
        
        // exposed getters
        
        get duration()
        {
            return this.#audio.duration;
        }
        
        get currentTime()
        {
            return this.#audio.currentTime;
        }
        
        
        // UI input
        
        #previous_button = () => {
            console.log("fucking fuck fuck shit #previous_button");
            console.log(this);
            this.previous();
        };
        #next_button = () => {
            console.log("fucking fuck fuck shit #next_button");
            console.log(this);
            this.next();
        };
        #stop_button = () => {
            this.stop();
        };
        #playpause_button = () => {
            this.togglePlay();
        };
        
        #process_scrubber = (e) =>
        {
            if(e.target!==this.#seek_bar)
            {
                return;
            }
            if(e.buttons !=1)
            {
                return;
            }
            var percent = e.offsetX / this.#seek_bar.clientWidth;
            this.seek(this.duration * percent);
        }
        
        #process_volumecontrol = (e) =>
        {
            if(e.target!==this.#volume_bar)
            {
                return;
            }
            if(e.buttons !=1)
            {
                return;
            }
            var read = e.offsetY;
            if(read<0)
            {
                read = 0;
            }
            if(read> this.#volume_bar.clientHeight)
            {
                read = this.#volume_bar.clientHeight;
            }
            var percent = read / this.#volume_bar.clientHeight;
            this.volume = 1-percent;
        }
        
        // UI output
        
        #update_time = () =>
        {
            let str_current = "--:--";
            let str_duration = "--:--";
            let pct = 0;
            try
            {
                str_current = new Date(1000 * this.currentTime)
                        .toISOString().substring(14, 19);
                str_duration = new Date(1000 * this.duration)
                        .toISOString().substring(14, 19);
                pct = (this.currentTime/this.duration) * 100;                
            }
            catch(ex)
            {
                console.log(this.currentTime);
                console.log(this.duration);
            }
            this.#current_time_indicator.textContent = str_current;
            this.#duration_indicator.textContent = str_duration;
            this.#seek_fill.style.width = pct + "%";
        }
        
        #update_volume = (e) =>
        {
            this.#volume_backlight.style.height = this.volume*100 + "%";
            this.#volume_thumb.style.bottom = ((this.volume * this.#volume_bar.clientHeight)-4)+"px"; 
        }
        
        #animate_song_display = (e) =>
        {
            var displaywidth = 21;
            if(!this.playlist)
                {
                    this.#song_display.textContent = "Insert disc";
                    return;
                }
            console.log(this);
            let title = this.currentTrack.title + " ";
            if(title.length <= displaywidth)
            {
                this.#song_display.textContent = title;
                return;
            }
            if(this.#title_animation_counter >= title.length)
            {
                this.#title_animation_counter = 0;
            }
            var end_offset = this.#title_animation_counter + displaywidth;
            var display_title = title.substring(this.#title_animation_counter, end_offset);
            if(this.#title_animation_counter+end_offset>title.length)
            {
                var start_offset = (this.#title_animation_counter+end_offset) - title.length ;
                display_title+=title.substring(0,start_offset);
            }
            console.log(display_title);
            this.#title_animation_counter++;
            this.#song_display.textContent = display_title;
        }
        
        // other actions
        
        loadLibrary()
        {
            fetch(CoresPlayer.libraryUrl)
                .then((response)=>{
                if(response.ok)
                {
                    response.json().then((data)=>{
                        this.library = data;
                        this.playlist = data;
                        if(this.onReady)
                        {
                            this.onReady();
                        }
                    });
                }
            });
        }
        
        playRandom()
        {
            let rnd = Math.floor(Math.random() * this.library.length);
            //rnd = 3
            this.playItem(rnd);
        }
        
    }
    //window.p = Player;
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
    .cores_player_time
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
    .cores_player_time span
    {
        
        font-size: 2.5rem;
        font-family: "Curved Seven Segment";
        line-height:2.5rem;
        letter-spacing: 4px;
        user-select: none;
        
    }
    .cores_player_scrubber
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
    .cores_player_scrubber_fill
    {
        background-color: #00F000;
        display:inline-block;
        height: 3px;
        pointer-events: none;
        user-select: none;
    }
    
    .cores_player_volumecontrol
    {
        background-color:#101010;
        padding:2px;
        overflow:visible;
        width: 24px;
        top: 18px;
        left: 31rem;
        position: absolute;   
        height: 7rem;
    }
    
    .cores_player_volumebg
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
    .cores_player_volumetrack
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
    .cores_player_volumethumb
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
    .cores_player_volumeticks
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
    
    .cores_player_song_title
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
    .cores_player_buttons
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
    
    <div class="cores_player_time"><span class="cores_player_current_time">--:--</span><br /><span class="cores_player_total_time">--:--</span></div>
    <div class="cores_player_scrubber"><span class="cores_player_scrubber_fill">&nbsp;</span></div>
    <div class="cores_player_song_title">Unknown Artist</div>
    <div class="cores_player_buttons"><button>&#x23f9;&#xfe0e;</button><button>&#x23ee;&#xfe0e;</button><button class="cores_player_playpausebutton">&#x23ef;&#xfe0e;</button><button>&#x23ed;&#xfe0e;</button></div>
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
    <audio preload="metadata"></audio>
</template>
<!--<div id="musicplayer">
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
</div>-->
<div id="musicplayer">
</div>
<script type="text/javascript">
    player = new CoresPlayer('musicplayer');
    player.onReady = ()=>{
        console.log(player);
        console.log(this);
        player.playRandom();
        player.stop();
    };
    player.loadLibrary();
    //ConnectPlayer('player');
    //setInterval(AnimateSongTitle, 500);
</script>