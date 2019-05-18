/**
 * @file
 * Contains the definition of the behaviour for process-link.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Attaches the placeAutocomplete Behaviour.
   */
  Drupal.behaviors.process_link = {
    attach: function (context, settings) {
      jQuery(document).ready(function () {});
    }
  };
  
  Drupal.AjaxCommands.prototype.rebindLink = function (ajax, response) {
    if (typeof response.selector !== 'undefined' &&
      typeof response.link !== 'undefined' &&
      typeof response.title !== 'undefined') {
      //var $link = $(response.selector);

      var $process_link = $(document).find(response.selector);

      $.each($process_link, function (index, link) {
        var $link = $(link);
        var link_id = $link.attr('id');

        $link.attr('href', response.link);
        $link.text(response.title);

        // Rebind ajax link.
        var element_settings = {
          url: response.link,
          event: 'click',
          base: link_id
        };

        Drupal.ajax[link_id] = new Drupal.ajax(element_settings);

        $link.unbind(Drupal.ajax[link_id].event);

        $link.bind(Drupal.ajax[link_id].event, function (event) {
          return Drupal.ajax[link_id].eventResponse($link, event);
        });
      });
    }
  };

  // Outdate message command.
  Drupal.AjaxCommands.prototype.outdateMessage = function (ajax, response) {
    alert('The friendship information is outdate please reload page.');
  };

})(jQuery, Drupal, drupalSettings);
