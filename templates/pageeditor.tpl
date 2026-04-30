<script src="/ckeditor/ckeditor.js"></script>
<script>
    function doExtImages(element)
    {
        if(!element || !element.value)
            return;
        let text = element.value;
        console.log(text);
        let images = [];
        let matches = text.matchAll(/\<img.+src\=(?:\"|\')(.+?)(?:\"|\')(?:.*?)\>/g);
        matches.forEach(e=>images.push(e[1]));
        console.log(images);
        window.TBD=images.length;
        images.forEach((img,i)=>{
            downloadAndAttach(img,"image"+i);
        });
        
        
    }
    function downloadAndAttach(link,fn)
    {
        fetch(link,{
  mode: "no-cors"})
            .then(response => response.blob())
            .then(blob => {
                let file = new File([blob], fn,{type:"image/jpeg", lastModified:new Date().getTime()});
                let container = new DataTransfer();
                container.items.add(file);
                let filein=document.createElement("input");
                filein.type="file";
                filein.files = container.files;
                console.log(container.files);
                document.getElementById("kbform").appendChild(filein);
                window.TBD--;
                if(window.TBD<=0)
                {
                    //document.getElementById("kbform").submit();
                }
            });
    }
    
</script>
<form enctype="multipart/form-data" action="/kb/save" method="POST" id="kbform">
<textarea name="text" id ="text" cols="56" rows="20">{%pagetext|%}</textarea>
<script>
CKEDITOR.replace("text");
</script>
<input name="pageid" type="hidden" value="{%pageid|-1%}" /><!-- onclick="doExtImages(this.parentElement.querySelector('#text'));event.preventDefault();"-->
<button>Save page</button>
</form> 
<script src="/js/peeler.js"></script>
<style>
#peeler
{
    width:100%;
    
}
#peeler div, #peeler ul, #peeler ol
{
    display:inline-block;
    width:24%;
    height: 16em;
    overflow: scroll;
}
</style>
<div id="peeler">
    <div id="targetcontainer">
        <iframe sandbox="allow-same-origin" id="target" srcdoc="<!doctype html><head></head><body contenteditable>a</body></html>"></iframe>
    </div>
    <div id="output" contenteditable></div>
    <ul id="depthinfo"></ul>
    <ol id="toc"></ol>
</div>
<input type="checkbox" id="autodetect" />
<input type="range" min="0" max="10" id="scraperdepth" />
<span>Skip first </span><input type="number" id="skipfirst" value="0" />
<span> and last </span><input value="0" type="number" id="skiplast" />
<span> blocks.</span>
<br />
<button id="scrape">go</button>

<script>
// pasting area
let inputframe=document.getElementById("target");
// settings
let ad = document.getElementById("autodetect");
let depthslider = document.getElementById("scraperdepth");
let skiplast = document.getElementById("skiplast");
let skipfirst = document.getElementById("skipfirst");
// runs the peeler/formatter
let gobutton=document.getElementById("scrape");
// outputs
let output = document.getElementById("output");
let depthinfo = document.getElementById("depthinfo");
let toc = document.getElementById("toc");

ad.addEventListener("change",(e)=>{
    depthslider.disabled=ad.checked;
});
// actually running the process
gobutton.addEventListener("click",(e)=>{
    // clear out everything
    output.innerHTML="";
    toc.innerHTML="";
    // init peeler with given settings
    let scraper = new HTMLPeeler(inputframe.contentDocument.body);
    // autodetect mode or manual
    scraper.depth=ad.checked?-1:depthslider.value;
    // run peeler
    let doc = scraper.scrape();
    // drop first/last blocks as needed
    doc.trim(skipfirst.value,skiplast.value);
    // prepare and run formatter
    let formatter = new HTMLFormatter(doc);
    formatter.outputHTMLDoc(output);
    //document.getElementById("text").innerText=output.innerHTML;
    CKEDITOR.instances.text.setData(output.innerHTML);
    formatter.outputTOC(toc);
    // output results to console for debugging and admiration
    console.log(doc.images);
    console.log(doc.headers);
    console.log(formatter.outline);
    console.log(doc);
});
// moving the slider shows a preview of detected tags on given depth
depthslider.addEventListener("input",(e)=>{
    depthinfo.innerHTML="";
    let scraper=new HTMLPeeler(inputframe.contentDocument.body);
    scraper.depth=depthslider.value;
    let tags = scraper.tagList;
    Object.entries(tags).forEach((tag)=>{
        let li = output.ownerDocument.createElement("li");
        let t="<"+tag[0]+"> x"+tag[1];
        li.appendChild(output.ownerDocument.createTextNode(t));
        depthinfo.appendChild(li);
    });
});
// only run this on load as the srcdoc replaces the body after the page loads
inputframe.addEventListener("load",(e)=> 
{
    // add an onPaste event to get the clipboard data and inject into body
    // default paste filters out iframes and other possibly useful things
    inputframe.contentDocument.body.addEventListener("paste",(e)=>{
        let d = e.clipboardData.getData('text/html');
        // keep this for inspection purposes
        console.log(d);
        // this is probably not a good idea - check for proper way
        inputframe.contentDocument.body.innerHTML = d;
        e.preventDefault();
    });
});
// trigger a redo of the depth preview procedure - #TODO better place for this to trigger
inputframe.addEventListener("blur",(e)=>{
    depthslider.dispatchEvent(new Event("input"));
});

ad.dispatchEvent(new Event("change"));
</script>