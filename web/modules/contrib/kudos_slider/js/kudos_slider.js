jQuery(document).ready(function() {
jQuery('.slider').each(function() {
  var jQuerythis = jQuery(this);
  var jQuerygroup = jQuerythis.find('.slide_group');
  var jQueryslides = jQuerythis.find('.slide');
  var bulletArray = [];
  var currentIndex = 0;
  var timeout;
  
  function move(newIndex) {
    var animateLeft, slideLeft;
    
    advance();
    
    if (jQuerygroup.is(':animated') || currentIndex === newIndex) {
      return;
    }
    
    bulletArray[currentIndex].removeClass('active');
    bulletArray[newIndex].addClass('active');
    
    if (newIndex > currentIndex) {
      slideLeft = '100%';
      animateLeft = '-100%';
    } else {
      slideLeft = '-100%';
      animateLeft = '100%';
    }
    
    jQueryslides.eq(newIndex).css({
      display: 'block',
      left: slideLeft
    });
    jQuerygroup.animate({
      left: animateLeft
    }, function() {
      jQueryslides.eq(currentIndex).css({
        display: 'none'
      });
      jQueryslides.eq(newIndex).css({
        left: 0
      });
      jQuerygroup.css({
        left: 0
      });
      currentIndex = newIndex;
    });
  }
  
  function advance() {
    clearTimeout(timeout);
    timeout = setTimeout(function() {
      if (currentIndex < (jQueryslides.length - 1)) {
        move(currentIndex + 1);
      } else {
        move(0);
      }
    }, 4000);
  }
  
  jQuery('.next_btn').on('click', function() {
    if (currentIndex < (jQueryslides.length - 1)) {
      move(currentIndex + 1);
    } else {
      move(0);
    }
  });
  
  jQuery('.previous_btn').on('click', function() {
    if (currentIndex !== 0) {
      move(currentIndex - 1);
    } else {
      move(3);
    }
  });
  
  jQuery.each(jQueryslides, function(index) {
    var jQuerybutton = jQuery('<a class="slide_btn">&bull;</a>');
    
    if (index === currentIndex) {
      jQuerybutton.addClass('active');
    }
    jQuerybutton.on('click', function() {
      move(index);
    }).appendTo('.slide_buttons');
    bulletArray.push(jQuerybutton);
  });
  
  advance();
});
});