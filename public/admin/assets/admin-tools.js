/**
 * Admin tools: fix media URLs and add one-click Copy URL buttons.
 * Works on both the media list and the single image page.
 */
(function () {
  function origin() {
    return (location.origin || (location.protocol + '//' + location.host));
  }

  function toPublicUrl(p) {
    if (!p) return null;
    p = String(p).replace(/\\/g, '/').trim();

    // Already a full URL
    if (/^https?:\/\//i.test(p)) return p;

    // Remove accidental "/admin" prefix before /uploads/
    p = p.replace(/^\/admin(\/uploads\/)/i, '$1');

    // If path contains /uploads/, make it absolute from there
    var idx = p.indexOf('/uploads/');
    if (idx >= 0) {
      var rel = p.slice(idx);
      return origin() + rel;
    }

    // Absolute path already
    if (p[0] === '/') return origin() + p;

    // Fallback: assume it's a file under /uploads/
    return origin() + '/uploads/' + p.replace(/^\.?\//, '');
  }

  function toast(msg) {
    var div = document.createElement('div');
    div.textContent = msg;
    Object.assign(div.style, {
      position: 'fixed',
      right: '16px',
      bottom: '16px',
      background: '#111',
      color: '#fff',
      padding: '8px 12px',
      borderRadius: '6px',
      zIndex: 9999,
      boxShadow: '0 4px 12px rgba(0,0,0,.35)',
      fontSize: '14px',
      opacity: '0',
      transition: 'opacity .15s ease'
    });
    document.body.appendChild(div);
    requestAnimationFrame(function(){ div.style.opacity = '1'; });
    setTimeout(function(){ div.style.opacity = '0'; setTimeout(function(){ div.remove(); }, 200); }, 1200);
  }

  function copy(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text).then(function(){ toast('Copied'); });
    }
    // Fallback for older browsers
    var t = document.createElement('textarea');
    t.value = text;
    document.body.appendChild(t);
    t.select();
    try { document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(t);
    toast('Copied');
    return Promise.resolve();
  }

  function injectCopyAfter(el, url) {
    if (!el || el.dataset.copyInjected === '1') return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Copy URL';
    btn.className = 'btn btn-sm btn-secondary ms-2';
    btn.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      copy(url);
    });
    el.insertAdjacentElement('afterend', btn);
    el.dataset.copyInjected = '1';
  }

  function enhanceUrlInputs() {
    // Look for obvious URL fields
    var candidates = Array.from(document.querySelectorAll('input[type="text"], input[readonly], textarea'));
    candidates.forEach(function(el){
      var v = el.value || el.textContent || '';
      if (!v) return;
      if (!/uploads\//i.test(v) && !/\.png|\.jpg|\.jpeg|\.gif|\.webp|\.svg/i.test(v)) return;

      var url = toPublicUrl(v);
      // Fix the visible value if it looks wrong (e.g., starts with /admin/uploads/...)
      if (url && url !== v) {
        if (el.value !== undefined) el.value = url;
        else el.textContent = url;
      }
      // Add a copy button next to it
      injectCopyAfter(el, url);
    });
  }

  function enhanceThumbnails() {
    // Add a small copy button over thumbnails that live under /uploads/
    var imgs = Array.from(document.querySelectorAll('img'));
    imgs.forEach(function(img){
      var src = img.getAttribute('src') || '';
      if (!/uploads\//i.test(src)) return;
      var url = toPublicUrl(src);

      // Place a button in the nearest positioned container
      var wrap = img.parentElement || img;
      var comp = getComputedStyle(wrap);
      if (comp.position === 'static') wrap.style.position = 'relative';

      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = 'Copy URL';
      b.className = 'btn btn-light btn-xs';
      Object.assign(b.style, {
        position: 'absolute',
        right: '6px',
        bottom: '6px',
        fontSize: '12px',
        padding: '2px 6px',
        borderRadius: '4px',
        boxShadow: '0 2px 6px rgba(0,0,0,.25)'
      });
      b.addEventListener('click', function(e){
        e.preventDefault(); e.stopPropagation();
        copy(url);
      });

      // Avoid duplicates on refresh / SPA-ish behavior
      if (!wrap.querySelector('.copy-url-overlay')) {
        b.classList.add('copy-url-overlay');
        wrap.appendChild(b);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    try { enhanceUrlInputs(); } catch(e){}
    try { enhanceThumbnails(); } catch(e){}
  });
})();
