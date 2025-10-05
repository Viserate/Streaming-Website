(function(){
  function $all(sel,root){return Array.from((root||document).querySelectorAll(sel));}
  function toast(msg){
    var d=document.createElement('div'); d.textContent=msg;
    Object.assign(d.style,{position:'fixed',bottom:'16px',right:'16px',background:'#111',color:'#fff',
      padding:'8px 12px',borderRadius:'6px',zIndex:9999,opacity:'0',transition:'opacity .15s'});
    document.body.appendChild(d); requestAnimationFrame(function(){d.style.opacity='1';});
    setTimeout(function(){d.style.opacity='0'; setTimeout(function(){d.remove();},200);},1200);
  }
  function copyText(t){
    return (navigator.clipboard&&navigator.clipboard.writeText)
      ? navigator.clipboard.writeText(t).then(function(){toast('Copied');})
      : new Promise(function(res){var ta=document.createElement('textarea'); ta.value=t; document.body.appendChild(ta); ta.select(); try{document.execCommand('copy');}catch(e){} document.body.removeChild(ta); res(); toast('Copied');});
  }
  function normalizePath(v){
    if(!v) return null;
    v = String(v).replace(/\\/g,'/');
    // strip scheme+host
    v = v.replace(/^https?:\/\/[^/]+/i,'');
    // if it contains /admin/uploads or /uploads, keep from there
    var m = v.match(/\/(?:admin\/)?uploads\/.*/i);
    if(m){ return m[0]; }
    // else return as relative; server will resolve
    return v.replace(/^\//,''); // relative
  }
  function mintAndCopy(value){
    var p = normalizePath(value);
    if(!p){ toast('Invalid path'); return; }
    fetch('/admin/media/share_url.php?path='+encodeURIComponent(p),{credentials:'same-origin'})
      .then(r=>r.json()).then(j=>{
        if(j && j.ok){ copyText(location.origin + j.url); }
        else { toast(j && j.error ? j.error : 'Error'); }
      }).catch(()=>toast('Error'));
  }
  function addCopyToInputs(){
    $all('input[type="text"], input[readonly], textarea').forEach(function(el){
      if(el.dataset._copy) return;
      var v = el.value || el.textContent || '';
      if(!/(?:^|\/)(?:admin\/)?uploads\//i.test(v)) return;
      var btn = document.createElement('button');
      btn.type='button'; btn.textContent='Copy URL'; btn.className='btn btn-secondary ms-2 btn-sm';
      btn.addEventListener('click', function(e){e.preventDefault(); e.stopPropagation(); mintAndCopy(v); });
      el.insertAdjacentElement('afterend', btn); el.dataset._copy='1';
    });
  }
  function addCopyToThumbs(){
    $all('img').forEach(function(img){
      var src = img.getAttribute('src')||'';
      if(!/(?:^|\/)(?:admin\/)?uploads\//i.test(src)) return;
      var parent = img.parentElement || img;
      var pos = getComputedStyle(parent).position; if(pos==='static') parent.style.position='relative';
      if(parent.querySelector('.copy-url-overlay')) return;
      var b=document.createElement('button'); b.type='button'; b.className='copy-url-overlay btn btn-light btn-xs';
      b.textContent='Copy URL';
      Object.assign(b.style,{position:'absolute', right:'6px', bottom:'6px', fontSize:'12px', padding:'2px 6px'});
      b.addEventListener('click', function(e){e.preventDefault(); e.stopPropagation(); mintAndCopy(src);});
      parent.appendChild(b);
    });
  }
  document.addEventListener('DOMContentLoaded', function(){
    addCopyToInputs();
    addCopyToThumbs();
  });
})();