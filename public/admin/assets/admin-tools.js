(function(){
  function $(sel, root){ return (root||document).querySelector(sel); }
  function $all(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }

  async function fetchShareUrl(path){
    try{
      const res = await fetch('/admin/media/share_url.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({path})
      });
      const j = await res.json();
      if(j && j.ok) return j.url;
    }catch(e){}
    return null;
  }

  function addCopyButton(input){
    if(!input || input.dataset.copyBound) return;
    input.dataset.copyBound = '1';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Copy';
    btn.className = 'btn btn-outline-secondary btn-sm ms-2';
    btn.addEventListener('click', async () => {
      input.select();
      try{ await navigator.clipboard.writeText(input.value); }catch(e){}
      btn.textContent = 'Copied!';
      setTimeout(()=>btn.textContent='Copy', 1200);
    });
    // Try to place next to input
    const wrap = document.createElement('div');
    wrap.className = 'd-flex align-items-center';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    wrap.appendChild(btn);
  }

  async function upgradeMediaUrlField(){
    // Heuristics: find a label that reads "URL" then the next input,
    // or fallback to the first text input with a likely image path.
    let urlInput = null;
    const labels = $all('label');
    for(const lb of labels){
      if((lb.textContent||'').trim().toLowerCase() === 'url'){
        const cand = lb.parentElement && lb.parentElement.querySelector('input[type="text"],input.form-control');
        if(cand){ urlInput = cand; break; }
      }
    }
    if(!urlInput){
      urlInput = $('input[type="text"].form-control');
    }
    if(!urlInput) return;

    // Try to use the preview image src if present
    let preview = document.querySelector('img[src]');
    let candidatePath = (preview && preview.getAttribute('src')) || urlInput.value || '';
    if(!candidatePath) return;

    const share = await fetchShareUrl(candidatePath);
    if(share){
      urlInput.value = share;
      addCopyButton(urlInput);
    }
  }

  document.addEventListener('DOMContentLoaded', upgradeMediaUrlField);
})();