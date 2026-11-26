// Quill stores inline styles on text and list types on terminating newlines.
function plainProcedure(text, formatExample = false) {
  const delta = new (Quill.import("delta"))();
  for (const line of text.split(/\r?\n/)) {
    if (formatExample && /^\d+\. /.test(line)) {
      delta.insert(line.replace(/^\d+\. /, "")).insert("\n", {list:"ordered"});
    } else {
      delta.insert(line, formatExample && line.endsWith(":") ? {bold:true} : undefined).insert("\n");
    }
  }
  return {ops:delta.ops};
}

function procedureText(delta) {
  return delta.ops.map(op => op.insert).join("");
}

function procedureParagraphs(delta) {
  const paragraphs = [];
  let spans = [], number = 0;
  for (const {insert, attributes = {}} of delta.ops) {
    const parts = insert.split("\n");
    parts.forEach((text, i) => {
      if (i) {
        const ordered = attributes.list === "ordered";
        number = ordered ? number + 1 : 0;
        const marker = ordered ? number + "." : attributes.list === "bullet" ? "•" : "";
        paragraphs.push({spans, marker});
        spans = [];
      }
      if (text) spans.push({str:text, bold:!!attributes.bold, italic:!!attributes.italic, underline:!!attributes.underline});
    });
  }
  if (spans.length || !paragraphs.length) paragraphs.push({spans, marker:""});
  return paragraphs;
}

// Wrap a paragraph while retaining each fragment's style and horizontal position.
function procedureLines(delta, width, size) {
  const rows = [];
  function textWidth(text, style) {
    measure.font = (style.italic ? "italic " : "") + (style.bold ? "bold " : "") + size + "px " + serif;
    return measure.measureText(text).width;
  }
  for (const {spans, marker} of procedureParagraphs(delta)) {
    const indent = marker ? Math.max(17, textWidth(marker + " ", {}) + 2) : 0;
    let x = indent, runs = marker ? [{str:marker, dx:0, size}] : [];
    const flush = () => {rows.push({runs}); runs = []; x = indent;};
    function add(text, style) {
      const last = runs.at(-1);
      if (last && last.style === style) last.str += text;
      else runs.push({...style, str:text, dx:x, size, style});
      x += textWidth(text, style);
    }
    let word = [], spaces = [];
    function putWord() {
      if (!word.length) return;
      const wordWidth = word.reduce((sum, part) => sum + textWidth(part.str, part.style), 0);
      const gapWidth = spaces.reduce((sum, part) => sum + textWidth(part.str, part.style), 0);
      if (x > indent && x + gapWidth + wordWidth > width) flush();
      if (x > indent) for (const part of spaces) add(part.str, part.style);
      for (const part of word) {
        if (wordWidth <= width - indent) add(part.str, part.style);
        else for (const ch of part.str) {
          if (x > indent && x + textWidth(ch, part.style) > width) flush();
          add(ch, part.style);
        }
      }
      word = []; spaces = [];
    }
    for (const style of spans) {
      for (const token of style.str.match(/\s+|\S+/gu) || []) {
        if (/^\s/u.test(token)) {putWord(); spaces.push({str:token, style});}
        else word.push({str:token, style});
      }
    }
    putWord(); flush();
  }
  return rows;
}
