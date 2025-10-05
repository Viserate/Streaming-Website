// public/assets/nav.js
(function(){
  function ready(fn){ if(document.readyState!=='loading'){fn()} else document.addEventListener('DOMContentLoaded',fn) }
  function build(items){
    var ul = document.createElement('ul'); ul.className='site-nav-list';
    items.forEach(function(it){
      var li=document.createElement('li'); li.className='site-nav-item';
      var a=document.createElement('a'); a.textContent=it.label; a.href=it.url;
      if(it.target){ a.target=it.target; }
      li.appendChild(a); ul.appendChild(li);
    });
    return ul;
  }
  function mount(where, ul){ if(!where) return; where.innerHTML=''; where.appendChild(ul); }
  ready(function(){
    fetch('/api/nav.php',{credentials:'same-origin'})
      .then(function(r){return r.json()})
      .then(function(data){
        if(!data || !data.items) return;
        var ul = build(data.items);
        var spot = document.querySelector('nav#site-nav') || document.querySelector('[data-nav="main"]');
        if(spot) mount(spot, ul);
      })
      .catch(function(){});
  });
})();