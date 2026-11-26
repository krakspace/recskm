const glyphs={
  layers:'<path d="m3 7 9-4 9 4-9 4z" fill="currentColor" stroke="none"/><path d="m3 11 9 4 9-4m-18 5 9 4 9-4"/>',
  grid:'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
  help:'<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.7 2.2q-1.2.7-1.2 2.1m0 3h.01"/>',
  download:'<path d="M12 3v12m-5-5 5 5 5-5M4 16v4h16v-4"/>',
  down:'<path d="m6 9 6 6 6-6"/>',file:'<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6m-6 3h5"/>',
  edit:'<path d="M12 4H5v16h14v-8M10 14l1-4 7-7 3 3-7 7z"/>',
  id:'<rect x="2.5" y="5" width="19" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M5.5 15q3-4 6 0M14 10h4m-4 4h3"/>',
  list:'<path d="M9 6h11M9 12h11M9 18h11M4 6h.5M4 12h.5M4 18h.5"/>',
  sign:'<path d="m5 16 1-4 10-9 3 3L9 15zM3 20h18"/>',
  eye:'<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="2.7"/>',
  expand:'<path d="M4 9V4h5m6 0h5v5m0 6v5h-5M9 20H4v-5"/>',
  left:'<path d="m14 6-6 6 6 6"/>',right:'<path d="m10 6 6 6-6 6"/>',
  x:'<path d="m6 6 12 12M18 6 6 18"/>',
  check:'<path d="m8.5 12 2.4 2.5 4.8-5"/><circle cx="12" cy="12" r="9"/>'
};
const get=id=>document.getElementById(id);
function icon(name){return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">'+glyphs[name]+'</svg>';}
document.querySelectorAll("[data-icon]").forEach(el=>{el.outerHTML=icon(el.dataset.icon);});
const key = "sysman.sop.v2:" + location.pathname.replace(/[^/]*$/, "");
let data = {...example, procedure:plainProcedure(example.procedure, true)};
let step=0, pageIndex=0, pages=[], timer, feedbackTimer, procedureField;
try {
  const draft = JSON.parse(localStorage.getItem(key) ?? localStorage.getItem("sysman.sop.v1"));
  if (draft) {
    for (const section of sections) for (const [id, , , , max] of section.fields) {
      if (id !== "procedure" && typeof draft[id] === "string") data[id] = draft[id].slice(0, max || 30000);
    }
    if (typeof draft.procedure === "string") data.procedure = plainProcedure(draft.procedure);
    else if (Array.isArray(draft.procedure?.ops) && draft.procedure.ops.every(op => typeof op?.insert === "string")) {
      data.procedure = draft.procedure;
    }
  }
} catch {}
function save(){
  try{localStorage.setItem(key,JSON.stringify(data));get("save").innerHTML=icon("check")+"Tersimpan lokal";}
  catch{get("save").textContent="Penyimpanan lokal nonaktif";}
}
function displayPage(){
  pageIndex=Math.min(Math.max(0,pageIndex),pages.length-1);
  get("paper").replaceChildren(renderSheet(pages[pageIndex]));
  document.querySelectorAll(".pagecount").forEach(el=>{el.textContent=(pageIndex+1)+" / "+pages.length;});
  document.querySelectorAll("[data-page]").forEach(el=>{el.disabled=el.dataset.page==="-1"?pageIndex===0:pageIndex===pages.length-1;});
}
function refresh(){pages=createLayout(data);displayPage();}
function notify(message){get("feedback").textContent=message;get("feedback").hidden=false;clearTimeout(feedbackTimer);feedbackTimer=setTimeout(()=>get("feedback").hidden=true,4600);}
function changed(id, value, element) {
  data[id] = value;
  element.removeAttribute("aria-invalid");
  element.removeAttribute("aria-describedby");
  element.closest(".field")?.querySelector(".invalid")?.remove();
  save();
  clearTimeout(timer);
  timer = setTimeout(refresh, 100);
}

function mountProcedure(wrap, label) {
  if (procedureField) return procedureField;
  const editorBox = document.createElement("div");
  editorBox.className = "richtext";
  const host = document.createElement("div");
  editorBox.append(host);
  label.id = "procedure-label";
  wrap.append(label, editorBox);
  const editor = new Quill(host, {
    theme:"snow", formats:["bold","italic","underline","list"],
    modules:{toolbar:[["bold","italic","underline"],[{list:"ordered"},{list:"bullet"}],["clean"]]}
  });
  editor.root.id = "procedure";
  editor.root.setAttribute("role","textbox");
  editor.root.setAttribute("aria-labelledby",label.id);
  editor.root.setAttribute("aria-multiline","true");
  editor.root.setAttribute("aria-required","true");
  label.onclick = () => editor.focus();
  const labels = {bold:"Tebal", italic:"Miring", underline:"Garis bawah", ordered:"Daftar bernomor", bullet:"Daftar berpoin", clean:"Hapus format"};
  for (const button of editorBox.querySelectorAll(".ql-toolbar button")) {
    const name = labels[button.value || button.classList[0].slice(3)];
    button.title = name; button.setAttribute("aria-label",name);
  }
  editor.setContents(data.procedure,"silent");
  editor.on("text-change", () => changed("procedure",{ops:editor.getContents().ops},editor.root));
  procedureField = wrap;
  return wrap;
}

function switchStep(i,focus=false){
  step=i;
  const section=sections[i],form=get("fields");
  form.replaceChildren();
  const title=document.createElement("h3");title.textContent=section.title;
  const grid=document.createElement("div");grid.className="fieldgrid";form.append(title,grid);
  for(const [id,name,type,required,max] of section.fields){
    const wrap=document.createElement("div");wrap.className="field"+(["number","revision"].includes(id)?"":" wide");
    const label=document.createElement("label");label.htmlFor=id;label.textContent=name;
    if(required){const star=document.createElement("em");star.textContent=" *";star.setAttribute("aria-hidden","true");label.append(star);}
    if(type==="quill"){grid.append(mountProcedure(wrap,label));continue;}
    const el=document.createElement(type==="textarea"?"textarea":"input");
    if(el.tagName==="INPUT")el.type=type;else el.rows=step===1?3:2;
    el.id=id;el.name=id;el.required=required;el.maxLength=max||30000;el.value=data[id];
    el.addEventListener("input",()=>{
      changed(id,el.value,el);
    });
    wrap.append(label,el);grid.append(wrap);
  }
  document.querySelectorAll("[data-step]").forEach(b=>{if(+b.dataset.step===i)b.setAttribute("aria-current","step");else b.removeAttribute("aria-current");});
  get("stepLabel").textContent="Bagian "+(i+1)+" dari "+sections.length;
  get("next").textContent=["Lanjut ke isi SOP","Unduh PDF"][i];
  if(focus)form.querySelector("input,textarea").focus();
}
document.querySelectorAll("[data-step]").forEach(b=>b.onclick=()=>switchStep(+b.dataset.step));
get("next").onclick=()=>{if(step===0)switchStep(1,true);else downloadPDF();};
get("fields").onsubmit=e=>e.preventDefault();
document.querySelectorAll("[data-page]").forEach(b=>b.onclick=()=>{pageIndex+=+b.dataset.page;displayPage();document.querySelector(".tray").scrollTop=0;});
// Same-origin Blade hosts can read the draft and request the PDF as a Blob.
window.SopForm = Object.freeze({
  getDraft: () => structuredClone(data),
  async createPdf() {
    const snapshot = this.getDraft();
    return generatePDF(createLayout(snapshot), snapshot);
  }
});
let previewUrl, previewRequest = 0;
get("expand").onclick = async () => {
  const request = ++previewRequest;
  get("large").showModal();
  get("pdfStatus").hidden = false;
  get("pdfStatus").textContent = "Menyiapkan PDF…";
  try {
    const blob = await window.SopForm.createPdf();
    if (request !== previewRequest || !get("large").open) return;
    previewUrl = URL.createObjectURL(blob);
    const target = previewUrl + "#page=" + (pageIndex+1);
    get("pdfViewer").src = target;
    get("pdfTab").href = target;
    get("pdfTab").hidden = get("pdfViewer").hidden = false;
    get("pdfStatus").hidden = true;
  } catch {
    if(request === previewRequest) get("pdfStatus").textContent = "PDF gagal dibuat. Tutup pratinjau dan coba kembali.";
  }
};
get("large").addEventListener("close", () => {
  ++previewRequest;
  get("pdfViewer").hidden = get("pdfTab").hidden = true;
  get("pdfViewer").removeAttribute("src");
  get("pdfTab").removeAttribute("href");
  if (previewUrl) URL.revokeObjectURL(previewUrl);
  previewUrl = null;
});
get("guide").onclick=()=>get("helpDialog").showModal();
document.querySelectorAll(".close").forEach(b=>b.onclick=()=>b.closest("dialog").close());
document.querySelectorAll("dialog").forEach(dialog=>dialog.addEventListener("click",e=>{if(e.target===dialog){const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)dialog.close();}}));
function valid(){
  for(const [i,s] of sections.entries())for(const [id,name,,required] of s.fields){
    if(required&&!(id==="procedure" ? procedureText(data.procedure) : data[id]).trim()){
      switchStep(i);const el=get(id);el.setAttribute("aria-invalid","true");
      const msg=document.createElement("span");msg.className="invalid";msg.id=id+"-error";msg.textContent=name+" wajib diisi.";
      el.closest(".field").append(msg);el.setAttribute("aria-describedby",msg.id);el.focus();return false;
    }
  }
  return true;
}
let busy=false;
async function downloadPDF(){
  if(busy || !valid())return;
  busy=true; get("export").disabled=true; get("next").disabled=true;
  try {
    const snapshot=structuredClone(data);
    const blob=await generatePDF(createLayout(snapshot),snapshot);
    const link=document.createElement("a"),url=URL.createObjectURL(blob);
    const base=[snapshot.number,snapshot.title,"Rev "+(snapshot.revision||"0")].join(" - ").replace(/[<>:"/\\|?*\x00-\x1F]/g,"-").slice(0,170);
    link.download=base+".pdf";link.href=url;document.body.append(link);link.click();link.remove();
    setTimeout(()=>URL.revokeObjectURL(url),60000);notify("Dokumen PDF siap diunduh.");
  } catch(err){console.error(err);notify("Unduhan gagal. Silakan coba kembali.");}
  finally{busy=false;get("export").disabled=false;get("next").disabled=false;}
}
get("export").onclick=downloadPDF;
switchStep(0);refresh();save();
