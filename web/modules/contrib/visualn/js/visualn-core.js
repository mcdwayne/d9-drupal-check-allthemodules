(function ($, Drupal) {

  // Adapters, drawers and mapperes register themselves to be triggered when needed.
  // Builders don't need to register themselves, they should be triggered
  // at visualnCoreProcessed event.
  Drupal.visualnData = {
    drawings : {}, adapters : {}, mappers : {}, drawers : {}, handlerItems : {}
  };

  // @todo: this would work only for newer versions of browsers
  // https://developer.mozilla.org/en-US/docs/Web/Guide/Events/Creating_and_triggering_events

  Drupal.behaviors.visualnCoreBehaviour = {
    attach: function (context, settings) {

      // Check settings.visualn, if not set then do nothing.
      if (typeof settings.visualn != 'undefined') {

        Drupal.visualnData.drawings = settings.visualn.drawings;
        // store a reference between handlers (drawers, mappers, adapters, builders)
        // and provided drawings
        Drupal.visualnData.handlerItems = settings.visualn.handlerItems;

        // Create a custom visualnCoreProcessed event to trigger builder scripts.
        // It is for those scripts to add event listeners.
        // Technically  may be also used by other scripts which don't need to rely
        // on builders, e.g. some exotic drawers.
        var event = new CustomEvent('visualnCoreProcessed', { 'detail': settings });
        window.dispatchEvent(event);
      }

    }
  };

})(jQuery, Drupal);
