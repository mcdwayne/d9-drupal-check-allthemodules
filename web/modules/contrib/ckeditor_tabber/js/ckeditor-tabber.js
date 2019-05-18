(function ($) {
  Drupal.behaviors.ckeditorTabber = {
    attach: function (context, settings) {
      // create tabbed functionality if tabs are available
      var $ckeditorTabber = $('.ckeditor-tabber');
      if($ckeditorTabber.length > 0) {
        // create simple tabbing mechanism for each tab
        var containerIndex = 0;
        $ckeditorTabber.each(function() {
          // go through the children and create the tabbed area
          var tabsHTML = '';
          var tabsContentHTML = '';
          var tabX = 0;
          var tabContentX = 0;

          ++containerIndex;

          $(this).children().each(function() {
            var $t = $(this);
            var nodeName = this.nodeName.toLowerCase();
            // check whether the child is tab title or tab contents
            if(nodeName.toLowerCase() == 'dt') {
              ++tabX;
              // the first tab is open by default
              var activeClass = (tabX == 1) ? ' active' : '';
              tabsHTML += '<li class="'+activeClass+'"><a class="ckeditor-tab ckeditor-tab-'+tabX + activeClass+'" data-container-index="'+containerIndex+'" data-tab-index="'+tabX+'" href="#">'+$t.text().trim()+'</a></li>';
            } else if(nodeName == 'dd') {
              ++tabContentX;
              // the first tab is open by default
              var activeClass = (tabX == 1) ? ' active' : '';
              tabsContentHTML += '<div class="ckeditor-tab-content ckeditor-tab-content-'+tabContentX + activeClass+'">'+$t.html()+'</div>';
            }
          });

          // create the tabs
          $(this).before('<section class="ckeditor-tabber-tabs ckeditor-container-index-'+containerIndex+'"><ul class="ckeditor-tabs-holder">'+tabsHTML+'</ul><div class="ckeditor-tabs-content-holder">'+tabsContentHTML+'</div></section>');

          // get the added tabs container
          var $tabsContainer = $(this).prev();

           // delete the <dl>
          $(this).remove();
        });

        // add click event to body once because quick edits & ajax calls might reset the HTML
        $('body').once('ckeditorTabberToggleEvent').on('click', '.ckeditor-tab', function(e) {
          var $t = $(this);

          // ignore if we are on active tab
          if(!$t.hasClass('active')) {
            var index = $t.attr('data-tab-index');
            var containerIndex = $t.attr('data-container-index');
            var $tabsContainer = $('.ckeditor-container-index-'+containerIndex);

            // remove active classes
            $tabsContainer.find('.active').removeClass('active');

            // add active to tab and to new tab content
            $t.addClass('active').parent().addClass('active');
            $tabsContainer.find('.ckeditor-tab-content-'+index).addClass('active');
          }

          // don't add hash to url
          e.preventDefault();
        });
      }
    }
  }
})(jQuery);