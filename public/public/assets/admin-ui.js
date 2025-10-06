(function(){
function toast(msg, ok){var t=document.createElement('div');t.className='toast';t.style.background=ok?'#2d7a46':'#8a1f1f';t.textContent=msg;document.body.appendChild(t);setTimeout(function(){t.remove()},2200);}
document.addEventListener('click', function(e){var b=e.target.closest('[data-copy]');if(!b)return;var txt=b.getAttribute('data-copy')||'';navigator.clipboard.writeText(txt).then(function(){toast('Copied ✓',1)},function(){toast('Copy failed',0)});});
})();