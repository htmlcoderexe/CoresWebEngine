<script src="/ckeditor/ckeditor.js"></script>
<script>
    
    
</script>
<form action="/kb/save" method="POST">
<textarea name="text" id ="text" cols="56" rows="20">{%pagetext|%}</textarea>
<script>
CKEDITOR.replace("text");
</script>
<input name="pageid" type="hidden" value="{%pageid|-1%}" />
<button type="submit">Save page</button>
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
<div id="peeler"><div id="target" contenteditable></div><div id="output" contenteditable></div><ul id="depthinfo"></ul><ol id="toc"></ol></div>
<button id="scrape">go</button><input value="0" type="range" min="0" max="10" id="scraperdepth" /><span>Skip first </span><input type="number" id="skipfirst" value="0" /><span> and last </span><input value="0" type="number" id="skiplast" /><span> blocks.</span>

<script>
let input=document.getElementById("target");
let output=document.getElementById("output");
let depthslider = document.getElementById("scraperdepth");
let depthinfo = document.getElementById("depthinfo");
let gobutton=document.getElementById("scrape");
let toc = document.getElementById("toc");
let skiplast = document.getElementById("skiplast");
let skipfirst = document.getElementById("skipfirst");
gobutton.addEventListener("click",(e)=>{
    let scraper = new HTMLPeeler(input);
    scraper.depth=depthslider.value;
    scraper.scrape();
    console.error("DONE SCRAPING WITH CLASS");
    
    output.innerHTML="";
    let formatter = new HTMLFormatter(scraper.blocks);
    formatter.outputHTMLDoc(output,skipfirst.value,skiplast.value);
    // scraper.outputHTMLDoc(output,skipfirst.value,skiplast.value);
    toc.innerHTML="";
    let headers = formatter.headers;
    console.log(formatter.images);
    console.log(scraper.blocks);
    for(let i=0;i<headers.length;i++)
    {
        let li=document.createElement("li");
        let a = document.createElement("a");
        a.href="#header"+i;
        a.innerText=headers[i][0];
        li.appendChild(a);
        toc.appendChild(li);
    }
});
depthslider.addEventListener("input",(e)=>{
    depthinfo.innerHTML="";
    let scraper=new HTMLPeeler(input);
    scraper.depth=depthslider.value;
    let tags = scraper.tagList;
    Object.entries(tags).forEach((tag)=>{
        let li = document.createElement("li");
        let t="<"+tag[0]+"> x"+tag[1];
        li.appendChild(document.createTextNode(t));
        depthinfo.appendChild(li);
    });
});
input.addEventListener("blur",(e)=>{
depthslider.dispatchEvent(new Event("input"));})
</script>