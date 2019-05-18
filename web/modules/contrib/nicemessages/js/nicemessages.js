(function ($) {
  Drupal.behaviors.nicemessages = {
    attach: function (context, settings) {
      // $(context).find('input.myCustomBehavior').once('myCustomBehavior').each(function () {
        // Apply the myCustomBehaviour effect to the elements only once.
      // });
      console.log(settings);
      if (settings.nicemessages) {
        // $.jGrowl.defaults.position = settings.nicemessages.position;
        $.jGrowl.defaults.closerTemplate = '<div>'+Drupal.t('[ close all ]')+'</div>';
        if (settings.nicemessages.items) {
          for (i in settings.nicemessages.items) {
            $.jGrowl(settings.nicemessages.items[i].content, {
              life: settings.nicemessages.items[i].life,
              glue: settings.nicemessages.items[i].glue,
              speed: settings.nicemessages.items[i].speed,
              theme: settings.nicemessages.items[i].type,
              sticky: settings.nicemessages.items[i].life == 0
            });
          }
          delete settings.nicemessages.items;
        }
      }
    }
  };
})(jQuery);
