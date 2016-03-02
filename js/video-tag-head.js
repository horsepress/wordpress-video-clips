var VIDEOTAG = (function () {
    "use strict";
    var hotKeySpec = function(spec){
        spec = spec || {};
        spec.key = spec.key || undefined;
        spec.modifiers = spec.modifiers || {ctrl:false,shift:false,alt:false, meta:false};
        spec.handler = spec.handler || function(player,options){};
        
        return {
            key: function(e) {
                //console.log(e.which);
                return (
                    (e.which === spec.key ) && 
                    (!!spec.modifiers.ctrl === !!e.ctrlKey) &&
                    (!!spec.modifiers.shift === !!e.shiftKey) &&
                    (!!spec.modifiers.alt === !!e.altKey) &&
                    (!!spec.modifiers.meta === !!e.metaKey)
                );
            },
            handler: spec.handler
        };
    };	

    var skip = function(change){
        return function(player){
            var newTime = player.currentTime() + (change);
            // The flash player tech will allow you to seek into negative
            // numbers and break the seekbar, so try to prevent that.
            if (newTime <= 0) {newTime = 0;}
            player.currentTime(newTime);
        };
    };		
		
    var speedChange = function(change){
        return function(player){
            var speed = player.playbackRate() + change;
            speed = speed.toFixed(2);
            if (speed >= 0 && speed <= 50){
                player.playbackRate(speed);
            }
        };
    };
			
    var hotkeyOptions = {
        volumeStep: 0.1,
        seekStep: 5,
        speedStep: 0.1,
        alwaysCaptureHotkeys: true,
        customKeys: {
            slowDownArBr: hotKeySpec({key:188,handler:speedChange(-0.1)})	    // 188 = <
            ,speedUp: hotKeySpec({key:190,handler:speedChange(0.1)})	 	// 190 = >
            ,bigSlowDown: hotKeySpec({key:188,modifiers:{shift:true}, handler:speedChange(-0.5)})					
            ,bigSpeedUp: hotKeySpec({key:190,modifiers:{shift:true}, handler:speedChange(0.5)})
            ,normalSpeedN: hotKeySpec({key:78,handler:function(p){p.playbackRate(1);}})    // 78 = n
            ,smallSkipBackZ: hotKeySpec({key:90,handler:skip(-2)})    // 90 = z
            ,vSmallSkipBack: hotKeySpec({key:90,handler:skip(-0.5),modifiers:{shift:true}})   
            ,vvSmallSkipBack: hotKeySpec({key:90,handler:skip(-0.1),modifiers:{ctrl:true}})   
            ,vvvSmallSkipBack: hotKeySpec({key:90,handler:skip(-0.1),modifiers:{ctrl:true,shift:true}})   
            ,smallSkipForward: hotKeySpec({key:88,handler:skip(2)})    // 88 =x 
            ,vSmallSkipForward: hotKeySpec({key:88,handler:skip(0.5),modifiers:{shift:true}})    
            ,vvSmallSkipForward: hotKeySpec({key:88,handler:skip(0.1),modifiers:{ctrl:true}})    
            ,vvvSmallSkipForward: hotKeySpec({key:88,handler:skip(0.02),modifiers:{ctrl:true,shift:true}})    
            ,startLoopA: hotKeySpec({key:65,handler:function(p){p.abLoopPlugin.setStart();}})
            ,endLoopB: hotKeySpec({key:66,handler:function(p){p.abLoopPlugin.setEnd();}})
            ,enableLoopL:hotKeySpec({key:76,handler:function(p){p.abLoopPlugin.toggle();}})
            ,goToStartKeySqBr:hotKeySpec({key:219,handler:function(p){p.abLoopPlugin.goToStart();}})
            ,goToEndSqBr:hotKeySpec({key:221,handler:function(p){p.abLoopPlugin.goToEnd();}})
            ,togglePauseOnLoopK: hotKeySpec({key:75,handler:function(p){p.abLoopPlugin.cyclePauseOnLooping();}})
        }
    };

    var activateClip = function(playerid,start,end){
        var player = videojs.players[playerid];
        if (player === undefined || player.abLoopPlugin === undefined){return true;}
    
        var a = player.abLoopPlugin;
        a.setStart(start).setEnd(end).goToStart().enable().player.play();
        return false;
    };    
    var activateClipByFragment = function(playerid,urlFragment){
        var player = videojs.players[playerid];
        if (player === undefined || player.abLoopPlugin === undefined){return true;}
    
        var a = player.abLoopPlugin;
        //a.applyUrlFragment(urlFragment).goToStart().enable().player.play();
        a.applyUrlFragment(urlFragment).playLoop();
        return false;
    };
    
    var latestVid = 0;

    var getOrCreateVideoDiv = function(id,url,fragment){
        var r = videojs.players[id] || createVideoDiv(id,url); 
        if (r.abLoopPlugin){
            r.abLoopPlugin
            .applyUrlFragment(fragment)
            .setOptions({'pauseAfterLooping':true})
            .playLoop()
            ;
        }
        return false;
    }
    var player;
    var createVideoDiv = function(id,url){
        id = id || 'vid_' + (++latestVid);
		var div = document.createElement('div');
		div.id = 'div_' + id;
		div.setAttribute('class', 'floating');
		div.setAttribute('draggable', "true");
		div.setAttribute('ondragstart', 'VIDEOTAG.drag_start(event)');
        // videojs(oldPlayer).dispose();
		div.innerHTML = '' + 
            '<button onclick="videojs(\'' + id + '\').dispose();this.parentElement.remove();">CLOSE</button>' +
            videoHTML(id, url) +
            '';
		document.body.appendChild(div);
        
        //div.style.width = "400px";
        videojs(id).ready(function() {             
            this.hotkeys(VIDEOTAG.hotkeyOptions); 
            //needed to fix glitch in youtube player
            this.controlBar.playbackRateMenuButton.updateLabel();
        }); 
        return videojs.players[id];
    };

    var videoHTML = function(id,url) {

        id = id || 'vid_';
        url = url || '';
        
        var re = /youtube\.com/i;
        var techOrder = re.test(url) ? ' ,"techOrder": ["youtube"] ' : '';
        var type = re.test(url) ? 'video/youtube' : 'video/mp4';
              
        var h = '';
        h += '<video controls id="'+ id +'" class="video-js vjs-default-skin" ';
        h += 'data-setup=\'{                                            ';
        h += '    "fluid": true                                         ';
        h += '    ,"playbackRates": [0.1, 0.25, 0.5, 1, 2, 5]            ';
        h += '    ,"controls":true                                      ';
        h += '    ,"preload":"metadata"                                 ';
        h += '    ,"plugins": {                                         ';
        h += '        "abLoopPlugin" : {}                               ';
        h += '    }                                                     ';
        h += techOrder;
        h += '}\'>                                                      ';
        h += '    <source src="' + url + '" type="' + type + '" />                ';
        h += '</video>                                                  ';
    
        return h;
    };

    var drag_start = function (event) {
        var style = window.getComputedStyle(event.target, null);
        var str =  (parseInt(style.getPropertyValue("left")) - event.clientX) + ',' + 
                   (parseInt(style.getPropertyValue("top"))  - event.clientY) + ',' + 
                    event.target.id;
        event.dataTransfer.setData("Text",str);
    }; 

    var  drop = function (event) {
        var offset = event.dataTransfer.getData("Text").split(',');
        var dm = document.getElementById(offset[2]);
        dm.style.left = (event.clientX + parseInt(offset[0],10)) + 'px';
        dm.style.top = (event.clientY + parseInt(offset[1],10)) + 'px';
        event.preventDefault();
        return false;
    };

    var drag_over = function (event){
        event.preventDefault();
        return false;
    };    
    
    return {
        activateClip: activateClipByFragment
        ,hotkeyOptions: hotkeyOptions
        ,drag_over:drag_over
        ,drop:drop
        ,drag_start:drag_start
        ,createVideoDiv:createVideoDiv
        ,getOrCreateVideoDiv: getOrCreateVideoDiv
        ,player:player
    };
}());


document.addEventListener("DOMContentLoaded", function() {
  document.body.ondragover = VIDEOTAG.drag_over;
  document.body.ondrop = VIDEOTAG.drop;
});
