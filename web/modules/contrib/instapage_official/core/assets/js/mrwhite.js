/**
* hoverIntent r6 // 2011.02.26 // jQuery 1.5.1+
* <http://cherne.net/brian/resources/jquery.hoverIntent.html>
*
* @param  f  onMouseOver function || An object with configuration options
* @param  g  onMouseOut function  || Nothing (use configuration options object)
* @author    Brian Cherne brian(at)cherne(dot)net
*/
(function($){$.fn.hoverIntent=function(f,g){var cfg={sensitivity:7,interval:100,timeout:0};cfg=$.extend(cfg,g?{over:f,out:g}:f);var cX,cY,pX,pY;var track=function(ev){cX=ev.pageX;cY=ev.pageY};var compare=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);if ((Math.abs(pX-cX)+Math.abs(pY-cY))<cfg.sensitivity){$(ob).unbind("mousemove",track);ob.hoverIntent_s=1;return cfg.over.apply(ob,[ev])}else{pX=cX;pY=cY;ob.hoverIntent_t=setTimeout(function(){compare(ev,ob)},cfg.interval)}};var delay=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);ob.hoverIntent_s=0;return cfg.out.apply(ob,[ev])};var handleHover=function(e){var ev=jQuery.extend({},e);var ob=this;if (ob.hoverIntent_t){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t)}if (e.type=="mouseenter"){pX=ev.pageX;pY=ev.pageY;$(ob).bind("mousemove",track);if (ob.hoverIntent_s!=1){ob.hoverIntent_t=setTimeout(function(){compare(ev,ob)},cfg.interval)}}else{$(ob).unbind("mousemove",track);if (ob.hoverIntent_s==1){ob.hoverIntent_t=setTimeout(function(){delay(ev,ob)},cfg.timeout)}}};return this.bind('mouseenter',handleHover).bind('mouseleave',handleHover)}})(jQuery);

/*
 * jQuery Templates Plugin 1.0.0pre
 * http://github.com/jquery/jquery-tmpl
 * Requires jQuery 1.4.2
 *
 * Copyright 2011, Software Freedom Conservancy, Inc.
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 */
(function(a){var r=a.fn.domManip,d="_tmplitem",q=/^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,b={},f={},e,p={key:0,data:{}},i=0,c=0,l=[];function g(g,d,h,e){var c={data:e||(e===0||e===false)?e:d?d.data:{},_wrap:d?d._wrap:null,tmpl:null,parent:d||null,nodes:[],calls:u,nest:w,wrap:x,html:v,update:t};g&&a.extend(c,g,{nodes:[],parent:d});if (h){c.tmpl=h;c._ctnt=c._ctnt||c.tmpl(a,c);c.key=++i;(l.length?f:b)[i]=c}return c}a.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(f,d){a.fn[f]=function(n){var g=[],i=a(n),k,h,m,l,j=this.length===1&&this[0].parentNode;e=b||{};if (j&&j.nodeType===11&&j.childNodes.length===1&&i.length===1){i[d](this[0]);g=this}else{for (h=0,m=i.length;h<m;h++){c=h;k=(h>0?this.clone(true):this).get();a(i[h])[d](k);g=g.concat(k)}c=0;g=this.pushStack(g,f,i.selector)}l=e;e=null;a.tmpl.complete(l);return g}});a.fn.extend({tmpl:function(d,c,b){return a.tmpl(this[0],d,c,b)},tmplItem:function(){return a.tmplItem(this[0])},template:function(b){return a.template(b,this[0])},domManip:function(d,m,k){if (d[0]&&a.isArray(d[0])){var g=a.makeArray(arguments),h=d[0],j=h.length,i=0,f;while(i<j&&!(f=a.data(h[i++],"tmplItem")));if (f&&c)g[2]=function(b){a.tmpl.afterManip(this,b,k)};r.apply(this,g)}else r.apply(this,arguments);c=0;!e&&a.tmpl.complete(b);return this}});a.extend({tmpl:function(d,h,e,c){var i,k=!c;if (k){c=p;d=a.template[d]||a.template(null,d);f={}}else if (!d){d=c.tmpl;b[c.key]=c;c.nodes=[];c.wrapped&&n(c,c.wrapped);return a(j(c,null,c.tmpl(a,c)))}if (!d)return[];if (typeof h==="function")h=h.call(c||{});e&&e.wrapped&&n(e,e.wrapped);i=a.isArray(h)?a.map(h,function(a){return a?g(e,c,d,a):null}):[g(e,c,d,h)];return k?a(j(c,null,i)):i},tmplItem:function(b){var c;if (b instanceof a)b=b[0];while(b&&b.nodeType===1&&!(c=a.data(b,"tmplItem"))&&(b=b.parentNode));return c||p},template:function(c,b){if (b){if (typeof b==="string")b=o(b);else if (b instanceof a)b=b[0]||{};if (b.nodeType)b=a.data(b,"tmpl")||a.data(b,"tmpl",o(b.innerHTML));return typeof c==="string"?(a.template[c]=b):b}return c?typeof c!=="string"?a.template(null,c):a.template[c]||a.template(null,q.test(c)?c:a(c)):null},encode:function(a){return(""+a).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")}});a.extend(a.tmpl,{tag:{tmpl:{_default:{$2:"null"},open:"if ($notnull_1){__=__.concat($item.nest($1,$2));}"},wrap:{_default:{$2:"null"},open:"$item.calls(__,$1,$2);__=[];",close:"call=$item.calls();__=call._.concat($item.wrap(call,__));"},each:{_default:{$2:"$index, $value"},open:"if ($notnull_1){$.each($1a,function($2){with(this){",close:"}});}"},"if":{open:"if (($notnull_1) && $1a){",close:"}"},"else":{_default:{$1:"true"},open:"}else if (($notnull_1) && $1a){"},html:{open:"if ($notnull_1){__.push($1a);}"},"=":{_default:{$1:"$data"},open:"if ($notnull_1){__.push($.encode($1a));}"},"!":{open:""}},complete:function(){b={}},afterManip:function(f,b,d){var e=b.nodeType===11?a.makeArray(b.childNodes):b.nodeType===1?[b]:[];d.call(f,b);m(e);c++}});function j(e,g,f){var b,c=f?a.map(f,function(a){return typeof a==="string"?e.key?a.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g,"$1 "+d+'="'+e.key+'" $2'):a:j(a,e,a._ctnt)}):e;if (g)return c;c=c.join("");c.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/,function(f,c,e,d){b=a(e).get();m(b);if (c)b=k(c).concat(b);if (d)b=b.concat(k(d))});return b?b:k(c)}function k(c){var b=document.createElement("div");b.innerHTML=c;return a.makeArray(b.childNodes)}function o(b){return new Function("jQuery","$item","var $=jQuery,call,__=[],$data=$item.data;with($data){__.push('"+a.trim(b).replace(/([\\'])/g,"\\$1").replace(/[\r\t\n]/g," ").replace(/\$\{([^\}]*)\}/g,"{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,function(m,l,k,g,b,c,d){var j=a.tmpl.tag[k],i,e,f;if (!j)throw"Unknown template tag: "+k;i=j._default||[];if (c&&!/\w$/.test(b)){b+=c;c=""}if (b){b=h(b);d=d?","+h(d)+")":c?")":"";e=c?b.indexOf(".")>-1?b+h(c):"("+b+").call($item"+d:b;f=c?e:"(typeof("+b+")==='function'?("+b+").call($item):("+b+"))"}else f=e=i.$1||"null";g=h(g);return"');"+j[l?"close":"open"].split("$notnull_1").join(b?"typeof("+b+")!=='undefined' && ("+b+")!=null":"true").split("$1a").join(f).split("$1").join(e).split("$2").join(g||i.$2||"")+"__.push('"})+"');}return __;")}function n(c,b){c._wrap=j(c,true,a.isArray(b)?b:[q.test(b)?b:a(b).html()]).join("")}function h(a){return a?a.replace(/\\'/g,"'").replace(/\\\\/g,"\\"):null}function s(b){var a=document.createElement("div");a.appendChild(b.cloneNode(true));return a.innerHTML}function m(o){var n="_"+c,k,j,l={},e,p,h;for (e=0,p=o.length;e<p;e++){if ((k=o[e]).nodeType!==1)continue;j=k.getElementsByTagName("*");for (h=j.length-1;h>=0;h--)m(j[h]);m(k)}function m(j){var p,h=j,k,e,m;if (m=j.getAttribute(d)){while(h.parentNode&&(h=h.parentNode).nodeType===1&&!(p=h.getAttribute(d)));if (p!==m){h=h.parentNode?h.nodeType===11?0:h.getAttribute(d)||0:0;if (!(e=b[m])){e=f[m];e=g(e,b[h]||f[h]);e.key=++i;b[i]=e}c&&o(m)}j.removeAttribute(d)}else if (c&&(e=a.data(j,"tmplItem"))){o(e.key);b[e.key]=e;h=a.data(j.parentNode,"tmplItem");h=h?h.key:0}if (e){k=e;while(k&&k.key!=h){k.nodes.push(j);k=k.parent}delete e._ctnt;delete e._wrap;a.data(j,"tmplItem",e)}function o(a){a=a+n;e=l[a]=l[a]||g(e,b[e.parent.key+n]||e.parent)}}}function u(a,d,c,b){if (!a)return l.pop();l.push({_:a,tmpl:d,item:this,data:c,options:b})}function w(d,c,b){return a.tmpl(a.template(d),c,b,this)}function x(b,d){var c=b.options||{};c.wrapped=d;return a.tmpl(a.template(b.tmpl),b.data,c,b.item)}function v(d,c){var b=this._wrap;return a.map(a(a.isArray(b)?b.join(""):b).filter(d||"*"),function(a){return c?a.innerText||a.textContent:a.outerHTML||s(a)})}function t(){var b=this.nodes;a.tmpl(null,null,null,this).insertBefore(b[0]);a(b).remove()}})(jQuery);


(function($) {
  $.fn.ipTooltips = function(opts) {
    var tip_trigger = $(this);
    var config = $.extend({}, {
      inner: tip_trigger.data('inner') || 'Dear developer, please set the proper tooltip text.',
      placement : tip_trigger.data('placement') || 'bottom',
      width: tip_trigger.data('width'),
      height: tip_trigger.data('height'),
      css_class: tip_trigger.data('class'),
      tip_size_param: tip_trigger.data('offset') || 10,
      ellipsis_mode: tip_trigger.data('ellipsis') || false
    }, opts);

    var tip_position = {};
    var tool_tip;

    tip_trigger.hoverIntent({
      over: init,
      out: destroy
    });

    function init() {
      if (tip_trigger[0].dataset.inner !== config.inner) {
        config.inner = tip_trigger[0].dataset.inner;
        alert(config.inner)
      }

      if (tip_trigger[0].dataset.class !== config.css_class) {
        config.css_class = tip_trigger[0].dataset.class;
        alert(config.inner)
      }

      tool_tip = $('#tooltip').tmpl({
        inner: config.inner,
        placement: config.placement.split('-')[0],
        cssClass: config.css_class
      });

      if (config.ellipsis_mode === false || hasEllipsisActivated()) {
        $('body').append(tool_tip);
        tip_position = getTipPosition();
        show();
      }
    }

    function getTipPosition() {
      var tip_trigger_width = tip_trigger.outerWidth();
      var tip_trigger_height = tip_trigger.outerHeight();
      var tip_offset = tip_trigger.offset();
      var tip_size_param = config.tip_size_param;
      var tip_width;
      var tip_height;

      tip_width = config.width || Math.ceil(tool_tip[0].getBoundingClientRect().width);
      tool_tip.css({
        width: tip_width
      });

      tip_height = config.height || Math.ceil(tool_tip[0].getBoundingClientRect().height);
      tool_tip.css({
        height: tip_height
      });

      return {
        'top': {
          left: parseInt((tip_offset.left + tip_trigger_width / 2 - tip_width / 2 ), 10),
          top: parseInt((tip_offset.top - tip_height - tip_size_param), 10 )
        },
        'top-left': {
          left: parseInt((tip_offset.left) + parseInt(tip_trigger.css('border-left-width') ), 10),
          top: parseInt((tip_offset.top - tip_height - tip_size_param), 10 )
        },
        'bottom': {
          left: parseInt((tip_offset.left + tip_trigger_width / 2 - tip_width / 2 ), 10),
          top:  parseInt((tip_offset.top + tip_trigger_height + tip_size_param), 10 )
        },
        'bottom-left': {
          left: parseInt((tip_offset.left) + parseInt(tip_trigger.css('border-left-width') ), 10),
          top:  parseInt((tip_offset.top + tip_trigger_height + tip_size_param), 10 )
        },
        'left': {
          left: parseInt((tip_offset.left - tip_size_param - tip_width ), 10),
          top: parseInt((tip_offset.top + tip_trigger_height / 2 - tip_height / 2), 10 )
        },
        'right': {
          left: parseInt((tip_offset.left + tip_size_param + tip_trigger_width ), 10),
          top: parseInt((tip_offset.top + tip_trigger_height / 2 - tip_height / 2), 10 )
        }
      };
    }

    function show() {
      tool_tip
        .css({
          left: tip_position[config.placement].left,
          top: tip_position[config.placement].top
        })
        .addClass('c-tooltip--show');
    }

    function destroy() {
      tip_position = {};

      tool_tip.removeClass('c-tooltip--show').addClass('c-tooltip--hide');

      setTimeout(function() {
        tool_tip.remove();
      }, 210);
    }

    tip_trigger.data('ipTooltips-initialized', true);

    return this;
  };

  $(function() {
    $(document).on('mouseenter', '[data-toggle="tooltip"]', function() {
      if (!$(this).data('ipTooltips-initialized')) {
        $(this).ipTooltips();
        $(this).trigger('mouseenter');
      }
    });

    document.addEventListener('click', function(e) {
      if ($(e.target).data('toggle') === 'tooltip') {
        return false;
      }

      Array.prototype.forEach.call(document.querySelectorAll('.c-tooltip'), function(element) {
        var node = false;
        var found = false;

        for (node = e.target; !found && node; node = node.parentNode) {
          if (node === element) {
            found = true;
          }
        }

        if (!found) {
          $(element).remove();
        }
      });
    }, false);
  });

})(jQuery);

(function($) {
// This script is based on this pen other http://codepen.io/zavoloklom/pen/Gubja
    $(document).ready(function() {
      $(".fx-ripple-effect").off('click').on('click', function(e) {
        var rippler = $(this);

        // create .ink element if it doesn't exist
        if (rippler.find(".fx-ink").length == 0) {
          rippler.append("<span class='fx-ink'></span>");
        }

        var ink = rippler.find(".fx-ink");

        // prevent quick double clicks
        ink.removeClass("fx-animate");

        // set .ink diametr
        if (!ink.height() && !ink.width()) {
          var d = Math.max(rippler.outerWidth(), rippler.outerHeight());
          ink.css({
            height: d,
            width: d
          });
        }

        // get click coordinates
        var x = e.pageX - rippler.offset().left - ink.width() / 2;
        var y = e.pageY - rippler.offset().top - ink.height() / 2;

        // set .ink position and add class .animate
        ink.css({
          top: y + 'px',
          left: x + 'px'
        }).addClass("fx-animate");
      });

    });

    /*------------------------------------*\
      EXPAND - COLLAPSE
    \*------------------------------------*/

    function switchExpandableContent(trigger) {
      var expandableContainer = trigger.closest($('.c-expandable-item'));
      var expandableWrapper = $(expandableContainer).find('.c-expandable-item__wrapper');
      var expandableContent = $(expandableContainer).find('.c-expandable-item__content');

      if (expandableContainer.hasClass('is-expanded')) {
        expandableContainer.removeClass('is-expanded');
        expandableWrapper.height(0);
      } else {
        expandableContainer.addClass('is-expanded');
        expandableWrapper.height(expandableContent.outerHeight(true));
      }
    }

    // document.ready
    $(document).ready(function() {
      $('.js-expand-trigger').on('click',function(e) {
        e.preventDefault();
        switchExpandableContent($(this));
      });
    });

    /*------------------------------------*\
      TABS
    \*------------------------------------*/

    // activate tabs
    function initTabs() {
      $.each($('.c-tabs'), function(index, item) {
        setTabSliderPosition(item);
      });
    }

    // set tab slider position
    function setTabSliderPosition(tabsContainer) {
        var activeTab = $(tabsContainer).find('.c-tab.is-active');
        $(tabsContainer).find('.c-tabs__slider').css({
        left: activeTab.position().left,
        width: activeTab.outerWidth()
      });
    }

    // switch active tab
    function switchActiveTab(newActiveTab) {
      var tabsContainer = newActiveTab.closest($('.c-tabs'));
      $(tabsContainer).find('.c-tab.is-active').removeClass('is-active');
      newActiveTab.addClass('is-active');
      setTabSliderPosition(tabsContainer);
    }

    // document.ready
    $(document).ready(function() {
      initTabs();
      $('.c-tab').on('click', function(e) {
        e.preventDefault();
        var activeTab = switchActiveTab($(this));
      });
    });


    /*------------------------------------*\
      INPUT
    \*------------------------------------*/
    $(document).ready(function() {
      $('.c-form-text-item__field').blur(function() {
        if ($(this).val()) {
          $(this).addClass('is-not-empty');
        } else {
          $(this).removeClass('is-not-empty');
        }
      });
    });


    /*------------------------------------*\
      SNACK BARS
    \*------------------------------------*/
    $(document).ready(function() {
      $('.snack-bar-trigger').on('click', function() {
        $('.c-snack-bar').removeClass('is-animated');
        var status = $(this).data('status');
        $('.c-snack-bar[data-status="'+status+'"]').addClass('is-animated');
        setTimeout(function() {
          $('.c-snack-bar[data-status="'+status+'"]').removeClass('is-animated');
        }, 3000);
      });
    });

    /*------------------------------------*\
      DROPDOWNS
    \*------------------------------------*/
    $(document).ready(function() {
      $('.c-dropdown__trigger').on('click',function(e) {
        e.stopPropagation();
        $(this).parent().toggleClass('is-open');
      });

      $('.c-dropdown__content').on('click',function(e) {
        e.stopPropagation();
      });

      $('body').click(function() {
        $('.c-dropdown').removeClass('is-open');
      });
    });
})(jQuery);