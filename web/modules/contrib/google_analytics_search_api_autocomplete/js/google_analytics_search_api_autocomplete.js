(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.google_analytics_search_api_autocomplete = {
    attach(context, settings) {
      // Follow settings of google_analytics, if ga() function doesn't exist
      // it means that tracking shouldn't trigger at this place.
      if (typeof ga === 'undefined') {
        return;
      }

      // Grab all drupalSettings.
      const gaSettings = drupalSettings.google_analytics_search_api_autocomplete;

      // Find autocomplete widget by search ID.
      const widget = $(`input[data-search-api-autocomplete-search="${gaSettings.search_api_search_id}"]`);

      // Using module configuration add event listeners to autocomplete widget.
      $.each(gaSettings.ga_events, (uiEvent, eventAction) => {
        // once(), to avoid bubbling on AJAX callbacks.
        $(widget).once(uiEvent).on(uiEvent, (event, ui) => {
          // Depending on event, we pick up value from different place,
          // it can be a value of autocomplete widget, or an UI item.
          let eventLabel = false;

          switch (uiEvent) {
            case 'autocompletesearch':
            case 'autocompleteresponse':
            case 'autocompleteopen':
            case 'autocompletechange':
            case 'autocompleteclose':
              eventLabel = $(event.target).val();
              break;
            case 'autocompleteselect':
            case 'autocompletefocus':
              eventLabel = ui.item.value;
              break;
            default:
              eventLabel = $(event.target).val();
              break;
          }

          // ga('send', 'event', [eventCategory], [eventAction], [eventLabel], [eventValue], [fieldsObject]);
          if (eventLabel) {
            if(!eventAction){
              eventAction = uiEvent;
            }
            ga(`${gaSettings.tracker_id}.send`, 'event', gaSettings.search_api_search_id, eventAction, eventLabel);
          }
        });
      });
    }
  };
}(jQuery, Drupal));
