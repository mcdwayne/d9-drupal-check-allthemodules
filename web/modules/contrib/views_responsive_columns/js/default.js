(function ($, Drupal) {
  Drupal.behaviors.viewsResponsiveColumnsDefaultBehavior = {
    attach: function (context, settings) {

      var responsiveColumnSettings = drupalSettings.viewsResponsiveColumns;

      var breakpointGroup = responsiveColumnSettings['breakpoint_group'].replace('.', '');
      var breakpoints = responsiveColumnSettings['breakpoints'][breakpointGroup];
      var matchedMedia = getMatchedMedia(breakpoints);

      function getMatchedMedia(breakpoints){

        matchedMedia = null;
        var matchedMediaWeight = 0;

        for (var property in breakpoints) {
          if (breakpoints.hasOwnProperty(property)) {
            if (window.matchMedia(breakpoints[property].media_query).matches) {
              if (breakpoints[property].weight >= matchedMediaWeight) {
                matchedMediaWeight = breakpoints[property].weight;
                matchedMedia = property;
              }
            }
          }
        }

        return matchedMedia;

      }

      function buildColumnElement(element){

        var columnCount = breakpoints[matchedMedia]['column_count'];

        if (columnCount > 1) {

          $(element).children('li').css({
            'width': 'calc(100% /' + columnCount + ')',
            'float': 'left'
          });

          $(element).children('li:nth-child(' + columnCount + 'n+1)').css({'clear': 'left'});

        }

      }

      function resetColumnElement(element){

        $(element).children('li').css({
          'width': '100%',
          'clear': 'none',
          'float': 'none'
        });

      }

      function updateColumnElements(){

        $(context).find('.responsive-columns').each(function () {
          resetColumnElement($(this));
          buildColumnElement($(this));
        });
      }

      // build column elements.
      updateColumnElements();

      $(window).resize(function(){
        getMatchedMedia(breakpoints);
        updateColumnElements();
      });

    }

  };
})(jQuery, Drupal);
