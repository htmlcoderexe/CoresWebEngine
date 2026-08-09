
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/simple-image"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/table@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@latest"></script>
<script>
    
    // fuck this for now, will probably need a proper search API or some stupid shite like that lol
    // alternatively create 2 types of blocks
    // one is just the 3 numbers and is in the source, so can be edited/updated
    // other is the nav links
    // backend preprocesses the received doc and swaps out one block type for the other
    
class ChapterNavEditable {
    static get toolbox() {
        return {
            title: 'Chapter Navigation',
            icon: '<svg width="17" height="15" viewBox="0 0 336 276" xmlns="http://www.w3.org/2000/svg"><path d="M291 150V79c0-19-15-34-34-34H79c-19 0-34 15-34 34v42l67-44 81 72 56-29 42 30zm0 52l-43-30-56 30-81-67-66 39v23c0 19 15 34 34 34h178c17 0 31-13 34-29zM79 0h178c44 0 79 35 79 79v118c0 44-35 79-79 79H79c-44 0-79-35-79-79V79C0 35 35 0 79 0z"/></svg>'
        };
    }
    constructor({data}){
        this.data = data;
    }
    render(){
        let container = document.createElement("div");
        let prev = document.createElement('input');
        let next = document.createElement('input');
        let index = document.createElement('input');
        prev.type="number";
        prev.dataset.prev="true";
        prev.value=this.data.prev;
        index.value=this.data.index;
        next.value=this.data.next;
        container.appendChild(prev);
        index.type="number";
        index.dataset.index="true";
        container.appendChild(index);
        next.type="number";
        next.dataset.next="true";
        container.appendChild(next);
        return container;
    }

    save(blockContent){
        return {
            prev: blockContent.querySelector("[data-prev]").value,
            index: blockContent.querySelector("[data-index]").value,
            next: blockContent.querySelector("[data-next]").value
        };
    }
}    


    
    
    
    
    
    
    
    
</script>




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
    function saveAndSubmit(e)
    {
        window.jseditor.save().then((obj)=>{
            document.getElementById("text").value = JSON.stringify(obj);
            document.getElementById("kbform").submit();
        });
        e.preventDefault();
    }
</script>
{{system/tagenable|id={%pageid%}|type=kbpage|linkprefix=/kb/tag/|boxid=tags_container_kb|tags={%tags%}}}
<form enctype="multipart/form-data" action="/kb/save" method="POST" id="kbform">
    <input name="title" id="title" size="50" value="{%title|%}" />
<!--<textarea name="text" id ="text" cols="56" rows="20">{%pagetext|%}</textarea>-->
    <input name="text" id="text" type="hidden" />
    <div id="editorjs"></div>
<input name="pageid" type="hidden" value="{%pageid|-1%}" /><!-- onclick="doExtImages(this.parentElement.querySelector('#text'));event.preventDefault();"-->
<button id="savebutton">Save page</button>
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
    height: 10em;
    overflow: scroll;
}
#peeler iframe
{
    width:100%;
    height: 100%;
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
<input type="checkbox" id="autodetect" checked />
<input type="range" min="0" max="10" id="scraperdepth" />
<span>Skip first </span><input type="number" id="skipfirst" value="0" />
<span> and last </span><input value="0" type="number" id="skiplast" />
<span> blocks.</span>
<input value="" id="articleSelector" placeholder="Selector for the main article element" />
<input value="" id="blockSelector" placeholder="Selector for the block elements" />
<input id="fetchurl" /><button id="fetchurlbutton">get this url</button>
<br />
<button id="scrape">go</button>

<script>
    
    document.getElementById("savebutton").addEventListener("click",(e)=>{
        saveAndSubmit(e);
    });
    window.jseditor = new EditorJS({
        tools: {
            list: {
                class: EditorjsList,
                inlineToolbar: true,
                config: {
                    defaultStyle: 'unordered'
                },
            },
            header: Header,
            image: SimpleImage,
            table: Table,
            quote: {
                class: Quote,
                inlineToolbar: true,
                config: {
                    quotePlaceholder: 'Enter a quote',
                    captionPlaceholder: '',
                },
            },
            code: {
                class: CodeTool,
            },
            chapternav: ChapterNavEditable
        }{#ifset|ejsdoc|,
        data: {%ejsdoc%}||#}
    });
    function editor(data){
    window.jseditor.render(data);
}
// pasting area
let inputframe=document.getElementById("target");
// settings
let ad = document.getElementById("autodetect");
let depthslider = document.getElementById("scraperdepth");
let skiplast = document.getElementById("skiplast");
let skipfirst = document.getElementById("skipfirst");
let as = document.getElementById("articleSelector");
let bs = document.getElementById("blockSelector");
// runs the peeler/formatter
let gobutton=document.getElementById("scrape");
// outputs
let output = document.getElementById("output");
let depthinfo = document.getElementById("depthinfo");
let toc = document.getElementById("toc");

    document.getElementById("fetchurlbutton").addEventListener("click",(e)=>{
    let req = new FormData();
    req.append("url",encodeURI(document.getElementById("fetchurl").value));
    fetch("/main/api/getdata",{
        method:"POST",
        body:req
        }).then((response)=>{
        return response.json();
        }).then((json)=>{
        if(json.status =="OK")
        {
            console.log(json.data);
            target.srcdoc = json.data;
        }
        });
    
    });
    
    
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
    scraper.filters.articleSelector = as.value;
    scraper.filters.blockSelector = bs.value;
    // autodetect mode or manual
    scraper.depth=ad.checked?-1:depthslider.value;
    // run peeler
    let doc = scraper.scrape();
    // drop first/last blocks as needed
    doc.trim(skipfirst.value,skiplast.value);
    // prepare and run formatter
    let formatter = new HTMLFormatter(doc);
    formatter.outputDoc(output);
    if(doc.title!="")
    {
        document.getElementById("title").value=doc.title;
    }
    formatter.outputTOC(toc);
    let ejsformatter = new EditorJSFormatter(doc);
    let ejsdoc = {
        time: 0,
        blocks:[]
    };
    ejsformatter.outputDoc(ejsdoc);
    window.editor(ejsdoc);
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