<script type="text/javascript">
    
    class CoresPlayer
    {
        
        static player_template_id = "cores_music_player";
        static libraryUrl = "/music/getlibrary";
        
        id = "";
        playing = false;
        playlist = [];
        currentIndex = 0;
        library = [];
        currentTrack;
        
        
        _audio;
        
        _current_time_indicator;
        _duration_indicator;
        
        _seek_bar;
        _seek_fill;
        _seek_moving;
        
        _volume_backlight;
        _volume_thumb;
        _volume_bar;
        _volume_moving;
        
        _playpausebutton;
        
        _song_display;
        _title_animation_counter = 0;
        
        _playlist_container;
        
        
        
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
            this._current_time_indicator = clocks[0];
            this._duration_indicator = clocks[1];
            // seek bar
            this._seek_bar = tpl.querySelector(".cores_player_scrubber");
            this._seek_bar.addEventListener("pointerdown",(e)=>{
                this._begin_seek_grab(e);
            });
            this._seek_fill = tpl.querySelector(".cores_player_scrubber_fill");
            // song display
            this._song_display = tpl.querySelector(".cores_player_song_title");
            // playlist
            this._playlist_container = tpl.querySelector(".cores_player_playlist");
            // buttons
            let buttons = tpl.querySelector(".cores_player_buttons").childNodes;
            buttons[0].addEventListener("click",(e)=>{
                this.stop();
            });
            buttons[1].addEventListener("click",(e)=>{
                this._previous_button();
            });
            this._playpausebutton = buttons[2];
            buttons[2].addEventListener("click",(e)=>{
                this.togglePlay();
            });
            buttons[3].addEventListener("click",(e)=>{this._next_button();});
            buttons[4].addEventListener("click",(e)=>{this._random_button();});
            // volume control
            this._volume_bar = tpl.querySelector(".cores_player_volumecontrol");
            this._volume_bar.addEventListener("pointerdown",(e)=>{
                this._begin_volume_grab(e);
            });
            this._volume_backlight = tpl.querySelector(".cores_player_volumebg");
            this._volume_thumb = tpl.querySelector(".cores_player_volumethumb");
            // audio piece
            this._audio = tpl.querySelector("audio");
            this._audio.addEventListener("timeupdate",(e)=>{
                this._update_time(this);
            });
            this._audio.addEventListener("durationchange",(e)=>{
                this._update_time(this);
            });
            this._audio.addEventListener("seeking",(e)=>{
                this._update_time(this);
            });
            this._audio.addEventListener("volumechange",(e)=>{
                this._update_volume(this);
            });

            this._audio.addEventListener("playing",(e)=>{
                this._playpausebutton.classList.add('pressed');
            });
            this._audio.addEventListener("pause",(e)=>{
                this._playpausebutton.classList.remove('pressed');
            });
            
            
            document.addEventListener("pointermove",(e)=>{
                this._process_volumecontrol(e);
                this._process_scrubber(e);
            });
            document.addEventListener("pointerup",(e)=>{
                this._process_release_grab(e);
            });
            
            // assemble, append and attach
            let element = null;
            while(element = tpl.firstElementChild)
            {
                root.append(element);
            }
            
            this._update_volume();
            setInterval(this._animate_song_display, 500,this);
            
        }
        
        // exposed control actions
        
        play()
        {
            this.playing = true;
            this._audio.play();
        }
        
        pause()
        {
            this.playing = false;
            this._audio.pause();
        }
        
        stop()
        {
            this.pause();
            this.seek(0);
        }
        
        seek(position)
        {
            this._audio.fastSeek(position);
        }
        
        next = ()=>
        {
            console.log("fucking fuck fuck shit next");
            console.log(this);
            console.log(this.currentIndex);
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
            console.log(this.currentIndex);
            this.currentIndex--;
            if(this.currentIndex<0)
            {
                this.currentIndex = this.playlist.length-1;
            }
            this.playItem(this.currentIndex);
        };
        
        togglePlay()
        {
            if(this._audio.paused)
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
            this.currentIndex = offset;
            for(const row of this._playlist_container.children)
            {
                row.dataset.playing ="no";
                if(row.dataset.index == this.currentIndex)
                {
                    row.dataset.playing = "yes";
                    row.scrollIntoView();
                }
            }
            console.log(this.currentIndex);
            this._title_animation_counter = 0;
            this._audio.src = "/files/stream/" + song.file + "/" + song.file + ".mp3";
            this._audio.load();
            this._audio.fastSeek(0);
            if(forcePlay || this.playing)
            {
                this.play();
            }
            else
            {
                this.play();
                this.stop();
            }
        }
        
        set volume(new_volume)
        {
            this._audio.volume = new_volume;
        }
        get volume()
        {
            return this._audio.volume;
        }
        
        
        // exposed getters
        
        get duration()
        {
            return this._audio.duration;
        }
        
        get currentTime()
        {
            return this._audio.currentTime;
        }
        
        
        // UI input
        
        _previous_button = () => {
            console.log("fucking fuck fuck shit _previous_button");
            console.log(this);
            this.previous();
        };
        _next_button = () => {
            console.log("fucking fuck fuck shit _next_button");
            console.log(this);
            this.next();
        };
        _stop_button = () => {
            this.stop();
        };
        _playpause_button = () => {
            this.togglePlay();
        };
        _random_button = () => {
            this.playRandom();
        };
        _play_track = (e) => {
            this.playItem(e.target.dataset.index);
        };
        
        _process_scrubber = (e) =>
        {
            
            if(!this._seek_moving)
            {
                return;
            }
            var read = e.offsetX;
            if(read<0)
            {
                read =0;
            }
            if(read>this._seek_bar.clientWidth)
            {
                read = this._seek_bar.clientWidth;
            }
            
            var percent = read / this._seek_bar.clientWidth;
            this.seek(this.duration * percent);
        }
        
        _process_release_grab = (e) =>
        {
            this._volume_moving = false;
            this._seek_moving = false;
        };
        
        _process_volumecontrol = (e) =>
        {
            if(!this._volume_moving)
            {
                return;
            }
            var read = e.offsetY;
            if(read<0)
            {
                read = 0;
            }
            if(read> this._volume_bar.clientHeight)
            {
                read = this._volume_bar.clientHeight;
            }
            var percent = read / this._volume_bar.clientHeight;
            this.volume = 1-percent;
            e.preventDefault();
            console.log(e);
        }
        
        _begin_volume_grab = (e) =>
        {
            this._volume_moving = true;
        }
        _begin_seek_grab = (e) =>
        {
            this._seek_moving = true;
        }
        
        // UI output
        
        _update_time = () =>
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
            this._current_time_indicator.textContent = str_current;
            this._duration_indicator.textContent = str_duration;
            this._seek_fill.style.width = pct + "%";
        }
        
        _update_volume = () =>
        {
            this._volume_backlight.style.height = this.volume*100 + "%";
            this._volume_thumb.style.bottom = ((this.volume * this._volume_bar.clientHeight)-4)+"px";
            console.log(this._volume_bar.clientHeight);
            console.log(this.volume);
            console.log("FUCK YOUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU!!!!!!!!!!!!!!!!!!!!!");
        }
        
        enqueue = (id) =>
        {
            let song = this.library.find((track)=>track.id == id);
            if(!song)
            {
                return;
            }
            let row = document.createElement("span");
            row.dataset.index=this.playlist.length;
            row.dataset.songId=song.id;
            row.dataset.playing="no";
            row.textContent = ((this.playlist.length + 1) + " ").padStart(4, "0") + song.title;
            row.addEventListener("click", this._play_track);
            this._playlist_container.append(row);
            console.log(this.playlist);
            this.playlist.push(song);
            
        };
        
        _animate_song_display = (e) =>
        {
            var displaywidth = 21;
            if(!this.playlist)
                {
                    this._song_display.textContent = "Insert disc";
                    return;
                }
            //console.log(this);
            let title = this.currentTrack.title + " ";
            if(title.length <= displaywidth)
            {
                this._song_display.textContent = title;
                return;
            }
            if(this._title_animation_counter >= title.length)
            {
                this._title_animation_counter = 0;
            }
            var end_offset = this._title_animation_counter + displaywidth;
            var display_title = title.substring(this._title_animation_counter, end_offset);
            if(this._title_animation_counter+displaywidth>title.length)
            {
                var start_offset = (this._title_animation_counter+displaywidth) - title.length ;
                display_title+=title.substring(0,start_offset);
            }
            //console.log(display_title);
            this._title_animation_counter++;
            this._song_display.textContent = display_title;
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
                        var i =0;
                        for(i=0;i<this.library.length;i++)
                        {
                            this.enqueue(this.library[i].id);
                        }
                        if(this.onReady)
                        {
                            this.onReady();
                        }
                    });
                }
            });
        }
        
        playRandom = () =>
        {
            let rnd = Math.floor(Math.random() * this.library.length);
            //rnd = 4
            this.playItem(rnd);
        }
        
        
        
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
        touch-action: none;
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
        touch-action: none;
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
    .cores_player_buttons button
    {
        display: inline-block;
        width: 40px;
        height: 34px;
        font-size: 20px;
        border-top-color: #004000;
        border-right-color: #007000;
        border-left-color: #007000;
        border-bottom-color: #009000;
        border-bottom-width: 2px;
        text-shadow: 0 0px 6px #007000, 0px 0px 3px #00C000, 0px 0px 3px #00C000;
        padding-top: 0;
        position:relative;
        top:0;
        text-align:center;
    }
    .cores_player_buttons button.pressed
    {
        top: 2px;
        border-bottom-width: 1px;
        
    }
    .cores_player_buttons button.stop
    {
        padding-top:4px;
        font-size: 16px;
    }
    
    .cores_player_playlist
    {
        font-family: "HD44780 5x8";
        font-size:8px;
        display: block;
        position: relative;
        left: 0;
        top: 16px;
        background-color: black;
        border: 3px solid #007000;
        border-top-color: #00B000;
        border-bottom-color: #004000;
        border-radius: 4px;
        width: 476px;
        height:180px;
        display: block;
        text-shadow: 0 1px 0px #007000, 0px 0px 2px #00C000;
        padding: 15px;
        padding-top: 5px;
        margin-bottom: 16px;
        scrollbar-color: #004000 #00a000;
        overflow-y: scroll;
        overflow-x: hidden;
        line-height: 1.3;
        scroll-snap-type: y mandatory;
    }
    .cores_player_playlist span
    {
        display:inline-block;
        width: 100%;
        word-break: keep-all;
        white-space: nowrap;
        scroll-snap-align: center;
    }
    .cores_player_playlist span[data-playing="yes"]
    {
        color: #001000;
        background-color: #00A000;
        /*
        background-size: 1.140px 1.25px;
        background-image: 
            linear-gradient(to right, #004000 0.125px, transparent 0.1250px, transparent 1.0px, #004000 1.0px, transparent 1.0px, transparent 1.125px),
            linear-gradient(to bottom, #004000 0.125px, transparent 0.1250px, transparent 1.0px);
            */
    }
</style>
<template id="cores_music_player">
    
    <div class="cores_player_time"><span class="cores_player_current_time">--:--</span><br /><span class="cores_player_total_time">--:--</span></div>
    <div class="cores_player_scrubber"><span class="cores_player_scrubber_fill">&nbsp;</span></div>
    <div class="cores_player_song_title">Unknown Artist</div>
    <div class="cores_player_buttons"><button class="stop">&#x23f9;&#xfe0e;</button><button>&#x23ee;&#xfe0e;</button><button class="cores_player_playpausebutton">&#x23ef;&#xfe0e;</button><button>&#x23ed;&#xfe0e;</button><button>&#x1f500;&#xfe0e;</button></div>
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
    <div class="cores_player_playlist"></div>
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