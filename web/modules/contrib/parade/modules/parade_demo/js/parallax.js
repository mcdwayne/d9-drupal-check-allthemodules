(function (exports) {
'use strict';

var commonjsGlobal = typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};





function createCommonjsModule(fn, module) {
	return module = { exports: {} }, fn(module, module.exports), module.exports;
}

var rellax = createCommonjsModule(function (module) {
// ------------------------------------------
// Rellax.js - v0.2
// Buttery smooth parallax library
// Copyright (c) 2016 Moe Amaya (@moeamaya)
// MIT license
//
// Thanks to Paraxify.js and Jaime Cabllero
// for parallax concepts
// ------------------------------------------

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory();
    } else {
        // Browser globals (root is window)
        root.Rellax = factory();
  }
}(commonjsGlobal, function () {
  var Rellax = function(el, options){
    "use strict";

    var self = Object.create(Rellax.prototype);

    // Rellax stays lightweight by limiting usage to desktops/laptops
    if (typeof window.orientation !== 'undefined') { return; }

    var posY = 0; // set it to -1 so the animate function gets called at least once
    var screenY = 0;
    var blocks = [];

    // check what requestAnimationFrame to use, and if
    // it's not supported, use the onscroll event
    var loop = window.requestAnimationFrame ||
    	window.webkitRequestAnimationFrame ||
    	window.mozRequestAnimationFrame ||
    	window.msRequestAnimationFrame ||
    	window.oRequestAnimationFrame ||
    	function(callback){ setTimeout(callback, 1000 / 60); };

    // Default Settings
    self.options = {
      speed: -2
    };

    // User defined options (might have more in the future)
    if (options){
      Object.keys(options).forEach(function(key){
        self.options[key] = options[key];
      });
    }

    // If some clown tries to crank speed, limit them to +-10
    if (self.options.speed < -10) {
      self.options.speed = -10;
    } else if (self.options.speed > 10) {
      self.options.speed = 10;
    }

    // By default, rellax class
    if (!el) {
      el = '.rellax';
    }

    // Classes
    if (document.getElementsByClassName(el.replace('.',''))){
      self.elems = document.getElementsByClassName(el.replace('.',''));
    }

    // Now query selector
    else if (document.querySelector(el) !== false) {
      self.elems = querySelector(el);
    }

    // The elements don't exist
    else {
      throw new Error("The elements you're trying to select don't exist.");
    }


    // Let's kick this script off
    // Build array for cached element values
    // Bind scroll and resize to animate method
    var init = function() {
      screenY = window.innerHeight;
      setPosition();

      // Get and cache initial position of all elements
      for (var i = 0; i < self.elems.length; i++){
        var block = createBlock(self.elems[i]);
        blocks.push(block);
      }

			window.addEventListener('resize', function(){
			  animate();
			});

			// Start the loop
      update();

      // The loop does nothing if the scrollPosition did not change
      // so call animate to make sure every element has their transforms
      animate();
    };


    // We want to cache the parallax blocks'
    // values: base, top, height, speed
    // el: is dom object, return: el cache values
    var createBlock = function(el) {

      // initializing at scrollY = 0 (top of browser)
      // ensures elements are positioned based on HTML layout
      var posY = 0;

      var blockTop = posY + el.getBoundingClientRect().top;
      var blockHeight = el.clientHeight || el.offsetHeight || el.scrollHeight;

      // apparently parallax equation everyone uses
      var percentage = (posY - blockTop + screenY) / (blockHeight + screenY);

      // Optional individual block speed as data attr, otherwise global speed
      var speed = el.dataset.rellaxSpeed ? el.dataset.rellaxSpeed : self.options.speed;
      var base = updatePosition(percentage, speed);

      // Store non-translate3d transforms
      var cssTransform = el.style.cssText.slice(11);

      return {
        base: base,
        top: blockTop,
        height: blockHeight,
        speed: speed,
        style: cssTransform
      };
    };


    // set scroll position (posY)
    // side effect method is not ideal, but okay for now
    // returns true if the scroll changed, false if nothing happened
    var setPosition = function() {
    	var oldY = posY;

      if (window.pageYOffset !== undefined) {
        posY = window.pageYOffset;
      } else {
        posY = (document.documentElement || document.body.parentNode || document.body).scrollTop;
      }

      if (oldY !== posY) {
      	// scroll changed, return true
      	return true;
      }

      // scroll did not change
      return false;
    };


    // Ahh a pure function, gets new transform value
    // based on scrollPostion and speed
    var updatePosition = function(percentage, speed) {
      var value = (speed * (100 * (1 - percentage)));
      return Math.round(value);
    };


    //
		var update = function() {
			if (setPosition()) {
				animate();
	    }

	    // loop again
	    loop(update);
		};

    // Transform3d on parallax element
    var animate = function() {
    	for (var i = 0; i < self.elems.length; i++){
        var percentage = ((posY - blocks[i].top + screenY) / (blocks[i].height + screenY));

        // Subtracting initialize value, so element stays in same spot as HTML
        var position = updatePosition(percentage, blocks[i].speed) - blocks[i].base;

        // Move that element
        var translate = 'translate3d(0,' + position + 'px' + ',0)' + blocks[i].style;
        self.elems[i].style.cssText = '-webkit-transform:'+translate+';-moz-transform:'+translate+';transform:'+translate+';';
      }
    };


    init();
    Object.freeze();
    return self;
  };
  return Rellax;
}));
});

/**
 * @file
 * Initialize parallaxes with Rellax lib.
 *
 * @see  https://github.com/dixonandmoe/rellax
 */

(function () {
	var rellax = new rellax('.parallax');
})();

}((this.LaravelElixirBundle = this.LaravelElixirBundle || {})));
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjpudWxsLCJzb3VyY2VzIjpbIkQ6L2RldmRlc2t0b3AvdGNzLmxvYy93ZWIvdGhlbWVzL3RpZXRvX2FkbWluL25vZGVfbW9kdWxlcy9yZWxsYXgvcmVsbGF4LmpzIiwiRDovZGV2ZGVza3RvcC90Y3MubG9jL3dlYi90aGVtZXMvdGlldG9fYWRtaW4vc3JjL3NjcmlwdHMvcGFyYWxsYXguanMiXSwic291cmNlc0NvbnRlbnQiOlsiXG4vLyAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS1cbi8vIFJlbGxheC5qcyAtIHYwLjJcbi8vIEJ1dHRlcnkgc21vb3RoIHBhcmFsbGF4IGxpYnJhcnlcbi8vIENvcHlyaWdodCAoYykgMjAxNiBNb2UgQW1heWEgKEBtb2VhbWF5YSlcbi8vIE1JVCBsaWNlbnNlXG4vL1xuLy8gVGhhbmtzIHRvIFBhcmF4aWZ5LmpzIGFuZCBKYWltZSBDYWJsbGVyb1xuLy8gZm9yIHBhcmFsbGF4IGNvbmNlcHRzIFxuLy8gLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tXG5cbihmdW5jdGlvbiAocm9vdCwgZmFjdG9yeSkge1xuICAgIGlmICh0eXBlb2YgZGVmaW5lID09PSAnZnVuY3Rpb24nICYmIGRlZmluZS5hbWQpIHtcbiAgICAgICAgLy8gQU1ELiBSZWdpc3RlciBhcyBhbiBhbm9ueW1vdXMgbW9kdWxlLlxuICAgICAgICBkZWZpbmUoW10sIGZhY3RvcnkpO1xuICAgIH0gZWxzZSBpZiAodHlwZW9mIG1vZHVsZSA9PT0gJ29iamVjdCcgJiYgbW9kdWxlLmV4cG9ydHMpIHtcbiAgICAgICAgLy8gTm9kZS4gRG9lcyBub3Qgd29yayB3aXRoIHN0cmljdCBDb21tb25KUywgYnV0XG4gICAgICAgIC8vIG9ubHkgQ29tbW9uSlMtbGlrZSBlbnZpcm9ubWVudHMgdGhhdCBzdXBwb3J0IG1vZHVsZS5leHBvcnRzLFxuICAgICAgICAvLyBsaWtlIE5vZGUuXG4gICAgICAgIG1vZHVsZS5leHBvcnRzID0gZmFjdG9yeSgpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgIC8vIEJyb3dzZXIgZ2xvYmFscyAocm9vdCBpcyB3aW5kb3cpXG4gICAgICAgIHJvb3QuUmVsbGF4ID0gZmFjdG9yeSgpO1xuICB9XG59KHRoaXMsIGZ1bmN0aW9uICgpIHtcbiAgdmFyIFJlbGxheCA9IGZ1bmN0aW9uKGVsLCBvcHRpb25zKXsgXG4gICAgXCJ1c2Ugc3RyaWN0XCI7XG5cbiAgICB2YXIgc2VsZiA9IE9iamVjdC5jcmVhdGUoUmVsbGF4LnByb3RvdHlwZSk7XG5cbiAgICAvLyBSZWxsYXggc3RheXMgbGlnaHR3ZWlnaHQgYnkgbGltaXRpbmcgdXNhZ2UgdG8gZGVza3RvcHMvbGFwdG9wc1xuICAgIGlmICh0eXBlb2Ygd2luZG93Lm9yaWVudGF0aW9uICE9PSAndW5kZWZpbmVkJykgeyByZXR1cm47IH1cblxuICAgIHZhciBwb3NZID0gMDsgLy8gc2V0IGl0IHRvIC0xIHNvIHRoZSBhbmltYXRlIGZ1bmN0aW9uIGdldHMgY2FsbGVkIGF0IGxlYXN0IG9uY2VcbiAgICB2YXIgc2NyZWVuWSA9IDA7XG4gICAgdmFyIGJsb2NrcyA9IFtdO1xuICAgIFxuICAgIC8vIGNoZWNrIHdoYXQgcmVxdWVzdEFuaW1hdGlvbkZyYW1lIHRvIHVzZSwgYW5kIGlmXG4gICAgLy8gaXQncyBub3Qgc3VwcG9ydGVkLCB1c2UgdGhlIG9uc2Nyb2xsIGV2ZW50XG4gICAgdmFyIGxvb3AgPSB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lIHx8XG4gICAgXHR3aW5kb3cud2Via2l0UmVxdWVzdEFuaW1hdGlvbkZyYW1lIHx8XG4gICAgXHR3aW5kb3cubW96UmVxdWVzdEFuaW1hdGlvbkZyYW1lIHx8XG4gICAgXHR3aW5kb3cubXNSZXF1ZXN0QW5pbWF0aW9uRnJhbWUgfHxcbiAgICBcdHdpbmRvdy5vUmVxdWVzdEFuaW1hdGlvbkZyYW1lIHx8XG4gICAgXHRmdW5jdGlvbihjYWxsYmFjayl7IHNldFRpbWVvdXQoY2FsbGJhY2ssIDEwMDAgLyA2MCk7IH07XG5cbiAgICAvLyBEZWZhdWx0IFNldHRpbmdzXG4gICAgc2VsZi5vcHRpb25zID0ge1xuICAgICAgc3BlZWQ6IC0yXG4gICAgfTtcblxuICAgIC8vIFVzZXIgZGVmaW5lZCBvcHRpb25zIChtaWdodCBoYXZlIG1vcmUgaW4gdGhlIGZ1dHVyZSlcbiAgICBpZiAob3B0aW9ucyl7XG4gICAgICBPYmplY3Qua2V5cyhvcHRpb25zKS5mb3JFYWNoKGZ1bmN0aW9uKGtleSl7XG4gICAgICAgIHNlbGYub3B0aW9uc1trZXldID0gb3B0aW9uc1trZXldO1xuICAgICAgfSk7XG4gICAgfVxuXG4gICAgLy8gSWYgc29tZSBjbG93biB0cmllcyB0byBjcmFuayBzcGVlZCwgbGltaXQgdGhlbSB0byArLTEwXG4gICAgaWYgKHNlbGYub3B0aW9ucy5zcGVlZCA8IC0xMCkge1xuICAgICAgc2VsZi5vcHRpb25zLnNwZWVkID0gLTEwO1xuICAgIH0gZWxzZSBpZiAoc2VsZi5vcHRpb25zLnNwZWVkID4gMTApIHtcbiAgICAgIHNlbGYub3B0aW9ucy5zcGVlZCA9IDEwO1xuICAgIH1cblxuICAgIC8vIEJ5IGRlZmF1bHQsIHJlbGxheCBjbGFzc1xuICAgIGlmICghZWwpIHtcbiAgICAgIGVsID0gJy5yZWxsYXgnO1xuICAgIH1cblxuICAgIC8vIENsYXNzZXNcbiAgICBpZiAoZG9jdW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZShlbC5yZXBsYWNlKCcuJywnJykpKXtcbiAgICAgIHNlbGYuZWxlbXMgPSBkb2N1bWVudC5nZXRFbGVtZW50c0J5Q2xhc3NOYW1lKGVsLnJlcGxhY2UoJy4nLCcnKSk7XG4gICAgfVxuXG4gICAgLy8gTm93IHF1ZXJ5IHNlbGVjdG9yXG4gICAgZWxzZSBpZiAoZG9jdW1lbnQucXVlcnlTZWxlY3RvcihlbCkgIT09IGZhbHNlKSB7XG4gICAgICBzZWxmLmVsZW1zID0gcXVlcnlTZWxlY3RvcihlbCk7XG4gICAgfVxuXG4gICAgLy8gVGhlIGVsZW1lbnRzIGRvbid0IGV4aXN0XG4gICAgZWxzZSB7XG4gICAgICB0aHJvdyBuZXcgRXJyb3IoXCJUaGUgZWxlbWVudHMgeW91J3JlIHRyeWluZyB0byBzZWxlY3QgZG9uJ3QgZXhpc3QuXCIpO1xuICAgIH1cblxuXG4gICAgLy8gTGV0J3Mga2ljayB0aGlzIHNjcmlwdCBvZmZcbiAgICAvLyBCdWlsZCBhcnJheSBmb3IgY2FjaGVkIGVsZW1lbnQgdmFsdWVzXG4gICAgLy8gQmluZCBzY3JvbGwgYW5kIHJlc2l6ZSB0byBhbmltYXRlIG1ldGhvZFxuICAgIHZhciBpbml0ID0gZnVuY3Rpb24oKSB7XG4gICAgICBzY3JlZW5ZID0gd2luZG93LmlubmVySGVpZ2h0O1xuICAgICAgc2V0UG9zaXRpb24oKTtcblxuICAgICAgLy8gR2V0IGFuZCBjYWNoZSBpbml0aWFsIHBvc2l0aW9uIG9mIGFsbCBlbGVtZW50c1xuICAgICAgZm9yICh2YXIgaSA9IDA7IGkgPCBzZWxmLmVsZW1zLmxlbmd0aDsgaSsrKXtcbiAgICAgICAgdmFyIGJsb2NrID0gY3JlYXRlQmxvY2soc2VsZi5lbGVtc1tpXSk7XG4gICAgICAgIGJsb2Nrcy5wdXNoKGJsb2NrKTtcbiAgICAgIH1cblx0XHRcdFxuXHRcdFx0d2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3Jlc2l6ZScsIGZ1bmN0aW9uKCl7XG5cdFx0XHQgIGFuaW1hdGUoKTtcblx0XHRcdH0pO1xuXHRcdFx0XG5cdFx0XHQvLyBTdGFydCB0aGUgbG9vcFxuICAgICAgdXBkYXRlKCk7XG4gICAgICBcbiAgICAgIC8vIFRoZSBsb29wIGRvZXMgbm90aGluZyBpZiB0aGUgc2Nyb2xsUG9zaXRpb24gZGlkIG5vdCBjaGFuZ2VcbiAgICAgIC8vIHNvIGNhbGwgYW5pbWF0ZSB0byBtYWtlIHN1cmUgZXZlcnkgZWxlbWVudCBoYXMgdGhlaXIgdHJhbnNmb3Jtc1xuICAgICAgYW5pbWF0ZSgpO1xuICAgIH07XG5cblxuICAgIC8vIFdlIHdhbnQgdG8gY2FjaGUgdGhlIHBhcmFsbGF4IGJsb2NrcydcbiAgICAvLyB2YWx1ZXM6IGJhc2UsIHRvcCwgaGVpZ2h0LCBzcGVlZFxuICAgIC8vIGVsOiBpcyBkb20gb2JqZWN0LCByZXR1cm46IGVsIGNhY2hlIHZhbHVlc1xuICAgIHZhciBjcmVhdGVCbG9jayA9IGZ1bmN0aW9uKGVsKSB7XG5cbiAgICAgIC8vIGluaXRpYWxpemluZyBhdCBzY3JvbGxZID0gMCAodG9wIG9mIGJyb3dzZXIpXG4gICAgICAvLyBlbnN1cmVzIGVsZW1lbnRzIGFyZSBwb3NpdGlvbmVkIGJhc2VkIG9uIEhUTUwgbGF5b3V0XG4gICAgICB2YXIgcG9zWSA9IDA7XG5cbiAgICAgIHZhciBibG9ja1RvcCA9IHBvc1kgKyBlbC5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKS50b3A7XG4gICAgICB2YXIgYmxvY2tIZWlnaHQgPSBlbC5jbGllbnRIZWlnaHQgfHwgZWwub2Zmc2V0SGVpZ2h0IHx8IGVsLnNjcm9sbEhlaWdodDtcblxuICAgICAgLy8gYXBwYXJlbnRseSBwYXJhbGxheCBlcXVhdGlvbiBldmVyeW9uZSB1c2VzXG4gICAgICB2YXIgcGVyY2VudGFnZSA9IChwb3NZIC0gYmxvY2tUb3AgKyBzY3JlZW5ZKSAvIChibG9ja0hlaWdodCArIHNjcmVlblkpO1xuXG4gICAgICAvLyBPcHRpb25hbCBpbmRpdmlkdWFsIGJsb2NrIHNwZWVkIGFzIGRhdGEgYXR0ciwgb3RoZXJ3aXNlIGdsb2JhbCBzcGVlZFxuICAgICAgdmFyIHNwZWVkID0gZWwuZGF0YXNldC5yZWxsYXhTcGVlZCA/IGVsLmRhdGFzZXQucmVsbGF4U3BlZWQgOiBzZWxmLm9wdGlvbnMuc3BlZWQ7XG4gICAgICB2YXIgYmFzZSA9IHVwZGF0ZVBvc2l0aW9uKHBlcmNlbnRhZ2UsIHNwZWVkKTtcblxuICAgICAgLy8gU3RvcmUgbm9uLXRyYW5zbGF0ZTNkIHRyYW5zZm9ybXNcbiAgICAgIHZhciBjc3NUcmFuc2Zvcm0gPSBlbC5zdHlsZS5jc3NUZXh0LnNsaWNlKDExKTtcblxuICAgICAgcmV0dXJuIHtcbiAgICAgICAgYmFzZTogYmFzZSxcbiAgICAgICAgdG9wOiBibG9ja1RvcCxcbiAgICAgICAgaGVpZ2h0OiBibG9ja0hlaWdodCxcbiAgICAgICAgc3BlZWQ6IHNwZWVkLFxuICAgICAgICBzdHlsZTogY3NzVHJhbnNmb3JtXG4gICAgICB9O1xuICAgIH07XG5cblxuICAgIC8vIHNldCBzY3JvbGwgcG9zaXRpb24gKHBvc1kpXG4gICAgLy8gc2lkZSBlZmZlY3QgbWV0aG9kIGlzIG5vdCBpZGVhbCwgYnV0IG9rYXkgZm9yIG5vd1xuICAgIC8vIHJldHVybnMgdHJ1ZSBpZiB0aGUgc2Nyb2xsIGNoYW5nZWQsIGZhbHNlIGlmIG5vdGhpbmcgaGFwcGVuZWRcbiAgICB2YXIgc2V0UG9zaXRpb24gPSBmdW5jdGlvbigpIHtcbiAgICBcdHZhciBvbGRZID0gcG9zWTtcbiAgICBcdFxuICAgICAgaWYgKHdpbmRvdy5wYWdlWU9mZnNldCAhPT0gdW5kZWZpbmVkKSB7XG4gICAgICAgIHBvc1kgPSB3aW5kb3cucGFnZVlPZmZzZXQ7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBwb3NZID0gKGRvY3VtZW50LmRvY3VtZW50RWxlbWVudCB8fCBkb2N1bWVudC5ib2R5LnBhcmVudE5vZGUgfHwgZG9jdW1lbnQuYm9keSkuc2Nyb2xsVG9wO1xuICAgICAgfVxuICAgICAgXG4gICAgICBpZiAob2xkWSAhPSBwb3NZKSB7XG4gICAgICBcdC8vIHNjcm9sbCBjaGFuZ2VkLCByZXR1cm4gdHJ1ZVxuICAgICAgXHRyZXR1cm4gdHJ1ZTtcbiAgICAgIH1cbiAgICAgIFxuICAgICAgLy8gc2Nyb2xsIGRpZCBub3QgY2hhbmdlXG4gICAgICByZXR1cm4gZmFsc2U7XG4gICAgfTtcblxuXG4gICAgLy8gQWhoIGEgcHVyZSBmdW5jdGlvbiwgZ2V0cyBuZXcgdHJhbnNmb3JtIHZhbHVlXG4gICAgLy8gYmFzZWQgb24gc2Nyb2xsUG9zdGlvbiBhbmQgc3BlZWRcbiAgICB2YXIgdXBkYXRlUG9zaXRpb24gPSBmdW5jdGlvbihwZXJjZW50YWdlLCBzcGVlZCkge1xuICAgICAgdmFyIHZhbHVlID0gKHNwZWVkICogKDEwMCAqICgxIC0gcGVyY2VudGFnZSkpKTtcbiAgICAgIHJldHVybiBNYXRoLnJvdW5kKHZhbHVlKTtcbiAgICB9O1xuXG5cbiAgICAvL1xuXHRcdHZhciB1cGRhdGUgPSBmdW5jdGlvbigpIHtcblx0XHRcdGlmIChzZXRQb3NpdGlvbigpKSB7XG5cdFx0XHRcdGFuaW1hdGUoKTtcblx0ICAgIH1cblx0ICAgIFxuXHQgICAgLy8gbG9vcCBhZ2FpblxuXHQgICAgbG9vcCh1cGRhdGUpO1xuXHRcdH07XG5cdFx0XG4gICAgLy8gVHJhbnNmb3JtM2Qgb24gcGFyYWxsYXggZWxlbWVudFxuICAgIHZhciBhbmltYXRlID0gZnVuY3Rpb24oKSB7XG4gICAgXHRmb3IgKHZhciBpID0gMDsgaSA8IHNlbGYuZWxlbXMubGVuZ3RoOyBpKyspe1xuICAgICAgICB2YXIgcGVyY2VudGFnZSA9ICgocG9zWSAtIGJsb2Nrc1tpXS50b3AgKyBzY3JlZW5ZKSAvIChibG9ja3NbaV0uaGVpZ2h0ICsgc2NyZWVuWSkpO1xuXG4gICAgICAgIC8vIFN1YnRyYWN0aW5nIGluaXRpYWxpemUgdmFsdWUsIHNvIGVsZW1lbnQgc3RheXMgaW4gc2FtZSBzcG90IGFzIEhUTUxcbiAgICAgICAgdmFyIHBvc2l0aW9uID0gdXBkYXRlUG9zaXRpb24ocGVyY2VudGFnZSwgYmxvY2tzW2ldLnNwZWVkKSAtIGJsb2Nrc1tpXS5iYXNlO1xuXG4gICAgICAgIC8vIE1vdmUgdGhhdCBlbGVtZW50XG4gICAgICAgIHZhciB0cmFuc2xhdGUgPSAndHJhbnNsYXRlM2QoMCwnICsgcG9zaXRpb24gKyAncHgnICsgJywwKScgKyBibG9ja3NbaV0uc3R5bGU7XG4gICAgICAgIHNlbGYuZWxlbXNbaV0uc3R5bGUuY3NzVGV4dCA9ICctd2Via2l0LXRyYW5zZm9ybTonK3RyYW5zbGF0ZSsnOy1tb3otdHJhbnNmb3JtOicrdHJhbnNsYXRlKyc7dHJhbnNmb3JtOicrdHJhbnNsYXRlKyc7JztcbiAgICAgIH1cbiAgICB9O1xuXG5cbiAgICBpbml0KCk7XG4gICAgT2JqZWN0LmZyZWV6ZSgpO1xuICAgIHJldHVybiBzZWxmO1xuICB9O1xuICByZXR1cm4gUmVsbGF4O1xufSkpOyIsIi8qKlxuICogQGZpbGVcbiAqIEluaXRpYWxpemUgcGFyYWxsYXhlcyB3aXRoIFJlbGxheCBsaWIuXG4gKlxuICogQHNlZSAgaHR0cHM6Ly9naXRodWIuY29tL2RpeG9uYW5kbW9lL3JlbGxheFxuICovXG5cbmltcG9ydCBSZWxsYXggZnJvbSAncmVsbGF4J1xuXG4oZnVuY3Rpb24gKCkge1xuXHR2YXIgcmVsbGF4ID0gbmV3IFJlbGxheCgnLnBhcmFsbGF4Jyk7XG59KSgpXG4iXSwibmFtZXMiOlsidGhpcyIsInJlbGxheCIsIlJlbGxheCJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBV0EsQ0FBQyxVQUFVLElBQUksRUFBRSxPQUFPLEVBQUU7SUFDdEIsSUFBSSxPQUFPLE1BQU0sS0FBSyxVQUFVLElBQUksTUFBTSxDQUFDLEdBQUcsRUFBRTs7UUFFNUMsTUFBTSxDQUFDLEVBQUUsRUFBRSxPQUFPLENBQUMsQ0FBQztLQUN2QixNQUFNLElBQUksT0FBTyxNQUFNLEtBQUssUUFBUSxJQUFJLE1BQU0sQ0FBQyxPQUFPLEVBQUU7Ozs7UUFJckQsY0FBYyxHQUFHLE9BQU8sRUFBRSxDQUFDO0tBQzlCLE1BQU07O1FBRUgsSUFBSSxDQUFDLE1BQU0sR0FBRyxPQUFPLEVBQUUsQ0FBQztHQUM3QjtDQUNGLENBQUNBLGNBQUksRUFBRSxZQUFZO0VBQ2xCLElBQUksTUFBTSxHQUFHLFNBQVMsRUFBRSxFQUFFLE9BQU8sQ0FBQztJQUNoQyxZQUFZLENBQUM7O0lBRWIsSUFBSSxJQUFJLEdBQUcsTUFBTSxDQUFDLE1BQU0sQ0FBQyxNQUFNLENBQUMsU0FBUyxDQUFDLENBQUM7OztJQUczQyxJQUFJLE9BQU8sTUFBTSxDQUFDLFdBQVcsS0FBSyxXQUFXLEVBQUUsRUFBRSxPQUFPLEVBQUU7O0lBRTFELElBQUksSUFBSSxHQUFHLENB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
