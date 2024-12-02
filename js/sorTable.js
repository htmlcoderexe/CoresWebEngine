function CellComparerNumber(a, b)
{
    return a[0]-b[0];
}

function CellComparerString(a,b)
{
    return a[0].localeCompare(b[0]);
}

function SetSortingIndicator(dir)
{
    this.dataset.sortingDir=dir;
}

function DoSort()
{
    let sourceTable = this.closest('table');
    let sortMode = this.dataset.sortMode;
    let sortingDir = this.dataset.sortingDir;
    if(sortMode==="none")
        return;
    if(!sortMode)
        sortMode="string";
    let rows={};
    let pointers=[];
    let thisHeader=this;
    sourceTable.querySelectorAll("tbody tr").forEach((row,row_number)=>
    {
        let cell=row.children[thisHeader.cellIndex];
        let value=cell.dataset.timestamp ? cell.dataset.timestamp : cell.textContent;
        let key=[value,row_number];
        rows[key]=row;
        pointers.push(key);
    });
    switch(sortMode)
    {
        case "num":
        {
            pointers.sort(CellComparerNumber);
            break;
        }
        default:
        {
            pointers.sort(CellComparerString);
            break;
        }
    }
    let newDir=sortingDir==="up"?"down":"up";
    if(newDir==="up")
        pointers.reverse();
    sourceTable.querySelectorAll("th").forEach((element)=>{
        element.dataset.sortingDir="neither";
        
    });
    thisHeader.dataset.sortingDir=newDir;
    let tbody=sourceTable.getElementsByTagName("tbody")[0];
    tbody.innerHTML="";
    pointers.forEach((p)=>{
        tbody.appendChild(rows[p]);
    });
}


function WireUpSorTable(headercell)
{
    headercell.addEventListener("click",DoSort);
}

window.addEventListener("load",function(){
    document.querySelectorAll(".sortable th").forEach(WireUpSorTable);
});