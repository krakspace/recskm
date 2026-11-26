const sheet = {width:612,height:792,x:52,top:36,bottom:724,cols:[171,119,88,130]};
const measure = document.createElement("canvas").getContext("2d");
const serif = '"Times New Roman",Times,serif';
function wrapLines(value, width, size=12, bold=false) {
  measure.font = (bold ? "bold " : "") + size + "px " + serif;
  const fits = s => measure.measureText(s).width <= width;
  const result=[];
  for (const paragraph of (value.trim() || "—").split(/\r?\n/)) {
    let line="";
    for (const word of paragraph.trim().split(/\s+/)) {
      const next=line ? line+" "+word : word;
      if (fits(next)) { line=next; continue; }
      if(line) result.push(line);
      line="";
      for(const char of word) {
        if(line && !fits(line+char)) {result.push(line);line="";}
        line+=char;
      }
    }
    result.push(line);
  }
  return result;
}
function createLayout(data) {
  const {x,top,bottom,cols}=sheet, width=cols.reduce((a,b)=>a+b);
  const [label,no,rev]=cols, content=width-label, bodySize=12, leading=14.3;
  const pages=[]; let page, y;
  const line=(str,size=12,bold=false,underline=false)=>({str,size,bold,underline});
  const lines=(str,w,size=12,bold=false)=>wrapLines(str,w,size,bold).map(t=>line(t,size,bold));
  function cell(left,t,w,h,items=[],opt={}) {
    const align=opt.align || "center", step=opt.leading || 14.3;
    const yy=opt.yText ?? (t+(h-items.length*step)/2+step*.78);
    const xx=align==="center"?left+w/2:left+7;
    page.cells.push({left,t,w,h,items:items.flatMap((item,i)=>(item.runs || [item]).map(run=>({...run,x:xx+(run.dx||0),y:yy+i*step,align})))});
    return page.cells.at(-1);
  }
  function newPage() {
    page={cells:[]}; pages.push(page);
    const title=lines(data.title.toLocaleUpperCase("id"),content-18,14,true);
    const unit=lines(data.unit,label-18,11.5,true);
    const orgLogo=data.organization.trim().toLowerCase()==="pt krakatau medika";
    const org=orgLogo?[]:lines(data.organization,label-20,13,true);
    const meta=[lines(data.number,no-10,11.5),lines(data.revision,rev-10,11.5),[line("0/0",11.5)]];
    const h2=Math.max(42,...meta.map(arr=>(arr.length+1)*14+6));
    const h1=Math.max(57,title.length*16+12,(orgLogo?52:org.length*15+8)+unit.length*14+10-h2);
    const leftCell=cell(x,top,label,h1+h2,unit,{leading:14,yText:top+h1+h2-7-(unit.length-1)*14});
    if(orgLogo)page.logo={x:x+(label-114)/2,y:top+6,w:114,h:47.8};
    else leftCell.items.push(...org.map((a,i)=>({...a,x:x+label/2,y:top+17+i*15,align:"center"})));
    cell(x+label,top,content,h1,title,{leading:16});
    let cx=x+label;
    ["No. Dokumen","No. Revisi","Halaman"].forEach((s,i)=>{
      const c=cell(cx,top+h1,cols[i+1],h2,[line(s+" :"),...meta[i]]);
      if(i===2)page.pageLine=c.items.at(-1);
      cx+=cols[i+1];
    });
    y=top+h1+h2;
  }
  newPage();
  for(const [key,caption] of sections[1].fields) {
    const all = key === "procedure"
      ? procedureLines(data.procedure, content-14, bodySize)
      : (data[key].trim() || "—").split(/\r?\n/).flatMap(p => p.trim() ? lines(p,content-14,bodySize) : [line("")]);
    let offset=0;
    while(offset<all.length) {
      let capacity=Math.floor((bottom-y-20)/leading);
      if(capacity<Math.min(2,all.length-offset)){newPage();capacity=Math.floor((bottom-y-20)/leading);}
      const part=all.slice(offset,offset+capacity);
      const h=Math.max(44,part.length*leading+20);
      const captionText=caption.toUpperCase();
      cell(x,y,label,h,[line(captionText,12,true)],{yText:y+20});
      cell(x+label,y,content,h,part,{align:"left",leading,yText:y+20});
      y+=h;offset+=part.length;
    }
  }
  pages.forEach((p,i)=>{p.pageLine.str=(i+1)+"/"+pages.length;});
  return pages;
}
const xmlNS="http://www.w3.org/2000/svg";
function svgNode(tag,attrs) {
  const node=document.createElementNS(xmlNS,tag);
  for(const [name,val] of Object.entries(attrs))node.setAttribute(name,val);
  return node;
}
function renderSheet(page) {
  const svg=svgNode("svg",{viewBox:"0 0 612 792",role:"img","aria-label":"Pratinjau SOP halaman "+page.pageLine.str});
  for(const c of page.cells) {
    svg.append(svgNode("rect",{x:c.left,y:c.t,width:c.w,height:c.h,fill:"white",stroke:"#202020","stroke-width":.65}));
    for(const a of c.items) {
      const tx=svgNode("text",{x:a.x,y:a.y,"font-family":serif,"font-size":a.size,"xml:space":"preserve","font-style":a.italic?"italic":"normal","font-weight":a.bold?700:400,"text-anchor":a.align==="center"?"middle":"start","text-decoration":a.underline?"underline":"none"});
      tx.textContent=a.str;svg.append(tx);
    }
  }
  if(page.logo){const a=page.logo;svg.append(svgNode("image",{x:a.x,y:a.y,width:a.w,height:a.h,href:logoData}));}
  return svg;
}
