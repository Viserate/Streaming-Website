(function(){
  var start=Date.now();
  function send(event, extra){
    try {
      var payload=Object.assign({event:event}, extra||{});
      var blob=new Blob([JSON.stringify(payload)],{type:'application/json'});
      navigator.sendBeacon('/api/track.php', blob);
    } catch(e){
      try{
        var xhr=new XMLHttpRequest();
        xhr.open('POST','/api/track.php',true);
        xhr.setRequestHeader('Content-Type','application/json');
        xhr.send(JSON.stringify(Object.assign({event:event}, extra||{})));
      }catch(e2){}
    }
  }
  // page view
  send('page_view');
  // time spent
  window.addEventListener('beforeunload',function(){
    var sec=Math.round((Date.now()-start)/1000);
    if(sec>1800) sec=1800;
    if(sec>0) send('time_spent',{duration:sec});
  });
  // first video play
  var v=document.querySelector('video');
  if(v){
    var fired=false;
    v.addEventListener('play',function(){
      if(!fired){
        var holder=document.querySelector('[data-video-id]');
        var vid=holder?parseInt(holder.getAttribute('data-video-id')||'0',10)||0:0;
        send('video_watch',{video_id:vid});
        fired=true;
      }
    },{once:true});
  }
})();
