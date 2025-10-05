// public/assets/frontend-nav.js
(function(){
  function ready(fn){ if(document.readyState!=='loading'){fn()} else document.addEventListener('DOMContentLoaded',fn) }
  function buildList(items){
    var ul = document.createElement('ul'); ul.className='site-nav-list';
    items.forEach(function(it){
      var li = document.createElement('li'); li.className='site-nav-item';
      var a = document.createElement('a'); a.textContent = it.label; a.href = it.url;
      if (it.target) a.target = it.target;
      li.appendChild(a);
      if (it.children && it.children.length){
        var sub = buildList(it.children); sub.classList.add('site-nav-sub');
        li.appendChild(sub);
      }
      ul.appendChild(li);
    });
    return ul;
  }
  function mount(target, items){
    if (!target) return;
    var ul = buildList(items);
    target.innerHTML='';
    target.appendChild(ul);
  }
  ready(function(){
    fetch('/api/frontend-nav.php',{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(data){
      if (!data || !data.items) return;
      var spot = document.querySelector('nav#site-nav') || document.querySelector('[data-nav="main"]');
      if (spot) mount(spot, data.items);
    }).catch(function(e){});
  });
})();