const libraries=new Map();
function loadLibrary(path) {
  if(!libraries.has(path)) libraries.set(path,new Promise((resolve,reject)=>{
    const tag=document.createElement("script");
    tag.src=path;tag.onload=resolve;tag.onerror=()=>{libraries.delete(path);tag.remove();reject(new Error("Pustaka ekspor gagal dimuat"));};
    document.head.append(tag);
  }));
  return libraries.get(path);
}
function logoBytes() {
  return Uint8Array.from(atob(logoData.split(",")[1]),c=>c.charCodeAt(0));
}
async function generatePDF(pages,data) {
  await loadLibrary(window.SOP_PDF_LIB_URL || "assets/pdf-lib.min.js");
  const {PDFDocument,StandardFonts,rgb}=PDFLib;
  const pdf=await PDFDocument.create();
  pdf.setTitle(data.title);pdf.setAuthor(data.organization);pdf.setCreator("Sysman");
  const normal=await pdf.embedFont(StandardFonts.TimesRoman),bold=await pdf.embedFont(StandardFonts.TimesRomanBold);
  const italic=await pdf.embedFont(StandardFonts.TimesRomanItalic),boldItalic=await pdf.embedFont(StandardFonts.TimesRomanBoldItalic);
  const logo=await pdf.embedPng(logoBytes());
  for(const p of pages) {
    const page=pdf.addPage([612,792]);
    for(const c of p.cells) {
      page.drawRectangle({x:c.left,y:792-c.t-c.h,width:c.w,height:c.h,borderWidth:.65,borderColor:rgb(.12,.12,.12)});
      for(const a of c.items) {
        if(!a.str)continue;
        const font=a.italic ? (a.bold ? boldItalic : italic) : (a.bold ? bold : normal);
        let w;
        try {w=font.widthOfTextAtSize(a.str,a.size);}
        catch {
          // Preserve text outside the built-in PDF font's character set.
          const canvas=document.createElement("canvas"),scale=4;
          const cssFont=(a.italic?"italic ":"")+(a.bold?"bold ":"")+a.size+"px "+serif;
          measure.font=cssFont;w=measure.measureText(a.str).width+2;
          canvas.width=Math.ceil(w*scale);canvas.height=Math.ceil(a.size*1.6*scale);
          const ctx=canvas.getContext("2d");ctx.scale(scale,scale);ctx.font=cssFont;ctx.fillText(a.str,1,a.size);
          const img=await pdf.embedPng(canvas.toDataURL());
          page.drawImage(img,{x:a.align==="center"?a.x-w/2:a.x,y:792-a.y-(canvas.height/scale-a.size),width:w,height:canvas.height/scale});
          const left=a.align==="center"?a.x-w/2:a.x;
          if(a.underline)page.drawLine({start:{x:left,y:791-a.y},end:{x:left+w,y:791-a.y},thickness:.55});
          continue;
        }
        const left=a.align==="center"?a.x-w/2:a.x;
        page.drawText(a.str,{x:left,y:792-a.y,font,size:a.size});
        if(a.underline)page.drawLine({start:{x:left,y:791-a.y},end:{x:left+w,y:791-a.y},thickness:.55});
      }
    }
    if(p.logo){const a=p.logo;page.drawImage(logo,{x:a.x,y:792-a.y-a.h,width:a.w,height:a.h});}
  }
  return new Blob([await pdf.save()],{type:"application/pdf"});
}
