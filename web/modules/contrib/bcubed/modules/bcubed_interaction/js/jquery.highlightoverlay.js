(function($){
  var highlightOverlay = function() {
    var container,
    defaults = {
      zIndex: 1000000,
      overlayOpacity: 0.5,
      exitOnOverlayClick: true,
      dismissCallback: undefined,
      actions: [
        {
          label: 'Dismiss',
          id: 'dismiss',
          callback: undefined
        }
      ]
    },
    topMask = $("<div/>").addClass("highlightMask"),
    bottomMask = $("<div/>").addClass("highlightMask"),
    leftMask = $("<div/>").addClass("highlightMask"),
    rightMask = $("<div/>").addClass("highlightMask"),
    message = $("<div/>").addClass("highlightMessage"),
    buttons = [];

    isFixed = function(element) {
        var $element = $(element);
        var $checkElements = $element.add($element.parents());
        var isFixed = false;
        $checkElements.each(function(){
          if ($(this).css("position") === "fixed") {
            isFixed = true;
            return false;
          }
        });
        return isFixed;
    },

    getElementAttrs = function(element) {
      return {
        top: element.offset().top,
        left: element.offset().left,
        width: element.outerWidth(),
        height: element.outerHeight()
      }
    },
    positionMask = function(element) {
      var attrs = getElementAttrs(element),
          top = attrs.top,
          left = attrs.left,
          width = attrs.width,
          height = attrs.height,
          pos = 'absolute',
          offset = 0;

      if(isFixed(element)) {
        pos = "fixed";
        offset = $(document).scrollTop();
      }

      topMask.css({
        height: (top - offset) + "px",
        position: pos
      });

      bottomMask.css({
        top: (height + top - offset) + "px",
        height: ($(document).height() - height - top) + "px",
        position: pos
      });

      leftMask.css({
        width: (left) + "px",
        top: (top - offset) + "px",
        height: (height) + "px",
        position: pos
      });

      rightMask.css({
        left: (left + width) + "px",
        top: (top - offset) + "px",
        height: (height) + "px",
        width: ($(document).width() - width - left) + "px",
        position: pos
      });
    },
    showMessage = function(html) {
      // set message content
      content = $("<div/>").addClass("messageContent");
      content.html(html);
      // add dismiss button
      content.append(buttons);
      // add to message container
      $(message).append(content);
    },
    scrollIntoView = function(element) {

      var elementTop = element.offset().top;
      var elementBottom = elementTop + element.outerHeight();

      var viewportTop = $(window).scrollTop();
      var viewportBottom = viewportTop + $(window).height();

      if ((elementBottom > viewportTop) && (elementTop < viewportBottom)) {
        // element already in viewport, no need to scroll
      }
      else {
        // element not in viewport, scroll into view
        $('body').animate({
            scrollTop: element.offset().top - 20
        });
      }
    },
    clear = function() {
      message.detach();
      topMask.add(bottomMask).add(leftMask).add(rightMask).stop().detach();
      if (typeof options.dismissCallback != 'undefined'){
        options.dismissCallback();
      }
    }

    return {
      init: function(opts) {
        container = $(this);
        options = $.extend({}, defaults, opts);

        topMask.add(bottomMask).add(leftMask).add(rightMask).css("z-index", options.zIndex + 1);
        message.css("z-index", options.zIndex + 2).html("");
        $.each(options.actions, function(index, value){
          button = $("<button/>").addClass("dismissButton");
          button.html(value.label);
          if (typeof value.id != 'undefined'){
            button.attr('id', value.id);
          }
          button.on("click", function() {
            if (!$(this).hasClass("disabled")) {
              clear();
              if (typeof value.callback != 'undefined'){
                value.callback();
              }
            }
          });
          buttons.push(button);
        });

        if (options.exitOnOverlayClick) {
          topMask.add(bottomMask).add(leftMask).add(rightMask).on("click", function() {
            clear();
          });
        }

        return {
          highlight: function(selector, html) {
            var element = $(selector);
            container.append(topMask, bottomMask, leftMask, rightMask);
            container.append(message);
            topMask.add(bottomMask).add(leftMask).add(rightMask).stop().show();
            positionMask(element);
            topMask.add(bottomMask).add(leftMask).add(rightMask).css({opacity: options.overlayOpacity});
            scrollIntoView(element);
            showMessage(html);
          },
          exit: function() {
            clear();
          }
        }
      },
    }
  }();

  $.fn.extend({
    highlightOverlay: highlightOverlay.init
  });
})(jQuery);
