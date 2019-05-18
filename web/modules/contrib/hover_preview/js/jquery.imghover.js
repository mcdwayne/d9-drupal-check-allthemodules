/**
 *  jquery.popupt
 *  (c) 2008 Semooh (http://semooh.jp/)
 *
 *  Dual licensed under the MIT (MIT-LICENSE.txt)
 *  and GPL (GPL-LICENSE.txt) licenses.
 *
 *
 * 9/4/10  by r043v (noferov at gmail) > adding preload option
 * 14/4/10 by r043v fix fade bug
 * 11/5/11 by Rob Loach: Don't fade out the original image.
 *
 **/
(function($){
        $.fn.extend({
                imghover: function(opt){
                        return this.each(function() {
        opt = $.extend({
            prefix: '',
            suffix: '_o',
            src: '',
            btnOnly: true,
            fade: false,
            fadeSpeed: 500,
            preload: false
          }, opt || {});

        var node = $(this);
        if(!node.is('img')&&!node.is(':image')){
          var sel = 'img,:image';
          if (opt.btnOnly) sel = 'a '+sel;
          node.find(sel).imghover(opt);
          return;
        }

        var orgImg = node.attr('src');

        var hoverImg;
        if(opt.src){
          hoverImg = opt.src;
        }else{
          hoverImg = orgImg;
          if(opt.prefix){
            var pos = hoverImg.lastIndexOf('/');
            if(pos>0){
              hoverImg = hoverImg.substr(0,pos-1)+opt.prefix+hoverImg.substr(pos-1);
            }else{
              hoverImg = opt.prefix+hoverImg;
            }
          }
          if(opt.suffix){
            var pos = hoverImg.lastIndexOf('.');
            if(pos>0){
              hoverImg = hoverImg.substr(0,pos)+opt.suffix+hoverImg.substr(pos);
            }else{
              hoverImg = hoverImg+opt.suffix;
            }
          }
        }

        if(opt.fade){
          var offset = node.offset();
          var hover = node.clone(true); hover.attr('src', hoverImg);
          node.wrap('<div />'); var hdiv = $(node).parent();
          hdiv.css({ position: 'relative', zIndex: 1000 });
          hover.css({ zIndex: 999, position:'absolute', left:'0',top:'0' }).hide().insertAfter(node);

          hdiv.hover(function() {
              var offset=node.offset();
              hover.css({left: '0', top: '0'});
              hover.stop(true,true).fadeIn(opt.fadeSpeed);
          },function() {
              hover.stop(true,true).fadeOut(opt.fadeSpeed);
          });

        }else{
          if(opt.preload) jQuery("<img>").attr("src", hoverImg);
          node.hover(
            function(){node.attr('src', hoverImg)},
            function(){node.attr('src', orgImg)}
          );
        }
                        });
                }
        });
})(jQuery);


